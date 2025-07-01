#!/bin/bash

# Script de Deploy Automático - Previdia
# Ubuntu 20.04 VPS

set -e  # Para o script se houver erro

echo "🚀 Iniciando deploy do Previdia no VPS..."
echo "IP: 177.153.20.195"
echo "Domínio: www.previdia.com.br"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para log
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    error "Este script deve ser executado como root"
fi

# Variáveis
DB_PASSWORD="SuaSenhaForte123!"
DOMAIN="www.previdia.com.br"
IP="177.153.20.195"

log "Atualizando sistema..."
apt update && apt upgrade -y

log "Criando usuário previdia..."
adduser --disabled-password --gecos "" previdia || true
usermod -aG sudo previdia

log "Configurando SSH para usuário previdia..."
mkdir -p /home/previdia/.ssh
cp ~/.ssh/authorized_keys /home/previdia/.ssh/ 2>/dev/null || true
chown -R previdia:previdia /home/previdia/.ssh
chmod 700 /home/previdia/.ssh
chmod 600 /home/previdia/.ssh/authorized_keys 2>/dev/null || true

log "Instalando PHP 8.2..."
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-sqlite3 php8.2-gd php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl php8.2-redis php8.2-imagick php8.2-tesseract php8.2-common php8.2-json php8.2-opcache php8.2-readline php8.2-xmlrpc php8.2-soap

log "Configurando PHP-FPM..."
systemctl enable php8.2-fpm
systemctl start php8.2-fpm

log "Instalando MySQL..."
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql

log "Configurando MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS previdia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'previdia_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON previdia_db.* TO 'previdia_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

log "Instalando Python 3.9..."
apt install -y python3.9 python3.9-pip python3.9-venv python3.9-dev

log "Instalando dependências do sistema para Python..."
apt install -y build-essential libssl-dev libffi-dev python3-dev
apt install -y libtesseract-dev tesseract-ocr tesseract-ocr-por
apt install -y libopencv-dev python3-opencv
apt install -y libgl1-mesa-glx libglib2.0-0 libsm6 libxext6 libxrender-dev libgomp1

log "Configurando ambiente virtual Python..."
cd /home/previdia
python3.9 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install PyMuPDF==1.23.8 pytesseract==0.3.10 Pillow==10.1.0 opencv-python==4.8.1.78 numpy==1.24.3 transformers==4.35.2 spacy==3.7.2 pandas==2.1.3 torch==2.1.1
python -m spacy download pt_core_news_sm

log "Instalando Nginx..."
apt install -y nginx
systemctl enable nginx
systemctl start nginx

log "Configurando firewall..."
ufw allow 'Nginx Full'
ufw allow OpenSSH
ufw --force enable

log "Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

log "Instalando Node.js 18..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

log "Instalando Redis..."
apt install -y redis-server
systemctl enable redis-server
systemctl start redis-server

log "Instalando Certbot..."
apt install -y certbot python3-certbot-nginx

log "Configurando projeto Laravel..."
cd /home/previdia

# Nota: Você precisa clonar seu repositório aqui
echo ""
warn "IMPORTANTE: Você precisa clonar seu repositório Git agora!"
echo "Execute: git clone https://github.com/seu-usuario/seu-repositorio.git previdia"
echo "Depois continue com o script..."
echo ""

# Aguardar confirmação do usuário
read -p "Pressione Enter após clonar o repositório..."

cd previdia

log "Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader

log "Instalando dependências Node.js..."
npm install
npm run build

log "Configurando arquivo .env..."
cp .env.example .env

# Configurar .env automaticamente
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i "s|APP_URL=http://localhost|APP_URL=https://$DOMAIN|" .env
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
sed -i 's/DB_PORT=3306/DB_PORT=3306/' .env
sed -i 's/DB_DATABASE=laravel/DB_DATABASE=previdia_db/' .env
sed -i 's/DB_USERNAME=root/DB_USERNAME=previdia_user/' .env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASSWORD/" .env
sed -i 's/CACHE_DRIVER=file/CACHE_DRIVER=redis/' .env
sed -i 's/SESSION_DRIVER=file/SESSION_DRIVER=redis/' .env
sed -i 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=redis/' .env

log "Gerando chave da aplicação..."
php artisan key:generate

log "Configurando permissões..."
chown -R previdia:www-data /home/previdia/previdia
chmod -R 755 /home/previdia/previdia
chmod -R 775 /home/previdia/previdia/storage
chmod -R 775 /home/previdia/previdia/bootstrap/cache

log "Configurando Nginx..."
cat > /etc/nginx/sites-available/previdia << 'EOF'
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
EOF

ln -sf /etc/nginx/sites-available/previdia /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

log "Executando migrações..."
php artisan migrate --force

log "Configurando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

log "Configurando backup automático..."
cat > /home/previdia/backup.sh << 'EOF'
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
EOF

chmod +x /home/previdia/backup.sh

# Configurar cron para backup
(crontab -l 2>/dev/null; echo "0 2 * * * /home/previdia/backup.sh") | crontab -

log "Configurando renovação automática do SSL..."
(crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -

log "Verificando status dos serviços..."
systemctl status nginx --no-pager -l
systemctl status php8.2-fpm --no-pager -l
systemctl status mysql --no-pager -l
systemctl status redis-server --no-pager -l

echo ""
log "✅ Deploy concluído com sucesso!"
echo ""
echo "📋 Próximos passos:"
echo "1. Configure o DNS do domínio www.previdia.com.br para apontar para $IP"
echo "2. Execute: sudo certbot --nginx -d www.previdia.com.br -d previdia.com.br"
echo "3. Teste o site: https://www.previdia.com.br"
echo ""
echo "🔧 Comandos úteis:"
echo "- Ver logs: sudo tail -f /home/previdia/previdia/storage/logs/laravel.log"
echo "- Reiniciar serviços: sudo systemctl restart nginx php8.2-fpm mysql redis-server"
echo "- Backup manual: /home/previdia/backup.sh"
echo ""
echo "🎯 O site estará disponível em: https://www.previdia.com.br" 