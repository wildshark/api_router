<?php
// Gateway base URL (adjust to your deployment)
$gatewayUrl = "http://localhost/api_router/index.php";

// Client ID (tenant identifier)
$clientId = "client123";

// Function to call the gateway
function callApiGateway($path, $method = "GET", $data = null) {
    global $gatewayUrl, $clientId;

    $url = $gatewayUrl . $path;
    $ch = curl_init($url);

    // Headers: Client ID
    $headers = [
        "Content-Type: application/json",
        "X-Client-ID: $clientId",
        "Authorization: Bearer f17f7e2d1bc9e94e701f10b8d56f06af"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Method and body
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    // Return response and HTTP status
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = ["error" => "cURL error: " . curl_error($ch), "http_code" => 0];
        curl_close($ch);
        return json_encode($error);
    }

    var_dump($response);
    exit();

    curl_close($ch);

    // Attach HTTP status to output for visibility
    $decoded = json_decode($response, true);
    if ($decoded !== null) {
        $decoded['_http_code'] = $httpCode;
        return json_encode($decoded, JSON_PRETTY_PRINT);
    }

    return $response;
}

// Example calls
echo "=== Users ===\n";
echo callApiGateway("/users") . "\n";

// echo "\n=== Orders ===\n";
// echo callApiGateway("/orders", "POST", ["orderId" => 123, "item" => "Laptop"]) . "\n";

// echo "\n=== Metrics ===\n";
// echo callApiGateway("/metrics") . "\n";

