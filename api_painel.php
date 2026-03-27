<?php
// api_painel.php
require 'config/conexao.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['grupo_id']) || !isset($_SESSION['mesa_id'])) {
    echo json_encode(['erro' => 'Sessão inválida']);
    exit;
}

// Verifica se o admin deslogou a mesa
$stmtCheck = $pdo->prepare("SELECT id FROM mesas WHERE id = ? AND id_usuario_atual = ?");
$stmtCheck->execute([$_SESSION['mesa_id'], $_SESSION['usuario_id']]);
if (!$stmtCheck->fetch()) {
    echo json_encode(['force_logout' => true]);
    exit;
}

$grupo_id = $_SESSION['grupo_id'];

// 1. Busca a fila DO GRUPO (Adicionado o campo 'id')
$stmtFila = $pdo->prepare("
    SELECT id, nome_pessoa, DATE_FORMAT(data_criacao, '%H:%i') as hora 
    FROM fila 
    WHERE status = 'aguardando' AND id_grupo = ? 
    ORDER BY id ASC
");
$stmtFila->execute([$grupo_id]);
$fila = $stmtFila->fetchAll();

// 2. Busca o histórico DO GRUPO (Adicionado o campo f.id as id_fila)
$stmtHist = $pdo->prepare("
    SELECT f.id as id_fila, f.nome_pessoa, m.nome as mesa, DATE_FORMAT(c.data_hora, '%H:%i') as hora 
    FROM chamadas c 
    JOIN fila f ON c.id_fila = f.id 
    JOIN mesas m ON c.id_mesa = m.id 
    WHERE f.id_grupo = ?
    ORDER BY c.id DESC LIMIT 15
");
$stmtHist->execute([$grupo_id]);
$historico = $stmtHist->fetchAll();

echo json_encode([
    'fila' => $fila,
    'historico' => $historico
]);