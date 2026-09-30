<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

if (($_SESSION['tipo_usuario'] ?? '') !== 'ALUNO') {
    header("Location: ../professor/professor.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../../adm/Config/database.php';

$usuario_id = (int) $_SESSION['usuario_id'];

// 1. Busca a foto de perfil do aluno logado
$dadoAluno = [];
try {
    $stmtAluno = $pdo->prepare("SELECT foto_perfil FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtAluno->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtAluno->execute();
    $dadoAluno = $stmtAluno->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log("Erro ao buscar foto do aluno: " . $e->getMessage());
}

$foto_aluno_banco = $dadoAluno['foto_perfil'] ?? null;
if (!empty($foto_aluno_banco) && file_exists("../../assets/uploads/perfil/" . $foto_aluno_banco)) {
    $foto_perfil = "../../assets/uploads/perfil/" . $foto_aluno_banco;
} else {
    $foto_perfil = "../../assets/imagens/perfil-padrao.png";
}

// 2. Busca as avaliações físicas do aluno
$avaliacoes = [];
try {
    $sqlAval = "SELECT a.*, u.nome AS nome_professor 
                FROM Avaliacoes a
                LEFT JOIN Usuarios u ON a.id_professor = u.id_usuario
                WHERE a.id_aluno = :id_aluno
                ORDER BY a.data_avaliacao DESC";
    $stmt = $pdo->prepare($sqlAval);
    $stmt->bindValue(':id_aluno', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar avaliações físicas: " . $e->getMessage());
}

// Pega a avaliação mais recente para preencher os cards superiores
$ultimaAvaliacao = $avaliacoes[0] ?? null;

// Cálculo das métricas da última avaliação
$pesoAtual = '--';
$gorduraAtual = '--';
$massaMagraAtual = '--';
$imcAtual = '--';

if ($ultimaAvaliacao) {
    $peso = (float) ($ultimaAvaliacao['peso'] ?? 0);
    $altura = (float) ($ultimaAvaliacao['altura'] ?? 0);
    $gordura = (float) ($ultimaAvaliacao['percentual_gordura'] ?? 0);

    if ($peso > 0) {
        $pesoAtual = number_format($peso, 1, ',', '.') . ' kg';
    }

    if ($gordura > 0) {
        $gorduraAtual = number_format($gordura, 1, ',', '.') . '%';
        // Cálculo aproximado de massa magra se houver peso e gordura
        if ($peso > 0) {
            $massaMagra = $peso * (1 - ($gordura / 100));
            $massaMagraAtual = number_format($massaMagra, 1, ',', '.') . ' kg';
        }
    }

    if ($altura > 0 && $peso > 0) {
        $imc = round($peso / ($altura * $altura), 1);
        $imcAtual = number_format($imc, 1, ',', '.');
    }
}

// Array com meses em português para formatar a data por extenso
$meses = [
    '01' => 'Janeiro',
    '02' => 'Fevereiro',
    '03' => 'Março',
    '04' => 'Abril',
    '05' => 'Maio',
    '06' => 'Junho',
    '07' => 'Julho',
    '08' => 'Agosto',
    '09' => 'Setembro',
    '10' => 'Outubro',
    '11' => 'Novembro',
    '12' => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AVALIAÇÕES | Academia Espaço Livre </title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pessoa.css">
    <link rel="stylesheet" href="../../assets/css/notif.css">
    <link rel="stylesheet" href="../../assets/css/avaliacoes.css">
    <link rel="stylesheet" href="../../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../../assets/imagens/favicon/line-chart-line.svg" type="image/x-icon">
</head>

<body>

    <header>
        <nav>
            <div>
                <img src="../../assets/imagens/Logo.png" alt="Logo">

                <div class="links">
                    <a href="aluno.php"> <i class="ri-home-2-line"></i> INÍCIO </a>
                    <a href="treinos.php"> <i class="ri-calendar-line"></i> TREINOS </a>
                    <a href="avaliacoes.php" class="active"> <i class="ri-line-chart-line"></i> AVALIAÇÕES </a>
                    <a href="notif.php" class="link-notificacoes">
                        <span class="icone-notificacao">
                            <i class="ri-notification-3-line"></i>
                            <span class="badge-notificacao" aria-label="Notificações não lidas"></span>
                        </span>
                        NOTIFICAÇÕES
                    </a>
                </div>
            </div>

            <div>
                <a href="../../adm/Controllers/logoutController.php" class="sair">
                    <i class="ri-logout-box-r-line"></i>
                    SAIR
                </a>
            </div>
        </nav>
    </header>

    <main>

        <section class="header">
            <article class="ola">
                <img src="<?php echo $foto_perfil; ?>" class="icon" alt="Foto de Perfil">

                <div>
                    <h2> Minhas <span> Avaliações</span> </h2>
                    <p> Acompanhe a evolução do seu corpo </p>
                </div>
            </article>
        </section>

        <section class="info-avaliacoes">

            <!-- CARDS DE DESTAQUE COM A ÚLTIMA AVALIAÇÃO -->
            <article class="container-avaliacao">

                <div class="card">
                    <i class="ri-scales-3-line"></i>
                    <h4> <?php echo $pesoAtual; ?> </h4>
                    <p> Peso Atual </p>
                </div>

                <div class="card">
                    <i class="ri-drop-line"></i>
                    <h4> <?php echo $gorduraAtual; ?> </h4>
                    <p> Gordura Corporal </p>
                </div>

                <div class="card">
                    <i class="ri-boxing-line"></i>
                    <h4> <?php echo $massaMagraAtual; ?> </h4>
                    <p> Massa Magra </p>
                </div>

                <div class="card">
                    <i class="ri-ruler-line"></i>
                    <h4> <?php echo $imcAtual; ?> </h4>
                    <p> IMC </p>
                </div>

            </article>

            <!-- HISTÓRICO COMPLETO DE AVALIAÇÕES -->
            <article class="box">

                <div class="titulo">
                    <div class="titulo-text">
                        <i class="ri-history-line"></i>
                        <h2> Histórico de Avaliações </h2>
                    </div>
                </div>

                <?php if (!empty($avaliacoes)): ?>
                    <?php foreach ($avaliacoes as $index => $aval):
                        $timestamp = strtotime($aval['data_avaliacao']);
                        $dia = date('d', $timestamp);
                        $mesNum = date('m', $timestamp);
                        $mesNome = $meses[$mesNum] ?? '';

                        $dataFormatada = $dia . ' de ' . $mesNome;
                        $nomeProf = !empty($aval['nome_professor']) ? $aval['nome_professor'] : 'Professor';
                        $pesoItem = (float) ($aval['peso'] ?? 0);
                        $gorduraItem = (float) ($aval['percentual_gordura'] ?? 0);
                        ?>

                        <hr>

                        <div class="linha-avaliacao">
                            <div class="data">
                                <h3> <?php echo $dataFormatada; ?> </h3>
                                <p> Avaliação com Prof. <?php echo htmlspecialchars($nomeProf, ENT_QUOTES, 'UTF-8'); ?> </p>
                            </div>
                            <div class="valores">
                                <p> <?php echo number_format($pesoItem, 1, ',', '.'); ?> kg </p>
                                <p> <?php echo number_format($gorduraItem, 1, ',', '.'); ?>% </p>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <hr>
                    <p style="text-align: center; padding: 1.5rem; color: #666;">
                        Nenhuma avaliação física cadastrada até o momento.
                    </p>
                <?php endif; ?>

            </article>

        </section>

    </main>

    <script src="../../assets/js/notificacoes.js"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>

</html>