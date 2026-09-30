const calendario = document.getElementById("calendario");
const titulo = document.getElementById("titulo");

const btnMes = document.getElementById("btnMes");
const btnSemana = document.getElementById("btnSemana");
const btnDia = document.getElementById("btnDia");

const anterior = document.getElementById("anterior");
const proximo = document.getElementById("proximo");

const containerSemanaChecks = document.querySelector(".checks-semana") || document.querySelector(".semana");

let dataAtual = new Date();
let diaSelecionado = new Date();
let modo = "mes";

const meses = [
    "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
    "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"
];

// Mapeamento dos dias da semana para bater com o ENUM do MySQL
const mapaDiasEnum = {
    1: "SEG",
    2: "TER",
    3: "QUA",
    4: "QUI",
    5: "SEX",
    6: "SAB"
};

// Atualiza os ícones FIXOS da barra superior (SEG a SÁB) com base na tabela Itens_Treino
function atualizarBarraSemanal() {
    if (!containerSemanaChecks) return;

    if (modo === "dia") {
        containerSemanaChecks.style.display = "none";
        return;
    }

    containerSemanaChecks.style.display = "flex";

    // Pega o array do PHP, ex: ["SEG", "QUA", "SEX"]
    const diasComTreino = window.DIAS_TREINO_ALUNO || [];
    const elementosSem = containerSemanaChecks.querySelectorAll(".sem");

    elementosSem.forEach((el) => {
        const numDia = parseInt(el.getAttribute("data-dia"), 10);
        const siglaDia = mapaDiasEnum[numDia]; // 1: "SEG", 2: "TER", ..., 6: "SAB"
        
        // Verifica se a sigla existe dentro de DIAS_TREINO_ALUNO
        const temTreino = diasComTreino.includes(siglaDia);

        const icone = el.querySelector("i");
        if (icone) {
            if (temTreino) {
                icone.classList.remove("ri-subtract-line");
                icone.classList.add("ri-check-line");
                el.classList.add("com-treino");
            } else {
                icone.classList.remove("ri-check-line");
                icone.classList.add("ri-subtract-line");
                el.classList.remove("com-treino");
            }
        }
    });
}

function atualizar() {
    atualizarBarraSemanal();

    if (modo === "mes") mostrarMes();
    else if (modo === "semana") mostrarSemana();
    else if (modo === "dia") mostrarDia();

    atualizarBotoes();
}

btnMes.onclick = () => { modo = "mes"; atualizar(); };
btnSemana.onclick = () => { modo = "semana"; atualizar(); };
btnDia.onclick = () => { 
    modo = "dia"; 
    dataAtual = new Date(diaSelecionado); 
    atualizar(); 
};

function atualizarBotoes() {
    btnMes.classList.toggle("ativo", modo === "mes");
    btnSemana.classList.toggle("ativo", modo === "semana");
    btnDia.classList.toggle("ativo", modo === "dia");
}

function mostrarMes() {
    calendario.innerHTML = "";

    const ano = dataAtual.getFullYear();
    const mes = dataAtual.getMonth();

    titulo.textContent = `${meses[mes]} ${ano}`;

    const diasNoMes = new Date(ano, mes + 1, 0).getDate();
    const primeiroDia = new Date(ano, mes, 1).getDay();

    const nomesDias = ["DOM", "SEG", "TER", "QUA", "QUI", "SEX", "SÁB"];
    const grade = document.createElement("div");
    grade.classList.add("grade-mes");

    nomesDias.forEach(nome => {
        const cabecalho = document.createElement("div");
        cabecalho.textContent = nome;
        cabecalho.style.fontWeight = "bold";
        cabecalho.style.textAlign = "center";
        grade.appendChild(cabecalho);
    });

    for (let i = 0; i < primeiroDia; i++) {
        grade.appendChild(document.createElement("div"));
    }

    const hoje = new Date();

    for (let dia = 1; dia <= diasNoMes; dia++) {
        const quadrado = document.createElement("div");
        quadrado.classList.add("quadrado");
        quadrado.textContent = dia;

        if (dia === hoje.getDate() && mes === hoje.getMonth() && ano === hoje.getFullYear()) {
            quadrado.classList.add("hoje");
        }

        if (dia === diaSelecionado.getDate() && mes === diaSelecionado.getMonth() && ano === diaSelecionado.getFullYear()) {
            quadrado.classList.add("selecionado");
        }

        // Seleciona o dia sem trocar de modo
        quadrado.addEventListener("click", () => {
            diaSelecionado = new Date(ano, mes, dia);
            atualizar();
        });

        grade.appendChild(quadrado);
    }

    calendario.appendChild(grade);
}

function mostrarSemana() {
    calendario.innerHTML = "";

    const nomesDias = ["DOM", "SEG", "TER", "QUA", "QUI", "SEX", "SÁB"];
    const ano = dataAtual.getFullYear();
    const mes = dataAtual.getMonth();

    titulo.textContent = `${meses[mes]} ${ano}`;

    const primeiroDia = new Date(dataAtual);
    primeiroDia.setDate(dataAtual.getDate() - dataAtual.getDay());

    const grade = document.createElement("div");
    grade.classList.add("grade-semana");

    const hoje = new Date();

    for (let i = 0; i < 7; i++) {
        const data = new Date(primeiroDia);
        data.setDate(primeiroDia.getDate() + i);

        const quadrado = document.createElement("div");
        quadrado.classList.add("quadrado");
        quadrado.innerHTML = `<strong>${nomesDias[i]}</strong><br>${data.getDate()}`;

        if (data.getDate() === hoje.getDate() && data.getMonth() === hoje.getMonth() && data.getFullYear() === hoje.getFullYear()) {
            quadrado.classList.add("hoje");
        }

        if (data.getDate() === diaSelecionado.getDate() && data.getMonth() === diaSelecionado.getMonth() && data.getFullYear() === diaSelecionado.getFullYear()) {
            quadrado.classList.add("selecionado");
        }

        quadrado.onclick = () => {
            diaSelecionado = new Date(data);
            dataAtual = new Date(data);
            atualizar();
        };

        grade.appendChild(quadrado);
    }

    calendario.appendChild(grade);
}

function mostrarDia() {
    calendario.innerHTML = "";

    const diasSemana = [
        "Domingo", "Segunda-feira", "Terça-feira",
        "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"
    ];

    const dia = dataAtual.getDate();
    const mes = dataAtual.getMonth();
    const ano = dataAtual.getFullYear();
    const semana = dataAtual.getDay();

    titulo.textContent = `${dia} de ${meses[mes]} de ${ano}`;

    const hoje = new Date();

    const card = document.createElement("div");
    card.classList.add("dia", "dia-unico");
    card.innerHTML = `
        <h2>${diasSemana[semana]}</h2>
        <h1>${dia}</h1>
        <p>${meses[mes]} ${ano}</p>
    `;

    if (dia === hoje.getDate() && mes === hoje.getMonth() && ano === hoje.getFullYear()) {
        card.classList.add("hoje");
    }

    calendario.appendChild(card);
}

anterior.addEventListener("click", () => {
    if (modo === "mes") dataAtual.setMonth(dataAtual.getMonth() - 1);
    else if (modo === "semana") dataAtual.setDate(dataAtual.getDate() - 7);
    else {
        dataAtual.setDate(dataAtual.getDate() - 1);
        diaSelecionado = new Date(dataAtual);
    }
    atualizar();
});

proximo.addEventListener("click", () => {
    if (modo === "mes") dataAtual.setMonth(dataAtual.getMonth() + 1);
    else if (modo === "semana") dataAtual.setDate(dataAtual.getDate() + 7);
    else {
        dataAtual.setDate(dataAtual.getDate() + 1);
        diaSelecionado = new Date(dataAtual);
    }
    atualizar();
});

// Inicialização
atualizar();