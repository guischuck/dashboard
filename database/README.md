# Sistema de Gestão Jurídica - Banco de Dados

## 📋 Instruções de Instalação

### 1. Criar o Banco de Dados no phpMyAdmin

#### Opção A: Via Interface do phpMyAdmin
1. Acesse o phpMyAdmin
2. Clique em "Novo" para criar um novo banco de dados
3. Nome do banco: `sistema_juridico`
4. Collation: `utf8mb4_unicode_ci`
5. Clique em "Criar"

#### Opção B: Via SQL (Recomendado)
1. No phpMyAdmin, clique na aba "SQL"
2. Copie e cole o conteúdo do arquivo `schema.sql`
3. Clique em "Executar"

### 2. Executar Scripts Adicionais

Após criar o banco principal, execute o arquivo `config.sql` para:
- Configurar timezone e charset
- Criar tabelas adicionais do Laravel
- Criar views e procedures úteis
- Configurar triggers

### 3. Configurar o Laravel

No arquivo `.env` do Laravel, configure:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_juridico
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 4. Executar Migrações do Laravel

```bash
php artisan migrate
```

### 5. Criar Usuário Administrador

O script SQL já cria um usuário admin padrão:
- **Email**: admin@sistema.com
- **Senha**: password

## 🗂️ Estrutura do Banco

### Tabelas Principais

| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários do sistema |
| `cases` | Casos jurídicos dos clientes |
| `inss_processes` | Processos administrativos do INSS |
| `employment_relationships` | Vínculos empregatícios |
| `documents` | Documentos anexados aos casos |
| `petitions` | Petições geradas |
| `tasks` | Tarefas do workflow |

### Views Úteis

| View | Descrição |
|------|-----------|
| `vw_cases_summary` | Resumo dos casos com contadores |
| `vw_inss_processes_changes` | Processos INSS com mudanças |

### Procedures

| Procedure | Descrição |
|-----------|-----------|
| `CleanOldData()` | Limpa dados antigos automaticamente |

## 🔧 Configurações Importantes

### Índices Criados
- Busca por CPF do cliente
- Filtros por status
- Otimização de consultas por tipo de benefício
- Índices para processos INSS com mudanças

### Triggers
- Atualização automática de `has_changes` em processos INSS
- Marcação automática de processos como não vistos

## 📊 Dados de Exemplo

Para inserir dados de exemplo, você pode:

1. Usar os seeders do Laravel:
```bash
php artisan db:seed
```

2. Ou executar SQL manualmente no phpMyAdmin

## 🔒 Segurança

- Todas as senhas são hasheadas com bcrypt
- Foreign keys configuradas para integridade referencial
- Soft deletes implementados para casos
- Índices otimizados para performance

## 🚀 Próximos Passos

1. Configure o arquivo `.env` do Laravel
2. Execute as migrações
3. Teste o login com o usuário admin
4. Comece a cadastrar casos e documentos

## 📞 Suporte

Em caso de problemas:
1. Verifique se o MySQL/MariaDB está rodando
2. Confirme as credenciais no `.env`
3. Verifique se o charset está correto
4. Execute `php artisan config:clear` se necessário 