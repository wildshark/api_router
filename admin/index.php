<?php
session_start();

// Shared DB path
define('DB_PATH', __DIR__ . '/../gateway.db');

function getDb() {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
}

if(!isset($_REQUEST['action'])){
    if(!isset($_REQUEST['main'])){
        require_once("login.php");
    }elseif($_REQUEST['main'] == 'dashboard'){
        require_once("dashboard.php");
    }
}else{
    $action = $_REQUEST['action'];
    switch($action){
        case "login":
            $username = $_REQUEST['username'] ?? '';
            $password = $_REQUEST['password'] ?? '';
            
            $envFile = __DIR__ . '/.env';
            $adminUser = 'admin';
            $adminPass = 'admin123';
            if (file_exists($envFile)) {
                $env = parse_ini_file($envFile);
                if (isset($env['ADMIN_USERNAME'])) $adminUser = $env['ADMIN_USERNAME'];
                if (isset($env['ADMIN_PASSWORD'])) $adminPass = $env['ADMIN_PASSWORD'];
            }

            if ($username === $adminUser && $password === $adminPass) {
                $_SESSION['username'] = $username;
                header("Location: index.php?main=dashboard");
            } else {
                header("Location: index.php?user=login");
            }
            break;

        case "add_route":
            $tenantId  = trim($_REQUEST['tenant_id']  ?? '');
            $path      = trim($_REQUEST['path']       ?? '');
            $targetUrl = trim($_REQUEST['target_url'] ?? '');
            if ($tenantId && $path && $targetUrl) {
                try {
                    $db   = getDb();
                    $stmt = $db->prepare(
                        "INSERT OR REPLACE INTO routes (tenant_id, path, target_url) VALUES (:tenant, :path, :url)"
                    );
                    $stmt->execute([':tenant' => $tenantId, ':path' => $path, ':url' => $targetUrl]);
                } catch (PDOException $e) {
                    // Silently ignore duplicate conflicts already handled by OR REPLACE
                }
            }
            header("Location: index.php?main=dashboard#routes");
            break;

        case "delete_route":
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $db   = getDb();
                $stmt = $db->prepare("DELETE FROM routes WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            header("Location: index.php?main=dashboard#routes");
            break;

        case "add_application":
            $tenantId = trim($_REQUEST['tenant_id'] ?? '');
            $appName  = trim($_REQUEST['application-name'] ?? '');
            $desc     = trim($_REQUEST['description'] ?? '');
            $status   = trim($_REQUEST['status'] ?? 'active');
            if ($tenantId && $appName) {
                try {
                    $db   = getDb();
                    $stmt = $db->prepare(
                        "INSERT OR REPLACE INTO applications (tenant_id, app_name, description, status) VALUES (:tenant, :name, :desc, :status)"
                    );
                    $stmt->execute([':tenant' => $tenantId, ':name' => $appName, ':desc' => $desc, ':status' => $status]);
                } catch (PDOException $e) {}
            }
            header("Location: index.php?main=dashboard#application");
            break;

        case "update_application":
            $id       = (int)($_REQUEST['id'] ?? 0);
            $tenantId = trim($_REQUEST['tenant_id'] ?? '');
            $appName  = trim($_REQUEST['application-name'] ?? '');
            $desc     = trim($_REQUEST['description'] ?? '');
            $status   = trim($_REQUEST['status'] ?? 'active');
            if ($id > 0 && $tenantId && $appName) {
                try {
                    $db   = getDb();
                    $stmt = $db->prepare(
                        "UPDATE applications SET tenant_id = :tenant, app_name = :name, description = :desc, status = :status WHERE id = :id"
                    );
                    $stmt->execute([':tenant' => $tenantId, ':name' => $appName, ':desc' => $desc, ':status' => $status, ':id' => $id]);
                } catch (Exception $e) {}
            }
            header("Location: index.php?main=dashboard#application");
            break;

        case "delete_application":
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $db   = getDb();
                $stmt = $db->prepare("DELETE FROM applications WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            header("Location: index.php?main=dashboard#application");
            break;

        case "get_applications":
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            $db   = getDb();
            $rows = $db->query("SELECT id, tenant_id, app_name, description, status FROM applications ORDER BY app_name")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
            break;

        case "generate_token":
            $email  = trim($_REQUEST['email'] ?? '');
            $expiry = trim($_REQUEST['expiry'] ?? '');
            $status = trim($_REQUEST['status'] ?? 'active');
            if ($email) {
                try {
                    $token = bin2hex(random_bytes(16)); // Generate secure random 32-char hex token
                    $db   = getDb();
                    $stmt = $db->prepare(
                        "INSERT INTO tokens (email, token, expiry, status) VALUES (:email, :token, :expiry, :status)"
                    );
                    $stmt->execute([':email' => $email, ':token' => $token, ':expiry' => $expiry ?: null, ':status' => $status]);
                } catch (Exception $e) {}
            }
            header("Location: index.php?main=dashboard#tokens");
            break;

        case "update_token":
            $id     = (int)($_REQUEST['id'] ?? 0);
            $email  = trim($_REQUEST['email'] ?? '');
            $expiry = trim($_REQUEST['expiry'] ?? '');
            $status = trim($_REQUEST['status'] ?? 'active');
            if ($id > 0 && $email) {
                try {
                    $db   = getDb();
                    $stmt = $db->prepare(
                        "UPDATE tokens SET email = :email, expiry = :expiry, status = :status WHERE id = :id"
                    );
                    $stmt->execute([':email' => $email, ':expiry' => $expiry ?: null, ':status' => $status, ':id' => $id]);
                } catch (Exception $e) {}
            }
            header("Location: index.php?main=dashboard#tokens");
            break;

        case "delete_token":
            $id = (int)($_REQUEST['id'] ?? 0);
            if ($id > 0) {
                $db   = getDb();
                $stmt = $db->prepare("DELETE FROM tokens WHERE id = :id");
                $stmt->execute([':id' => $id]);
            }
            header("Location: index.php?main=dashboard#tokens");
            break;

        case "get_tokens":
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            $db   = getDb();
            $rows = $db->query("SELECT id, email, token, expiry, status, created_at FROM tokens ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
            break;

        case "get_routes":
            header('Content-Type: application/json');
            header('Access-Control-Allow-Origin: *');
            $db   = getDb();
            $rows = $db->query("SELECT id, tenant_id, path, target_url FROM routes ORDER BY tenant_id, path")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
            break;

        default:
            require_once("login.php");
            break;
    }
}