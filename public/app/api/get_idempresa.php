<?php

/**
 * Proxy para o endpoint get-id-empresa do Laravel
 * Mantém compatibilidade com a extensão Chrome
 */

// Incluir o autoloader do Laravel
require_once __DIR__ . '/../../../vendor/autoload.php';

// Bootstrapar a aplicação Laravel
$app = require_once __DIR__ . '/../../../bootstrap/app.php';

// Configurar headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Criar kernel HTTP
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Criar request a partir dos dados atuais
    $request = Illuminate\Http\Request::capture();
    
    // Ajustar a URI para o endpoint Laravel correto
    $request->server->set('REQUEST_URI', '/api/extension/get-id-empresa');
    $request->server->set('PATH_INFO', '/api/extension/get-id-empresa');
    
    // Processar request através do Laravel
    $response = $kernel->handle($request);
    
    // Enviar resposta
    $response->send();
    
    // Cleanup
    $kernel->terminate($request, $response);
    
} catch (Exception $e) {
    error_log("Erro em get_idempresa.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor'], JSON_UNESCAPED_UNICODE);
} 