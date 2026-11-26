#!/bin/bash
# Prepara o app "Notas de Transporte Animal" no seu servidor Docker (container banco_mysql).
# Uso, dentro desta pasta:   sudo bash instalar.sh
set -e
cd "$(dirname "$(readlink -f "$0")")"

if ! docker ps --format '{{.Names}}' | grep -qx banco_mysql; then
  echo "ERRO: o container banco_mysql nao esta rodando. Veja: docker ps"; exit 1
fi

SENHA="$(openssl rand -hex 12)"

echo ">> 1/4 Criando banco notas_animais e tabelas..."
docker exec -i banco_mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < database.sql

echo ">> 2/4 Criando usuario 'notas' (acesso so ao banco notas_animais)..."
docker exec -i banco_mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' <<SQL
CREATE USER IF NOT EXISTS 'notas'@'%' IDENTIFIED BY '$SENHA';
ALTER USER 'notas'@'%' IDENTIFIED BY '$SENHA';
GRANT ALL PRIVILEGES ON notas_animais.* TO 'notas'@'%';
FLUSH PRIVILEGES;
SQL

echo ">> 3/4 Gravando a senha em config/database.php..."
sed -i "s/\(NOTAS_DB_PASS') ?: \)'[^']*'/\1'$SENHA'/" config/database.php

echo ">> 4/4 Ajustando permissoes..."
mkdir -p storage
chgrp -R www-data storage vendor/dompdf/dompdf/lib/fonts
chmod -R g+rwX storage vendor/dompdf/dompdf/lib/fonts

echo
echo "PRONTO. Abra: http://servertecnico.local/gtatech/notas-senai-app-docker/"
