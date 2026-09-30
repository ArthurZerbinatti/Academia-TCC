<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// 1. Validação de Autenticação do Aluno
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

// Token CSRF (mesmo nome usado no aluno.php; só cria se ainda não existir)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$usuario_id = (int) $_SESSION['usuario_id'];

// ------------------------------------------------------------
// Funções auxiliares
// ------------------------------------------------------------
function esc($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plural($n, $singular, $plural)
{
    return (int) $n . ' ' . ((int) $n === 1 ? $singular : $plural);
}

// Desenha uma ficha completa (título + um card por dia da semana)
function render_ficha($id_ficha, $ficha, $nomes_dias, $categoria_grupo, $hoje_sigla, $hoje_data)
{
    if ($ficha['encerrada']) {
        $selo = 'Encerrada';
        $selo_classe = 'encerrada';
    } elseif ($ficha['data_inicio'] > $hoje_data) {
        $selo = 'Começa em ' . date('d/m/Y', strtotime($ficha['data_inicio']));
        $selo_classe = 'futura';
    } else {
        $selo = 'Ativa';
        $selo_classe = 'ativa';
    }
    ?>
    <div class="tr-ficha">

        <div class="tr-ficha-cab">
            <h4>
                <?= esc($ficha['titulo']) ?>
                <span class="tr-selo-ficha tr-selo-ficha--<?= $selo_classe ?>"><?= esc($selo) ?></span>
            </h4>
            <p>
                Prof: <?= esc($ficha['professor']) ?>
                · Início: <?= date('d/m/Y', strtotime($ficha['data_inicio'])) ?>
                <?php if (!empty($ficha['data_fim'])): ?>
                    · Fim: <?= date('d/m/Y', strtotime($ficha['data_fim'])) ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if (empty($ficha['dias'])): ?>
            <p class="tr-aviso">Nenhum exercício cadastrado nesta ficha.</p>
        <?php endif; ?>

        <div class="tr-dias">
            <?php foreach ($ficha['dias'] as $sigla => $itens): ?>
                <?php
                $grupos = array_values(array_unique(array_filter(array_column($itens, 'grupo_muscular'))));

                $cats = [];
                foreach ($grupos as $g) {
                    if (isset($categoria_grupo[$g])) {
                        $cats[] = $categoria_grupo[$g];
                    }
                }
                $cats = array_values(array_unique($cats));

                $total_series = array_sum(array_column($itens, 'series'));
                $eh_hoje = ($sigla === $hoje_sigla) && $selo_classe === 'ativa';
                ?>
                <details class="tr-dia<?= $eh_hoje ? ' tr-dia--hoje' : '' ?>" data-grupo="<?= esc(implode(' ', $cats)) ?>">

                    <summary>
                        <div class="tr-dia-nome">
                            <h5>Treino da <?= esc($nomes_dias[$sigla] ?? $sigla) ?><?= $eh_hoje ? ' <em>(hoje)</em>' : '' ?>
                            </h5>
                            <p><?= esc(implode(' • ', $grupos)) ?></p>
                        </div>
                        <div class="tr-dia-meta">
                            <span><i class="ri-list-check-2"></i> <?= plural(count($itens), 'exercício', 'exercícios') ?></span>
                            <span><i class="ri-repeat-line"></i> <?= plural($total_series, 'série', 'séries') ?></span>
                        </div>
                        <i class="ri-arrow-down-s-line tr-seta"></i>
                    </summary>

                    <div class="tr-lista">
                        <?php foreach ($itens as $it): ?>
                            <div class="tr-linha">
                                <p>
                                    <?= esc($it['nome_exercicio']) ?>
                                    <?php if ($it['carga_kg'] !== null && $it['carga_kg'] > 0): ?>
                                        <small><?= (float) $it['carga_kg'] ?> kg · descanso <?= (int) $it['descanso_segundos'] ?>s</small>
                                    <?php endif; ?>
                                </p>
                                <span><?= (int) $it['series'] ?>x<?= esc($it['repeticoes']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </details>
            <?php endforeach; ?>
        </div>

    </div>
    <?php
}

// ------------------------------------------------------------
// 2. Foto de perfil
// ------------------------------------------------------------
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

// ------------------------------------------------------------
// 3. Configurações de exibição
// ------------------------------------------------------------
$siglas = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM'];

$nomes_dias = [
    'SEG' => 'Segunda',
    'TER' => 'Terça',
    'QUA' => 'Quarta',
    'QUI' => 'Quinta',
    'SEX' => 'Sexta',
    'SAB' => 'Sábado',
    'DOM' => 'Domingo'
];

$dias_longos = [
    'SEG' => 'Segunda-feira',
    'TER' => 'Terça-feira',
    'QUA' => 'Quarta-feira',
    'QUI' => 'Quinta-feira',
    'SEX' => 'Sexta-feira',
    'SAB' => 'Sábado',
    'DOM' => 'Domingo'
];

// Categoria usada pelos filtros (Abdómen aparece apenas em "Todos")
$categoria_grupo = [
    'Peito' => 'superiores',
    'Costas' => 'superiores',
    'Ombros' => 'superiores',
    'Bíceps' => 'superiores',
    'Tríceps' => 'superiores',
    'Pernas' => 'inferiores'
];

$hoje_data = date('Y-m-d');
$hoje_sigla = $siglas[(int) date('N') - 1];

// ------------------------------------------------------------
// 4. Fichas + itens (uma única consulta), agrupadas por ficha e por dia
// ------------------------------------------------------------
$fichas = [];
try {
    $sql = "SELECT f.id_ficha, f.titulo, f.data_inicio, f.data_fim,
                   u.nome AS nome_professor,
                   i.dia_semana, i.series, i.repeticoes, i.carga_kg, i.descanso_segundos,
                   e.nome AS nome_exercicio, e.grupo_muscular
            FROM Fichas_Treino f
            LEFT JOIN Usuarios u ON f.id_professor = u.id_usuario
            LEFT JOIN Itens_Treino i ON i.id_ficha = f.id_ficha
            LEFT JOIN Exercicios e ON e.id_exercicio = i.id_exercicio
            WHERE f.id_aluno = :id_aluno
            ORDER BY f.id_ficha DESC,
                     FIELD(i.dia_semana, 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SAB', 'DOM'),
                     i.id_item ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_aluno', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $id = $row['id_ficha'];

        if (!isset($fichas[$id])) {
            $encerrada = !empty($row['data_fim']) && $row['data_fim'] < $hoje_data;
            $futura = $row['data_inicio'] > $hoje_data;

            $fichas[$id] = [
                'titulo' => $row['titulo'],
                'professor' => $row['nome_professor'] ?? 'Geral',
                'data_inicio' => $row['data_inicio'],
                'data_fim' => $row['data_fim'],
                'encerrada' => $encerrada,
                'ativa' => !$encerrada && !$futura,
                'dias' => []
            ];
        }

        if ($row['dia_semana'] !== null) {
            $fichas[$id]['dias'][$row['dia_semana']][] = $row;
        }
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar treinos do aluno: " . $e->getMessage());
}

$fichas_ativas = [];
$fichas_anteriores = [];
foreach ($fichas as $id => $f) {
    if ($f['ativa']) {
        $fichas_ativas[$id] = $f;
    } else {
        $fichas_anteriores[$id] = $f;
    }
}

// ------------------------------------------------------------
// 5. Treino de hoje: vale a ficha ativa mais recente que tenha treino hoje
// ------------------------------------------------------------
$treino_hoje = null;
foreach ($fichas_ativas as $id => $f) {
    if (!empty($f['dias'][$hoje_sigla])) {
        $treino_hoje = [
            'id_ficha' => $id,
            'titulo' => $f['titulo'],
            'itens' => $f['dias'][$hoje_sigla]
        ];
        break;
    }
}

// ------------------------------------------------------------
// 6. Treinos concluídos na semana atual (segunda a domingo)
// ------------------------------------------------------------
$offset_segunda = (int) date('N') - 1;
$segunda_ts = strtotime("-{$offset_segunda} days", strtotime($hoje_data));
$inicio_semana = date('Y-m-d', $segunda_ts);
$fim_semana = date('Y-m-d', strtotime("+6 days", $segunda_ts));

$concluidos = [];
try {
    $stmtC = $pdo->prepare("SELECT data_treino FROM Treinos_Concluidos
                            WHERE id_aluno = :id AND data_treino BETWEEN :ini AND :fim");
    $stmtC->execute([':id' => $usuario_id, ':ini' => $inicio_semana, ':fim' => $fim_semana]);
    foreach ($stmtC->fetchAll(PDO::FETCH_COLUMN) as $data) {
        $concluidos[$data] = true;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar treinos concluídos: " . $e->getMessage());
}

$hoje_concluido = isset($concluidos[$hoje_data]);

// Status de cada dia da semana
$semana = [];
$total_planejados = 0;
$total_feitos = 0;

foreach ($siglas as $idx => $sigla) {
    $data = date('Y-m-d', strtotime("+{$idx} days", $segunda_ts));
    $feito = isset($concluidos[$data]);

    // O dia está planejado se alguma ficha cobre essa data e tem treino nesse dia da semana
    $planejado = false;
    foreach ($fichas as $f) {
        if (
            !empty($f['dias'][$sigla])
            && $f['data_inicio'] <= $data
            && (empty($f['data_fim']) || $f['data_fim'] >= $data)
        ) {
            $planejado = true;
            break;
        }
    }

    if ($sigla === 'DOM' && !$planejado && !$feito) {
        continue; // domingo só aparece se houver treino
    }

    if ($feito) {
        $status = 'feito';
    } elseif ($data === $hoje_data) {
        $status = $planejado ? 'hoje' : 'descanso';
    } elseif ($planejado) {
        $status = ($data < $hoje_data) ? 'perdido' : 'proximo';
    } else {
        $status = 'descanso';
    }

    if ($planejado) {
        $total_planejados++;
    }
    if ($feito) {
        $total_feitos++;
    }

    $semana[] = ['rotulo' => $sigla === 'SAB' ? 'SÁB' : $sigla, 'status' => $status];
}

$total_planejados = max($total_planejados, $total_feitos);
$percentual = $total_planejados > 0 ? round(($total_feitos / $total_planejados) * 100) : 0;

$icones_status = [
    'feito' => 'ri-check-line',
    'hoje' => 'ri-time-line',
    'proximo' => 'ri-time-line',
    'perdido' => 'ri-close-line',
    'descanso' => 'ri-subtract-line'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEUS TREINOS | Academia Espaço Livre</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pessoa.css">
    <link rel="stylesheet" href="../../assets/css/notif.css">
    <link rel="stylesheet" href="../../assets/css/treinos-aluno.css">
    <link rel="stylesheet" href="../../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../../assets/imagens/favicon/calendar-2-fill.svg" type="image/x-icon">
</head>

<body>

    <header>
        <nav>
            <div>
                <img src="../../assets/imagens/Logo.png" alt="Logo">

                <div class="links">
                    <a href="aluno.php"> <i class="ri-home-2-line"></i> INÍCIO </a>
                    <a href="treinos.php" class="active"> <i class="ri-calendar-line"></i> TREINOS </a>
                    <a href="avaliacoes.php"> <i class="ri-line-chart-line"></i> AVALIAÇÕES </a>
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
                <img src="<?= esc($foto_perfil) ?>" class="icon" alt="Foto de Perfil">

                <div>
                    <h2> Meus <span>Treinos</span> </h2>
                    <p> Acompanhe sua semana e não perca o ritmo </p>
                </div>
            </article>
        </section>

        <div class="tr-pagina">

            <?php if (empty($fichas)): ?>

                <section class="tr-card tr-vazio">
                    <i class="ri-dumbbell-line"></i>
                    <p>Você ainda não possui fichas de treino cadastradas.</p>
                    <small>Solicite ao seu professor para montar sua ficha personalizada!</small>
                </section>

            <?php else: ?>

                <!-- ========== TREINO DE HOJE ========== -->
                <?php if ($treino_hoje): ?>
                    <?php
                    $grupos_hoje = array_values(array_unique(array_filter(array_column($treino_hoje['itens'], 'grupo_muscular'))));
                    ?>
                    <section class="tr-hoje<?= $hoje_concluido ? ' tr-hoje--feito' : '' ?>" id="tr-hoje"
                        data-ficha="<?= (int) $treino_hoje['id_ficha'] ?>" data-csrf="<?= esc($csrf_token) ?>">

                        <div class="tr-hoje-topo">
                            <div>
                                <span class="tr-etiqueta"><?= $hoje_concluido ? 'TREINO CONCLUÍDO' : 'TREINO DE HOJE' ?></span>
                                <h2><?= esc($dias_longos[$hoje_sigla]) ?> · <?= date('d/m') ?></h2>
                                <p><?= esc($treino_hoje['titulo']) ?><?= $grupos_hoje ? ' · ' . esc(implode(' • ', $grupos_hoje)) : '' ?>
                                </p>
                            </div>
                            <div class="tr-selo">
                                <i class="<?= $hoje_concluido ? 'ri-check-line' : 'ri-time-line' ?>"></i>
                            </div>
                        </div>

                        <div class="tr-lista">
                            <?php foreach ($treino_hoje['itens'] as $it): ?>
                                <div class="tr-linha">
                                    <p>
                                        <?= esc($it['nome_exercicio']) ?>
                                        <?php if ($it['carga_kg'] !== null && $it['carga_kg'] > 0): ?>
                                            <small><?= (float) $it['carga_kg'] ?> kg · descanso
                                                <?= (int) $it['descanso_segundos'] ?>s</small>
                                        <?php endif; ?>
                                    </p>
                                    <span><?= (int) $it['series'] ?>x<?= esc($it['repeticoes']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($hoje_concluido): ?>
                            <div class="tr-rodape">
                                <p class="tr-parabens"><i class="ri-trophy-line"></i> Treino concluído. Bom trabalho!</p>
                                <button type="button" class="tr-btn tr-btn--desfazer" data-acao="desfazer">
                                    <i class="ri-arrow-go-back-line"></i> Desfazer
                                </button>
                            </div>
                        <?php else: ?>
                            <button type="button" class="tr-btn tr-btn--concluir" data-acao="concluir">
                                <i class="ri-check-double-line"></i> Concluir treino
                            </button>
                        <?php endif; ?>

                    </section>

                <?php else: ?>

                    <section class="tr-hoje tr-hoje--descanso">
                        <div class="tr-hoje-topo">
                            <div>
                                <span class="tr-etiqueta tr-etiqueta--suave">HOJE</span>
                                <h2><?= esc($dias_longos[$hoje_sigla]) ?> · <?= date('d/m') ?></h2>
                                <?php if (empty($fichas_ativas)): ?>
                                    <p>Você não tem uma ficha ativa no momento. Fale com seu professor.</p>
                                <?php else: ?>
                                    <p>Hoje é dia de descanso. Aproveite para recuperar!</p>
                                <?php endif; ?>
                            </div>
                            <div class="tr-selo"><i class="ri-moon-line"></i></div>
                        </div>
                    </section>

                <?php endif; ?>

                <!-- ========== SEMANA ========== -->
                <section class="tr-card tr-semana">
                    <div class="tr-semana-topo">
                        <h3>Sua semana</h3>
                        <span><?= (int) $total_feitos ?> de <?= (int) $total_planejados ?> treinos</span>
                    </div>

                    <div class="tr-barra">
                        <div class="tr-barra-preenchida" style="width: <?= (int) $percentual ?>%;"></div>
                    </div>

                    <div class="tr-semana-dias">
                        <?php foreach ($semana as $d): ?>
                            <div class="tr-sem tr-sem--<?= esc($d['status']) ?>">
                                <strong><?= esc($d['rotulo']) ?></strong>
                                <i class="<?= $icones_status[$d['status']] ?>"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- ========== FICHAS ========== -->
                <section class="tr-fichas">

                    <div class="tr-fichas-topo">
                        <h3>Minhas fichas</h3>

                        <div class="tr-filtros">
                            <button type="button" class="tr-filtro ativo" data-grupo="todos"> Todos </button>
                            <button type="button" class="tr-filtro" data-grupo="superiores"> Superiores </button>
                            <button type="button" class="tr-filtro" data-grupo="inferiores"> Inferiores </button>
                        </div>
                    </div>

                    <?php foreach ($fichas_ativas as $id_ficha => $ficha): ?>
                        <?php render_ficha($id_ficha, $ficha, $nomes_dias, $categoria_grupo, $hoje_sigla, $hoje_data); ?>
                    <?php endforeach; ?>

                    <?php if (!empty($fichas_anteriores)): ?>
                        <details class="tr-anteriores">
                            <summary>
                                <i class="ri-history-line"></i>
                                Fichas anteriores ou futuras (<?= count($fichas_anteriores) ?>)
                            </summary>

                            <?php foreach ($fichas_anteriores as $id_ficha => $ficha): ?>
                                <?php render_ficha($id_ficha, $ficha, $nomes_dias, $categoria_grupo, $hoje_sigla, $hoje_data); ?>
                            <?php endforeach; ?>
                        </details>
                    <?php endif; ?>

                </section>

            <?php endif; ?>

        </div>

    </main>

    <script src="../../assets/js/treinos-aluno.js"></script>
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