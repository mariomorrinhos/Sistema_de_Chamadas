<?php
// admin/grupos.php
require '../config/conexao.php';
checkLogin(true);

$edit_id = $_GET['edit'] ?? null;
$edit_nome = '';

// Processa formulário de Salvar/Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'];
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE grupos SET nome = ? WHERE id = ?")->execute([$nome, $_POST['id']]);
    } else {
        $pdo->prepare("INSERT INTO grupos (nome) VALUES (?)")->execute([$nome]);
    }
    header("Location: grupos.php");
    exit;
}

// Processa Exclusão
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM grupos WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: grupos.php");
    exit;
}

// Carrega dados para edição
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT nome FROM grupos WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_nome = $stmt->fetchColumn();
}

$grupos = $pdo->query("SELECT * FROM grupos ORDER BY nome")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Grupos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Gerenciar Grupos de Serviço</h1>
            <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded">Voltar ao Admin</a>
        </div>

        <div class="bg-white p-6 rounded shadow mb-8">
            <form method="POST" class="flex gap-4 items-end">
                <input type="hidden" name="id" value="<?= $edit_id ?>">
                <div class="flex-1">
                    <label class="block text-gray-700 mb-2"><?= $edit_id ? 'Editar Grupo' : 'Novo Grupo' ?></label>
                    <input type="text" name="nome" value="<?= $edit_nome ?>" required placeholder="Ex: Triagem, Digitalização..." class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    <?= $edit_id ? 'Atualizar' : 'Salvar' ?>
                </button>
                <?php if($edit_id): ?>
                    <a href="grupos.php" class="bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-gray-700">
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Nome do Grupo</th>
                        <th class="p-4 border-b w-32">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($grupos as $g): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 border-b text-gray-500">#<?= $g['id'] ?></td>
                        <td class="p-4 border-b font-semibold"><?= $g['nome'] ?></td>
                        <td class="p-4 border-b flex gap-2">
                            <a href="?edit=<?= $g['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm hover:bg-yellow-600">Editar</a>
                            <a href="?delete=<?= $g['id'] ?>" onclick="return confirm('Excluir este grupo?');" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>