# 📢 Sistema de Chamadas e Gestão de Filas

Um sistema completo, moderno e responsivo para gerenciamento de atendimento presencial, painel de senhas (TV) e controle de produtividade de equipe. 

Desenvolvido para ser leve, rápido e de fácil implementação em ambientes corporativos, clínicas ou repartições públicas.


## 📄 Licença
Este projeto está sob a licença MIT. Fique à vontade para usar, modificar e distribuir mas mantenha a autoria a MARIO HENRIQUE INACIO DE PAULA, @mariomorrinhos, telefone (64)992238703. Fique à vontade para entrar em contato pelo whatsapp para ajuda (quanto a instalação) ou ainda para sugerir ou contribuir melhorias.

---

## ✨ Principais Funcionalidades

### 📺 Painel Público (TV / Recepção)
* **Chamadas em Tempo Real:** Atualização automática sem necessidade de recarregar a página (via AJAX/Fetch API).
* **Alerta Sonoro:** Emissão de aviso sonoro padrão sempre que uma nova senha é chamada.
* **Histórico Visível:** Exibição das últimas senhas chamadas para evitar que o cidadão perca a vez.
* **Auto-Limpeza:** O sistema zera o painel público automaticamente na virada do dia.

### 🧑‍💻 Painel do Atendente (Guichês)
* **Múltiplos Serviços:** O atendente pode escolher qual serviço (triagem, cadastro, etc.) vai atender no momento.
* **Controle Total:** Botões para cadastrar, chamar o próximo, rechamar (repetir alerta na TV) ou chamar pessoas fora da ordem (prioridades).
* **Prevenção de Erros:** Identidade visual clara indicando a mesa atual e o serviço selecionado.

### 🛡️ Dashboard Administrativo
* **Estatísticas Avançadas:** Gráficos interativos (Chart.js) mostrando volume diário e atendimentos por serviço.
* **Controle de Ponto Eletrônico:** O sistema contabiliza automaticamente as horas trabalhadas dos atendentes com base nos logins e logouts.
* **Gestão em Tempo Real:** Visão de quais mesas estão operando no momento e lista de espera de cidadãos.
* **Impressão Limpa:** Relatórios otimizados para impressão via navegador (sem URLs ou cabeçalhos indesejados).
* **Hard Reset:** Botões de emergência para zerar a fila diária ou resetar todo o banco de dados para um novo ciclo.

---

## 🛠️ Tecnologias Utilizadas

* **Backend:** PHP 8+
* **Banco de Dados:** MySQL / MariaDB
* **Frontend:** HTML5, JavaScript Vanilla
* **Estilização:** Tailwind CSS (via CDN)
* **Gráficos:** Chart.js

---

## 🚀 Como Instalar e Rodar o Projeto

1. **Clone este repositório** na pasta pública do seu servidor local (ex: `htdocs` no XAMPP ou `www` no WAMP):
   ```bash
   git clone [https://github.com/mariomorrinhos/Sistema_de_Chamadas.git](https://github.com/mariomorrinhos/Sistema_de_Chamadas.git)

   Crie o Banco de Dados:

2. **edite o arquivo arquivo.sql** e coloque o nome do seu banco de dados onde tiver 'NOME_DO_BANCO_DE_DADOS' nas linhas 1 e 2.

3. **Copie todo o código já editado de arquivo.sql**.

4. **Abra o seu phpMyAdmin**.

Importe o arquivo arquivo.sql que está na raiz do projeto. Ele criará o banco conforme você descreveu (o nome do banco de dados), todas as tabelas e o usuário administrador padrão.

**Configure a Conexão**:

Acesse a pasta config/ e abra o arquivo **conexao.php**.

Verifique se os dados de $user e $pass correspondem às credenciais do seu servidor MySQL local (no XAMPP, geralmente o usuário é root e a senha é em branco '') ou coloque o nome do seu banco de dados, o nome do usuário do banco de dados e a senha.

Acesse o Sistema:

Abra o navegador e acesse: http://localhost/Sistema_de_Chamadas/

Login Padrão de Administrador:

**E-mail: admin@admin.com**

**Senha: 12345**


## 📄 Licença
Este projeto está sob a licença MIT. Fique à vontade para usar, modificar e distribuir mas mantenha a autoria a MARIO HENRIQUE INACIO DE PAULA, @mariomorrinhos, telefone (64)992238703. Fique à vontade para entrar em contato pelo whatsapp para ajuda (quanto a instalação) ou ainda para sugerir ou contribuir melhorias.
