<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário já estiver logado, redireciona direto para o painel dele
if (!empty($_SESSION['logado']) && $_SESSION['logado'] === true) {
    if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'PROFESSOR') {
        header("Location: ../Private/professor/prof.php");
    } else {
        header("Location: ../Private/aluno/aluno.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> CADASTRO | Academia Espaço Livre </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/forms.css">
    <link rel="stylesheet" href="../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../assets/imagens/favicon/draft-line.svg" type="image/x-icon">

    <style>
        body {
            background-image: 
                linear-gradient(to right, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0)),
                url(../assets/imagens/cadastro.png);
        }
    </style>
    
</head>
<body>

    <header>

        <nav>
            <a href="index.php" class="voltar">
                <i class="ri-arrow-left-s-line"></i>
            </a>
        </nav>

        <div class="titulo-text">
                <h1> BEM VINDO(A) À ACADEMIA <span> ESPAÇO LIVRE </span> </h1>

                <p> Crie uma conta para começar sua evolução! </p>
        </div>
        
        <img class="logo" src="../assets/imagens/Logo.png">

    </header>
    
    <main>

            <section class="arquivo">

                <div class="abas">
                    <button type="button" class="aba ativa" data-tipo="aluno"> ALUNO </button>
                    <button type="button" class="aba" data-tipo="professor"> PROFESSOR </button>
                </div>

                <article class="formulario">
                    <div class="header">
                        <h2> CRIE UMA CONTA </h2>
                        <p> Entre com suas informações para acessar sua conta </p>
                    </div>

                    <div id="mensagem-alerta" style="color: red"></div>

                    <form action="../adm/Controllers/cadastroController.php" method="POST" id="formCadastro">

                        <input type="hidden" name="tipo_usuario" id="tipo_usuario" value="ALUNO">

                            <label for="nome">
                                NOME
                                <div class="campo">
                                    <i class="ri-user-fill"></i>
                                    <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    placeholder="Digite seu nome completo"
                                    autocomplete="given-name"
                                    required>
                                </div>
                            </label>
                            <label for="cpf">
                                CPF
                                <div class="campo">
                                    <i class="ri-asterisk"></i>
                                    <input
                                    type="text"
                                    id="cpf"
                                    name="cpf"
                                    placeholder="000.000.000-00"
                                    inputmode="numeric"
                                    autocomplete="username"
                                    required>
                                </div>
                            </label>

                            <div class="campos-professor">

                                <label for="CREF">
                                    CREF 
                                    <div class="campo">
                                        <i class="ri-info-i"></i>
                                        <input
                                        type="text"
                                        id="cref"
                                        name="cref"
                                        placeholder="Digite seu CREF"
                                        disabled>
                                    </div>
                                </label>

                                <label for="especializacao">
                                    ESPECIALIZAÇÃO 
                                    <div class="campo">
                                        <i class="ri-graduation-cap-line"></i>
                                        <select id="especializacao" name="especializacao" disabled>
                                            <option value="" selected> Selecione sua especialização</option>
                                            <option value="musculacao"> Musculação </option>
                                            <option value="danca"> Dança </option>
                                            <option value="natacao"> Natação </option>
                                        </select>
                                    </div>

                                </label>

                            </div>

                            <label for="tel">
                                TELEFONE
                                <div class="campo">
                                    <i class="ri-phone-fill"></i>
                                    <input
                                    type="tel"
                                    id="telefone"
                                    name="tel"
                                    placeholder="(__) 9____-____"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    oninput="mascaraTelefone(event)"
                                    required>
                                </div>
                            </label>
                            <label for="email">
                                E-MAIL
                                <div class="campo">
                                    <i class="ri-mail-line"></i>
                                    <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    placeholder="Digite seu e-mail"
                                    inputmode="email"
                                    autocomplete="email"
                                    required>
                                </div>
                            </label>
                            <label for="senha">
                                SENHA
                                <div class="campo">
                                    <i class="ri-lock-2-line"></i>
                                    <input
                                    type="password"
                                    id="senha"
                                    name="senha"
                                    placeholder="Digite sua senha"
                                    autocomplete="new-password"
                                    required>
                                    <button
                                    type="button"
                                    class="mostrar-senha"
                                    aria-label="Mostrar senha">
                                        <i style="color: white" class="ri-eye-close-line"></i>
                                    </button>
                                </div>
                            </label>
                            <label for="senha_conf">
                                CONFIRMAR SENHA
                                <div class="campo">
                                    <i class="ri-lock-2-line"></i>
                                    <input
                                    type="password"
                                    id="senha_conf"
                                    name="senha_conf"
                                    placeholder="Confirme sua senha"
                                    autocomplete="new-password"
                                    required>
                                    <button
                                    type="button"
                                    class="mostrar-senha"
                                    aria-label="Mostrar senha">
                                        <i style="color: white" class="ri-eye-close-line"></i>
                                    </button>
                                </div>
                            </label>
                            <div class="vertical">
                            <label for="sexo" class="metade">
                                SEXO
                                <label class="sexo">
                                    <input
                                    type="radio"
                                    id="M"
                                    name="sexo"
                                    value="M"
                                    required>
                                        Masculino
                                </label>
                                <label class="sexo">
                                    <input
                                    type="radio"
                                    id="F"
                                    name="sexo"
                                    value="F"
                                    required>
                                        Feminino
                                </label>
                                <label class="sexo">
                                    <input
                                    type="radio"
                                    id="O"
                                    name="sexo"
                                    value="O"
                                    required>
                                        Outro
                                </label>
                            </label>

                            <label for="data_nascimento" class="metade">
                                DATA DE NASCIMENTO
                                <input
                                type="date"
                                id="data_nascimento"
                                name="data_nascimento"
                                placeholder="MM/DD/AAAA"
                                required>
                            </label>

                            </div>

                            <button type="submit" class="btn-card">
                                CRIAR CONTA
                                <i class="ri-arrow-right-s-line"></i>
                            </button>

                    </form>

                    <div class="separator" aria-hidden="true">
                        <span></span>
                        <p> OU </p>
                        <span></span>
                    </div>

                    <div class="footer">
                        <p> Já tem uma conta? </p>
                        <a href="login.php"> ENTRAR </a>
                    </div>

            </article>

        </section>

    </main>

<script src="../assets/js/cadastro.js"></script>
<script src="../assets/js/forms.js"></script>
<script src="../assets/js/erros.js"></script>
</body>
</html>