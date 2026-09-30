<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder($ok, $mensagem, $http = 200, $extra = [])
{
    http_response_code($http);
    echo json_encode(array_merge(['ok' => $ok, 'mensagem' => $mensagem], $extra));
    exit;
}

// Qualquer usuário logado (aluno ou professor) pode usar
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    responder(false, 'Acesso não autorizado.', 403);
}

require_once '../Config/database.php';

$idUsuario = (int)$_SESSION['usuario_id'];
$acao      = $_POST['acao'] ?? $_GET['acao'] ?? '';

function contarNaoLidas($pdo, $idUsuario)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Notificacoes WHERE id_usuario = :id AND lida = 0");
    $stmt->execute([':id' => $idUsuario]);
    return (int)$stmt->fetchColumn();
}

try {

    // Somente leitura: não precisa de CSRF (usado pela bolinha do menu)
    if ($acao === 'contar') {
        responder(true, '', 200, ['nao_lidas' => contarNaoLidas($pdo, $idUsuario)]);
    }

    // Daqui para baixo altera dados: só POST + token CSRF
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(false, 'Método não permitido.', 405);
    }

    $tokenSessao  = $_SESSION['csrf_token'] ?? '';
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    if ($tokenSessao === '' || !hash_equals($tokenSessao, $tokenEnviado)) {
        responder(false, 'Sessão expirada. Recarregue a página.', 403);
    }

    if ($acao === 'ler') {
        $idNotificacao = filter_input(INPUT_POST, 'id_notificacao', FILTER_VALIDATE_INT);
        if (!$idNotificacao) {
            responder(false, 'Dados inválidos.', 400);
        }

        // O "AND id_usuario" garante que ninguém mexe na notificação de outra pessoa
        $stmt = $pdo->prepare("UPDATE Notificacoes SET lida = 1
                               WHERE id_notificacao = :id AND id_usuario = :usuario");
        $stmt->execute([':id' => $idNotificacao, ':usuario' => $idUsuario]);

        responder(true, 'Notificação lida.', 200, ['nao_lidas' => contarNaoLidas($pdo, $idUsuario)]);
    }

    if ($acao === 'ler_todas') {
        $stmt = $pdo->prepare("UPDATE Notificacoes SET lida = 1 WHERE id_usuario = :usuario AND lida = 0");
        $stmt->execute([':usuario' => $idUsuario]);

        responder(true, 'Todas marcadas como lidas.', 200, ['nao_lidas' => 0]);
    }

    responder(false, 'Ação inválida.', 400);

} catch (PDOException $e) {
    error_log('Erro no notificacaoController: ' . $e->getMessage());
    responder(false, 'Erro ao processar. Tente novamente.', 500);
}