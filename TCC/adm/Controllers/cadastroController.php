<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('pcre.jit', 0);

session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once("../Config/database.php");
require_once("../Config/notificacoes.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. DADOS BÁSICOS (Tabela Usuarios)
    $tipo_usuario  = strtoupper(trim($_POST['tipo_usuario'] ?? 'ALUNO'));
    $nome          = trim($_POST['nome'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $senha         = $_POST['senha'] ?? '';
    $senha_conf    = $_POST['senha_conf'] ?? '';
    $sexo          = $_POST['sexo'] ?? 'O';
    
    $data_raw      = trim($_POST['data_nascimento'] ?? '');
    $data_nasc     = !empty($data_raw) ? $data_raw : null;

    $cpf_bruto     = $_POST['cpf'] ?? '';
    $tel_bruto     = $_POST['tel'] ?? '';
    $cpf           = preg_replace('/[^0-9]/', '', $cpf_bruto);
    $tel           = preg_replace('/[^0-9]/', '', $tel_bruto);

    // 2. DADOS ESPECÍFICOS (Tabela Professores)
    $cref          = trim($_POST['cref'] ?? '');
    $especializacao = trim($_POST['especializacao'] ?? '');

    // 3. VALIDAÇÕES GERAIS DE CAMPOS
    if (empty($nome) || empty($email) || empty($senha) || empty($cpf)) {
        header("Location: ../../Public/cadastro.php?erro=campos_vazios");
        exit();
    }

    if ($senha !== $senha_conf) {
        header("Location: ../../Public/cadastro.php?erro=senhas_diferentes");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../../Public/cadastro.php?erro=email_invalido");
        exit();
    }

    if (strlen($cpf) !== 11) {
        header("Location: ../../Public/cadastro.php?erro=cpf_invalido");
        exit();
    }

    // SANITIZAÇÃO DE TIPO DE USUÁRIO (White-list para o enum)
    if (!in_array($tipo_usuario, ['ALUNO', 'PROFESSOR'], true)) {
        $tipo_usuario = 'ALUNO';
    }

    // 4. VALIDAÇÕES EXCLUSIVAS DE PROFESSOR
    if ($tipo_usuario === 'PROFESSOR') {
        if (empty($cref)) {
            header("Location: ../../Public/cadastro.php?erro=cref_obrigatorio");
            exit();
        }

        // Valida formato do CREF: 6 números + hífen + G ou P + barra + 2 letras maiúsculas
        if (!preg_match('/^\d{6}-[GP]\/[A-Z]{2}$/', $cref)) {
            header("Location: ../../Public/cadastro.php?erro=cref_invalido");
            exit();
        }

        // Validação por Lista Branca (White-list) da especialização
        $especialidadesValidas = ['musculacao', 'danca', 'natacao'];
        if (!in_array($especializacao, $especialidadesValidas, true)) {
            $especializacao = null;
        }
    } else {
        // Se for aluno, garante que esses valores fiquem vazios/nulos
        $cref = null;
        $especializacao = null;
    }

    try {
        // 5. VERIFICAÇÃO DE DUPLICIDADE EM BANCO
        // CPF ou E-mail na tabela Usuarios
        $sql_check = "SELECT id_usuario FROM Usuarios WHERE cpf = ? OR email = ? LIMIT 1";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$cpf, $email]);

        if ($stmt_check->fetch()) {
            header("Location: ../../Public/cadastro.php?erro=duplicado");
            exit();
        }

        // CREF na tabela Professores
        if ($tipo_usuario === 'PROFESSOR') {
            $sql_check_cref = "SELECT id_professor FROM Professores WHERE cref = ? LIMIT 1";
            $stmt_check_cref = $pdo->prepare($sql_check_cref);
            $stmt_check_cref->execute([$cref]);

            if ($stmt_check_cref->fetch()) {
                header("Location: ../../Public/cadastro.php?erro=cref_duplicado");
                exit();
            }
        }

        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // 6. TRANSAÇÃO DE BANCO DE DADOS
        $pdo->beginTransaction();

        // 1º INSERT: Tabela Usuarios
        $sql_usuario = "INSERT INTO Usuarios (nome, cpf, email, senha, telefone, sexo, data_nascimento, tipo_usuario) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_u = $pdo->prepare($sql_usuario);
        $stmt_u->execute([$nome, $cpf, $email, $senha_hash, $tel, $sexo, $data_nasc, $tipo_usuario]);

        $id_novo_usuario = $pdo->lastInsertId();

        // 2º INSERT: Tabela Professores (apenas se for PROFESSOR)
        if ($tipo_usuario === 'PROFESSOR') {
            $sql_prof = "INSERT INTO Professores (id_professor, cref, especializacao) VALUES (?, ?, ?)";
            $stmt_p = $pdo->prepare($sql_prof);
            
            $esp_valor = !empty($especializacao) ? $especializacao : null;
            $stmt_p->execute([$id_novo_usuario, $cref, $esp_valor]);
        }

        $pdo->commit();

        // NOTIFICAÇÕES (depois do commit; se falhar, o cadastro continua normalmente)
        // Professor novo -> todos recebem | Aluno novo -> os professores recebem
        if ($tipo_usuario === 'PROFESSOR') {
            notificarNovoProfessor($pdo, (int)$id_novo_usuario, $nome);
        } else {
            notificarNovoAluno($pdo, (int)$id_novo_usuario, $nome);
        }

        // 7. SESSÃO E REDIRECIONAMENTO
        session_regenerate_id(true);

        $_SESSION['usuario_id']       = $id_novo_usuario;
        $_SESSION['usuario_nome']     = $nome;
        $_SESSION['usuario_email']    = $email;
        $_SESSION['usuario_telefone'] = $tel_bruto;
        $_SESSION['tipo_usuario']     = $tipo_usuario;
        $_SESSION['logado']           = true;

        if ($tipo_usuario === 'PROFESSOR') {
            header("Location: ../../Private/professor/professor.php");
        } else {
            header("Location: ../../Private/aluno/aluno.php");
        }
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erro no cadastro: " . $e->getMessage());
        header("Location: ../../Public/cadastro.php?erro=falha_sistema");
        exit();
    }

} else {
    header('Location: ../../Public/cadastro.php');
    exit();
}
?>