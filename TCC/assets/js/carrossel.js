const imagens = [
    "../assets/imagens/area-ginastica.png",
    "../assets/imagens/area-musculacao.png",
    "../assets/imagens/area-vestiario.png"
];

const imagem = document.querySelector("#imagem");
const anterior = document.querySelector(".anterior");
const proximo = document.querySelector(".proximo");
const bolinhas = document.querySelectorAll(".bolinha");

let atual = 0;
let contador;


/* ATUALIZA O CARROSSEL */

function atualizarCarrossel(direcao) {

    imagem.classList.remove(
        "sair-esquerda",
        "sair-direita",
        "entrar-esquerda",
        "entrar-direita"
    );

    void imagem.offsetWidth;

    if (direcao === "proximo") {
        imagem.classList.add("sair-esquerda");
    } else {
        imagem.classList.add("sair-direita");
    }

    setTimeout(() => {

        imagem.src = imagens[atual];

        imagem.classList.remove(
            "sair-esquerda",
            "sair-direita"
        );

        if (direcao === "proximo") {
            imagem.classList.add("entrar-direita");
        } else {
            imagem.classList.add("entrar-esquerda");
        }

    }, 500);


    /* Atualiza as bolinhas */

    bolinhas.forEach((bolinha, indice) => {

        bolinha.classList.toggle(
            "ativa",
            indice === atual
        );

    });
}


/* REINICIA O CONTADOR */

function reiniciarContador() {

    clearInterval(contador);

    contador = setInterval(() => {

        atual++;

        if (atual >= imagens.length) {
            atual = 0;
        }

        atualizarCarrossel("proximo");

    }, 5000);
}


/* PRÓXIMA */

proximo.addEventListener("click", () => {

    atual++;

    if (atual >= imagens.length) {
        atual = 0;
    }

    atualizarCarrossel("proximo");

    reiniciarContador();

});


/* ANTERIOR */

anterior.addEventListener("click", () => {

    atual--;

    if (atual < 0) {
        atual = imagens.length - 1;
    }

    atualizarCarrossel("anterior");

    reiniciarContador();

});


/* BOLINHAS */

bolinhas.forEach((bolinha, indice) => {

    bolinha.addEventListener("click", () => {

        if (indice === atual) {
            reiniciarContador();
            return;
        }

        const direcao =
            indice > atual
                ? "proximo"
                : "anterior";

        atual = indice;

        atualizarCarrossel(direcao);

        reiniciarContador();

    });

});


/* INICIA O CARROSSEL AUTOMÁTICO */

reiniciarContador();