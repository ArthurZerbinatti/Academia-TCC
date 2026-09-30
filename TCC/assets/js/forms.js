document.addEventListener("DOMContentLoaded", () => {

    // 1. MÁSCARA DE CPF
    const inputCPF = document.querySelector('#cpf');
    if (inputCPF) {
        inputCPF.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '').slice(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }

    // 2. MÁSCARA DE TELEFONE
    const inputTel = document.querySelector('#telefone');
    if (inputTel) {
        inputTel.addEventListener('input', (e) => {
            let valor = e.target.value.replace(/\D/g, "").slice(0, 11);
            if (valor.length > 10) {
                valor = valor.replace(/^(\d{2})(\d{5})(\d{4}).*/, "($1) $2-$3");
            } else if (valor.length > 6) {
                valor = valor.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, "($1) $2-$3");
            } else if (valor.length > 2) {
                valor = valor.replace(/^(\d{2})(\d{0,5})/, "($1) $2");
            } else if (valor.length > 0) {
                valor = valor.replace(/^(\d*)/, "($1");
            }
            e.target.value = valor;
        });
    }

    // 3. MOSTRAR / OCULTAR SENHAS
    const botoesMostrarSenha = document.querySelectorAll('.mostrar-senha');
    botoesMostrarSenha.forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            const icone = btn.querySelector('i');

            if (input && icone) {
                const tipoAtual = input.type === 'password' ? 'text' : 'password';
                input.type = tipoAtual;

                icone.classList.toggle('ri-eye-close-line');
                icone.classList.toggle('ri-eye-line');
            }
        });
    });

    const inputCREF = document.querySelector('#cref');
    if (inputCREF) {
    inputCREF.addEventListener('input', mascaraCREF);
}

});

function mascaraCREF(e) {
    let input = e.target;
    // Pega o valor e força tudo em maiúsculas
    let valor = input.value.toUpperCase();

    // 1. Separa o valor entre a parte do início (números + letra) e o estado (UF no final)
    let partes = valor.split('/');
    let corpo = partes[0] || '';
    let uf = partes[1] || '';

    // 2. Limpa e formata a primeira parte: apenas números seguidos de 'G' ou 'P'
    let numeros = corpo.replace(/\D/g, '').slice(0, 6);
    let letrasCategoria = corpo.replace(/[^GPgp]/g, ''); 
    let categoria = letrasCategoria.length > 0 ? letrasCategoria[letrasCategoria.length - 1] : '';

    let resultado = numeros;

    // Se já digitou os 6 números e inseriu uma categoria válida (G ou P)
    if (numeros.length === 6 && categoria) {
        resultado += '-' + categoria;
    } else if (numeros.length === 6 && corpo.includes('-')) {
        // Mantém o hífen enquanto a pessoa digita
        resultado += '-';
    }

    // 3. Limpa e formata a UF (máximo 2 letras de A-Z)
    uf = uf.replace(/[^A-Z]/g, '').slice(0, 2);

    if (resultado.length === 8) { // Se já tem "000000-G" ou "000000-P"
        if (uf.length > 0 || valor.includes('/')) {
            resultado += '/' + uf;
        }
    }

    input.value = resultado;
}