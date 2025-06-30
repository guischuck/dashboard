<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

function getApiKey() {
    $headers = getallheaders();
    if ($headers && isset($headers['X-API-Key'])) {
        return trim($headers['X-API-Key']);
    }
    
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        return trim($_SERVER['HTTP_X_API_KEY']);
    }
    
    if (isset($_GET['api_key'])) {
        return trim($_GET['api_key']);
    }
    
    return null;
}

function conectarBanco() {
    $host = 'localhost';
    $user = 'u673101203_pmfb4gxg';
    $pass = 'AK3of3kUXl89yc4m';
    $db = 'u673101203_a7r3w1n1';
    
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception('Erro de conexão com o banco');
    }
    
    $conn->set_charset('utf8mb4');
    return $conn;
}

function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $apiKey = getApiKey();
    
    if (!$apiKey) {
        sendJsonResponse(['error' => 'API Key não fornecida'], 401);
    }
    
    if (strpos($apiKey, ',') !== false) {
        $parts = explode(',', $apiKey);
        $apiKey = trim($parts[0]);
    }
    
    $conn = conectarBanco();
    
    $stmt = $conn->prepare("SELECT id, razao_social FROM empresas WHERE api_key = ?");
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta');
    }
    
    $stmt->bind_param('s', $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        sendJsonResponse(['error' => 'API Key inválida'], 401);
    }
    
    $empresa = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    sendJsonResponse([
        'success' => true,
        'id_empresa' => (int)$empresa['id'],
        'razao_social' => $empresa['razao_social']
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    
    error_log("Erro em get_idempresa.php: " . $e->getMessage());
    sendJsonResponse(['error' => 'Erro interno do servidor'], 500);
} catch (Error $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    
    error_log("Erro fatal em get_idempresa.php: " . $e->getMessage());
    sendJsonResponse(['error' => 'Erro interno do servidor'], 500);
}
?>