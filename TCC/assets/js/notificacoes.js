async function verificarNotificacoes() {
    try {
        const resposta = await fetch(
            '../../adm/Controllers/notificacaoController.php?acao=contar',
            {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );

        const dados = await resposta.json();

        if (!resposta.ok || !dados.ok) {
            return;
        }

        atualizarBadgeNotificacoes(dados.nao_lidas);

    } catch (erro) {
        console.error('Erro ao verificar notificações:', erro);
    }
}


function atualizarBadgeNotificacoes(quantidade) {

    const badges = document.querySelectorAll('.badge-notificacao');

    badges.forEach(badge => {

        if (quantidade > 0) {
            badge.classList.add('visivel');
        } else {
            badge.classList.remove('visivel');
        }

    });
}


// Disponibiliza a função para outros scripts da página
window.atualizarBadgeNotificacoes = atualizarBadgeNotificacoes;


// Verifica quando a página carregar
document.addEventListener('DOMContentLoaded', function () {
    verificarNotificacoes();
});