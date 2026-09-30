<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarAcesso(string $tipoPermitido) {
    // 1. Verifica se está logado
    if (empty($_SESSION['logado']) || empty($_SESSION['tipo_usuario'])) {
        header("Location: ../../Public/login.html?erro=nao_autenticado");
        exit();
    }

    // 2. Verifica se o tipo de usuário atual é o permitido para esta área
    if ($_SESSION['tipo_usuario'] !== strtoupper($tipoPermitido)) {
        // Redireciona para a página correta do perfil do usuário ou para o login
        if ($_SESSION['tipo_usuario'] === 'ALUNO') {
            header("Location: ../../Private/aluno/aluno.html");
        } else if ($_SESSION['tipo_usuario'] === 'PROFESSOR') {
            header("Location: ../../Private/professor/professor.html");
        } else {
            header("Location: ../../Public/login.html?erro=acesso_negado");
        }
        exit();
    }
}
?>