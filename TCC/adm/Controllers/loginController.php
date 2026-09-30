<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once("../Config/database.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login_input = trim($_POST['cpf'] ?? ''); // Pode ser E-mail ou CPF
    $senha       = $_POST['senha'] ?? '';

    if (empty($login_input) || empty($senha)) {
        header("Location: ../../Public/login.php?erro=campos_vazios");
        exit();
    }

    // Limpa pontuação se o usuário digitou CPF com pontos/traço
    $login_limpo = preg_replace('/[^0-9]/', '', $login_input);

    try {
        // Busca por E-mail OU CPF limpo
        $sql = "SELECT id_usuario, nome, email, senha, telefone, tipo_usuario 
                FROM Usuarios 
                WHERE email = ? OR cpf = ? 
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$login_input, $login_limpo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica credenciais
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            session_regenerate_id(true);

            // Grava dados genéricos na sessão
            $_SESSION['usuario_id']       = $usuario['id_usuario'];
            $_SESSION['usuario_nome']     = $usuario['nome'];
            $_SESSION['usuario_email']    = $usuario['email'];
            $_SESSION['usuario_telefone'] = $usuario['telefone'];
            $_SESSION['tipo_usuario']     = $usuario['tipo_usuario'];
            $_SESSION['logado']           = true;

            // Se for PROFESSOR, busca os dados da tabela Professores
            if ($usuario['tipo_usuario'] === 'PROFESSOR') {
                $sql_prof = "SELECT cref, especializacao FROM Professores WHERE id_professor = ? LIMIT 1";
                $stmt_prof = $pdo->prepare($sql_prof);
                $stmt_prof->execute([$usuario['id_usuario']]);
                $dados_prof = $stmt_prof->fetch(PDO::FETCH_ASSOC);

                if ($dados_prof) {
                    $_SESSION['cref']           = $dados_prof['cref'];
                    $_SESSION['especializacao'] = $dados_prof['especializacao'];
                }

                header("Location: ../../Private/professor/professor.php");
                exit();
            } else {
                header("Location: ../../Private/aluno/aluno.php");
                exit();
            }

        } else {
            // Credenciais inválidas
            header("Location: ../../Public/login.php?erro=credenciais_invalidas");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Erro no login: " . $e->getMessage());
        header("Location: ../../Public/login.php?erro=falha_sistema");
        exit();
    }

} else {
    header('Location: ../../Public/login.php');
    exit();
}
?>