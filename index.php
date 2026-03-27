<?php
// index.php
require 'config/conexao.php';

// Se já estiver logado, redireciona para a página correta e não duplica o ponto
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['is_admin']) {
        header("Location: admin/index.php");
    } else {
        header("Location: seleciona_mesa.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['senha'])) {
        // Salva os dados na Sessão
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['is_admin'] = $user['is_admin'];

        // ========================================================
        // REGISTRA O PONTO ELETRÔNICO PARA TODOS (INCLUINDO ADMIN)
        // ========================================================
        $stmtPonto = $pdo->prepare("INSERT INTO ponto_eletronico (id_usuario, hora_entrada) VALUES (?, NOW())");
        $stmtPonto->execute([$user['id']]);
        $_SESSION['ponto_id'] = $pdo->lastInsertId();

        // Redirecionamento baseado no nível de acesso
        if ($user['is_admin']) {
            header("Location: admin/index.php");
        } else {
            header("Location: seleciona_mesa.php");
        }
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso - Sistema de Chamadas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-800 via-green-700 to-green-900 h-screen flex items-center justify-center relative font-sans">
    
    <img src="imagens/brasao.png" alt="Brasão Fundo" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 h-[80vh] opacity-5 pointer-events-none">

    <div class="bg-white/10 backdrop-blur-md p-10 rounded-3xl shadow-2xl border border-white/20 w-full max-w-md z-10 relative">
        <div class="flex flex-col items-center justify-center mb-8">
            <div class="bg-white p-4 rounded-full shadow-lg mb-4">
                <img src="imagens/brasao.png" alt="Brasão" class="h-24">
            </div>
            <h2 class="text-3xl font-bold text-center text-white tracking-wider uppercase drop-shadow-md">Acesso ao Sistema</h2>
        </div>
        
        <?php if(isset($erro)): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 p-3 rounded-lg mb-6 text-center font-bold backdrop-blur-sm">
                <?= $erro ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="flex flex-col gap-5">
            <div>
                <label class="block text-green-100 font-semibold mb-2 uppercase text-sm tracking-wide">Seu E-mail</label>
                <input type="email" name="email" required placeholder="Digite seu e-mail cadastrado" class="w-full px-5 py-3 border border-transparent rounded-xl focus:outline-none focus:ring-4 focus:ring-green-400 bg-white/80 text-green-900 font-medium placeholder-gray-500 transition-all shadow-inner">
            </div>
            <div>
                <label class="block text-green-100 font-semibold mb-2 uppercase text-sm tracking-wide">Sua Senha</label>
                <input type="password" name="senha" required placeholder="••••••••" class="w-full px-5 py-3 border border-transparent rounded-xl focus:outline-none focus:ring-4 focus:ring-green-400 bg-white/80 text-green-900 font-medium placeholder-gray-500 transition-all shadow-inner">
            </div>
            <button type="submit" class="w-full bg-green-500 hover:bg-green-400 text-green-900 py-4 mt-4 rounded-xl font-extrabold text-lg shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all uppercase tracking-widest">
                Entrar no Sistema
            </button>
        </form>
    </div>
</body>
</html>