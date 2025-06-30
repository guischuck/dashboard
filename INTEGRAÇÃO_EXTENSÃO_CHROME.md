# Integração com Extensão Chrome - Sistema de Sincronização de Processos

## Visão Geral

O sistema foi integrado ao Laravel para sincronizar dados de processos obtidos via scraping de uma extensão Chrome. Os endpoints originais em PHP foram convertidos para controllers Laravel com melhor segurança, validação e estrutura.

## Endpoints Disponíveis

### 1. Obter ID da Empresa
**Endpoint:** `GET /api/extension/get-id-empresa`
**Headers:** `X-API-Key: {api_key}`
**Resposta:**
```json
{
    "success": true,
    "id_empresa": 1,
    "razao_social": "Nome da Empresa"
}
```

### 2. Sincronizar Processos
**Endpoint:** `POST /api/extension/sync`
**Headers:** `X-API-Key: {api_key}`
**Payload:**
```json
{
    "id_empresa": 1,
    "processos": [
        {
            "protocolo": "12345678901",
            "cpf": "12345678901",
            "servico": "Auxílio Doença",
            "situacao": "Em análise",
            "nome": "João da Silva",
            "ultimaAtualizacao": "2025-06-30T10:30:00.000Z",
            "dataProtocolo": "2025-06-01T08:00:00.000Z"
        }
    ]
}
```

**Resposta:**
```json
{
    "success": true,
    "processados": 1,
    "mudancas": 0,
    "total": 1,
    "message": "1 processos sincronizados",
    "historico_disponivel": true,
    "protocolado_disponivel": true
}
```

## Estrutura do Banco de Dados

### Tabela `companies`
- Adicionados campos: `api_key`, `razao_social`

### Tabela `processos`
- `protocolo` - Protocolo do processo
- `servico` - Tipo de serviço/benefício
- `situacao` - Situação atual do processo
- `situacao_anterior` - Situação anterior (para histórico)
- `ultima_atualizacao` - Data da última atualização
- `protocolado_em` - Data de protocolo
- `cpf` - CPF do beneficiário
- `nome` - Nome do beneficiário
- `id_empresa` - Referência à empresa

### Tabela `historico_situacoes`
- `id_processo` - Referência ao processo
- `situacao_anterior` - Situação anterior
- `situacao_atual` - Nova situação
- `data_mudanca` - Timestamp da mudança
- `id_empresa` - Referência à empresa

## Comandos Artisan

### Gerenciar API Keys
```bash
# Listar todas as API keys
php artisan company:api-key list

# Gerar API key para uma empresa específica
php artisan company:api-key generate --company=1

# Gerar API keys para todas as empresas sem API key
php artisan company:api-key generate --all

# Regenerar API key de uma empresa
php artisan company:api-key regenerate --company=1

# Mostrar API key de uma empresa
php artisan company:api-key show --company=1
```

## Funcionalidades

### 1. Detecção de Mudanças
- O sistema detecta automaticamente quando a situação de um processo muda
- Registra o histórico na tabela `historico_situacoes`
- Mantém a situação anterior no processo

### 2. Conversão de Datas
- Converte automaticamente datas ISO (JavaScript) para formato MySQL
- Suporta formatos brasileiros (dd/mm/yyyy)
- Fallback para data atual em caso de erro

### 3. Validação Robusta
- Valida estrutura dos dados recebidos
- Verifica existência e validade da API key
- Sanitiza dados antes de armazenar

### 4. Transações
- Usa transações do banco para garantir consistência
- Rollback automático em caso de erro

### 5. CORS
- Middleware configurado para permitir acesso da extensão
- Headers apropriados para requisições cross-origin

## Modelos Laravel

### Company
- Relacionamentos com `processos` e `historicoSituacoes`
- Geração automática de API key na criação
- Métodos para verificar limites e permissões

### Processo
- Relacionamentos com `Company` e `HistoricoSituacao`
- Scopes para filtrar por empresa, CPF, situação
- Métodos de formatação de datas

### HistoricoSituacao
- Registro de mudanças de situação
- Relacionamentos com processo e empresa
- Métodos para formatação e descrição

## Migração dos Arquivos Originais

Os arquivos originais `sync.php` e `get_idempresa.php` foram movidos para `backup_original_files/` e substituídos pelos controllers Laravel:

- **sync.php** → `App\Http\Controllers\Api\ProcessoSyncController`
- **get_idempresa.php** → `App\Http\Controllers\Api\CompanyApiController`

## Configuração da Extensão Chrome

A extensão mantém as URLs originais através de arquivos proxy:

**URLs Originais (Mantidas):**
- GET `https://previdia.com.br/app/api/get_idempresa.php`
- POST `https://previdia.com.br/app/api/sync.php`

**Endpoints Laravel (Alternativos):**
- GET `{seu_dominio}/api/extension/get-id-empresa`
- POST `{seu_dominio}/api/extension/sync`

**Headers obrigatórios:**
- `Content-Type: application/json`
- `X-API-Key: {api_key_da_empresa}`

### Arquivos Proxy
Os arquivos em `public/app/api/` funcionam como proxies para os controllers Laravel:
- `public/app/api/get_idempresa.php` → `CompanyApiController@getIdEmpresa`
- `public/app/api/sync.php` → `ProcessoSyncController@sync`

## API Keys Geradas

As empresas existentes receberam API keys:

1. **Escritório de Advocacia Exemplo** (ID: 1): `sHfVMZ48OWqMB0SKpaAxrR8vyD9WatSu`
2. **Advocacia Silva & Associados** (ID: 2): `fW3OpobR3X2BAfXTCiQEQc8xRXNT7lC4`

## Logs e Monitoramento

- Erros são registrados nos logs do Laravel (`storage/logs/`)
- Use `Log::info()` para adicionar logs personalizados
- Monitore as tabelas de processos e histórico para verificar sincronização 