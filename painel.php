<?php
// painel.php
require 'config/conexao.php';
checkLogin();

// CORREÇÃO: Removemos a exceção do admin. Absolutamente TODOS precisam
// escolher uma mesa e um serviço antes de acessar esta tela.
if (!isset($_SESSION['mesa_id']) || !isset($_SESSION['grupo_id'])) {
    header("Location: seleciona_mesa.php");
    exit;
}

// Trava de segurança (Logout Forçado pelo Admin)
$stmtCheckMesa = $pdo->prepare("SELECT id FROM mesas WHERE id = ? AND id_usuario_atual = ?");
$stmtCheckMesa->execute([$_SESSION['mesa_id'], $_SESSION['usuario_id']]);
if (!$stmtCheckMesa->fetch()) {
    header("Location: logout.php");
    exit;
}

// Trocar de grupo temporariamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'trocar_grupo') {
    $novo_grupo_id = $_POST['novo_grupo_id'];
    $_SESSION['grupo_id'] = $novo_grupo_id;
    $pdo->prepare("UPDATE mesas SET id_grupo_atual = ? WHERE id = ?")->execute([$novo_grupo_id, $_SESSION['mesa_id']]);
    $msg_topo = "Serviço alterado com sucesso! Você agora está atendendo esta fila.";
}

$grupo_id_sessao = $_SESSION['grupo_id'];
$todos_grupos = $pdo->query("SELECT * FROM grupos ORDER BY nome")->fetchAll();

// Ações Principais do Painel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // 1. Cadastrar
    if ($acao === 'cadastrar' && !empty($_POST['nome_cliente']) && !empty($_POST['id_grupo_destino'])) {
        $stmt = $pdo->prepare("INSERT INTO fila (id_grupo, nome_pessoa) VALUES (?, ?)");
        $stmt->execute([$_POST['id_grupo_destino'], $_POST['nome_cliente']]);
        
        $nome_grupo_destino = '';
        foreach($todos_grupos as $g) {
            if($g['id'] == $_POST['id_grupo_destino']) $nome_grupo_destino = $g['nome'];
        }
        $msg = "Cidadão encaminhado para a fila de: " . $nome_grupo_destino;
    } 
    // 2. Chamar o Próximo da Fila
    elseif ($acao === 'chamar_proximo') {
        $stmt = $pdo->prepare("SELECT * FROM fila WHERE status = 'aguardando' AND id_grupo = ? ORDER BY data_criacao ASC, id ASC LIMIT 1");
        $stmt->execute([$grupo_id_sessao]);
        $proximo = $stmt->fetch();
        
        if ($proximo) {
            $pdo->prepare("UPDATE fila SET status = 'chamado' WHERE id = ?")->execute([$proximo['id']]);
            $pdo->prepare("INSERT INTO chamadas (id_fila, id_usuario, id_mesa) VALUES (?, ?, ?)")
                ->execute([$proximo['id'], $_SESSION['usuario_id'], $_SESSION['mesa_id']]);
            
            $_SESSION['ultima_chamada_id'] = $pdo->lastInsertId();
            $msg = "Chamada realizada com sucesso!";
        } else {
            $erro = "A fila para o seu serviço atual está vazia.";
        }
    }
    // 3. Repetir Última Chamada do Botão Central
    elseif ($acao === 'chamar_novamente') {
        if (isset($_SESSION['ultima_chamada_id'])) {
            $stmt = $pdo->prepare("SELECT id_fila, id_usuario, id_mesa FROM chamadas WHERE id = ?");
            $stmt->execute([$_SESSION['ultima_chamada_id']]);
            $last = $stmt->fetch();
            
            if($last){
                $pdo->prepare("INSERT INTO chamadas (id_fila, id_usuario, id_mesa) VALUES (?, ?, ?)")
                    ->execute([$last['id_fila'], $last['id_usuario'], $last['id_mesa']]);
                $_SESSION['ultima_chamada_id'] = $pdo->lastInsertId();
                $msg = "Chamada repetida no painel!";
            }
        } else {
            $erro = "Nenhuma chamada anterior na sua sessão.";
        }
    }
    // 4. Chamar Especifico (Pular a Fila)
    elseif ($acao === 'chamar_especifico' && !empty($_POST['id_fila'])) {
        $id_fila = $_POST['id_fila'];
        
        $stmtCheck = $pdo->prepare("SELECT id FROM fila WHERE id = ? AND status = 'aguardando'");
        $stmtCheck->execute([$id_fila]);
        
        if ($stmtCheck->fetch()) {
            $pdo->prepare("UPDATE fila SET status = 'chamado' WHERE id = ?")->execute([$id_fila]);
            $pdo->prepare("INSERT INTO chamadas (id_fila, id_usuario, id_mesa) VALUES (?, ?, ?)")
                ->execute([$id_fila, $_SESSION['usuario_id'], $_SESSION['mesa_id']]);
            
            $_SESSION['ultima_chamada_id'] = $pdo->lastInsertId();
            $msg = "Cidadão chamado fora de ordem com sucesso!";
        } else {
            $erro = "Esta pessoa já foi chamada por outro guichê.";
        }
    }
    // 5. Rechamar pelo Histórico
    elseif ($acao === 'rechamar_historico' && !empty($_POST['id_fila'])) {
        $id_fila = $_POST['id_fila'];
        $pdo->prepare("INSERT INTO chamadas (id_fila, id_usuario, id_mesa) VALUES (?, ?, ?)")
            ->execute([$id_fila, $_SESSION['usuario_id'], $_SESSION['mesa_id']]);
        
        $_SESSION['ultima_chamada_id'] = $pdo->lastInsertId();
        $msg = "Pessoa chamada novamente no painel público!";
    }
    // 6. Limpar Painel Público (Apenas Admin)
    elseif ($acao === 'limpar_painel_publico') {
        if ($_SESSION['is_admin']) {
            $pdo->exec("UPDATE chamadas SET visivel = FALSE");
            $msg_topo = "A tela do Painel Público (TV) foi limpa com sucesso!";
        } else {
            $erro = "Ação não permitida.";
        }
    }
}

$mesa_atual = $pdo->query("SELECT nome FROM mesas WHERE id = " . $_SESSION['mesa_id'])->fetchColumn();
$grupo_atual = $pdo->query("SELECT nome FROM grupos WHERE id = " . $grupo_id_sessao)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Atendimento</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen overflow-x-hidden flex flex-col">
    <nav class="bg-green-800 text-white p-4 shadow-md flex justify-between items-center shrink-0">
        <div class="flex items-center gap-4">
            <img src="imagens/brasao.png" alt="Brasão" class="h-10">
            <h1 class="text-xl font-bold tracking-wider uppercase hidden md:block">Sistema de Chamadas</h1>
        </div>
        <div>
            <span class="mr-4 font-semibold text-green-100">Olá, <?= $_SESSION['usuario_nome'] ?></span>
            <?php if($_SESSION['is_admin']): ?>
                <a href="admin/index.php" class="bg-yellow-500 text-gray-900 font-bold px-4 py-2 rounded shadow hover:bg-yellow-400 transition-colors mr-2">Painel Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="bg-red-600 font-bold px-4 py-2 rounded shadow hover:bg-red-700 transition-colors">Encerrar Sessão</a>
        </div>
    </nav>

    <div class="bg-white mx-6 mt-6 p-6 rounded-xl shadow-lg border-l-8 border-green-600 flex flex-col md:flex-row justify-between items-center gap-6">
        <div>
            <p class="text-gray-500 font-extrabold uppercase tracking-widest text-sm mb-1">Você está chamando senhas para o serviço:</p>
            <h2 class="text-4xl md:text-5xl font-extrabold text-green-800 uppercase drop-shadow-sm"><?= $grupo_atual ?></h2>
        </div>
        
        <div class="flex flex-col md:flex-row items-center gap-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div class="text-center md:text-right border-b md:border-b-0 md:border-r border-gray-300 pb-4 md:pb-0 md:pr-6">
                <p class="text-gray-500 font-bold uppercase tracking-widest text-xs mb-1">Seu Local / Balcão</p>
                <h3 class="text-2xl font-bold text-gray-800 uppercase"><?= $mesa_atual ?></h3>
            </div>
            
            <form method="POST" class="flex flex-col w-full md:w-auto">
                <input type="hidden" name="acao" value="trocar_grupo">
                <label class="text-xs font-bold text-gray-500 uppercase mb-2 text-center md:text-left">Trocar de Serviço Temporariamente</label>
                <select name="novo_grupo_id" class="bg-white border-2 border-green-600 text-green-900 text-sm font-bold rounded-lg focus:ring-green-500 focus:border-green-500 block w-full p-2.5 shadow-sm cursor-pointer hover:bg-green-50 transition-colors" onchange="this.form.submit()">
                    <?php foreach($todos_grupos as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= ($g['id'] == $grupo_id_sessao) ? 'selected' : '' ?>>
                            <?= $g['nome'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>
    
    <?php if(isset($msg_topo)) echo "<div class='mx-6 mt-4 p-3 bg-green-100 border border-green-400 text-green-800 rounded font-bold text-center shadow-sm'>$msg_topo</div>"; ?>

    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 items-start">
        
        <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-green-600">
            <h2 class="text-lg font-bold mb-4 text-gray-800">Cadastrar e Encaminhar Cidadão</h2>
            <?php if(isset($msg)) echo "<p class='text-green-700 bg-green-100 border border-green-300 p-2 rounded mb-4 shadow-sm font-semibold'>$msg</p>"; ?>
            <?php if(isset($erro)) echo "<p class='text-red-700 bg-red-100 border border-red-300 p-2 rounded mb-4 shadow-sm font-semibold'>$erro</p>"; ?>
            
            <form method="POST" class="flex flex-col gap-4">
                <input type="hidden" name="acao" value="cadastrar">
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1">Nome do Cidadão</label>
                    <input type="text" name="nome_cliente" placeholder="Nome Completo" required class="w-full border border-gray-300 p-3 rounded outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1">Encaminhar para o Serviço:</label>
                    <select name="id_grupo_destino" required class="w-full border border-gray-300 p-3 rounded outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                        <option value="">-- Selecione o Serviço --</option>
                        <?php foreach($todos_grupos as $g): ?>
                            <option value="<?= $g['id'] ?>" <?= ($g['id'] == $grupo_id_sessao) ? 'selected' : '' ?>>
                                <?= $g['nome'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="bg-green-600 text-white py-3 mt-2 rounded font-bold hover:bg-green-700 transition-colors shadow uppercase tracking-wide">Adicionar à Fila</button>
            </form>
            
            <hr class="my-6">
            
            <h2 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Ações do Painel</h2>
            <form method="POST" class="flex flex-col gap-3 mb-3">
                <input type="hidden" name="acao" value="chamar_proximo">
                <button type="submit" class="bg-green-700 text-white py-6 rounded-lg text-2xl font-extrabold hover:bg-green-800 shadow-md transition-all uppercase tracking-widest">Chamar Próximo</button>
            </form>
            <form method="POST" class="flex flex-col gap-3">
                <input type="hidden" name="acao" value="chamar_novamente">
                <button type="submit" class="bg-yellow-400 text-gray-900 py-3 rounded font-bold hover:bg-yellow-500 shadow transition-colors uppercase text-sm">Repetir Última Chamada</button>
            </form>

            <?php if($_SESSION['is_admin']): ?>
                <hr class="my-6 border-red-200">
                <h2 class="text-lg font-bold mb-4 text-red-800 border-b border-red-200 pb-2 flex items-center gap-2">
                    🛡️ Ações de Administrador
                </h2>
                <form method="POST" class="flex flex-col gap-3" onsubmit="return confirm('Deseja realmente limpar a tela da TV da recepção? O histórico interno de relatórios será mantido, mas o público verá o painel vazio.');">
                    <input type="hidden" name="acao" value="limpar_painel_publico">
                    <button type="submit" class="bg-gray-800 text-white py-3 rounded font-bold hover:bg-gray-900 shadow transition-colors uppercase text-sm flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Limpar Painel Público (TV)
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-gray-600">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h2 class="text-lg font-bold text-gray-800">Aguardando Atendimento</h2>
                <span id="contador_fila" class="bg-gray-200 text-gray-800 font-bold px-3 py-1 rounded-full text-sm">...</span>
            </div>
            
            <ul id="lista_fila" class="h-[55vh] overflow-y-auto pr-2 flex flex-col gap-2">
            </ul>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg border-t-4 border-yellow-500">
            <div class="flex items-center gap-2 mb-4 border-b pb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-lg font-bold text-gray-800">Histórico Deste Serviço</h2>
            </div>
            
            <ul id="lista_historico" class="h-[55vh] overflow-y-auto pr-2 flex flex-col gap-3">
            </ul>
        </div>
    </div>

    <script>
        async function atualizarDadosPainel() {
            try {
                const response = await fetch('api_painel.php');
                const data = await response.json();

                if (data.force_logout) {
                    window.location.href = 'logout.php';
                    return;
                }

                if (data.erro) return;

                const contadorFila = document.getElementById('contador_fila');
                const listaFila = document.getElementById('lista_fila');
                
                contadorFila.innerText = data.fila.length + " pessoa(s)";
                listaFila.innerHTML = '';
                
                if (data.fila.length > 0) {
                    data.fila.forEach(f => {
                        listaFila.innerHTML += `
                            <li class="p-3 bg-gray-50 border border-gray-200 rounded flex justify-between items-center shadow-sm border-l-4 border-l-green-500 hover:bg-green-100 transition-colors">
                                <div>
                                    <span class="font-bold text-gray-800 uppercase block">${f.nome_pessoa}</span>
                                    <span class="text-gray-500 text-xs bg-gray-200 px-2 py-0.5 rounded-md font-mono">${f.hora}</span>
                                </div>
                                <form method="POST" onsubmit="return confirm('ATENÇÃO: Deseja chamar o(a) ${f.nome_pessoa} FORA DA ORDEM original agora?');" class="m-0">
                                    <input type="hidden" name="acao" value="chamar_especifico">
                                    <input type="hidden" name="id_fila" value="${f.id}">
                                    <button type="submit" title="Chamar fora de ordem" class="bg-green-100 text-green-700 hover:bg-green-600 hover:text-white border border-green-300 px-3 py-1.5 rounded text-xs font-bold shadow-sm transition-all uppercase">Chamar</button>
                                </form>
                            </li>
                        `;
                    });
                } else {
                    listaFila.innerHTML = '<li class="text-gray-400 text-center py-8 italic mt-10">A fila para este serviço está vazia no momento.</li>';
                }

                const listaHistorico = document.getElementById('lista_historico');
                listaHistorico.innerHTML = '';
                
                if (data.historico.length > 0) {
                    data.historico.forEach(h => {
                        listaHistorico.innerHTML += `
                            <li class="p-3 border rounded shadow-sm hover:shadow-md transition-shadow bg-white">
                                <div class="flex justify-between items-start mb-1">
                                    <div>
                                        <span class="font-extrabold text-gray-800 uppercase text-sm block">${h.nome_pessoa}</span>
                                        <span class="text-xs text-gray-500 font-mono bg-gray-100 px-1 rounded">${h.hora}</span>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Repetir o chamado de ${h.nome_pessoa} no painel público?');" class="m-0">
                                        <input type="hidden" name="acao" value="rechamar_historico">
                                        <input type="hidden" name="id_fila" value="${h.id_fila}">
                                        <button type="submit" title="Chamar novamente no painel" class="bg-yellow-50 text-yellow-700 hover:bg-yellow-500 hover:text-white border border-yellow-300 px-2 py-1 rounded text-xs font-bold shadow-sm transition-all uppercase">
                                            Rechamar
                                        </button>
                                    </form>
                                </div>
                                <div class="text-xs font-bold text-green-800 bg-green-100 inline-block px-2 py-1 rounded mt-1">
                                    Mesa: ${h.mesa}
                                </div>
                            </li>
                        `;
                    });
                } else {
                    listaHistorico.innerHTML = '<li class="text-gray-400 text-center py-8 italic mt-10">Nenhuma chamada realizada.</li>';
                }

            } catch (error) {
                console.error("Erro ao buscar dados da API do painel:", error);
            }
        }

        atualizarDadosPainel();
        setInterval(atualizarDadosPainel, 2000);
    </script>
</body>
</html>