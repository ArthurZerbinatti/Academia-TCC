(function () {
    const CONTROLLER = "../../adm/Controllers/concluirTreinoController.php";

    // ------------------------------------------------------------
    // Concluir / desfazer o treino de hoje
    // ------------------------------------------------------------
    const areaHoje = document.getElementById("tr-hoje");

    if (areaHoje) {
        areaHoje.querySelectorAll("[data-acao]").forEach(function (botao) {
            botao.addEventListener("click", async function () {
                const acao = botao.dataset.acao;

                if (acao === "desfazer" && !confirm("Desmarcar o treino de hoje como concluído?")) {
                    return;
                }

                botao.disabled = true;

                try {
                    const dados = new FormData();
                    dados.append("acao", acao);
                    dados.append("id_ficha", areaHoje.dataset.ficha);
                    dados.append("csrf_token", areaHoje.dataset.csrf);

                    const resposta = await fetch(CONTROLLER, {
                        method: "POST",
                        body: dados,
                        credentials: "same-origin"
                    });

                    const json = await resposta.json();

                    if (!resposta.ok || !json.ok) {
                        throw new Error(json.mensagem || "Não foi possível salvar.");
                    }

                    // Recarrega para atualizar o card de hoje, a semana e a barra de progresso
                    window.location.reload();
                } catch (erro) {
                    alert(erro.message || "Erro de conexão. Tente novamente.");
                    botao.disabled = false;
                }
            });
        });
    }

    // ------------------------------------------------------------
    // Filtros das fichas (Todos / Superiores / Inferiores)
    // ------------------------------------------------------------
    const botoesFiltro = document.querySelectorAll(".tr-filtro");

    botoesFiltro.forEach(function (botao) {
        botao.addEventListener("click", function () {
            botoesFiltro.forEach(function (b) { b.classList.remove("ativo"); });
            botao.classList.add("ativo");

            const alvo = botao.dataset.grupo;

            document.querySelectorAll(".tr-dia").forEach(function (card) {
                const grupos = (card.dataset.grupo || "").split(" ");
                card.hidden = !(alvo === "todos" || grupos.includes(alvo));
            });

            // Esconde a ficha inteira se nenhum dia dela combina com o filtro
            document.querySelectorAll(".tr-ficha").forEach(function (ficha) {
                const visiveis = ficha.querySelectorAll(".tr-dia:not([hidden])").length;
                ficha.hidden = alvo !== "todos" && visiveis === 0;
            });
        });
    });
})();
