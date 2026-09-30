document.addEventListener("DOMContentLoaded", () => {

    const abas = document.querySelectorAll(".aba");
    const camposProfessor = document.querySelector(".campos-professor");
    const inputTipoUsuario = document.querySelector("#tipo_usuario");

    if (!camposProfessor) return;

    const camposExtras = camposProfessor.querySelectorAll("input, select");
    let handlerAnimationEnd = null;

    abas.forEach(aba => {
        aba.addEventListener("click", () => {
            const tipo = aba.dataset.tipo;

            if (aba.classList.contains("ativa")) {
                return;
            }

            abas.forEach(item => {
                item.classList.remove("ativa");
            });

            aba.classList.add("ativa");

            // Atualiza o valor para ALUNO ou PROFESSOR
            if (inputTipoUsuario && tipo) {
                inputTipoUsuario.value = tipo.toUpperCase();
            }

            // PROFESSOR
            if (tipo === "professor") {
                if (handlerAnimationEnd) {
                    camposProfessor.removeEventListener("animationend", handlerAnimationEnd);
                    handlerAnimationEnd = null;
                }

                camposProfessor.classList.remove("esconder");
                camposProfessor.classList.add("mostrar");

                camposExtras.forEach(campo => {
                    campo.disabled = false;
                });
            }
            // ALUNO
            else {
                if (camposProfessor.classList.contains("mostrar")) {
                    camposProfessor.classList.remove("mostrar");
                    camposProfessor.classList.add("esconder");

                    handlerAnimationEnd = function aoTerminar() {
                        camposProfessor.classList.remove("esconder");
                        camposProfessor.removeEventListener("animationend", handlerAnimationEnd);
                        handlerAnimationEnd = null;
                    };

                    camposProfessor.addEventListener("animationend", handlerAnimationEnd);
                }

                camposExtras.forEach(campo => {
                    campo.disabled = true;
                });
            }
        });
    });

});