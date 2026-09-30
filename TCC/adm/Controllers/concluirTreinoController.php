<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
header('Content-Type: application/json; charset=utf-8');

function responder($ok, $mensagem, $http = 200, $extra = [])
{
    http_response_code($http);
    echo json_encode(array_merge(['ok' => $ok, 'mensagem' => $mensagem], $extra));
    exit;
}

// 1. Somente POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método não permitido.', 405);
}

// 2. Somente aluno logado
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'ALUNO') {
    responder(false, 'Acesso não autorizado.', 403);
}

// 3. Proteção CSRF
$tokenSessao  = $_SESSION['csrf_token'] ?? '';
$tokenEnviado = $_POST['csrf_token'] ?? '';
if ($tokenSessao === '' || !hash_equals($tokenSessao, $tokenEnviado)) {
    responder(false, 'Sessão expirada. Recarregue a página e tente de novo.', 403);
}

require_once '../Config/database.php';
require_once '../Config/notificacoes.php';

$idAluno = (int)$_SESSION['usuario_id'];
$idFicha = filter_input(INPUT_POST, 'id_ficha', FILTER_VALIDATE_INT);
$acao    = $_POST['acao'] ?? '';

if (!$idFicha || !in_array($acao, ['concluir', 'desfazer'], true)) {
    responder(false, 'Dados inválidos.', 400);
}

// Data e dia da semana SEMPRE do servidor (o aluno não escolhe a data)
$hoje    = date('Y-m-d');
$siglas  = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM'];
$diaHoje = $siglas[(int)date('N') - 1];

try {

    // Desmarcar o treino de hoje
    if ($acao === 'desfazer') {
        $stmt = $pdo->prepare("DELETE FROM Treinos_Concluidos WHERE id_aluno = :aluno AND data_treino = :data");
        $stmt->execute([':aluno' => $idAluno, ':data' => $hoje]);

        // Apaga também o aviso enviado ao professor (evita spam ao marcar e desmarcar)
        limparNotificacaoTreinoConcluido($pdo, $idAluno);

        responder(true, 'Treino desmarcado.', 200, ['concluido' => false]);
    }

    // Concluir: a ficha precisa ser do aluno, estar ativa e ter treino hoje.
    // Já busca o professor responsável e o título da ficha para a notificação.
    $sqlValida = "SELECT f.id_professor, f.titulo
                  FROM Fichas_Treino f
                  WHERE f.id_ficha = :ficha
                    AND f.id_aluno = :aluno
                    AND f.data_inicio <= :hoje1
                    AND (f.data_fim IS NULL OR f.data_fim >= :hoje2)
                    AND EXISTS (SELECT 1 FROM Itens_Treino i
                                WHERE i.id_ficha = f.id_ficha AND i.dia_semana = :dia)";
    $stmtValida = $pdo->prepare($sqlValida);
    $stmtValida->execute([
        ':ficha' => $idFicha,
        ':aluno' => $idAluno,
        ':hoje1' => $hoje,
        ':hoje2' => $hoje,
        ':dia'   => $diaHoje
    ]);
    $ficha = $stmtValida->fetch(PDO::FETCH_ASSOC);

    if (!$ficha) {
        responder(false, 'Essa ficha não tem treino para hoje.', 422);
    }

    // Já estava concluído hoje? (para não avisar o professor duas vezes)
    $stmtJa = $pdo->prepare("SELECT COUNT(*) FROM Treinos_Concluidos WHERE id_aluno = :aluno AND data_treino = :data");
    $stmtJa->execute([':aluno' => $idAluno, ':data' => $hoje]);
    $jaConcluido = (int)$stmtJa->fetchColumn() > 0;

    // Se já estava concluído, apenas mantém (não gera erro)
    $stmtInsere = $pdo->prepare("INSERT INTO Treinos_Concluidos (id_aluno, id_ficha, dia_semana, data_treino)
                                 VALUES (:aluno, :ficha, :dia, :data)
                                 ON DUPLICATE KEY UPDATE concluido_em = concluido_em");
    $stmtInsere->execute([
        ':aluno' => $idAluno,
        ':ficha' => $idFicha,
        ':dia'   => $diaHoje,
        ':data'  => $hoje
    ]);

    // NOTIFICAÇÃO: avisa o professor responsável pela ficha (só na primeira vez do dia)
    if (!$jaConcluido && !empty($ficha['id_professor'])) {
        notificarTreinoConcluido(
            $pdo,
            (int)$ficha['id_professor'],
            $idAluno,
            $_SESSION['usuario_nome'] ?? 'Um aluno',
            $ficha['titulo']
        );
    }

    responder(true, 'Treino concluído!', 200, ['concluido' => true]);

} catch (PDOException $e) {
    error_log('Erro ao registrar treino concluído: ' . $e->getMessage());
    responder(false, 'Erro ao salvar. Tente novamente.', 500);
}