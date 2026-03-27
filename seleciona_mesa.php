<?php
// seleciona_mesa.php
require 'config/conexao.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mesa']) && isset($_POST['id_grupo'])) {
    $id_mesa = $_POST['id_mesa'];
    $id_grupo = $_POST['id_grupo'];
    
    // Verifica se a mesa está livre
    $stmt = $pdo->prepare("SELECT id FROM mesas WHERE id = ? AND id_usuario_atual IS NULL");
    $stmt->execute([$id_mesa]);
    
    if ($stmt->fetch()) {
        // Bloqueia a mesa para o usuário atual E vincula o grupo de atendimento
        $pdo->prepare("UPDATE mesas SET id_usuario_atual = ?, id_grupo_atual = ? WHERE id = ?")
            ->execute([$_SESSION['usuario_id'], $id_grupo, $id_mesa]);
        
        $_SESSION['mesa_id'] = $id_mesa;
        $_SESSION['grupo_id'] = $id_grupo; // Salva o grupo na sessão
        
        header("Location: painel.php");
        exit;
    } else {
        $erro = "Esta mesa já está em uso por outro usuário.";
    }
}

$mesas_livres = $pdo->query("SELECT * FROM mesas WHERE id_usuario_atual IS NULL ORDER BY nome")->fetchAll();
$grupos = $pdo->query("SELECT * FROM grupos ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurar Atendimento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-6 text-center text-gray-800">Configure seu Atendimento</h2>
        <?php if(isset($erro)): ?><p class="text-red-500 mb-4 text-center"><?= $erro ?></p><?php endif; ?>
        
        <form method="POST" class="flex flex-col gap-4">
            <div>
                <label class="block text-gray-700 font-semibold mb-1">1. Selecione sua Mesa/Balcão</label>
                <select name="id_mesa" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="">-- Mesas Disponíveis --</option>
                    <?php foreach($mesas_livres as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= $m['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">2. Selecione o Serviço (Grupo)</label>
                <select name="id_grupo" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="">-- Qual serviço você fará hoje? --</option>
                    <?php foreach($grupos as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= $g['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="w-full mt-4 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-bold text-lg shadow-md transition-colors">
                Iniciar Atendimento
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="logout.php" class="text-red-500 underline hover:text-red-700">Cancelar e Sair</a>
        </div>
    </div>
</body>
</html>