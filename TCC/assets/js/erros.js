    const urlParams = new URLSearchParams(window.location.search);
    const erro = urlParams.get('erro');
    const status = urlParams.get('status');
    const container = document.getElementById('mensagem-alerta');

    const mensagensErro = {
        // Formulário de Cadastro e Login
    'campos_vazios': 'Por favor, preencha todos os campos obrigatórios.',
    'senhas_diferentes': 'As senhas digitadas não coincidem.',
    'email_invalido': 'Informe um endereço de e-mail válido.',
    'cpf_invalido': 'O CPF deve conter exatamente 11 dígitos.',
    'duplicado': 'Já existe uma conta cadastrada com este CPF ou E-mail.',
    'credenciais_invalidas': 'E-mail/CPF ou senha incorretos.',

    // Validações Exclusivas de Professor (Cadastro)
    'cref_obrigatorio': 'O registro CREF é obrigatório para professores.',
    'cref_invalido': 'Formato de CREF inválido. Use o formato: 123456-G/SP.',
    'cref_duplicado': 'Já existe um professor cadastrado com este CREF.',

    // Segurança e Controle de Acesso
    'nao_autorizado': 'Faça login para acessar o sistema.',
    'metodo_invalido': 'Requisição inválida. O formulário deve ser enviado via POST.',
    'token_invalido': 'Sessão expirada ou requisição inválida. Tente novamente.',
    'falha_sistema': 'Ocorreu um erro interno. Tente novamente mais tarde.'
    };

    if (erro && mensagensErro[erro]) {
        container.innerHTML = `<div class="alerta erro">${mensagensErro[erro]}</div>`;
    } else if (status === 'cadastro_sucesso') {
        container.innerHTML = `<div class="alerta sucesso">Cadastro realizado com sucesso! Faça seu login.</div>`;
    }
