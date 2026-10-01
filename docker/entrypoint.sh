#!/bin/sh
set -e

# L'hébergeur (Render…) indique le port à écouter dans $PORT
PORT="${PORT:-80}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Crée les tables (et les données de démo si SEED_DEMO=true) ; réessaie si la base n'est pas encore prête
for attempt in 1 2 3 4 5; do
    if php /var/www/html/api/setup.php; then break; fi
    echo "Base de données injoignable (essai ${attempt}/5), nouvel essai dans 5 s..."
    sleep 5
done

exec apache2-foreground
