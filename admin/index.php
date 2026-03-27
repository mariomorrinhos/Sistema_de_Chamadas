<?php
// admin/index.php
require '../config/conexao.php';
checkLogin(true); 

$p = $_GET['p'] ?? 'dashboard';

function formatarSegundos($segundos) {
    $h = floor($segundos / 3600);
    $m = floor(($segundos % 3600) / 60);
    return sprintf("%02d:%02d", $h, $m);
}

// ==========================================
// AÇÕES DE RESET DO SISTEMA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    // RESET TOTAL (Apaga Tudo, mantém apenas usuários)
    if ($_POST['acao'] === 'reset_total') {
        $pdo->exec("DELETE FROM chamadas");
        $pdo->exec("DELETE FROM fila");
        $pdo->exec("DELETE FROM ponto_eletronico");
        $pdo->exec("DELETE FROM mesas");
        $pdo->exec("DELETE FROM grupos");
        
        $pdo->exec("ALTER TABLE chamadas AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE fila AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE ponto_eletronico AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE mesas AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE grupos AUTO_INCREMENT = 1");
        
        header("Location: index.php?p=dashboard&msg=reset_total_sucesso");
        exit;
    }
    
    // RESET PARCIAL (Apaga filas, chamadas e ponto, MAS MANTÉM Mesas e Serviços)
    if ($_POST['acao'] === 'reset_atendimentos') {
        $pdo->exec("DELETE FROM chamadas");
        $pdo->exec("DELETE FROM fila");
        $pdo->exec("DELETE FROM ponto_eletronico");
        
        $pdo->exec("ALTER TABLE chamadas AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE fila AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE ponto_eletronico AUTO_INCREMENT = 1");
        
        // Desvincula todo mundo das mesas forçadamente para o próximo turno
        $pdo->exec("UPDATE mesas SET id_usuario_atual = NULL, id_grupo_atual = NULL");
        
        header("Location: index.php?p=dashboard&msg=reset_atend_sucesso");
        exit;
    }
}

// ==========================================
// LÓGICA DE LIBERAR MESA MANUALMENTE (DESLOGAR)
// ==========================================
if ($p === 'dashboard' && isset($_GET['liberar_mesa'])) {
    $id_mesa_liberar = $_GET['liberar_mesa'];
    $stmtUser = $pdo->prepare("SELECT id_usuario_atual FROM mesas WHERE id = ?");
    $stmtUser->execute([$id_mesa_liberar]);
    $user_id_bloqueando = $stmtUser->fetchColumn();
    if ($user_id_bloqueando) {
        $pdo->prepare("UPDATE mesas SET id_usuario_atual = NULL, id_grupo_atual = NULL WHERE id = ?")->execute([$id_mesa_liberar]);
        $pdo->prepare("UPDATE ponto_eletronico SET hora_saida = NOW() WHERE id_usuario = ? AND hora_saida IS NULL")->execute([$user_id_bloqueando]);
        header("Location: index.php?p=dashboard&msg=mesa_liberada");
        exit;
    }
}

// ==========================================
// CRUD GRUPOS
// ==========================================
if ($p === 'grupos') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_grupo'])) {
        $nome = trim($_POST['nome_grupo']);
        $id = $_POST['id_grupo'] ?? '';
        if (!empty($id)) $pdo->prepare("UPDATE grupos SET nome = ? WHERE id = ?")->execute([$nome, $id]);
        else $pdo->prepare("INSERT INTO grupos (nome) VALUES (?)")->execute([$nome]);
        header("Location: index.php?p=grupos&msg=sucesso");
        exit;
    }
    if (isset($_GET['delete_grupo'])) {
        $pdo->prepare("DELETE FROM grupos WHERE id = ?")->execute([$_GET['delete_grupo']]);
        header("Location: index.php?p=grupos&msg=excluido");
        exit;
    }
    $grupos = $pdo->query("SELECT * FROM grupos ORDER BY nome")->fetchAll();
    $edit_grupo = null;
    if (isset($_GET['edit_grupo'])) {
        $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
        $stmt->execute([$_GET['edit_grupo']]);
        $edit_grupo = $stmt->fetch();
    }
}

// ==========================================
// CRUD MESAS
// ==========================================
if ($p === 'mesas') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_mesa'])) {
        $nome = trim($_POST['nome_mesa']);
        $id = $_POST['id_mesa'] ?? '';
        if (!empty($id)) $pdo->prepare("UPDATE mesas SET nome = ? WHERE id = ?")->execute([$nome, $id]);
        else $pdo->prepare("INSERT INTO mesas (nome) VALUES (?)")->execute([$nome]);
        header("Location: index.php?p=mesas&msg=sucesso");
        exit;
    }
    if (isset($_GET['delete_mesa'])) {
        $pdo->prepare("DELETE FROM mesas WHERE id = ?")->execute([$_GET['delete_mesa']]);
        header("Location: index.php?p=mesas&msg=excluido");
        exit;
    }
    $mesas = $pdo->query("SELECT * FROM mesas ORDER BY nome")->fetchAll();
    $edit_mesa = null;
    if (isset($_GET['edit_mesa'])) {
        $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ?");
        $stmt->execute([$_GET['edit_mesa']]);
        $edit_mesa = $stmt->fetch();
    }
}

// ==========================================
// CRUD USUÁRIOS
// ==========================================
if ($p === 'usuarios') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome_usuario'])) {
        $nome = trim($_POST['nome_usuario']);
        $email = trim($_POST['email_usuario']);
        $senha = $_POST['senha_usuario'];
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $id = $_POST['id_usuario'] ?? '';
        if (!empty($id)) {
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, is_admin = ? WHERE id = ?")->execute([$nome, $email, $senha_hash, $is_admin, $id]);
            } else {
                $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, is_admin = ? WHERE id = ?")->execute([$nome, $email, $is_admin, $id]);
            }
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO usuarios (nome, email, senha, is_admin) VALUES (?, ?, ?, ?)")->execute([$nome, $email, $senha_hash, $is_admin]);
        }
        header("Location: index.php?p=usuarios&msg=sucesso");
        exit;
    }
    if (isset($_GET['delete_usuario'])) {
        if ($_GET['delete_usuario'] == $_SESSION['usuario_id']) { header("Location: index.php?p=usuarios&msg=erro_auto_exclusao"); exit; }
        $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$_GET['delete_usuario']]);
        header("Location: index.php?p=usuarios&msg=excluido");
        exit;
    }
    $usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome")->fetchAll();
    $edit_usuario = null;
    if (isset($_GET['edit_usuario'])) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_GET['edit_usuario']]);
        $edit_usuario = $stmt->fetch();
    }
}

// ==========================================
// RELATÓRIO DE PONTO
// ==========================================
$todos_usuarios_ativos = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetchAll();
if ($p === 'ponto') {
    $filtro_usuario = $_GET['filtro_usuario'] ?? '';
    $filtro_data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
    $filtro_data_fim = $_GET['data_fim'] ?? date('Y-m-t');

    $where = "DATE(p.hora_entrada) BETWEEN :data_inicio AND :data_fim";
    $params = [':data_inicio' => $filtro_data_inicio, ':data_fim' => $filtro_data_fim];
    if (!empty($filtro_usuario)) {
        $where .= " AND p.id_usuario = :id_usuario";
        $params[':id_usuario'] = $filtro_usuario;
    }
    $sql = "SELECT u.nome, DATE(p.hora_entrada) as data_ponto, MIN(p.hora_entrada) as primeira_entrada, MAX(p.hora_saida) as ultima_saida, SUM(TIMESTAMPDIFF(SECOND, p.hora_entrada, IFNULL(p.hora_saida, NOW()))) as total_segundos FROM ponto_eletronico p JOIN usuarios u ON p.id_usuario = u.id WHERE $where GROUP BY u.id, DATE(p.hora_entrada) ORDER BY DATE(p.hora_entrada) DESC, u.nome ASC";
    $stmtPonto = $pdo->prepare($sql);
    $stmtPonto->execute($params);
    $relatorio_ponto = $stmtPonto->fetchAll();
}

// ==========================================
// DADOS DO DASHBOARD
// ==========================================
if ($p === 'dashboard') {
    $total_chamadas_hoje = $pdo->query("SELECT COUNT(*) FROM chamadas WHERE DATE(data_hora) = CURDATE()")->fetchColumn();
    $total_grupos = $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn();
    $total_mesas = $pdo->query("SELECT COUNT(*) FROM mesas")->fetchColumn();
    
    $mesas_ativas = $pdo->query("
        SELECT m.id, m.nome as mesa, u.nome as usuario, g.nome as grupo 
        FROM mesas m JOIN usuarios u ON m.id_usuario_atual = u.id JOIN grupos g ON m.id_grupo_atual = g.id
    ")->fetchAll();

    $fila_espera = $pdo->query("
        SELECT f.nome_pessoa, g.nome as grupo, DATE_FORMAT(f.data_criacao, '%H:%i') as hora, 
               TIMESTAMPDIFF(MINUTE, f.data_criacao, NOW()) as minutos_espera 
        FROM fila f JOIN grupos g ON f.id_grupo = g.id 
        WHERE f.status = 'aguardando' ORDER BY f.data_criacao ASC
    ")->fetchAll();
}

// ==========================================
// ESTATÍSTICAS E GRÁFICOS (CORRIGIDO)
// ==========================================
if ($p === 'estatisticas') {
    $filtro_data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
    $filtro_data_fim = $_GET['data_fim'] ?? date('Y-m-t');
    $filtro_usuario = $_GET['filtro_usuario'] ?? '';

    $where_est = "DATE(c.data_hora) BETWEEN :inicio AND :fim";
    $params_est = [':inicio' => $filtro_data_inicio, ':fim' => $filtro_data_fim];
    
    if (!empty($filtro_usuario)) {
        $where_est .= " AND c.id_usuario = :id_usuario";
        $params_est[':id_usuario'] = $filtro_usuario;
    }

    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM chamadas c WHERE $where_est");
    $stmtTotal->execute($params_est);
    $total_periodo = $stmtTotal->fetchColumn();

    $stmtGrupo = $pdo->prepare("
        SELECT g.nome, COUNT(c.id) as total 
        FROM chamadas c JOIN fila f ON c.id_fila = f.id JOIN grupos g ON f.id_grupo = g.id 
        WHERE $where_est GROUP BY g.id ORDER BY total DESC
    ");
    $stmtGrupo->execute($params_est);
    $stats_grupos = $stmtGrupo->fetchAll();

    $stmtDia = $pdo->prepare("
        SELECT DATE_FORMAT(c.data_hora, '%d/%m') as dia, COUNT(c.id) as total 
        FROM chamadas c WHERE $where_est GROUP BY DATE(c.data_hora) ORDER BY DATE(c.data_hora) ASC
    ");
    $stmtDia->execute($params_est);
    $stats_dias = $stmtDia->fetchAll();
    
    // O fallback `?: []` previne erros no JS se a consulta vier vazia
    $labels_grupos = json_encode(array_column($stats_grupos, 'nome') ?: []);
    $data_grupos = json_encode(array_column($stats_grupos, 'total') ?: []);
    $labels_dias = json_encode(array_column($stats_dias, 'dia') ?: []);
    $data_dias = json_encode(array_column($stats_dias, 'total') ?: []);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Administração - Sistema de Chamadas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            @page { margin: 0; } 
            body { margin: 1.6cm; background-color: white !important; }
            .print\:hidden { display: none !important; }
            canvas { max-height: 400px !important; } 
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex overflow-hidden font-sans print:h-auto print:block">
    
    <aside class="w-64 bg-green-900 text-white flex flex-col shrink-0 shadow-xl z-20 print:hidden">
        <div class="p-6 text-center border-b border-green-800 flex flex-col items-center gap-3">
            <img src="../imagens/brasao.png" alt="Logo" class="h-16 brightness-0 invert opacity-90">
            <span class="font-extrabold text-xl tracking-wider uppercase">Painel Admin</span>
        </div>
        <nav class="flex-1 p-4 flex flex-col gap-2 overflow-y-auto">
            <a href="?p=dashboard" class="p-3 rounded-lg font-semibold transition-colors <?= $p === 'dashboard' ? 'bg-green-700 shadow' : 'hover:bg-green-800 text-green-100' ?>">📊 Dashboard</a>
            <a href="?p=estatisticas" class="p-3 rounded-lg font-semibold transition-colors <?= $p === 'estatisticas' ? 'bg-green-700 shadow' : 'hover:bg-green-800 text-green-100' ?>">📈 Estatísticas</a>
            <a href="?p=grupos" class="p-3 rounded-lg font-semibold transition-colors <?= $p === 'grupos' ? 'bg-green-700 shadow' : 'hover:bg-green-800 text-green-100' ?>">📂 Grupos (Serviços)</a>
            <a href="?p=mesas" class="p-3 rounded-lg font-semibold transition-colors <?= $p === 'mesas' ? 'bg-green-700 shadow' : 'hover:bg-green-800 text-green-100' ?>">🖥️ Mesas (Balcões)</a>
            <a href="?p=usuarios" class="p-3 rounded-lg font-semibold transition-colors <?= $p === 'usuarios' ? 'bg-green-700 shadow' : 'hover:bg-green-800 text-green-100' ?>">👥 Usuários</a>
            <a href="?p=ponto" class="p-3 rounded-lg font-semibold transition-colors <?= $p === 'ponto' ? 'bg-green-700 shadow' : 'hover:bg-green-800 text-green-100' ?>">🕒 Relatório de Ponto</a>
        </nav>
        
        <div class="p-4 border-t border-green-800 flex flex-col gap-3">
            <a href="../painel.php" class="block w-full text-center bg-green-700 hover:bg-green-600 text-white py-2 rounded transition-colors font-semibold shadow">Voltar ao Atendimento</a>
            <a href="../logout.php" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white py-2 rounded transition-colors font-semibold shadow">Sair do Sistema</a>
            
            <hr class="border-green-800 my-1">
            
            <form method="POST" onsubmit="return confirm('Deseja limpar todos os atendimentos, filas e pontos do sistema?\n\nAs Mesas, Serviços e Usuários serão mantidos.\nTodos os usuários ativos nas mesas serão deslogados.');">
                <input type="hidden" name="acao" value="reset_atendimentos">
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-orange-500 hover:bg-orange-600 text-white py-2 px-3 rounded shadow-md transition-colors font-bold text-xs uppercase tracking-wider">
                    Limpar Atendimentos
                </button>
            </form>

            <form method="POST" onsubmit="return confirm('ATENÇÃO EXTREMA:\nVocê tem certeza ABSOLUTA que deseja apagar TODOS os dados do sistema, incluindo Mesas e Serviços? \n\nApenas os usuários serão mantidos. Esta ação NÃO pode ser desfeita!');">
                <input type="hidden" name="acao" value="reset_total">
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-red-900 hover:bg-red-950 border border-red-700 text-red-200 py-2 px-3 rounded shadow-md transition-colors font-bold text-xs uppercase tracking-wider" title="Apagar todo o banco de dados">
                    Hard Reset Total
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-gray-50 relative print:bg-white print:overflow-visible print:w-full">
        
        <div class="hidden print:block text-center mb-8 border-b-2 border-gray-800 pb-4">
            <img src="../imagens/brasao.png" alt="Logo" class="h-20 mx-auto mb-2 filter grayscale">
            <h1 class="text-2xl font-bold uppercase">Relatório Oficial de Sistema</h1>
            <p class="text-gray-600">Impresso em: <?= date('d/m/Y H:i') ?></p>
        </div>

        <header class="bg-white shadow-sm p-6 flex justify-between items-center z-10 sticky top-0 print:hidden">
            <h2 class="text-2xl font-bold text-gray-800 uppercase tracking-wide">
                <?php 
                    if($p === 'dashboard') echo 'Visão Geral do Sistema';
                    if($p === 'estatisticas') echo 'Estatísticas e Gráficos';
                    if($p === 'grupos') echo 'Gerenciamento de Serviços';
                    if($p === 'mesas') echo 'Gerenciamento de Mesas';
                    if($p === 'usuarios') echo 'Gerenciamento de Usuários';
                    if($p === 'ponto') echo 'Controle de Ponto Eletrônico';
                ?>
            </h2>
            <div class="text-sm font-semibold text-gray-500">
                Administrador logado: <span class="text-green-700"><?= $_SESSION['usuario_nome'] ?></span>
            </div>
        </header>

        <div class="p-8 print:p-0">
            
            <?php 
            if(isset($_GET['msg'])): 
                $msg_class = 'bg-green-100 text-green-800 border-l-4 border-green-500';
                $msg_text = 'Operação realizada com sucesso!';
                
                if($_GET['msg'] === 'excluido') { $msg_class = 'bg-red-100 text-red-800 border-l-4 border-red-500'; $msg_text = 'Registro excluído com sucesso!'; }
                if($_GET['msg'] === 'reset_total_sucesso') { $msg_class = 'bg-red-200 text-red-900 border-l-4 border-red-700 font-extrabold'; $msg_text = 'HARD RESET: Todos os dados operacionais e de estrutura foram apagados.'; }
                if($_GET['msg'] === 'reset_atend_sucesso') { $msg_class = 'bg-orange-100 text-orange-800 border-l-4 border-orange-500 font-extrabold'; $msg_text = 'ATENDIMENTOS ZERADOS! Filas, chamadas e ponto eletrônico foram limpos. Mesas e Serviços mantidos.'; }
                if($_GET['msg'] === 'mesa_liberada') { $msg_class = 'bg-blue-100 text-blue-800 border-l-4 border-blue-500 font-bold'; $msg_text = 'Mesa liberada! O ponto eletrônico do usuário foi encerrado.'; }
                if($_GET['msg'] === 'erro_auto_exclusao') { $msg_class = 'bg-red-100 text-red-800 border-l-4 border-red-500 font-bold'; $msg_text = 'Erro: Você não pode excluir a si mesmo enquanto estiver logado no sistema!'; }
            ?>
                <div class="mb-6 p-4 rounded-lg <?= $msg_class ?> shadow-sm print:hidden"><?= $msg_text ?></div>
            <?php endif; ?>

            <?php if ($p === 'dashboard'): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-green-500">
                        <h3 class="text-gray-500 font-bold uppercase text-xs tracking-wider mb-2">Senhas Chamadas Hoje</h3>
                        <p class="text-4xl font-extrabold text-gray-800"><?= $total_chamadas_hoje ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-blue-500">
                        <h3 class="text-gray-500 font-bold uppercase text-xs tracking-wider mb-2">Serviços Cadastrados</h3>
                        <p class="text-4xl font-extrabold text-gray-800"><?= $total_grupos ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-md border-t-4 border-yellow-500">
                        <h3 class="text-gray-500 font-bold uppercase text-xs tracking-wider mb-2">Mesas Cadastradas</h3>
                        <p class="text-4xl font-extrabold text-gray-800"><?= $total_mesas ?></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                        <div class="bg-gray-50 p-4 border-b border-gray-200">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">🖥️ Mesas em Operação Agora</h2>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            <?php foreach($mesas_ativas as $ma): ?>
                                <li class="p-4 flex flex-col items-start hover:bg-gray-50 transition-colors gap-3">
                                    <div class="flex items-center flex-wrap gap-2 w-full justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 text-lg border-r border-gray-300 pr-3"><?= $ma['mesa'] ?></span>
                                            <span class="font-semibold text-blue-700"><?= $ma['usuario'] ?></span>
                                        </div>
                                        <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full border border-green-200 uppercase tracking-wider">
                                            <?= $ma['grupo'] ?>
                                        </span>
                                    </div>
                                    <a href="?p=dashboard&liberar_mesa=<?= $ma['id'] ?>" onclick="return confirm('Deslogar este usuário e liberar a mesa?');" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 px-3 py-1 rounded-lg text-xs font-bold transition-colors w-full text-center">
                                        Deslogar / Liberar
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <?php if(empty($mesas_ativas)) echo "<li class='p-8 text-center text-gray-400 italic'>Nenhum usuário logado nas mesas.</li>"; ?>
                        </ul>
                    </div>

                    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                        <div class="bg-gray-50 p-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">⏱️ Atendimentos Pendentes</h2>
                            <span class="bg-yellow-200 text-yellow-800 font-bold px-3 py-1 rounded-full text-sm"><?= count($fila_espera) ?></span>
                        </div>
                        <ul class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                            <?php foreach($fila_espera as $fe): 
                                $isAtrasado = $fe['minutos_espera'] >= 10;
                                $bgClass = $isAtrasado ? 'bg-red-50' : '';
                                $textClass = $isAtrasado ? 'text-red-700' : 'text-gray-800';
                                $timeBadge = $isAtrasado ? 'bg-red-600 text-white animate-pulse' : 'bg-gray-200 text-gray-600';
                            ?>
                                <li class="p-4 flex items-center justify-between transition-colors <?= $bgClass ?>">
                                    <div>
                                        <span class="font-bold uppercase block <?= $textClass ?>"><?= $fe['nome_pessoa'] ?></span>
                                        <span class="text-xs text-gray-500 font-semibold"><?= $fe['grupo'] ?> (chegou às <?= $fe['hora'] ?>)</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="px-3 py-1 rounded font-bold text-sm shadow-sm <?= $timeBadge ?>">
                                            <?= $fe['minutos_espera'] ?> min
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            <?php if(empty($fila_espera)) echo "<li class='p-8 text-center text-gray-400 italic'>Ninguém aguardando no momento.</li>"; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($p === 'estatisticas'): ?>
                <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-100 print:hidden">
                    <h3 class="block text-gray-700 font-bold mb-4 text-sm uppercase tracking-wider border-b pb-2">🔍 Filtros do Gráfico</h3>
                    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                        <input type="hidden" name="p" value="estatisticas">
                        <div class="w-full md:w-1/4">
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Data Início</label>
                            <input type="date" name="data_inicio" value="<?= $filtro_data_inicio ?>" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div class="w-full md:w-1/4">
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Data Fim</label>
                            <input type="date" name="data_fim" value="<?= $filtro_data_fim ?>" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div class="w-full md:w-1/3">
                            <label class="block text-gray-700 font-semibold mb-1 text-sm">Atendente (Usuário)</label>
                            <select name="filtro_usuario" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 outline-none">
                                <option value="">-- Todos --</option>
                                <?php foreach($todos_usuarios_ativos as $tu): ?>
                                    <option value="<?= $tu['id'] ?>" <?= ($filtro_usuario == $tu['id']) ? 'selected' : '' ?>><?= $tu['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w-full md:w-auto flex gap-2">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-bold shadow-md transition-all">Filtrar</button>
                            <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-900 font-bold shadow-md transition-all flex items-center gap-2">🖨️ Imprimir</button>
                        </div>
                    </form>
                </div>

                <div class="mb-6 bg-green-800 text-white p-6 rounded-xl shadow text-center">
                    <p class="text-green-200 font-bold uppercase tracking-widest text-sm mb-1">Total de Atendimentos no Período</p>
                    <h2 class="text-5xl font-extrabold"><?= $total_periodo ?></h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
                        <h3 class="text-center font-bold text-gray-800 mb-4 uppercase">Atendimentos por Serviço</h3>
                        <div class="relative h-72 w-full">
                            <canvas id="chartGrupos"></canvas>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
                        <h3 class="text-center font-bold text-gray-800 mb-4 uppercase">Volume Diário</h3>
                        <div class="relative h-72 w-full">
                            <canvas id="chartDias"></canvas>
                        </div>
                    </div>
                </div>

                <script>
                    const ctxGrupos = document.getElementById('chartGrupos').getContext('2d');
                    new Chart(ctxGrupos, {
                        type: 'doughnut',
                        data: {
                            labels: <?= $labels_grupos ?>,
                            datasets: [{
                                data: <?= $data_grupos ?>,
                                backgroundColor: ['#16a34a', '#2563eb', '#eab308', '#dc2626', '#9333ea', '#0891b2'],
                                borderWidth: 1
                            }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    const ctxDias = document.getElementById('chartDias').getContext('2d');
                    new Chart(ctxDias, {
                        type: 'bar',
                        data: {
                            labels: <?= $labels_dias ?>,
                            datasets: [{
                                label: 'Qtd de Senhas Chamadas',
                                data: <?= $data_dias ?>,
                                backgroundColor: '#15803d',
                                borderRadius: 4
                            }]
                        },
                        options: { 
                            responsive: true, 
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if ($p === 'grupos'): ?>
                 <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-100">
                    <form method="POST" action="?p=grupos" class="flex flex-col md:flex-row gap-4 items-end">
                        <input type="hidden" name="id_grupo" value="<?= $edit_grupo['id'] ?? '' ?>">
                        <div class="flex-1 w-full">
                            <label class="block text-gray-700 font-bold mb-2 text-sm uppercase tracking-wider">
                                <?= $edit_grupo ? '✏️ Editando Serviço' : '➕ Criar Novo Serviço' ?>
                            </label>
                            <input type="text" name="nome_grupo" value="<?= $edit_grupo['nome'] ?? '' ?>" required placeholder="Ex: Triagem, Cadastro..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-50">
                        </div>
                        <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 font-bold shadow-md transition-all uppercase"><?= $edit_grupo ? 'Atualizar' : 'Salvar' ?></button>
                    </form>
                </div>
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-green-50 text-green-900 uppercase text-sm tracking-wider">
                                <th class="p-4 border-b font-bold w-20">ID</th><th class="p-4 border-b font-bold">Serviço</th><th class="p-4 border-b font-bold w-48 text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($grupos as $g): ?>
                            <tr class="hover:bg-gray-50"><td class="p-4 text-gray-500 font-mono">#<?= $g['id'] ?></td><td class="p-4 font-bold text-gray-800"><?= $g['nome'] ?></td>
                            <td class="p-4 flex justify-center gap-2"><a href="?p=grupos&edit_grupo=<?= $g['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded font-bold shadow-sm">Editar</a><a href="?p=grupos&delete_grupo=<?= $g['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded font-bold shadow-sm">Excluir</a></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($p === 'mesas'): ?>
                 <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-100">
                    <form method="POST" action="?p=mesas" class="flex flex-col md:flex-row gap-4 items-end">
                        <input type="hidden" name="id_mesa" value="<?= $edit_mesa['id'] ?? '' ?>">
                        <div class="flex-1 w-full">
                            <label class="block text-gray-700 font-bold mb-2 text-sm uppercase tracking-wider">
                                <?= $edit_mesa ? '✏️ Editando Mesa' : '➕ Criar Nova Mesa' ?>
                            </label>
                            <input type="text" name="nome_mesa" value="<?= $edit_mesa['nome'] ?? '' ?>" required placeholder="Ex: Mesa 01, Guichê A..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 bg-gray-50">
                        </div>
                        <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 font-bold shadow-md transition-all uppercase"><?= $edit_mesa ? 'Atualizar' : 'Salvar' ?></button>
                    </form>
                </div>
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                    <table class="w-full text-left border-collapse">
                        <thead><tr class="bg-green-50 text-green-900 uppercase text-sm"><th class="p-4 border-b font-bold w-20">ID</th><th class="p-4 border-b font-bold">Mesa</th><th class="p-4 border-b font-bold w-48 text-center">Ações</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($mesas as $m): ?>
                            <tr class="hover:bg-gray-50"><td class="p-4 text-gray-500 font-mono">#<?= $m['id'] ?></td><td class="p-4 font-bold text-gray-800"><?= $m['nome'] ?></td>
                            <td class="p-4 flex justify-center gap-2"><a href="?p=mesas&edit_mesa=<?= $m['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded font-bold shadow-sm">Editar</a><a href="?p=mesas&delete_mesa=<?= $m['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded font-bold shadow-sm">Excluir</a></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($p === 'usuarios'): ?>
                 <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-100">
                    <form method="POST" action="?p=usuarios" class="flex flex-col gap-4">
                        <input type="hidden" name="id_usuario" value="<?= $edit_usuario['id'] ?? '' ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block font-semibold mb-1">Nome</label><input type="text" name="nome_usuario" value="<?= $edit_usuario['nome'] ?? '' ?>" required class="w-full px-4 py-2 border rounded focus:ring-2 bg-gray-50"></div>
                            <div><label class="block font-semibold mb-1">E-mail</label><input type="email" name="email_usuario" value="<?= $edit_usuario['email'] ?? '' ?>" required class="w-full px-4 py-2 border rounded focus:ring-2 bg-gray-50"></div>
                            <div><label class="block font-semibold mb-1">Senha</label><input type="password" name="senha_usuario" <?= $edit_usuario ? '' : 'required' ?> class="w-full px-4 py-2 border rounded focus:ring-2 bg-gray-50"></div>
                            <div class="flex items-center mt-6"><label class="flex items-center cursor-pointer"><input type="checkbox" name="is_admin" class="h-5 w-5 text-green-600 rounded" <?= ($edit_usuario['is_admin'] ?? false) ? 'checked' : '' ?>><span class="ml-2 font-bold">Admin</span></label></div>
                        </div>
                        <button type="submit" class="mt-4 w-48 bg-green-600 text-white py-3 rounded hover:bg-green-700 font-bold uppercase shadow-md"><?= $edit_usuario ? 'Atualizar' : 'Salvar' ?></button>
                    </form>
                </div>
                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                    <table class="w-full text-left border-collapse">
                        <thead><tr class="bg-green-50 text-green-900 uppercase text-sm"><th class="p-4 border-b font-bold w-16">ID</th><th class="p-4 border-b font-bold">Nome</th><th class="p-4 border-b font-bold text-center">Nível</th><th class="p-4 border-b font-bold text-center">Ações</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($usuarios as $u): ?>
                            <tr class="hover:bg-gray-50"><td class="p-4 text-gray-500 font-mono">#<?= $u['id'] ?></td><td class="p-4 font-bold text-gray-800"><?= $u['nome'] ?></td>
                            <td class="p-4 text-center"><?= $u['is_admin'] ? '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Admin</span>' : '<span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold uppercase">Atendente</span>' ?></td>
                            <td class="p-4 flex justify-center gap-2"><a href="?p=usuarios&edit_usuario=<?= $u['id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded font-bold shadow-sm">Editar</a><a href="?p=usuarios&delete_usuario=<?= $u['id'] ?>" class="bg-red-500 text-white px-3 py-1 rounded font-bold shadow-sm">Excluir</a></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($p === 'ponto'): ?>
                <div class="bg-white p-6 rounded-xl shadow-md mb-8 border border-gray-100 print:hidden">
                    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                        <input type="hidden" name="p" value="ponto">
                        <div class="w-full md:w-1/4"><label class="block font-semibold mb-1 text-sm">Data Início</label><input type="date" name="data_inicio" value="<?= $filtro_data_inicio ?>" class="w-full px-4 py-2 border rounded outline-none focus:ring-2 focus:ring-green-500"></div>
                        <div class="w-full md:w-1/4"><label class="block font-semibold mb-1 text-sm">Data Fim</label><input type="date" name="data_fim" value="<?= $filtro_data_fim ?>" class="w-full px-4 py-2 border rounded outline-none focus:ring-2 focus:ring-green-500"></div>
                        <div class="w-full md:w-1/3">
                            <label class="block font-semibold mb-1 text-sm">Usuário</label>
                            <select name="filtro_usuario" class="w-full px-4 py-2 border rounded outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos</option>
                                <?php foreach($todos_usuarios_ativos as $tu): ?>
                                    <option value="<?= $tu['id'] ?>" <?= ($filtro_usuario == $tu['id']) ? 'selected' : '' ?>><?= $tu['nome'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2"><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 font-bold shadow-md transition-all">Filtrar</button>
                        <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-900 font-bold shadow-md flex items-center gap-2">🖨️ Imprimir</button></div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 print:shadow-none print:border-none">
                    <table class="w-full text-left border-collapse print:text-sm">
                        <thead>
                            <tr class="bg-green-50 text-green-900 uppercase text-xs tracking-wider print:bg-gray-100 print:text-black">
                                <th class="p-4 border-b font-bold">Data</th><th class="p-4 border-b font-bold">Usuário</th>
                                <th class="p-4 border-b font-bold text-center">Entrada</th><th class="p-4 border-b font-bold text-center">Saída</th>
                                <th class="p-4 border-b font-bold text-center">Total Trab.</th><th class="p-4 border-b font-bold text-center text-red-700">Faltando</th><th class="p-4 border-b font-bold text-center text-green-700">Extra</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 print:divide-gray-300">
                            <?php 
                            $meta = 8.5 * 3600; 
                            foreach($relatorio_ponto as $rp): 
                                $segundos = $rp['total_segundos']; $extra = 0; $falta = 0;
                                if ($segundos > $meta) $extra = $segundos - $meta;
                                elseif ($segundos < $meta) $falta = $meta - $segundos;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 text-gray-800 font-semibold"><?= date('d/m/Y', strtotime($rp['data_ponto'])) ?></td>
                                <td class="p-4 font-bold text-gray-800 uppercase"><?= $rp['nome'] ?></td>
                                <td class="p-4 text-center font-mono"><?= date('H:i', strtotime($rp['primeira_entrada'])) ?></td>
                                <td class="p-4 text-center font-mono"><?= $rp['ultima_saida'] ? date('H:i', strtotime($rp['ultima_saida'])) : 'Em Aberto' ?></td>
                                <td class="p-4 text-center font-mono font-bold bg-blue-50/30"><?= formatarSegundos($segundos) ?></td>
                                <td class="p-4 text-center font-mono font-bold text-red-600"><?= $falta > 0 ? formatarSegundos($falta) : '--:--' ?></td>
                                <td class="p-4 text-center font-mono font-bold text-green-600"><?= $extra > 0 ? formatarSegundos($extra) : '--:--' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </main>
</body>
</html>