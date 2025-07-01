# Tutorial Completo - Deploy do Site Laravel no VPS Ubuntu 20.04

## 📋 Informações do Projeto
- **Domínio**: www.previdia.com.br
- **IP do VPS**: 177.153.20.195
- **Sistema**: Ubuntu 20.04
- **Framework**: Laravel 12 + React + TypeScript
- **Banco de Dados**: MySQL
- **Extras**: Python para extração CNIS, Google Document AI, OpenAI

---

## 🚀 PASSO 1: Preparação do VPS

### 1.1 Conectar ao VPS
```bash
ssh root@177.153.20.195
```

### 1.2 Atualizar o sistema
```bash
apt update && apt upgrade -y
```

### 1.3 Criar usuário para o projeto
```bash
adduser previdia
usermod -aG sudo previdia
```

### 1.4 Configurar SSH para o novo usuário
```bash
mkdir -p /home/previdia/.ssh
cp ~/.ssh/authorized_keys /home/previdia/.ssh/
chown -R previdia:previdia /home/previdia/.ssh
chmod 700 /home/previdia/.ssh
chmod 600 /home/previdia/.ssh/authorized_keys
```

---

## 🐘 PASSO 2: Instalação do PHP 8.2

### 2.1 Adicionar repositório do PHP
```bash
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
```

### 2.2 Instalar PHP 8.2 e extensões necessárias
```bash
apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl php8.2-redis php8.2-imagick php8.2-tesseract php8.2-common php8.2-json php8.2-opcache php8.2-readline php8.2-xmlrpc php8.2-soap
```

### 2.3 Configurar PHP-FPM
```bash
systemctl enable php8.2-fpm
systemctl start php8.2-fpm
```

---

## 🗄️ PASSO 3: Instalação do MySQL

### 3.1 Instalar MySQL
```bash
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql
```

### 3.2 Configurar segurança do MySQL
```bash
mysql_secure_installation
```

### 3.3 Criar banco de dados e usuário
```bash
mysql -u root -p
```

No MySQL:
```sql
CREATE DATABASE previdia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'previdia_user'@'localhost' IDENTIFIED BY 'SuaSenhaForte123!';
GRANT ALL PRIVILEGES ON previdia_db.* TO 'previdia_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🐍 PASSO 4: Instalação do Python e Dependências

### 4.1 Instalar Python 3.9
```bash
apt install -y python3.9 python3.9-pip python3.9-venv python3.9-dev
```

### 4.2 Instalar dependências do sistema para Python
```bash
apt install -y build-essential libssl-dev libffi-dev python3-dev
apt install -y libtesseract-dev tesseract-ocr tesseract-ocr-por
apt install -y libopencv-dev python3-opencv
apt install -y libgl1-mesa-glx libglib2.0-0
apt install -y libsm6 libxext6 libxrender-dev libgomp1
```

### 4.3 Criar ambiente virtual Python
```bash
cd /home/previdia
python3.9 -m venv venv
source venv/bin/activate
```

### 4.4 Instalar dependências Python
```bash
pip install --upgrade pip
pip install PyMuPDF==1.23.8 pytesseract==0.3.10 Pillow==10.1.0 opencv-python==4.8.1.78 numpy==1.24.3 transformers==4.35.2 spacy==3.7.2 pandas==2.1.3 torch==2.1.1
python -m spacy download pt_core_news_sm
```

---

## 🌐 PASSO 5: Instalação do Nginx

### 5.1 Instalar Nginx
```bash
apt install -y nginx
systemctl enable nginx
systemctl start nginx
```

### 5.2 Configurar firewall
```bash
ufw allow 'Nginx Full'
ufw allow OpenSSH
ufw enable
```

---

## 📦 PASSO 6: Instalação do Composer e Node.js

### 6.1 Instalar Composer
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

### 6.2 Instalar Node.js 18
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs
```

### 6.3 Verificar instalações
```bash
composer --version
node --version
npm --version
```

---

## 🔧 PASSO 7: Configuração do Projeto

### 7.1 Mudar para usuário previdia
```bash
su - previdia
```

### 7.2 Clonar o projeto (substitua pela URL do seu repositório)
```bash
cd /home/previdia
git clone https://github.com/seu-usuario/seu-repositorio.git previdia
cd previdia
```

### 7.3 Instalar dependências PHP
```bash
composer install --no-dev --optimize-autoloader
```

### 7.4 Instalar dependências Node.js
```bash
npm install
npm run build
```

### 7.5 Configurar arquivo .env
```bash
cp .env.example .env
nano .env
```

Configurações importantes no .env:
```env
APP_NAME="Previdia"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.previdia.com.br

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=previdia_db
DB_USERNAME=previdia_user
DB_PASSWORD=SuaSenhaForte123!

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 7.6 Gerar chave da aplicação
```bash
php artisan key:generate
```

### 7.7 Configurar permissões
```bash
sudo chown -R previdia:www-data /home/previdia/previdia
sudo chmod -R 755 /home/previdia/previdia
sudo chmod -R 775 /home/previdia/previdia/storage
sudo chmod -R 775 /home/previdia/previdia/bootstrap/cache
```

---

## 🗄️ PASSO 8: Configuração do Redis

### 8.1 Instalar Redis
```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

## ⚙️ PASSO 9: Configuração do Nginx

### 9.1 Criar configuração do site
```bash
sudo nano /etc/nginx/sites-available/previdia
```

Conteúdo da configuração:
```nginx
server {
    listen 80;
    server_name www.previdia.com.br previdia.com.br;
    root /home/previdia/previdia/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 9.2 Ativar o site
```bash
sudo ln -s /etc/nginx/sites-available/previdia /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔒 PASSO 10: Configuração do SSL (Certbot)

### 10.1 Instalar Certbot
```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 10.2 Obter certificado SSL
```bash
sudo certbot --nginx -d www.previdia.com.br -d previdia.com.br
```

### 10.3 Configurar renovação automática
```bash
sudo crontab -e
```

Adicionar linha:
```
0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 🚀 PASSO 11: Configuração Final do Laravel

### 11.1 Executar migrações
```bash
cd /home/previdia/previdia
php artisan migrate --force
```

### 11.2 Executar seeders (se necessário)
```bash
php artisan db:seed --force
```

### 11.3 Configurar cache
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 11.4 Configurar filas (opcional)
```bash
sudo nano /etc/systemd/system/previdia-queue.service
```

Conteúdo:
```ini
[Unit]
Description=Previdia Queue Worker
After=network.target

[Service]
Type=simple
User=previdia
Group=www-data
Restart=always
ExecStart=/usr/bin/php /home/previdia/previdia/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=append:/home/previdia/previdia/storage/logs/queue.log
StandardError=append:/home/previdia/previdia/storage/logs/queue.log

[Install]
WantedBy=multi-user.target
```

Ativar o serviço:
```bash
sudo systemctl enable previdia-queue
sudo systemctl start previdia-queue
```

---

## 🔧 PASSO 12: Configuração do Supervisor (Opcional)

### 12.1 Instalar Supervisor
```bash
sudo apt install -y supervisor
```

### 12.2 Configurar processo
```bash
sudo nano /etc/supervisor/conf.d/previdia.conf
```

Conteúdo:
```ini
[program:previdia-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /home/previdia/previdia/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=previdia
numprocs=2
redirect_stderr=true
stdout_logfile=/home/previdia/previdia/storage/logs/worker.log
```

### 12.3 Ativar Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start previdia-queue:*
```

---

## 📋 PASSO 13: Verificações Finais

### 13.1 Verificar status dos serviços
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
```

### 13.2 Verificar logs
```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /home/previdia/previdia/storage/logs/laravel.log
```

### 13.3 Testar o site
```bash
curl -I https://www.previdia.com.br
```

---

## 🔧 PASSO 14: Configurações Adicionais

### 14.1 Configurar backup automático
```bash
sudo nano /home/previdia/backup.sh
```

Conteúdo:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/previdia/backups"
mkdir -p $BACKUP_DIR

# Backup do banco
mysqldump -u previdia_user -p'SuaSenhaForte123!' previdia_db > $BACKUP_DIR/db_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /home/previdia/previdia

# Manter apenas os últimos 7 backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

```bash
chmod +x /home/previdia/backup.sh
```

### 14.2 Configurar cron para backup
```bash
crontab -e
```

Adicionar:
```
0 2 * * * /home/previdia/backup.sh
```

---

## 🎯 PASSO 15: Configuração do Domínio

### 15.1 Configurar DNS
No seu provedor de DNS, configure:
- **A Record**: www.previdia.com.br → 177.153.20.195
- **A Record**: previdia.com.br → 177.153.20.195

### 15.2 Aguardar propagação
Pode levar até 24 horas para propagar completamente.

---

## ✅ Checklist Final

- [ ] PHP 8.2 instalado e configurado
- [ ] MySQL instalado e banco criado
- [ ] Python 3.9 com dependências instaladas
- [ ] Nginx configurado
- [ ] SSL configurado
- [ ] Laravel instalado e configurado
- [ ] Dependências Node.js instaladas
- [ ] Migrações executadas
- [ ] Cache configurado
- [ ] Filas configuradas (opcional)
- [ ] Backup configurado
- [ ] DNS configurado

---

## 🆘 Troubleshooting

### Problemas comuns:

1. **Erro 502 Bad Gateway**: Verificar se PHP-FPM está rodando
2. **Erro de permissão**: Verificar permissões do storage e cache
3. **Erro de conexão com banco**: Verificar configurações do .env
4. **SSL não funciona**: Verificar se o domínio está apontando corretamente

### Comandos úteis:
```bash
# Reiniciar serviços
sudo systemctl restart nginx php8.2-fpm mysql redis-server

# Ver logs
sudo journalctl -u nginx -f
sudo journalctl -u php8.2-fpm -f

# Limpar cache Laravel
php artisan cache:clear
php artisan config:clear
```

---

## 📞 Suporte

Se encontrar problemas, verifique:
1. Logs do Nginx: `/var/log/nginx/error.log`
2. Logs do Laravel: `/home/previdia/previdia/storage/logs/laravel.log`
3. Logs do PHP-FPM: `/var/log/php8.2-fpm.log`

O site estará disponível em: https://www.previdia.com.br 