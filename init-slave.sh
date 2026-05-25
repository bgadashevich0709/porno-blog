#!/bin/bash
set -e

echo "=== Проверяем доступность Мастер-базы... ==="
until mysqladmin ping -h"db_master" -u"replicator" -p"repl_password_123" --silent; do
    echo "Мастер еще не готов, ждем 2 секунды..."
    sleep 2
done

echo "=== Мастер готов! Настраиваем репликацию... ==="
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<EOSQL
CHANGE REPLICATION SOURCE TO 
  SOURCE_HOST='db_master', 
  SOURCE_USER='replicator', 
  SOURCE_PASSWORD='repl_password_123', 
  SOURCE_AUTO_POSITION=1;
START REPLICA;
EOSQL

echo "=== Репликация успешно запущена на автомате! ==="
