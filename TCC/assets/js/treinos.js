const cards = document.querySelectorAll('.treino-card');
const filtros = document.querySelectorAll('.filtro');

cards.forEach((card) => {
    card.addEventListener('click', () => {
        const exercicios = card.querySelector('.exercicios');
        exercicios.classList.toggle('oculto');
    });
});

filtros.forEach((filtro) => {
    filtro.addEventListener('click', () => {
        filtros.forEach((f) => f.classList.remove('ativo'));
        filtro.classList.add('ativo');

        // Aqui entra a lógica de mostrar/esconder os cards
        // de acordo com o grupo muscular (data-grupo),
        // que depende de como o PHP for entregar essa informação.
    });
});