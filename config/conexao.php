<?php
// config/conexao.php
session_start();
date_default_timezone_set('America/Sao_Paulo');

$host = 'localhost';
$db   = 'nome_do_banco_de_dados';
$user = 'nome_de_usuario';
$pass = 'senha_de_acesso';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Função auxiliar para verificar login
function checkLogin($adminOnly = false) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: /index.php");
        exit;
    }
    if ($adminOnly && !$_SESSION['is_admin']) {
        die("Acesso restrito.");
    }
}
?>
