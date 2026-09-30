<?php
$servidor = "localhost";
$porta = "3306";
$usuario = "depurador_tcc";
$senha = "A52329";
$banco = "Academia";
$charset = "utf8mb4";

$dns = "mysql:host=$servidor;port=$porta;dbname=$banco;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

try {
    $pdo = new PDO($dns, $usuario, $senha, $options);
} catch (\PDOException $e) {
    error_log("Conexão Falhou: ". $e->getMessage());
    die("Erro de conexão ao banco de dados.". $e->getMessage());
}