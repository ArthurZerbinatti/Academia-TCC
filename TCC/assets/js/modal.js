// ==================================================
// FUNÇÕES DOS MODAIS
// ==================================================

function abrirModal(id) {

    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('aberto');
    }

}


function fecharModal(id) {

    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.remove('aberto');
    }

}


// ==================================================
// QUANDO A PÁGINA TERMINAR DE CARREGAR
// ==================================================

document.addEventListener('DOMContentLoaded', () => {


    // ==================================================
    // CARDS DOS ALUNOS
    // ==================================================

    const cards = document.querySelectorAll('.aluno-card');

    cards.forEach((card) => {
        card.addEventListener('click', () => {
            const idAluno = card.dataset.id;
            const nomeAluno = card.dataset.nome;

            if (idAluno && nomeAluno) {
                // Abre primeiro o modal de ESCOLHA, em vez da avaliação direta
                abrirModalEscolha(idAluno, nomeAluno);
            } else {
                console.warn("Card clicado, mas faltam os atributos data-id ou data-nome no HTML:", card);
            }
        });
    }); 


    // ==================================================
    // FECHAR CLICANDO FORA DO MODAL
    // ==================================================

    const fundos = document.querySelectorAll('.modal-fundo');

    fundos.forEach((fundo) => {

        fundo.addEventListener('click', (evento) => {

            if (evento.target === fundo) {

                fundo.classList.remove('aberto');

            }

        });

    });


    // ==================================================
    // FECHAR COM ESC
    // ==================================================

    document.addEventListener('keydown', (evento) => {

        if (evento.key === 'Escape') {

            const modaisAbertos =
                document.querySelectorAll('.modal-fundo.aberto');

            modaisAbertos.forEach((modal) => {

                modal.classList.remove('aberto');

            });

        }

    });


    // ==================================================
    // BOTÕES DE FECHAR / CANCELAR
    // ==================================================

    const botoesFechar =
        document.querySelectorAll('.modal-cancelar, .modal-fechar');

    botoesFechar.forEach((botao) => {

        botao.addEventListener('click', () => {

            const modal = botao.closest('.modal-fundo');

            if (modal) {

                modal.classList.remove('aberto');

            }

        });

    });


    // ==================================================
    // BOTÃO SALVAR
    // ==================================================

    const botoesSalvar =
        document.querySelectorAll('.modal-salvar');

    botoesSalvar.forEach((botao) => {

        botao.addEventListener('click', () => {

            const formId = botao.getAttribute('form');

            const form = formId
                ? document.getElementById(formId)
                : botao.closest('form');


            if (form && form.reportValidity()) {

                form.submit();

            }

        });

    });


    // ==================================================
    // CÁLCULO DO IMC
    // ==================================================

    const inputPeso =
        document.getElementById('input-peso');

    const inputAltura =
        document.getElementById('input-altura');


    if (inputPeso && inputAltura) {

        inputPeso.addEventListener(
            'input',
            calcularIMCAoVivo
        );

        inputAltura.addEventListener(
            'input',
            calcularIMCAoVivo
        );

    }

});


// ==================================================
// CALCULAR IMC
// ==================================================

function calcularIMCAoVivo() {

    const pesoInput =
        document.getElementById('input-peso');

    const alturaInput =
        document.getElementById('input-altura');

    const campoIMC =
        document.getElementById('input-imc');


    if (!pesoInput || !alturaInput || !campoIMC) {
        return;
    }


    const peso =
        parseFloat(pesoInput.value);

    const altura =
        parseFloat(alturaInput.value);


    if (peso > 0 && altura > 0) {

        const imc =
            peso / (altura * altura);

        campoIMC.value =
            imc.toFixed(2);

    } else {

        campoIMC.value = '';

    }

}


// ==================================================
// ABRIR MODAL DE ESCOLHA (OPÇÕES DO ALUNO)
// ==================================================

function abrirModalEscolha(idAluno, nomeAluno) {

    const spanNome =
        document.getElementById('nome-aluno-escolha');

    const btnAvaliacao =
        document.getElementById('btn-opcao-avaliacao');

    const btnTreino =
        document.getElementById('btn-opcao-treino');


    // Exibe o nome do aluno no título/subtítulo do modal de escolha (se o elemento existir)
    if (spanNome) {

        spanNome.textContent = nomeAluno;

    }


    // Configura o botão de ir para AVALIAÇÃO
    if (btnAvaliacao) {

        btnAvaliacao.onclick = () => {

            fecharModal('modal-escolha');

            abrirModalAvaliacao(idAluno, nomeAluno);

        };

    }


    // Configura o botão de ir para TREINO
    if (btnTreino) {

        btnTreino.onclick = () => {

            fecharModal('modal-escolha');

            abrirModalTreino(idAluno, nomeAluno);

        };

    }


    // Finalmente abre o modal de escolha
    abrirModal('modal-escolha');

}


// ==================================================
// ABRIR MODAL DE AVALIAÇÃO
// ==================================================

function abrirModalAvaliacao(idAluno, nomeAluno) {

    const inputId =
        document.getElementById('modal-id-aluno');

    const labelNome =
        document.getElementById('modal-nome-aluno');

    const form =
        document.getElementById('formAvaliacao');


    // Primeiro limpa o formulário
    if (form) {

        form.reset();

    }


    // DEPOIS coloca os dados do aluno
    if (inputId) {

        inputId.value = idAluno;

    }


    if (labelNome) {

        labelNome.textContent = nomeAluno;

    }


    // Finalmente abre o modal
    abrirModal('modal-avaliacao');

}


// ==================================================
// ABRIR MODAL DE TREINO
// ==================================================

function abrirModalTreino(idAluno, nomeAluno) {

    const inputId =
        document.getElementById('idAlunoTreino');

    const spanNome =
        document.getElementById('nomeAlunoTreino');

    const container =
        document.getElementById('containerExercicios');

    const form =
        document.getElementById('formTreino');


    // Limpa formulário e exercícios de aberturas anteriores
    if (form) {

        form.reset();

    }


    if (container) {

        container.innerHTML = '';

    }


    // Preenche os dados do aluno
    if (inputId) {

        inputId.value = idAluno;

    }


    if (spanNome) {

        spanNome.textContent = nomeAluno;

    }


    // Adiciona a primeira linha de exercício por padrão
    adicionarLinhaExercicio();


    // Abre o modal de treino
    abrirModal('modalTreino');

}


// ==================================================
// FECHAR MODAL DE TREINO
// ==================================================

function fecharModalTreino() {

    fecharModal('modalTreino');

}


// ==================================================
// ADICIONAR LINHA DE EXERCÍCIO DINÂMICA
// ==================================================

function adicionarLinhaExercicio() {

    const container =
        document.getElementById('containerExercicios');

    if (!container) {
        return;
    }


    // Obtém os exercícios passados pelo PHP (window.listaExercicios)
    const exercicios =
        window.listaExercicios || [];

    let optionsEx = '<option value="">Selecione...</option>';


    if (exercicios.length > 0) {

        exercicios.forEach((ex) => {

            optionsEx += `<option value="${ex.id_exercicio}">${ex.grupo_muscular} - ${ex.nome}</option>`;

        });

    } else {

        optionsEx = '<option value="">Nenhum exercício cadastrado</option>';

    }


    const divRow =
        document.createElement('div');

    divRow.className = 'linha-exercicio';


    divRow.innerHTML = `
        <select name="id_exercicio[]" class="select-ex" required>${optionsEx}</select>
        <select name="dia_semana[]" class="select-dia" required>
            <option value="SEG">SEG</option>
            <option value="TER">TER</option>
            <option value="QUA">QUA</option>
            <option value="QUI">QUI</option>
            <option value="SEX">SEX</option>
            <option value="SAB">SAB</option>
            <option value="DOM">DOM</option>
        </select>
        <input type="number" name="series[]" class="input-series" value="3" min="1" required placeholder="Séries">
        <input type="text" name="repeticoes[]" class="input-reps" placeholder="Ex: 10-12" required>
        <input type="number" step="0.5" name="carga_kg[]" class="input-carga" placeholder="Kg">
        <input type="number" name="descanso_segundos[]" class="input-descanso" value="60" placeholder="Seg">
        <button type="button" class="btn-remover-linha" onclick="this.parentElement.remove()">
            <i class="ri-delete-bin-line"></i>
        </button>
    `;


    container.appendChild(divRow);

}