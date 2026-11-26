<?php
// Conexão PDO com o banco.
// No servidor (Docker): host "db" = container do MySQL. O instalar.sh cria o banco/usuário e grava a senha aqui.
// Usa variáveis NOTAS_DB_* (e NÃO DB_*), porque o container já define DB_NAME/DB_USER de outro projeto.
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('NOTAS_DB_HOST') ?: 'db';
        $nome = getenv('NOTAS_DB_NAME') ?: 'notas_animais';
        $user = getenv('NOTAS_DB_USER') ?: 'notas';
        $pass = getenv('NOTAS_DB_PASS') ?: '35fe851905f67df27e71ab2c';

        $pdo = new PDO(
            "mysql:host=$host;dbname=$nome;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}
