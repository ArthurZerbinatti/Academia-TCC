<?php
session_start();

// 1. Validação de Autenticação
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

// 2. Validação Exclusiva de Nível de Acesso (Apenas PROFESSOR)
if (($_SESSION['tipo_usuario'] ?? '') !== 'PROFESSOR') {
    header("Location: ../aluno/aluno.php");
    exit();
}

// 3. Cabeçalhos Anti-Cache (IMPEDE O BOTÃO VOLTAR DO NAVEGADOR)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

require_once '../../adm/Config/database.php';

// 4. Sanitização dos Dados da Sessão do Professor
$usuario_id   = (int)$_SESSION['usuario_id'];
$nome_user    = htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8');
$email_user   = htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8');
$tel_user     = htmlspecialchars($_SESSION['usuario_telefone'] ?? '', ENT_QUOTES, 'UTF-8');
$cref_user    = htmlspecialchars($_SESSION['cref'] ?? 'Não informado', ENT_QUOTES, 'UTF-8');
$esp_user     = htmlspecialchars($_SESSION['especializacao'] ?? 'Geral', ENT_QUOTES, 'UTF-8');

$primeiro_nome = explode(' ', trim($nome_user))[0];

// Busca a foto de perfil do professor no Banco de Dados
$dadoUsuario = [];
try {
    $stmtUser = $pdo->prepare("SELECT foto_perfil FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtUser->execute();
    $dadoUsuario = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log("Erro ao buscar foto do professor no cronograma: " . $e->getMessage());
}

// Lógica de foto de perfil personalizada ou genérica
$foto_banco = $dadoUsuario['foto_perfil'] ?? null;

if (!empty($foto_banco) && file_exists("../../assets/uploads/perfil/" . $foto_banco)) {
    $foto_perfil = "../../assets/uploads/perfil/" . $foto_banco;
} else {
    $foto_perfil = "../../assets/imagens/perfil-padrao.png";
}

// 5. Dados do cronograma
// Modalidades disponíveis para o formulário "Nova Aula"
$modalidades = [];

try {
    $stmtModalidades = $pdo->query("
        SELECT id_modalidade, nome
        FROM Modalidades
        ORDER BY nome ASC
    ");
    $modalidades = $stmtModalidades->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar modalidades no cronograma: " . $e->getMessage());
}

// Aulas do professor para o dia atual
$aulas_hoje = [];

$mapa_dias = [
    1 => 'DOM',
    2 => 'SEG',
    3 => 'TER',
    4 => 'QUA',
    5 => 'QUI',
    6 => 'SEX',
    7 => 'SAB'
];

$dia_hoje = $mapa_dias[(int)date('w') + 1] ?? 'SEG';

try {
    $sqlAulas = "
        SELECT
            a.id_aula,
            a.nome_turma,
            a.horario_inicio,
            a.horario_fim,
            a.capacidade,
            m.nome AS modalidade,
            COUNT(ag.id_agendamento) AS total_agendados
        FROM Aulas a
        INNER JOIN Modalidades m
            ON m.id_modalidade = a.id_modalidade
        LEFT JOIN Agendamentos_Aula ag
            ON ag.id_aula = a.id_aula
            AND ag.data_agendamento = CURDATE()
        WHERE a.id_professor = :id_prof
          AND a.dia_semana = :dia_semana
          AND a.ativa = 1
        GROUP BY
            a.id_aula,
            a.nome_turma,
            a.horario_inicio,
            a.horario_fim,
            a.capacidade,
            m.nome
        ORDER BY a.horario_inicio ASC
    ";

    $stmtAulas = $pdo->prepare($sqlAulas);
    $stmtAulas->bindValue(':id_prof', $usuario_id, PDO::PARAM_INT);
    $stmtAulas->bindValue(':dia_semana', $dia_hoje, PDO::PARAM_STR);
    $stmtAulas->execute();
    $aulas_hoje = $stmtAulas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar aulas do professor no cronograma: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRONOGRAMA | Academia Espaço Livre</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/notif.css">
    <link rel="stylesheet" href="../../assets/css/pessoa.css">
    <link rel="stylesheet" href="../../assets/css/calendario.css">
    <link rel="stylesheet" href="../../assets/css/cronograma.css">
    <link rel="stylesheet" href="../../assets/css/modal.css">
    <link rel="stylesheet" href="../../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../../assets/imagens/favicon/calendar-2-fill.svg" type="image/x-icon">


</head>
<body>

    <header>
        <nav>
            <div>
                <img src="../../assets/imagens/Logo.png" alt="Logo">

                <div class="links">
                    <a href="professor.php"> <i class="ri-home-2-line"></i> INÍCIO </a>
                    <a href="cronograma.php" class="active"> <i class="ri-calendar-line"></i> CRONOGRAMA </a>
                    <a href="alunos.php"> <i class="ri-team-line"></i> ALUNOS </a>
                    <a href="notif-prof.php" class="link-notificacoes">
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
                <!-- Foto de perfil dinâmica (upload do professor ou padrão se NULL) -->
                <img src="<?php echo $foto_perfil; ?>" class="icon" alt="Foto de Perfil">

                <div>
                    <h2> Seu <span>Cronograma</span> </h2>
                    <p> Organize suas aulas e horários da semana </p>
                </div>
            </article>
        </section>

        <section class="info-cronograma">

            <article class="box calendario-box">
                <div class="titulo">
                    <div class="titulo-text">
                        <i class="ri-calendar-2-line"></i>
                        <h2> Agenda </h2>
                    </div>

                    <div class="trocar">
                        <button type="button"
                                id="btnNovaAula"
                                class="btn-troca"
                                onclick="abrirModal('modal-nova-aula')">
                            <i class="ri-add-line"></i> NOVA AULA
                        </button>

                        <button id="btnMes" class="btn-troca"> MÊS </button>
                        <button id="btnSemana" class="btn-troca"> SEMANA </button>
                        <button id="btnDia" class="btn-troca"> DIA </button>
                    </div>
                </div>

                <hr>

                <div class="calendario">
                    <div class="mes">
                        <button id="anterior" class="btn-mes"> <i class="ri-arrow-left-s-fill"></i> </button>
                        <h2 id="titulo"></h2>
                        <button id="proximo" class="btn-mes"> <i class="ri-arrow-right-s-fill"></i> </button>
                    </div>

                    <div id="calendario"></div>
                </div>
            </article>

            <article class="box aulas-box">
                <div class="titulo">
                    <div class="titulo-text">
                        <i class="ri-list-check"></i>
                        <h2> Aulas de Hoje </h2>
                    </div>
                </div>

                <hr>

                <?php if (!empty($aulas_hoje)): ?>
                    <?php foreach ($aulas_hoje as $index => $aula): ?>
                        <?php
                            $inicio = date('H:i', strtotime($aula['horario_inicio']));
                            $fim = date('H:i', strtotime($aula['horario_fim']));
                            $capacidade = $aula['capacidade'];
                            $agendados = (int)$aula['total_agendados'];
                        ?>
                        <div class="aula">
                            <div class="horario">
                                <h3><?php echo $inicio; ?></h3>
                                <p>até <?php echo $fim; ?></p>
                            </div>

                            <div class="aula-info">
                                <h4>
                                    <?php echo htmlspecialchars($aula['modalidade'], ENT_QUOTES, 'UTF-8'); ?>
                                </h4>
                                <p>
                                    <?php echo htmlspecialchars($aula['nome_turma'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($capacidade !== null): ?>
                                        · <?php echo $agendados; ?>/<?php echo (int)$capacidade; ?> alunos
                                    <?php else: ?>
                                        · <?php echo $agendados; ?> alunos
                                    <?php endif; ?>
                                </p>
                            </div>

                            <p class="status confirmada">ATIVA</p>
                        </div>

                        <?php if ($index < count($aulas_hoje) - 1): ?>
                            <hr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="aula aula-vazia">
                        <div class="aula-info">
                            <h4>Nenhuma aula hoje.</h4>
                            <p>Não há aulas cadastradas para você neste dia.</p>
                        </div>
                    </div>
                <?php endif; ?>

            </article>

        </section>


        <!-- Modal: Nova Aula -->
        <div id="modal-nova-aula" class="modal-fundo">

            <div class="modal">

                <div class="modal-topo">
                    <h2>Nova Aula</h2>

                    <button type="button"
                            class="modal-fechar"
                            aria-label="Fechar modal">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <form action="../../adm/Controllers/cronogramaController.php"
                      method="POST"
                      id="formNovaAula">

                    <div class="modal-corpo">

                        <label for="id_modalidade">
                            Modalidade

                            <div class="campo">
                                <select name="id_modalidade"
                                        id="id_modalidade"
                                        required>
                                    <option value="">Selecione uma modalidade</option>

                                    <?php foreach ($modalidades as $modalidade): ?>
                                        <option value="<?php echo (int)$modalidade['id_modalidade']; ?>">
                                            <?php echo htmlspecialchars($modalidade['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </label>

                        <label for="nome_turma">
                            Nome da turma

                            <div class="campo">
                                <input type="text"
                                       name="nome_turma"
                                       id="nome_turma"
                                       maxlength="100"
                                       placeholder="Ex.: Turma A"
                                       required>
                            </div>
                        </label>

                        <label for="dia_semana">
                            Dia da semana

                            <div class="campo">
                                <select name="dia_semana"
                                        id="dia_semana"
                                        required>
                                    <option value="">Selecione o dia</option>
                                    <option value="SEG">Segunda-feira</option>
                                    <option value="TER">Terça-feira</option>
                                    <option value="QUA">Quarta-feira</option>
                                    <option value="QUI">Quinta-feira</option>
                                    <option value="SEX">Sexta-feira</option>
                                    <option value="SAB">Sábado</option>
                                    <option value="DOM">Domingo</option>
                                </select>
                            </div>
                        </label>

                        <label for="capacidade">
                            Capacidade

                            <div class="campo">
                                <input type="number"
                                       name="capacidade"
                                       id="capacidade"
                                       min="1"
                                       max="999"
                                       placeholder="Ex.: 15"
                                       required>
                            </div>
                        </label>

                        <label for="horario_inicio">
                            Horário de início

                            <div class="campo">
                                <input type="time"
                                       name="horario_inicio"
                                       id="horario_inicio"
                                       required>
                            </div>
                        </label>

                        <label for="horario_fim">
                            Horário de término

                            <div class="campo">
                                <input type="time"
                                       name="horario_fim"
                                       id="horario_fim"
                                       required>
                            </div>
                        </label>

                    </div>

                    <div class="modal-rodape">

                        <button type="button"
                                class="modal-cancelar">
                            Cancelar
                        </button>

                        <button type="submit"
                                class="modal-salvar">
                            <i class="ri-save-line"></i>
                            Salvar aula
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

    <script src="../../assets/js/calendario.js"></script>
    <script src="../../assets/js/modal.js"></script>
    <script src="../../assets/js/notificacoes.js"></script>

    <script>
        // Validação específica do formulário de nova aula.
        document.addEventListener('DOMContentLoaded', () => {
            const formNovaAula = document.getElementById('formNovaAula');

            if (formNovaAula) {
                formNovaAula.addEventListener('submit', (event) => {
                    const inicio = document.getElementById('horario_inicio').value;
                    const fim = document.getElementById('horario_fim').value;

                    if (inicio && fim && inicio >= fim) {
                        event.preventDefault();
                        alert('O horário de término deve ser posterior ao horário de início.');
                    }
                });
            }
        });
    </script>

    <script>
        // Prevenção do Botão "Voltar" do Navegador (Força validação da sessão no servidor)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>