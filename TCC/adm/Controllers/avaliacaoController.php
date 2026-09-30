<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('pcre.jit', 0);
session_start();

// 1. Validação de Autenticação e Permissão
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'PROFESSOR') {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../Private/professor/alunos.php?erro=metodo_invalido");
    exit();
}

// 2. Validação do Token CSRF
$csrf_post = $_POST['csrf_token'] ?? '';
$csrf_session = $_SESSION['csrf_token'] ?? '';
if (empty($csrf_post) || !hash_equals($csrf_session, $csrf_post)) {
    header("Location: ../../Private/professor/alunos.php?erro=csrf_invalido");
    exit();
}

require_once '../Config/database.php';
require_once '../Config/notificacoes.php';

// 3. Sanitização e Coleta dos Dados
$id_aluno     = filter_input(INPUT_POST, 'id_aluno', FILTER_VALIDATE_INT);
$id_professor = (int)$_SESSION['usuario_id'];

$peso   = filter_var(str_replace(',', '.', $_POST['peso'] ?? ''), FILTER_VALIDATE_FLOAT);
$altura = filter_var(str_replace(',', '.', $_POST['altura'] ?? ''), FILTER_VALIDATE_FLOAT);

$gordura_raw        = str_replace(',', '.', $_POST['percentual_gordura'] ?? '');
$percentual_gordura = filter_var($gordura_raw, FILTER_VALIDATE_FLOAT) ?: null;

$observacoes = trim($_POST['observacoes'] ?? '') ?: null;

// Validação dos campos obrigatórios do seu banco (peso e altura)
if (!$id_aluno || !$peso || !$altura || $peso <= 0 || $altura <= 0) {
    header("Location: ../../Private/professor/alunos.php?erro=dados_invalidos");
    exit();
}

// 4. Cálculo do IMC no servidor
$imc = round($peso / ($altura * $altura), 2);

// 5. Inserção na tabela Avaliacoes e Vinculo Professor_Aluno
try {
    // Primeiro INSERT: Avaliacoes
    $sqlAvaliacao = "INSERT INTO Avaliacoes 
            (id_aluno, id_professor, data_avaliacao, peso, altura, imc, percentual_gordura, observacoes) 
            VALUES 
            (:id_aluno, :id_professor, CURDATE(), :peso, :altura, :imc, :percentual_gordura, :observacoes)";

    $stmt = $pdo->prepare($sqlAvaliacao);
    $stmt->bindValue(':id_aluno', $id_aluno, PDO::PARAM_INT);
    $stmt->bindValue(':id_professor', $id_professor, PDO::PARAM_INT);
    $stmt->bindValue(':peso', $peso);
    $stmt->bindValue(':altura', $altura);
    $stmt->bindValue(':imc', $imc);

    if (is_null($percentual_gordura)) {
        $stmt->bindValue(':percentual_gordura', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':percentual_gordura', $percentual_gordura);
    }

    if (is_null($observacoes)) {
        $stmt->bindValue(':observacoes', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':observacoes', $observacoes, PDO::PARAM_STR);
    }

    $stmt->execute();

    // Segundo INSERT: Professor_Aluno (Executado separadamente)
    // Usamos um bloco IGNORE caso o vínculo já exista no banco para não gerar erro de chave duplicada
    $sqlVinculo = "INSERT IGNORE INTO Professor_Aluno 
            (id_professor, id_aluno) 
            VALUES 
            (:id_professor, :id_aluno)";

    $stmtVinculo = $pdo->prepare($sqlVinculo);
    $stmtVinculo->bindValue(':id_professor', $id_professor, PDO::PARAM_INT);
    $stmtVinculo->bindValue(':id_aluno', $id_aluno, PDO::PARAM_INT);
    $stmtVinculo->execute();

    // NOTIFICAÇÃO: avisa o aluno que recebeu uma nova avaliação física
    notificarNovaAvaliacao(
        $pdo,
        $id_aluno,
        $_SESSION['usuario_nome'] ?? 'Seu professor',
        $id_professor
    );

    header("Location: ../../Private/professor/alunos.php?sucesso=avaliacao_cadastrada");
    exit();

} catch (PDOException $e) {
    echo "<h2>Erro ao salvar no banco:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    exit();
}
