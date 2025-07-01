#!/bin/bash

# Quick Deploy Script - Previdia Dashboard
# Executa: curl -fsSL https://raw.githubusercontent.com/SEU_USUARIO/dashboard/main/quick-deploy.sh | bash

set -e

echo "🚀 Quick Deploy - Previdia Dashboard"
echo "======================================"

# Verificar se é Ubuntu
if ! grep -q "Ubuntu" /etc/os-release; then
    echo "❌ Este script é para Ubuntu. Sistema não suportado."
    exit 1
fi

# Atualizar sistema
echo "📦 Atualizando sistema..."
sudo apt update -qq

# Instalar dependências essenciais
echo "⚙️ Instalando dependências..."
sudo DEBIAN_FRONTEND=noninteractive apt install -y \
    nginx mysql-server php8.1-fpm php8.1-mysql php8.1-xml \
    php8.1-mbstring php8.1-curl php8.1-zip php8.1-bcmath \
    unzip git nodejs npm curl

# Instalar Composer
if ! command -v composer &> /dev/null; then
    echo "🎵 Instalando Composer..."
    curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

# Configurar projeto
PROJECT_PATH="/var/www/previdia"
REPO_URL="https://github.com/SEU_USUARIO/dashboard.git"

echo "📁 Configurando projeto..."
if [ -d "$PROJECT_PATH" ]; then
    echo "⚠️ Projeto existe. Fazendo backup..."
    sudo mv $PROJECT_PATH ${PROJECT_PATH}_backup_$(date +%Y%m%d_%H%M%S)
fi

# Clonar projeto
echo "⬇️ Clonando projeto..."
sudo git clone $REPO_URL $PROJECT_PATH
sudo chown -R www-data:www-data $PROJECT_PATH
cd $PROJECT_PATH

# Instalar dependências
echo "📚 Instalando dependências PHP..."
sudo -u www-data composer install --optimize-autoloader --no-dev --no-interaction

echo "📚 Instalando dependências Node..."
sudo -u www-data npm install --silent
sudo -u www-data npm run build

# Configurar ambiente
echo "🔧 Configurando ambiente..."
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate --force

# Configurar permissões
echo "🔐 Configurando permissões..."
sudo chown -R www-data:www-data $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH
sudo chmod -R 775 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache

# Configurar Nginx básico
echo "🌐 Configurando Nginx..."
sudo tee /etc/nginx/sites-available/previdia > /dev/null <<'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    root /var/www/previdia/public;
    index index.php index.html;
    
    server_name _;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
EOF

# Ativar site
sudo rm -f /etc/nginx/sites-enabled/default
sudo ln -sf /etc/nginx/sites-available/previdia /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# Iniciar serviços
echo "🚀 Iniciando serviços..."
sudo systemctl enable nginx php8.1-fpm mysql
sudo systemctl start nginx php8.1-fpm mysql

echo ""
echo "✅ Deploy concluído!"
echo "======================================"
echo "📍 Projeto instalado em: $PROJECT_PATH"
echo "🌐 Acesse: http://$(curl -s ifconfig.me)"
echo ""
echo "⚠️ PRÓXIMOS PASSOS:"
echo "1. Configure o banco de dados:"
echo "   sudo mysql -e \"CREATE DATABASE previdia_db;\""
echo "   sudo mysql -e \"CREATE USER 'previdia'@'localhost' IDENTIFIED BY 'sua_senha';\""
echo "   sudo mysql -e \"GRANT ALL ON previdia_db.* TO 'previdia'@'localhost';\""
echo ""
echo "2. Configure o .env:"
echo "   sudo nano $PROJECT_PATH/.env"
echo ""
echo "3. Execute as migrations:"
echo "   cd $PROJECT_PATH"
echo "   sudo -u www-data php artisan migrate --force"
echo "   sudo -u www-data php artisan db:seed --force"
echo ""
echo "4. Otimize o cache:"
echo "   sudo -u www-data php artisan config:cache"
echo "   sudo -u www-data php artisan route:cache"
echo ""