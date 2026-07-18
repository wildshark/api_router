<?php
class ApiGateway {
    private $db;
    private $cache = [];
    private $requestCount = 0; // global counter

    public function __construct($dbFile = "gateway.db") {
        $dbExists = file_exists($dbFile);

        // Connect to SQLite
        $this->db = new PDO("sqlite:" . $dbFile);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Always run schema migrations (CREATE TABLE IF NOT EXISTS is safe to run every time)
        $this->migrateSchema();

        // Only seed default data when DB is brand new
        if (!$dbExists) {
            $this->seedDefaults();
        }
    }

    private function migrateSchema() {
        // Routes table (multi‑tenant aware)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS routes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id TEXT NOT NULL,
                path TEXT NOT NULL,
                target_url TEXT NOT NULL,
                UNIQUE(tenant_id, path)
            );
        ");

        // Applications table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tenant_id TEXT NOT NULL UNIQUE,
                app_name TEXT NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'active'
            );
        ");
        try { $this->db->exec("ALTER TABLE applications ADD COLUMN status TEXT DEFAULT 'active'"); } catch (Exception $e) {}

        // Tokens table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                token TEXT NOT NULL UNIQUE,
                expiry TEXT,
                status TEXT DEFAULT 'active',
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
        ");
        try { $this->db->exec("ALTER TABLE tokens ADD COLUMN status TEXT DEFAULT 'active'"); } catch (Exception $e) {}

        // Client counters table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS client_counters (
                client_id TEXT PRIMARY KEY,
                request_count INTEGER DEFAULT 0,
                rate_limit INTEGER DEFAULT 10
            );
        ");

        // Request logs table
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                client_id TEXT NOT NULL,
                path TEXT NOT NULL,
                method TEXT NOT NULL DEFAULT 'GET',
                latency REAL NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );
        ");

        // Add method column to existing logs tables (safe migration)
        try {
            $this->db->exec("ALTER TABLE logs ADD COLUMN method TEXT NOT NULL DEFAULT 'GET'");
        } catch (PDOException $e) {
            // Column already exists — ignore
        }
    }

    private function seedDefaults() {
        // Insert default routes for tenant 'client123'
        $stmt = $this->db->prepare("INSERT INTO routes (tenant_id, path, target_url) VALUES (:tenant, :path, :url)");
        $stmt->execute([":tenant" => "client123", ":path" => "/users", ":url" => "http://localhost/api_router/test.php"]);
        // $stmt->execute([":tenant" => "client123", ":path" => "/orders", ":url" => "http://localhost:8081/orders"]);

        // Default client counter
        $stmt = $this->db->prepare("INSERT INTO client_counters (client_id, request_count, rate_limit) VALUES (:client, 0, 10)");
        $stmt->execute([":client" => "client123"]);
    }

    private function getRoute($tenantId, $path) {
        $stmt = $this->db->prepare("SELECT target_url FROM routes WHERE tenant_id = :tenant AND path = :path");
        $stmt->execute([':tenant' => $tenantId, ':path' => $path]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['target_url'] : null;
    }

    private function checkRateLimit($clientId) {
        $stmt = $this->db->prepare("SELECT rate_limit, request_count FROM client_counters WHERE client_id = :client");
        $stmt->execute([":client" => $clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return true; // no record, allow

        return $row['request_count'] < $row['rate_limit'];
    }

    private function incrementClientCounter($clientId) {
        $stmt = $this->db->prepare("INSERT INTO client_counters (client_id, request_count, rate_limit)
            VALUES (:client, 1, 10)
            ON CONFLICT(client_id) DO UPDATE SET request_count = request_count + 1");
        $stmt->execute([":client" => $clientId]);
    }

    public function handleRequest($path) {
        header("Content-Type: application/json");

        // Token verification
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            $authHeader = $apacheHeaders['Authorization'] ?? '';
        }

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(["error" => "Missing or invalid Authorization Bearer header"]);
            return;
        }

        $tokenStr = $matches[1];
        $stmt = $this->db->prepare("SELECT status, expiry FROM tokens WHERE token = :token");
        $stmt->execute([':token' => $tokenStr]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            http_response_code(401);
            echo json_encode(["error" => "Invalid token"]);
            return;
        }

        if ($tokenData['status'] === 'blocked') {
            http_response_code(403);
            echo json_encode(["error" => "api token is block contact administrator"]);
            return;
        }

        if (!empty($tokenData['expiry'])) {
            // Compare YYYY-MM-DD strings directly or via strtotime
            $currentDate = date('Y-m-d');
            if (strtotime($currentDate) > strtotime($tokenData['expiry'])) {
                http_response_code(403);
                echo json_encode(["error" => "token has expired"]);
                return;
            }
        }

        // Read tenant/client ID from header
        $clientId = $_SERVER['HTTP_X_CLIENT_ID'] ?? null;
        if (!$clientId) {
            http_response_code(400);
            echo json_encode(["error" => "Missing X-Client-ID header"]);
            return;
        }

        // Rate limit check
        if (!$this->checkRateLimit($clientId)) {
            http_response_code(429);
            echo json_encode(["error" => "Rate limit exceeded"]);
            return;
        }

        // Route lookup from SQLite
        $targetUrl = $this->getRoute($clientId, $path);
        if (!$targetUrl) {
            http_response_code(404);
            echo json_encode(["error" => "Route not found for tenant"]);
            return;
        }

        $cacheKey = $clientId . "_" . $path . "_" . md5(file_get_contents("php://input"));

        // Cache check
        if (isset($this->cache[$cacheKey])) {
            $this->requestCount++;
            $this->incrementClientCounter($clientId);
            echo $this->cache[$cacheKey];
            return;
        }

        $start = microtime(true);

        $input = file_get_contents("php://input");
        $options = [
            "http" => [
                "method" => $_SERVER['REQUEST_METHOD'],
                "header" => "Content-Type: application/json\r\n",
                "content" => $input
            ]
        ];
        $context = stream_context_create($options);
        $response = @file_get_contents($targetUrl, false, $context);

        if ($response === false) {
            http_response_code(502);
            echo json_encode(["error" => "Bad Gateway: failed to reach backend"]);
            return;
        }

        $duration = microtime(true) - $start;
        $method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Persist log entry to SQLite
        $stmt = $this->db->prepare(
            "INSERT INTO logs (client_id, path, method, latency) VALUES (:client, :path, :method, :latency)"
        );
        $stmt->execute([':client' => $clientId, ':path' => $path, ':method' => $method, ':latency' => $duration]);

        // Cache response
        $this->cache[$cacheKey] = $response;

        // Increment counters
        $this->requestCount++;
        $this->incrementClientCounter($clientId);

        echo $response;
    }

    public function getMetrics() {
        header("Content-Type: application/json");
        header("Access-Control-Allow-Origin: *");

        // Fetch per-client counters
        $stmt = $this->db->query("SELECT client_id, request_count, rate_limit FROM client_counters");
        $clientStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch logs from SQLite (latest 100)
        $stmt = $this->db->query("SELECT client_id, path, method, latency, created_at FROM logs ORDER BY id DESC LIMIT 100");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total requests from DB (sum of all counters)
        $totalStmt = $this->db->query("SELECT SUM(request_count) as total FROM client_counters");
        $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);

        // Overall latency stats across all logged requests
        $latencyStmt = $this->db->query("
            SELECT
                ROUND(AVG(latency), 6)  AS avg_latency,
                ROUND(MIN(latency), 6)  AS min_latency,
                ROUND(MAX(latency), 6)  AS max_latency,
                COUNT(*)                AS total_logged
            FROM logs
        ");
        $latencyStats = $latencyStmt->fetch(PDO::FETCH_ASSOC);

        // Per-path average latency breakdown
        $pathStmt = $this->db->query("
            SELECT path, ROUND(AVG(latency), 6) AS avg_latency, COUNT(*) AS requests
            FROM logs
            GROUP BY path
            ORDER BY avg_latency DESC
        ");
        $pathStats = $pathStmt->fetchAll(PDO::FETCH_ASSOC);

        // Total hits in the last 7 days
        $weekStmt = $this->db->query("
            SELECT COUNT(*) AS week_hits FROM logs
            WHERE created_at >= datetime('now', '-7 days')
        ");
        $weekRow = $weekStmt->fetch(PDO::FETCH_ASSOC);

        // Most recently hit endpoint
        $currentStmt = $this->db->query("SELECT path, method FROM logs ORDER BY id DESC LIMIT 1");
        $currentEndpoint = $currentStmt->fetch(PDO::FETCH_ASSOC);

        // Per-hour request count broken down by HTTP method (last 24 hours)
        $hourlyStmt = $this->db->query("
            SELECT
                strftime('%H', created_at) AS hour,
                method,
                COUNT(*) AS count
            FROM logs
            WHERE created_at >= datetime('now', '-24 hours')
            GROUP BY hour, method
            ORDER BY hour ASC
        ");
        $hourlyStats = $hourlyStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalApps = (int) $this->db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
        $totalTokens = (int) $this->db->query("SELECT COUNT(*) FROM tokens")->fetchColumn();
        $activeTokens = (int) $this->db->query("SELECT COUNT(*) FROM tokens WHERE status='active'")->fetchColumn();
        $blockedTokens = (int) $this->db->query("SELECT COUNT(*) FROM tokens WHERE status='blocked'")->fetchColumn();
        $totalBlocks = (int) $this->db->query("SELECT COUNT(*) FROM client_counters WHERE request_count >= rate_limit")->fetchColumn();

        echo json_encode([
            "total_requests"    => (int)($totalRow['total'] ?? 0),
            "week_hits"         => (int)($weekRow['week_hits'] ?? 0),
            "current_endpoint"  => $currentEndpoint ?: null,
            "latency"           => [
                "avg_seconds"   => (float)($latencyStats['avg_latency'] ?? 0),
                "min_seconds"   => (float)($latencyStats['min_latency'] ?? 0),
                "max_seconds"   => (float)($latencyStats['max_latency'] ?? 0),
                "total_logged"  => (int)($latencyStats['total_logged'] ?? 0),
                "by_path"       => $pathStats,
            ],
            "requests_per_hour" => $hourlyStats,
            "client_requests"   => $clientStats,
            "logs"              => $logs,
            "total_apps"        => $totalApps,
            "total_tokens"      => $totalTokens,
            "active_tokens"     => $activeTokens,
            "blocked_tokens"    => $blockedTokens,
            "total_blocks"      => $totalBlocks
        ], JSON_PRETTY_PRINT);
    }
}

// Example usage
$gateway = new ApiGateway("gateway.db");

// Extract just the route path, stripping the script's own prefix
// e.g. /api_router/index.php/users -> /users
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Remove query string
$uriPath = parse_url($requestUri, PHP_URL_PATH);

// Strip the script name prefix (e.g. /api_router/index.php)
if ($scriptName && strpos($uriPath, $scriptName) === 0) {
    $path = substr($uriPath, strlen($scriptName));
} else {
    // Fallback: strip directory prefix only
    $scriptDir = dirname($scriptName);
    $path = '/' . ltrim(substr($uriPath, strlen(rtrim($scriptDir, '/'))), '/');
}

// Ensure path starts with /
if ($path === '' || $path === false) {
    $path = '/';
}
// echo $path;
// exit();
if ($path === '/metrics') {
    $gateway->getMetrics();
} else {
    $gateway->handleRequest($path);
}
