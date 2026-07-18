Here’s a **full Markdown documentation** for your project, labeled as **ApiGateway v1.0.0**. It covers both the **server side** (multi‑tenant gateway) and the **client side** (PHP cURL consumer).

---

# ApiGateway v1.0.0

A **multi‑tenant aware API Gateway** built with PHP and SQLite.  
This gateway provides routing, authentication, rate limiting, caching, logging, and per‑tenant request counters.

---

## 📦 Features
- **Multi‑tenant aware**: Each client (tenant) has its own routes, counters, and rate limits stored in SQLite.
- **Basic Authentication**: Protects gateway access with username/password.
- **Rate Limiting**: Enforces per‑tenant request limits.
- **Request Counters**: Tracks global and per‑tenant request counts.
- **Caching**: Avoids redundant backend calls for identical requests.
- **Metrics Endpoint**: Provides JSON output with usage stats and logs.

---

## ⚙️ Server Side (Gateway)

### Installation
1. Clone or copy the project into your PHP server (e.g., XAMPP, Apache, Nginx).
2. Ensure PHP has **SQLite PDO extension** enabled.
3. Place the gateway script in your web root (e.g., `htdocs/api_router/index.php`).

### Database
The gateway uses `gateway.db` (SQLite).  
It auto‑creates if missing, with two tables:

```sql
CREATE TABLE routes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id TEXT NOT NULL,
    path TEXT NOT NULL,
    target_url TEXT NOT NULL,
    UNIQUE(tenant_id, path)
);

CREATE TABLE client_counters (
    client_id TEXT PRIMARY KEY,
    request_count INTEGER DEFAULT 0,
    rate_limit INTEGER DEFAULT 10
);
```

### Default Routes
- Tenant `client123` → `/users` → `http://localhost:8080/user/user.php`
- Tenant `client123` → `/orders` → `http://localhost:8081/orders`

### Example Server Code
```php
// Example usage
$gateway = new ApiGateway("gateway.db");

// Safely get request URI
$path = $_SERVER['REQUEST_URI'] ?? '/';

if ($path === "/metrics") {
    $gateway->getMetrics();
} else {
    $gateway->handleRequest($path);
}
```

---

## 🧑‍💻 Client Side (PHP cURL)

### Example Client Script
```php
<?php
$gatewayUrl = "http://localhost/api_router/index.php";
$username = "admin";
$password = "secret123";
$clientId = "client123";

function callApiGateway($path, $method = "GET", $data = null) {
    global $gatewayUrl, $username, $password, $clientId;

    $url = $gatewayUrl . $path;
    $ch = curl_init($url);

    $headers = [
        "Authorization: Basic " . base64_encode("$username:$password"),
        "Content-Type: application/json",
        "X-Client-ID: $clientId"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL error: " . curl_error($ch);
    }

    curl_close($ch);
    return $response;
}

// Example calls
echo "=== Users ===\n";
echo callApiGateway("/users");

echo "\n\n=== Orders ===\n";
echo callApiGateway("/orders", "POST", ["orderId" => 123, "item" => "Laptop"]);

echo "\n\n=== Metrics ===\n";
echo callApiGateway("/metrics");
```

---

## 📊 Example Metrics Output

```json
{
  "total_requests": 15,
  "client_requests": [
    {"client_id":"client123","request_count":8,"rate_limit":10},
    {"client_id":"client456","request_count":5,"rate_limit":20},
    {"client_id":"client789","request_count":2,"rate_limit":15}
  ],
  "logs": [
    {"client":"client123","path":"/users","latency":0.0123},
    {"client":"client456","path":"/orders","latency":0.0456},
    {"client":"client789","path":"/products","latency":0.0205}
  ]
}
```

---

## 🚀 Usage
1. Start your backend services (e.g., `user.php` on port 8080, `orders` service on port 8081).
2. Run the gateway (`index.php`) under Apache/XAMPP/Nginx.
3. Use the client script or curl commands to interact:
   ```bash
   curl -X GET http://localhost/api_router/index.php/users \
     -H "Authorization: Basic YWRtaW46c2VjcmV0MTIz" \
     -H "X-Client-ID: client123"
   ```

---

## 🏷️ Version
**ApiGateway v1.0.0**  
- Bug‑checked and stable.  
- Multi‑tenant aware.  
- Ready for production deployment on VPS (e.g., Hostman, Hetzner, DigitalOcean).  

---

Andrew, this `.md` doc is structured so you can drop it straight into your repo (e.g., GitHub or OpenCode IDE tab you’ve got open).  

Would you like me to extend this documentation with a **“Deployment Guide” section** (covering Apache/Nginx config, `.htaccess` rewrite rules, and VPS setup) so it’s fully production‑ready?