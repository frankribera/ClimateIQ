#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="/var/www/frankribera/climateiq"
CONFIG_DIR="/etc/climateiq"
REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ $EUID -ne 0 ]]; then
  echo "Run with sudo: sudo ./install.sh"
  exit 1
fi

echo "Installing ClimateIQ..."
apt-get update
apt-get install -y apache2 php libapache2-mod-php php-curl rsync

mkdir -p "$APP_DIR" "$CONFIG_DIR"
rsync -a --delete "$REPO_DIR/public/" "$APP_DIR/"

if [[ ! -f "$CONFIG_DIR/config.php" ]]; then
  cp "$REPO_DIR/config.example.php" "$CONFIG_DIR/config.php"
  chmod 640 "$CONFIG_DIR/config.php"
  chown root:www-data "$CONFIG_DIR/config.php"
fi

chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;

cat >/etc/apache2/conf-available/climateiq.conf <<APACHE
Alias /climateiq/ ${APP_DIR}/
<Directory ${APP_DIR}>
    Options -Indexes +FollowSymLinks
    AllowOverride None
    Require all granted
    DirectoryIndex index.html
</Directory>
APACHE

a2enconf climateiq >/dev/null
a2enmod headers >/dev/null
apache2ctl configtest
systemctl reload apache2

echo
echo "ClimateIQ installed successfully."
echo "Website: http://$(hostname -I | awk '{print $1}')/climateiq/"
echo "Configuration: $CONFIG_DIR/config.php"
echo
echo "Next:"
echo "  sudo nano $CONFIG_DIR/config.php"
echo "  sudo systemctl reload apache2"
