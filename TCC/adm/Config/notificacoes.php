<?php
/**
 * CENTRAL DE NOTIFICAÇÕES
 *
 * Uso nos controllers:
 *   require_once '../Config/notificacoes.php';
 *   notificarNovoAluno($pdo, $idNovoAluno, $nome);
 *
 * Regra de ouro: NENHUMA função aqui derruba a página. Se a notificação falhar
 * por QUALQUER motivo, o erro vai para o log e a ação principal
 * (cadastro, treino...) continua normalmente.
 */

// Corta e limpa o texto para caber nas colunas do banco.
// Funciona com ou sem a extensão mbstring do PHP.
function notificacaoTexto($texto, $limite)
{
    $texto  = trim((string)$texto);
    $limite = (int)$limite;

    if (function_exists('mb_substr')) {
        return mb_substr($texto, 0, $limite, 'UTF-8');
    }

    // Sem mbstring: corta por caracteres UTF-8 usando expressão regular
    if (preg_match('/^.{0,' . $limite . '}/us', $texto, $trecho)) {
        return $trecho[0];
    }

    return substr($texto, 0, $limite);
}

// Envia uma notificação para UMA pessoa
function notificarUsuario($pdo, $idDestinatario, $tipo, $titulo, $mensagem, $idOrigem = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO Notificacoes (id_usuario, id_origem, tipo, titulo, mensagem)
                               VALUES (:destinatario, :origem, :tipo, :titulo, :mensagem)");
        $stmt->execute([
            ':destinatario' => (int)$idDestinatario,
            ':origem'       => $idOrigem !== null ? (int)$idOrigem : null,
            ':tipo'         => $tipo,
            ':titulo'       => notificacaoTexto($titulo, 100),
            ':mensagem'     => notificacaoTexto($mensagem, 255)
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('Erro ao criar notificação: ' . $e->getMessage());
        return false;
    }
}

// Envia para UM GRUPO: 'ALUNO', 'PROFESSOR' ou null (todos).
// Quem causou a ação ($idOrigem) nunca recebe o próprio aviso.
function notificarGrupo($pdo, $tipoUsuario, $tipo, $titulo, $mensagem, $idOrigem = null)
{
    try {
        $sql = "INSERT INTO Notificacoes (id_usuario, id_origem, tipo, titulo, mensagem)
                SELECT u.id_usuario, :origem, :tipo, :titulo, :mensagem
                FROM Usuarios u
                WHERE 1 = 1";

        $params = [
            ':origem'   => $idOrigem !== null ? (int)$idOrigem : null,
            ':tipo'     => $tipo,
            ':titulo'   => notificacaoTexto($titulo, 100),
            ':mensagem' => notificacaoTexto($mensagem, 255)
        ];

        if ($tipoUsuario !== null) {
            $sql .= " AND u.tipo_usuario = :tipo_usuario";
            $params[':tipo_usuario'] = $tipoUsuario;
        }

        if ($idOrigem !== null) {
            $sql .= " AND u.id_usuario <> :exceto";
            $params[':exceto'] = (int)$idOrigem;
        }

        $pdo->prepare($sql)->execute($params);
        return true;
    } catch (Throwable $e) {
        error_log('Erro ao notificar grupo: ' . $e->getMessage());
        return false;
    }
}

// ------------------------------------------------------------
// Um atalho para cada evento do sistema
// ------------------------------------------------------------

// Professor novo cadastrado -> TODOS (alunos e professores), menos ele mesmo
function notificarNovoProfessor($pdo, $idNovoProfessor, $nome)
{
    return notificarGrupo(
        $pdo, null, 'novo_professor',
        'Novo professor na equipe',
        'Prof. ' . $nome . ' entrou para a equipe da academia.',
        $idNovoProfessor
    );
}

// Aluno novo cadastrado -> todos os PROFESSORES
function notificarNovoAluno($pdo, $idNovoAluno, $nome)
{
    return notificarGrupo(
        $pdo, 'PROFESSOR', 'novo_aluno',
        'Novo aluno',
        $nome . ' acabou de entrar na academia.',
        $idNovoAluno
    );
}

// Professor fez uma avaliação física -> o ALUNO avaliado
function notificarNovaAvaliacao($pdo, $idAluno, $nomeProfessor, $idProfessor = null)
{
    return notificarUsuario(
        $pdo, $idAluno, 'avaliacao',
        'Nova avaliação física',
        'Prof. ' . $nomeProfessor . ' registrou uma nova avaliação física para você.',
        $idProfessor
    );
}

// Professor montou uma ficha de treino -> o ALUNO da ficha
function notificarNovoTreino($pdo, $idAluno, $idProfessor, $nomeProfessor, $tituloFicha)
{
    return notificarUsuario(
        $pdo, $idAluno, 'treino',
        'Novo treino disponível',
        'Prof. ' . $nomeProfessor . ' montou a ficha "' . $tituloFicha . '" para você.',
        $idProfessor
    );
}

// Aluno concluiu o treino de hoje -> o PROFESSOR responsável pela ficha
function notificarTreinoConcluido($pdo, $idProfessor, $idAluno, $nomeAluno, $tituloFicha)
{
    return notificarUsuario(
        $pdo, $idProfessor, 'treino_concluido',
        'Treino concluído',
        $nomeAluno . ' concluiu o treino de hoje (' . $tituloFicha . ').',
        $idAluno
    );
}

// Aluno desfez a conclusão -> apaga o aviso de hoje (evita spam ao marcar e desmarcar)
function limparNotificacaoTreinoConcluido($pdo, $idAluno)
{
    try {
        $stmt = $pdo->prepare("DELETE FROM Notificacoes
                               WHERE tipo = 'treino_concluido'
                                 AND id_origem = :aluno
                                 AND data_criacao >= CURDATE()");
        $stmt->execute([':aluno' => (int)$idAluno]);
    } catch (Throwable $e) {
        error_log('Erro ao limpar notificação: ' . $e->getMessage());
    }
}