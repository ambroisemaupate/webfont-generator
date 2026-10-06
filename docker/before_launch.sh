# Install dependencies when the repository is mounted without vendor/
[ -f /var/www/html/vendor/autoload.php ] || COMPOSER_ALLOW_SUPERUSER=1 composer install -n -d /var/www/html
# Fix volume permissions
exec chown -R www-data:www-data /var/www/html
