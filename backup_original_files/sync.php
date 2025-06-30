<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
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
    
    return null;
}

function conectarBanco() {
    $host = 'localhost';
    $user = 'u673101203_pmfb4gxg';
    $pass = 'AK3of3kUXl89yc4m';
    $db = 'u673101203_a7r3w1n1';
    
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        throw new Exception('Erro de conexão');
    }
    
    $conn->set_charset('utf8mb4');
    return $conn;
}

function criarTabelaHistoricoSeguro($conn) {
    try {
        $result = $conn->query("SHOW TABLES LIKE 'historico_situacoes'");
        if ($result->num_rows == 0) {
            $sql = "CREATE TABLE historico_situacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_processo INT NOT NULL,
                situacao_anterior VARCHAR(255) NULL,
                situacao_atual VARCHAR(255) NOT NULL,
                data_mudanca TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                id_empresa INT NOT NULL,
                INDEX idx_processo_empresa (id_processo, id_empresa),
                INDEX idx_data_mudanca (data_mudanca)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$conn->query($sql)) {
                error_log("Erro ao criar tabela historico_situacoes: " . $conn->error);
            }
        }
        
        $result = $conn->query("SHOW COLUMNS FROM processos LIKE 'situacao_anterior'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE processos ADD COLUMN situacao_anterior VARCHAR(255) NULL AFTER situacao";
            if (!$conn->query($sql)) {
                error_log("Erro ao adicionar coluna situacao_anterior: " . $conn->error);
            }
        }
        
        $result = $conn->query("SHOW COLUMNS FROM processos LIKE 'protocolado_em'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE processos ADD COLUMN protocolado_em DATETIME NULL AFTER ultima_atualizacao";
            if (!$conn->query($sql)) {
                error_log("Erro ao adicionar coluna protocolado_em: " . $conn->error);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro na criação de estruturas: " . $e->getMessage());
        return false;
    }
}

function registrarMudancaSituacao($conn, $id_processo, $situacao_anterior, $situacao_atual, $id_empresa) {
    if ($situacao_anterior !== $situacao_atual) {
        try {
            $result = $conn->query("SHOW TABLES LIKE 'historico_situacoes'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO historico_situacoes (id_processo, situacao_anterior, situacao_atual, id_empresa) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('issi', $id_processo, $situacao_anterior, $situacao_atual, $id_empresa);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao registrar mudança: " . $e->getMessage());
        }
    }
}

function formatarDataMySQL($dataISO) {
    if (empty($dataISO)) {
        return date('Y-m-d H:i:s');
    }
    
    try {
        // Se é formato ISO (2025-06-23T22:49:00.000Z)
        if (strpos($dataISO, 'T') !== false) {
            // Remove o .000Z do final se existir
            $dataISO = preg_replace('/\.\d{3}Z$/', 'Z', $dataISO);
            // Remove o Z do final se existir
            $dataISO = rtrim($dataISO, 'Z');
            // Substitui T por espaço
            $dataISO = str_replace('T', ' ', $dataISO);
        }
        
        // Se já está em formato brasileiro dd/mm/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dataISO, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $ano = $matches[3];
            return "{$ano}-{$mes}-{$dia} 00:00:00";
        }
        
        // Tentar criar DateTime e formatar
        $date = new DateTime($dataISO);
        return $date->format('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        error_log("Erro ao formatar data: {$dataISO} - " . $e->getMessage());
        return date('Y-m-d H:i:s');
    }
}

try {
    $apiKey = getApiKey();
    
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['error' => 'API Key não fornecida']);
        exit;
    }
    
    if (strpos($apiKey, ',') !== false) {
        $parts = explode(',', $apiKey);
        $apiKey = trim($parts[0]);
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['processos']) || !isset($data['id_empresa'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    
    $id_empresa = (int)$data['id_empresa'];
    $processos = $data['processos'];
    
    if (empty($processos)) {
        echo json_encode(['success' => true, 'processados' => 0, 'message' => 'Nenhum processo']);
        exit;
    }
    
    $conn = conectarBanco();
    
    criarTabelaHistoricoSeguro($conn);
    
    $stmt = $conn->prepare("SELECT id FROM empresas WHERE id = ? AND api_key = ?");
    $stmt->bind_param('is', $id_empresa, $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Empresa não encontrada']);
        exit;
    }
    $stmt->close();
    
    $conn->begin_transaction();
    
    // Buscar processos existentes para comparar situações
    $protocolos = array_map(function($p) { return $p['protocolo']; }, $processos);
    $processosExistentes = [];
    
    if (!empty($protocolos)) {
        $placeholders = str_repeat('?,', count($protocolos) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, protocolo, situacao FROM processos WHERE protocolo IN ($placeholders) AND id_empresa = ?");
        if ($stmt) {
            $params = array_merge($protocolos, [$id_empresa]);
            $types = str_repeat('s', count($protocolos)) . 'i';
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $processosExistentes[$row['protocolo']] = [
                    'id' => $row['id'],
                    'situacao_atual' => $row['situacao']
                ];
            }
            $stmt->close();
        }
    }
    
    // Verificar colunas disponíveis
    $temColunaAnterior = false;
    $temColunaProtocolado = false;
    
    $result = $conn->query("SHOW COLUMNS FROM processos LIKE 'situacao_anterior'");
    if ($result && $result->num_rows > 0) {
        $temColunaAnterior = true;
    }
    
    $result = $conn->query("SHOW COLUMNS FROM processos LIKE 'protocolado_em'");
    if ($result && $result->num_rows > 0) {
        $temColunaProtocolado = true;
    }
    
    // Preparar query baseado nas colunas disponíveis
    if ($temColunaAnterior && $temColunaProtocolado) {
        $stmtProcesso = $conn->prepare("
            INSERT INTO processos (protocolo, servico, situacao, situacao_anterior, ultima_atualizacao, protocolado_em, cpf, nome, id_empresa, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            situacao_anterior = IF(situacao != VALUES(situacao), situacao, situacao_anterior),
            servico = VALUES(servico),
            situacao = VALUES(situacao),
            ultima_atualizacao = VALUES(ultima_atualizacao),
            protocolado_em = COALESCE(protocolado_em, VALUES(protocolado_em)),
            nome = VALUES(nome),
            atualizado_em = NOW()
        ");
    } elseif ($temColunaProtocolado) {
        $stmtProcesso = $conn->prepare("
            INSERT INTO processos (protocolo, servico, situacao, ultima_atualizacao, protocolado_em, cpf, nome, id_empresa, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            servico = VALUES(servico),
            situacao = VALUES(situacao),
            ultima_atualizacao = VALUES(ultima_atualizacao),
            protocolado_em = COALESCE(protocolado_em, VALUES(protocolado_em)),
            nome = VALUES(nome),
            atualizado_em = NOW()
        ");
    } else {
        $stmtProcesso = $conn->prepare("
            INSERT INTO processos (protocolo, servico, situacao, ultima_atualizacao, cpf, nome, id_empresa, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            servico = VALUES(servico),
            situacao = VALUES(situacao),
            ultima_atualizacao = VALUES(ultima_atualizacao),
            nome = VALUES(nome),
            atualizado_em = NOW()
        ");
    }
    
    $processados = 0;
    $mudancas = 0;
    
    foreach ($processos as $processo) {
        // Validação rigorosa
        if (!isset($processo['protocolo']) || !isset($processo['cpf'])) {
            continue;
        }
        
        $protocolo = trim($processo['protocolo']);
        $cpf = trim($processo['cpf']);
        
        if ($protocolo === '' || $cpf === '' || strlen($protocolo) < 3 || strlen($cpf) < 8) {
            continue;
        }
        
        $servico = trim($processo['servico'] ?? 'N/A');
        $situacao = trim($processo['situacao'] ?? 'N/A');
        $nome = trim($processo['nome'] ?? 'N/A');
        
        // CORREÇÃO PRINCIPAL: Converter datas ISO para formato MySQL
        $ultimaAtualizacao = formatarDataMySQL($processo['ultimaAtualizacao'] ?? null);
        $protocoladoEm = null;
        
        if (isset($processo['dataProtocolo'])) {
            $protocoladoEm = formatarDataMySQL($processo['dataProtocolo']);
        }
        
        // Verificar mudança de situação
        $situacao_anterior = null;
        if (isset($processosExistentes[$protocolo])) {
            $situacao_anterior = $processosExistentes[$protocolo]['situacao_atual'];
            if ($situacao_anterior !== $situacao) {
                registrarMudancaSituacao($conn, $processosExistentes[$protocolo]['id'], $situacao_anterior, $situacao, $id_empresa);
                $mudancas++;
            }
        }
        
        // Executar query baseado nas colunas disponíveis
        try {
            if ($temColunaAnterior && $temColunaProtocolado) {
                $stmtProcesso->bind_param('ssssssssi', $protocolo, $servico, $situacao, $situacao_anterior, $ultimaAtualizacao, $protocoladoEm, $cpf, $nome, $id_empresa);
            } elseif ($temColunaProtocolado) {
                $stmtProcesso->bind_param('sssssssi', $protocolo, $servico, $situacao, $ultimaAtualizacao, $protocoladoEm, $cpf, $nome, $id_empresa);
            } else {
                $stmtProcesso->bind_param('ssssssi', $protocolo, $servico, $situacao, $ultimaAtualizacao, $cpf, $nome, $id_empresa);
            }
            
            if ($stmtProcesso->execute()) {
                $processados++;
            }
        } catch (Exception $e) {
            error_log("Erro ao processar protocolo $protocolo: " . $e->getMessage());
        }
    }
    
    $stmtProcesso->close();
    $conn->commit();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'processados' => $processados,
        'mudancas' => $mudancas,
        'total' => count($processos),
        'message' => "{$processados} processos sincronizados" . ($mudancas > 0 ? " ({$mudancas} mudanças detectadas)" : ""),
        'historico_disponivel' => $temColunaAnterior,
        'protocolado_disponivel' => $temColunaProtocolado
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->close();
    }
    
    error_log("Erro em sync.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
}
?>