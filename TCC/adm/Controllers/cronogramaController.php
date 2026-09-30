<?php

session_start();

if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

if (($_SESSION['tipo_usuario'] ?? '') !== 'PROFESSOR') {
    header("Location: ../aluno/aluno.php");
    exit();
}

require_once '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../Private/professor/cronograma.php");
    exit();
}

$usuario_id = (int)$_SESSION['usuario_id'];

$id_modalidade = filter_input(INPUT_POST, 'id_modalidade', FILTER_VALIDATE_INT);
$nome_turma = trim($_POST['nome_turma'] ?? '');
$dia_semana = strtoupper(trim($_POST['dia_semana'] ?? ''));
$horario_inicio = trim($_POST['horario_inicio'] ?? '');
$horario_fim = trim($_POST['horario_fim'] ?? '');
$capacidade = filter_input(INPUT_POST, 'capacidade', FILTER_VALIDATE_INT);

$dias_validos = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM'];

if (
    !$id_modalidade ||
    $nome_turma === '' ||
    !in_array($dia_semana, $dias_validos, true) ||
    $horario_inicio === '' ||
    $horario_fim === '' ||
    !$capacidade ||
    $capacidade < 1
) {
    header("Location: ../../Private/professor/cronograma.php?erro=dados_invalidos");
    exit();
}

if ($horario_inicio >= $horario_fim) {
    header("Location: ../../Private/professor/cronograma.php?erro=horario_invalido");
    exit();
}

try {
    $stmtModalidade = $pdo->prepare("
        SELECT id_modalidade
        FROM Modalidades
        WHERE id_modalidade = :id_modalidade
        LIMIT 1
    ");

    $stmtModalidade->bindValue(':id_modalidade', $id_modalidade, PDO::PARAM_INT);
    $stmtModalidade->execute();

    if (!$stmtModalidade->fetchColumn()) {
        header("Location: ../../Private/professor/cronograma.php?erro=modalidade_invalida");
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO Aulas (
            id_modalidade,
            id_professor,
            nome_turma,
            dia_semana,
            horario_inicio,
            horario_fim,
            capacidade,
            ativa
        ) VALUES (
            :id_modalidade,
            :id_professor,
            :nome_turma,
            :dia_semana,
            :horario_inicio,
            :horario_fim,
            :capacidade,
            1
        )
    ");

    $stmt->bindValue(':id_modalidade', $id_modalidade, PDO::PARAM_INT);
    $stmt->bindValue(':id_professor', $usuario_id, PDO::PARAM_INT);
    $stmt->bindValue(':nome_turma', $nome_turma, PDO::PARAM_STR);
    $stmt->bindValue(':dia_semana', $dia_semana, PDO::PARAM_STR);
    $stmt->bindValue(':horario_inicio', $horario_inicio, PDO::PARAM_STR);
    $stmt->bindValue(':horario_fim', $horario_fim, PDO::PARAM_STR);
    $stmt->bindValue(':capacidade', $capacidade, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../../Private/professor/cronograma.php?sucesso=aula_criada");
    exit();

} catch (PDOException $e) {
    die("ERRO AO CRIAR AULA: " . $e->getMessage());
}
