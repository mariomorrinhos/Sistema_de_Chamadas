<?php
// logout.php
require 'config/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    
    // 1. Libera a mesa e o serviço, caso o usuário tenha vinculado alguma
    $pdo->prepare("UPDATE mesas SET id_usuario_atual = NULL, id_grupo_atual = NULL WHERE id_usuario_atual = ?")
        ->execute([$_SESSION['usuario_id']]);

    // 2. Registra o horário de saída no ponto eletrônico (PARA QUALQUER USUÁRIO)
    if (isset($_SESSION['ponto_id'])) {
        $pdo->prepare("UPDATE ponto_eletronico SET hora_saida = NOW() WHERE id = ?")
            ->execute([$_SESSION['ponto_id']]);
    }
}

// Destrói a sessão completamente
session_destroy();

// Redireciona de volta para a tela de login elegante
header("Location: index.php");
exit;