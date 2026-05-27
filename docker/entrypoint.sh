#!/bin/bash
set -e

echo "🚀 LMSAdvisor starting..."

# Wait for MariaDB
until mysql -h"${DB_HOST:-db}" -u"${DB_USER:-lmsadvisor}" -p"${DB_PASS:-secret}" "${DB_NAME:-lmsadvisor}" -e "SELECT 1" &>/dev/null; do
  echo "⏳ Waiting for database..."
  sleep 2
done
echo "✅ Database connected"

# Run migrations
php /var/www/html/migrate.php && echo "✅ Migrations complete"

# Start cron
service cron start && echo "✅ Cron started"

exec "$@"
