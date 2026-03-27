<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel de Chamadas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .piscar { animation: piscarAnim 1.5s infinite; }
        @keyframes piscarAnim { 
            0% { opacity: 1; color: #14532d; transform: scale(1); }
            50% { opacity: 0.9; color: #dc2626; transform: scale(1.02); text-shadow: 0px 0px 15px rgba(220, 38, 38, 0.4); }
            100% { opacity: 1; color: #14532d; transform: scale(1); } 
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.5); border-radius: 10px; }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 via-green-100 to-red-50 text-green-900 h-screen overflow-hidden flex flex-col relative font-sans">
    
    <img src="../imagens/brasao.png" alt="Logo Fundo" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 h-[80vh] opacity-5 pointer-events-none z-0">

    <div id="overlay-iniciar" class="fixed inset-0 bg-white/70 backdrop-blur-lg z-50 flex flex-col items-center justify-center text-green-900">
        <img src="../imagens/brasao.png" alt="Logo" class="h-40 mb-8 drop-shadow-xl">
        <h1 class="text-5xl font-extrabold mb-8 text-green-800 tracking-tight">Painel de Chamadas Público</h1>
        <button onclick="iniciarPainel()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-10 rounded-2xl text-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
            Iniciar Painel e Ativar Som
        </button>
        <p class="mt-6 text-green-700 font-medium">Clique para permitir as notificações sonoras de chamada.</p>
    </div>

    <audio id="somAlerta" src="alerta.mp3"></audio>

    <div class="flex-1 flex flex-col md:flex-row p-6 gap-6 z-10 relative">
        
        <div class="w-full md:w-2/3 flex flex-col items-center justify-center p-10 bg-white/40 backdrop-blur-xl border border-white/60 shadow-2xl rounded-3xl relative">
            <img src="../imagens/brasao.png" alt="Logo" class="absolute top-8 left-8 h-24 opacity-80 drop-shadow-md">
            
            <h2 class="text-3xl font-bold text-green-700 tracking-widest mb-6 uppercase opacity-80">Senha Chamada</h2>
            
            <div id="nome_destaque" class="text-7xl font-extrabold text-center uppercase mb-10 text-green-900 drop-shadow-sm transition-all duration-300">
                AGUARDANDO...
            </div>
            
            <div class="flex flex-wrap justify-center gap-6 text-center w-full max-w-4xl">
                <div class="flex-1 min-w-[250px] flex flex-col items-center bg-white/60 p-6 rounded-3xl border border-white/80 shadow-md">
                    <span class="text-green-800 font-bold text-xl uppercase tracking-widest mb-2 opacity-70">Serviço</span>
                    <span id="grupo_destaque" class="font-extrabold text-red-600 text-4xl leading-tight">--</span>
                </div>
                
                <div class="flex-1 min-w-[250px] flex flex-col items-center bg-white/60 p-6 rounded-3xl border border-white/80 shadow-md">
                    <span class="text-green-800 font-bold text-xl uppercase tracking-widest mb-2 opacity-70">Mesa/Balcão</span>
                    <span id="mesa_destaque" class="font-extrabold text-red-600 text-5xl leading-tight">--</span>
                </div>
            </div>
            
            <div class="mt-12 text-2xl font-medium text-green-800/70 bg-white/50 px-8 py-3 rounded-full border border-white/60 shadow-sm flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span id="hora_destaque" class="font-bold tracking-wider">--:--</span>
            </div>
        </div>

        <div class="w-full md:w-1/3 bg-white/40 backdrop-blur-xl border border-white/60 shadow-2xl rounded-3xl p-8 flex flex-col">
            <div class="flex items-center gap-3 mb-6 border-b border-green-200 pb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <h3 class="text-2xl font-bold text-green-800 uppercase tracking-wider">Últimas Chamadas</h3>
            </div>
            
            <ul id="lista_historico" class="flex flex-col gap-4 flex-1 overflow-y-auto pr-2">
                </ul>
        </div>
    </div>

    <script>
        let ultimoIdChamada = 0;
        const audio = document.getElementById('somAlerta');
        let intervaloAtualizacao;

        function iniciarPainel() {
            document.getElementById('overlay-iniciar').style.display = 'none';
            
            audio.volume = 0; 
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audio.volume = 1; 
            }).catch(e => console.log("Erro ao inicializar áudio:", e));

            atualizarPainel();
            intervaloAtualizacao = setInterval(atualizarPainel, 1500);
        }

        async function atualizarPainel() {
            try {
                const response = await fetch('api_chamadas.php');
                const data = await response.json();

                if (data.destaque) {
                    document.getElementById('nome_destaque').innerText = data.destaque.nome_pessoa;
                    document.getElementById('grupo_destaque').innerText = data.destaque.grupo; 
                    document.getElementById('mesa_destaque').innerText = data.destaque.mesa;
                    document.getElementById('hora_destaque').innerText = data.destaque.hora;

                    if (data.destaque.id > ultimoIdChamada) {
                        if (ultimoIdChamada !== 0) { 
                            audio.currentTime = 0;
                            audio.play().catch(e => console.log('Erro ao tocar áudio:', e));
                            
                            const nomeElement = document.getElementById('nome_destaque');
                            nomeElement.classList.add('piscar');
                            setTimeout(() => nomeElement.classList.remove('piscar'), 5000);
                        }
                        ultimoIdChamada = data.destaque.id;
                    }
                } else {
                    // Se a API não retornar destaque (ex: virou o dia), volta ao estado original
                    document.getElementById('nome_destaque').innerText = 'AGUARDANDO...';
                    document.getElementById('grupo_destaque').innerText = '--';
                    document.getElementById('mesa_destaque').innerText = '--';
                    document.getElementById('hora_destaque').innerText = '--:--';
                    ultimoIdChamada = 0; // Zera o contador para o novo dia
                }

                const lista = document.getElementById('lista_historico');
                lista.innerHTML = '';
                
                if (data.historico && data.historico.length > 0) {
                    data.historico.forEach(item => {
                        lista.innerHTML += `
                            <li class="bg-white/70 backdrop-blur-sm p-5 rounded-2xl shadow-sm border border-white/80 hover:shadow-md transition-shadow">
                                <div class="text-xl font-extrabold uppercase text-green-900">${item.nome_pessoa}</div>
                                <div class="text-sm font-bold text-red-600 uppercase tracking-wider mt-1 mb-3">${item.grupo}</div>
                                <div class="flex justify-between items-center mt-2 pt-3 border-t border-green-100">
                                    <span class="bg-red-50 text-red-800 text-sm font-bold px-3 py-1 rounded-lg border border-red-200 shadow-sm">${item.mesa}</span>
                                    <span class="text-sm font-semibold text-green-700/70">${item.hora}</span>
                                </div>
                            </li>
                        `;
                    });
                } else if (!data.destaque) {
                    lista.innerHTML = '<li class="text-center text-green-800/60 font-medium py-8 italic">Nenhuma chamada hoje.</li>';
                }
            } catch (error) {
                console.error("Erro ao buscar chamadas", error);
            }
        }
    </script>
</body>
</html>