<?php
// admin/mesas.php
require '../config/conexao.php';
checkLogin(true);

$edit_id = $_GET['edit'] ?? null;
$edit_nome = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
    $nome = $_POST['nome'];
    if (!empty($_POST['id'])) {
        $pdo->prepare("UPDATE mesas SET nome = ? WHERE id = ?")->execute([$nome, $_POST['id']]);
    } else {
        $pdo->prepare("INSERT INTO mesas (nome) VALUES (?)")->execute([$nome]);
    }
    header("Location: mesas.php");
    exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM mesas WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: mesas.php");
    exit;
}

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT nome FROM mesas WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_nome = $stmt->fetchColumn();
}

$mesas = $pdo->query("SELECT * FROM mesas ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Mesas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Gerenciar Balcões/Mesas</h1>
            <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded">Voltar ao Admin</a>
        </div>

        <div class="bg-white p-6 rounded shadow mb-8">
            <form method="POST" class="flex gap-4 items-end">
                <input type="hidden" name="id" value="<?= $edit_id ?>">
                <div class="flex-1">
                    <label class="block text-gray-700 mb-2"><?= $edit_id ? 'Editar Mesa' : 'Nova Mesa' ?></label>
                    <input type="text" name="nome" value="<?= $edit_nome ?>" required placeholder="Ex: Mesa 01, Guichê A..." class="w-full px-4 py-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    <?= $edit_id ? 'Atualizar' : 'Salvar' ?>
                </button>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-gray-700">
                        <th class="p-4 border-b">ID</th>
                        <th class="p-4 border-b">Nome da Mesa</th>
                        <th class="p-4 border-b w-32">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($mesas as $m): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 border-b text-gray-500">#<?= $m['id'] ?></td>
                        <td class="p-4 border-b font-semibold"><?= $m['nome'] ?></td>
                        <td class="p-4 border-b flex gap-2">
                            <a href="?edit=<?= $m['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">Editar</a>
                            <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('Excluir?');" class="bg-red-500 text-white px-3 py-1 rounded text-sm">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>