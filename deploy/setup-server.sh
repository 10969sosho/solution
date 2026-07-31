#!/bin/bash
# ============================================
# ADMS Server - Server Setup Script
# Target: SSH alurelab
# Repo: /alurelab/repositories/solution
# Deploy: /alurelab/payroll.3putraperkasa.com
# ============================================

set -e

echo "=========================================="
echo " ADMS Server Setup"
echo "=========================================="

# --- 1. System Update ---
echo "[1/8] Update system packages..."
sudo apt update && sudo apt upgrade -y

# --- 2. Install PHP 8.2 + Extensions ---
echo "[2/8] Install PHP 8.2..."
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli \
    php8.2-mysql php8.2-sqlite3 php8.2-pgsql \
    php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-bcmath php8.2-tokenizer \
    php8.2-gd php8.2-intl php8.2-redis

# --- 3. Install Composer ---
echo "[3/8] Install Composer..."
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"
fi

# --- 4. Install Nginx ---
echo "[4/8] Install Nginx..."
sudo apt install -y nginx

# --- 5. Install MySQL (optional - skip if DB set manually) ---
echo "[5/8] Install MySQL..."
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# --- 6. Create Deploy Directory ---
echo "[6/8] Setup directories..."
sudo mkdir -p /alurelab/repositories
sudo mkdir -p /alurelab/payroll.3putraperkasa.com

# --- 7. Clone Repository ---
echo "[7/8] Clone repository..."
if [ ! -d "/alurelab/repositories/solution/.git" ]; then
    sudo git clone https://github.com/10969sosho/solution.git /alurelab/repositories/solution
else
    echo "Repository already exists, pulling latest..."
    cd /alurelab/repositories/solution && sudo git pull origin main
fi

# --- 8. Setup Laravel ---
echo "[8/8] Setup Laravel..."
cd /alurelab/repositories/solution

sudo composer install --no-dev --optimize-autoloader

sudo cp .env.example .env
sudo php artisan key:generate

# Set permissions
sudo chown -R www-data:www-data /alurelab/repositories/solution
sudo chmod -R 775 /alurelab/repositories/solution/storage
sudo chmod -R 775 /alurelab/repositories/solution/bootstrap/cache

# Run migrations
sudo php artisan migrate --force

echo "=========================================="
echo " Setup complete!"
echo " Next: Configure Nginx (see nginx-adms.conf)"
echo "=========================================="
