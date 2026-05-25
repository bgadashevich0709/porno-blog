CREATE USER 'replicator'@'%' IDENTIFIED WITH mysql_native_password BY 'repl_password_123';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
FLUSH PRIVILEGES;
