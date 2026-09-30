<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário já estiver logado, redireciona direto para o painel dele
if (!empty($_SESSION['logado']) && $_SESSION['logado'] === true) {
    if (!empty($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'PROFESSOR') {
        header("Location: ../Private/professor/professor.php");
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
    <title> LOGIN | Academia Espaço Livre </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/forms.css">
    <link rel="stylesheet" href="../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../assets/imagens/favicon/login-box-line.svg" type="image/x-icon">

    <style>
        body {
            background-image: 
                linear-gradient(to right, rgba(0, 0, 0, 1), rgba(0, 0, 0, 0)),
                url(../assets/imagens/login.jpeg);
        }

        .card {
            display: flex;
            flex-direction: column;
            z-index: 1;
            margin: 0 100px;
            height: 650px;

            background: var(--bg-card); 
            border: var(--borda); 
            border-radius: var(--radius-grande); 
            backdrop-filter: blur(12px); 
            padding: 30px; 
            box-shadow: var(--sombra); 
            justify-content: space-between;

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

            <p> Acesse sua conta e continue sua evolução </p>
        </div>

        <img class="logo" src="../assets/imagens/Logo.png">

    </header>
    
    <main>

        <section class="card">

            <article class="header">
                <h2> FAÇA SEU LOGIN </h2>
                <p> Entre com suas informações para acessar sua conta </p>
            </article>

            <div id="mensagem-alerta" style="color: red"></div>
                <form action="../adm/Controllers/loginController.php" method="POST">

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

                    <label for="senha">

                        SENHA

                        <div class="campo">

                            <i class="ri-lock-2-line"></i>

                            <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required>

                            <button
                            type="button"
                            class="mostrar-senha"
                            aria-label="Mostrar senha">
                                <i style="color: white" class="ri-eye-close-line"></i>
                            </button>

                        </div>

                    </label>

                    <a href="#" class="esqueci-senha"> Esqueci minha senha </a>

                    <button type="submit" class="btn-card">
                        ENTRAR
                        <i class="ri-arrow-right-s-line"></i>
                    </button>

                </form>

                <div class="separator" aria-hidden="true">
                    <span></span>
                    <p> OU </p>
                    <span></span>
                </div>

                <div class="footer">
                    <p> Ainda não tem uma conta? </p>

                    <a href="cadastro.php"> CADASTRE-SE </a>
                </div>

        </section>

    </main>
    <script src="../assets/js/forms.js"></script>
    <script src="../assets/js/erros.js"></script>

</body>
</html>