<?php
// publico/api_chamadas.php
require '../config/conexao.php';
header('Content-Type: application/json');

$hoje = date('Y-m-d');

// Busca a chamada mais recente em destaque (APENAS AS VISÍVEIS)
$stmtDest = $pdo->prepare("
    SELECT c.id, f.nome_pessoa, m.nome as mesa, g.nome as grupo, DATE_FORMAT(c.data_hora, '%H:%i') as hora
    FROM chamadas c
    JOIN fila f ON c.id_fila = f.id
    JOIN mesas m ON c.id_mesa = m.id
    JOIN grupos g ON f.id_grupo = g.id
    WHERE DATE(c.data_hora) = ? AND c.visivel = TRUE
    ORDER BY c.id DESC LIMIT 1
");
$stmtDest->execute([$hoje]);
$destaque = $stmtDest->fetch();

// Busca o histórico (APENAS AS VISÍVEIS)
$historico = [];
if ($destaque) {
    $stmtHist = $pdo->prepare("
        SELECT f.nome_pessoa, m.nome as mesa, g.nome as grupo, DATE_FORMAT(c.data_hora, '%H:%i') as hora
        FROM chamadas c
        JOIN fila f ON c.id_fila = f.id
        JOIN mesas m ON c.id_mesa = m.id
        JOIN grupos g ON f.id_grupo = g.id
        WHERE c.id < ? AND DATE(c.data_hora) = ? AND c.visivel = TRUE
        ORDER BY c.id DESC LIMIT 4
    ");
    $stmtHist->execute([$destaque['id'], $hoje]);
    $historico = $stmtHist->fetchAll();
}

echo json_encode(['destaque' => $destaque, 'historico' => $historico]);