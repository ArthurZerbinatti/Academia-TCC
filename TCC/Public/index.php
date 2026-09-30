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
    <title> ACADEMIA ESPAÇO LIVRE </title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/index.css">
    <link rel="stylesheet" href="../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../assets/imagens/favicon/home-9-fill.svg" type="image/x-icon">

</head>
<body>

    <header>
        
        <nav> 
            
            <figure>
                <img src="../assets/imagens/Logo.png" class="logo">
            </figure>
            
            <div class="itens">
                <a href="cadastro.php" class="cadastrar"> Cadastrar </a>
                <a href="login.php" class="entrar"> Entrar </a>
            </div>
            
        </nav>
        
    </header>
    
    <main> 
        
        <section class="titulo" id="inicio">
            
            <div class="titulo-text">
                <div>
                    <h2> <span> ACADEMIA ESPAÇO LIVRE </span> </h2>
                
                    <p> Mais que um treino, um estilo de vida </p>
                </div>
            
                <div class="sub-titulo">
                    <i class="ri-scroll-to-bottom-line"></i>
                    <p> Role para baixo </p>
                </div>

            </div>
            
        </section>
        
        
        <section class="sobre">
            
            <figure>
                <img class="fachada" src="../assets/imagens/fachada.jpeg">
            </figure>
            
            <div class="sobre-texto">
                <div>
                    <h2> <span> SOBRE NÓS </span> </h2>
                    <p> Na Espaço Livre, o nosso foco é o seu desempenho. Somos o espaço ideal para quem busca superar desafios e quebrar recordes diários. Com uma estrutura de musculação completa, pesos livres, aparelhos de alto padrão e uma equipe de personal trainers prontos para orientar o seu treino, aqui a evolução é constante. Se você quer disciplina, intensidade e resultados reais, encontrou o seu novo lugar. </p>
                </div>

                <a href="sobre.html" class="mais"> 
                    Nossa História <i class="ri-arrow-right-line"></i> 
                </a>
            </div>
            
        </section>
        
        <section class="interior">

            <h2><span>Junte-se a nós!</span></h2>

            <div class="carrossel">

                <button class="anterior">
                    <i class="ri-arrow-left-line"></i>
                </button>

                <img 
                    src="../assets/imagens/area-ginastica.png" 
                    class="slide"
                    id="imagem">

                <button class="proximo">
                    <i class="ri-arrow-right-line"></i>
                </button>

            </div>

            <div class="indicadores">
                <span class="bolinha ativa"></span>
                <span class="bolinha"></span>
                <span class="bolinha"></span>
            </div>

        </section>
        
    </main>

    <footer>
        <div class="area">
            <img src="../assets/imagens/Logo.png" class="logo">
            <div class="links">
                <a href="#"> Termos de Uso </a>
                <a href="#"> Política de Privacidade </a>
                <p class="copy"> © 2026 Espaço Livre. Todos os direitos reservados. </p>
            </div>
        </div>

        <hr>

        <div class="area">
            <h2> NAVEGAÇÃO </h2>
            <div class="links">
                <a href="#inicio"> Início </a>
                <a href="sobre.html"> Sobre Nós </a>
                <a href="#"> Suporte </a>
            </div>
        </div>

        <hr>

        <div class="area">
            <h2> CONTATO </h2>

            <div class="links"> 
                <p> <i class="ri-whatsapp-line"></i> (35) 3591-2505 </p>

                <p> <i class="ri-mail-line"></i> email </p>

                <p> <i class="ri-map-pin-line"></i> Praça Joaquim Bernardes, 80, Monte Santo de Minas </p>
            </div>
        </div>

        <hr>

        <div class="area">
            <h2> SIGA-NOS </h2>

            <div class="social">
                <a href="https://www.instagram.com/academiaespacolivre/" target="_blank"> <i class="ri-instagram-line"></i> </a>
                <a href="https://www.facebook.com/acad.espacolivre/" target="_blank"> <i class="ri-facebook-fill"></i> </a>
            </div>
        </div>
    </footer>
    
<script src="../assets/js/carrossel.js"></script>
</body>
</html>