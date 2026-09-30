<?php
session_start();

require_once '../Config/database.php';
require_once '../Config/notificacoes.php';

if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'PROFESSOR') {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idAluno     = filter_input(INPUT_POST, 'id_aluno', FILTER_VALIDATE_INT);
    $idProfessor = (int)$_SESSION['usuario_id'];
    $titulo      = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
    $dataInicio  = $_POST['data_inicio'] ?? null;
    $dataFim     = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;

    if (!$idAluno || !$idProfessor || empty($titulo) || empty($dataInicio)) {
        echo "<h3 style='color:red'>Erro de Validação:</h3>";
        echo "id_aluno: " . var_export($idAluno, true) . "<br>";
        echo "id_professor: " . var_export($idProfessor, true) . "<br>";
        echo "titulo: " . var_export($titulo, true) . "<br>";
        echo "data_inicio: " . var_export($dataInicio, true) . "<br>";
        exit;
    }

    $exercicios = $_POST['id_exercicio'] ?? $_POST['exercicio_id'] ?? [];
    $dias       = $_POST['dia_semana'] ?? [];
    $series     = $_POST['series'] ?? [];
    $repeticoes = $_POST['repeticoes'] ?? [];
    $cargas     = $_POST['carga_kg'] ?? [];
    $descansos  = $_POST['descanso_segundos'] ?? [];

    if (empty($exercicios)) {
        echo "<h3 style='color:red'>Erro: Nenhum exercício foi recebido pelo formulário.</h3>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        $sqlFicha = "INSERT INTO Fichas_Treino (id_aluno, id_professor, titulo, data_inicio, data_fim) 
                     VALUES (:id_aluno, :id_professor, :titulo, :data_inicio, :data_fim)";
        
        $stmtFicha = $pdo->prepare($sqlFicha);
        $stmtFicha->execute([
            ':id_aluno'     => $idAluno,
            ':id_professor' => $idProfessor,
            ':titulo'       => $titulo,
            ':data_inicio'  => $dataInicio,
            ':data_fim'     => $dataFim
        ]);

        $idFichaCriada = $pdo->lastInsertId();

        $sqlItem = "INSERT INTO Itens_Treino 
                    (id_ficha, id_exercicio, dia_semana, series, repeticoes, carga_kg, descanso_segundos) 
                    VALUES (:id_ficha, :id_exercicio, :dia_semana, :series, :repeticoes, :carga_kg, :descanso_segundos)";
        
        $stmtItem = $pdo->prepare($sqlItem);

        $diasValidos = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM'];

        for ($i = 0; $i < count($exercicios); $i++) {
            $idExercicio = filter_var($exercicios[$i], FILTER_VALIDATE_INT);
            if (!$idExercicio) continue;

            $diaSemana = strtoupper(trim($dias[$i] ?? 'SEG'));
            if (!in_array($diaSemana, $diasValidos)) {
                $diaSemana = 'SEG';
            }

            $stmtItem->execute([
                ':id_ficha'          => $idFichaCriada,
                ':id_exercicio'      => $idExercicio,
                ':dia_semana'        => $diaSemana,
                ':series'            => !empty($series[$i]) ? (int)$series[$i] : 3,
                ':repeticoes'        => !empty($repeticoes[$i]) ? trim($repeticoes[$i]) : '10',
                ':carga_kg'          => ($cargas[$i] !== '' && $cargas[$i] !== null) ? (float)$cargas[$i] : null,
                ':descanso_segundos' => !empty($descansos[$i]) ? (int)$descansos[$i] : 60
            ]);
        }

        $pdo->commit();

        // NOTIFICAÇÃO: avisa o aluno que ganhou uma ficha nova.
        // (fica depois do commit e nunca interrompe o fluxo se falhar)
        notificarNovoTreino(
            $pdo,
            $idAluno,
            $idProfessor,
            $_SESSION['usuario_nome'] ?? 'Seu professor',
            html_entity_decode($titulo, ENT_QUOTES, 'UTF-8')
        );

        // Redirecionamento limpo para evitar duplo ?erro na URL
        header('Location: ../../Private/professor/alunos.php?sucesso=treino_salvo');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // MOSTRA O ERRO EXATO DO BANCO NA TELA
        echo "<div style='background:#111; color:#ff4444; padding:20px; font-family:monospace;'>";
        echo "<h2>Erro SQL ao Salvar Treino:</h2>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        exit;
    }

} else {
    header('Location: ../../Private/professor/alunos.php');
    exit;
}