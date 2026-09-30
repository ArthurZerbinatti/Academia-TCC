<?php
session_start();

if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

if (($_SESSION['tipo_usuario'] ?? '') !== 'PROFESSOR') {
    header("Location: ../professor/professor.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../../adm/Config/database.php';

// Token CSRF usado pelo botão "Marcar todas como lidas"
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuario_id = (int)$_SESSION['usuario_id'];

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

// Lógica para a foto do aluno (Upload ou Genérica)
$foto_aluno_banco = $dadoAluno['foto_perfil'] ?? null;
if (!empty($foto_aluno_banco) && file_exists("../../assets/uploads/perfil/" . $foto_aluno_banco)) {
    $foto_perfil = "../../assets/uploads/perfil/" . $foto_aluno_banco;
} else {
    $foto_perfil = "../../assets/imagens/perfil-padrao.png";
}

// Recebe os segundos calculados pelo próprio MySQL (evita erro de fuso entre PHP e banco)
function tempoRelativo($segundos) {
    $segundos = max(0, (int)$segundos);

    if ($segundos < 60) {
        return "Há poucos segundos";
    } elseif ($segundos < 3600) {
        $mins = floor($segundos / 60);
        return "Há {$mins} min" . ($mins > 1 ? 's' : '');
    } elseif ($segundos < 86400) {
        $horas = floor($segundos / 3600);
        return "Há {$horas} hora" . ($horas > 1 ? 's' : '');
    }

    $dias = floor($segundos / 86400);
    return "Há {$dias} dia" . ($dias > 1 ? 's' : '');
}

$notificacoes = [];
try {
    $sqlNotif = "SELECT id_notificacao, titulo, mensagem, tipo, lida, data_criacao,
                        TIMESTAMPDIFF(SECOND, data_criacao, NOW()) AS segundos
                 FROM Notificacoes 
                 WHERE id_usuario = :id_user 
                 ORDER BY data_criacao DESC, id_notificacao DESC
                 LIMIT 50";
    $stmt = $pdo->prepare($sqlNotif);
    $stmt->bindValue(':id_user', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao carregar notificações do aluno: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOTIFICAÇÕES | Academia Espaço Livre</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pessoa.css">
    <link rel="stylesheet" href="../../assets/css/notif.css">
    <link rel="stylesheet" href="../../assets/css/notificacoes.css">
    <link rel="stylesheet" href="../../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../../assets/imagens/favicon/notification-3-fill.svg" type="image/x-icon">
</head>
<body>

    <header>
        <nav>
            <div>
                <img src="../../assets/imagens/Logo.png" alt="Logo">

                <div class="links">
                    <a href="professor.php"> <i class="ri-home-2-line"></i> INÍCIO </a>
                    <a href="cronograma.php"> <i class="ri-calendar-line"></i> CRONOGRAMA </a>
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
                <!-- Foto de Perfil Dinâmica do Aluno -->
                <img src="<?php echo $foto_perfil; ?>" class="icon" alt="Foto de Perfil">

                <div>
                    <h2> Minhas <span>Notificações</span> </h2>
                    <p> Fique por dentro dos avisos e atualizações do seu treino </p>
                </div>
            </article>
        </section>

        <section class="info-notificacoes">

            <article class="box">
                <div class="titulo">
                    <div class="titulo-text">
                        <i class="ri-notification-3-line"></i>
                        <h2> Recentes </h2>
                    </div>
                    <button type="button" class="editar" id="btnMarcarLidas"
                            data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        Marcar todas como lidas
                    </button>
                </div>

                <hr>

                <div id="containerNotificacoes">
                    <?php if (!empty($notificacoes)): ?>
                        <?php foreach ($notificacoes as $index => $notif): 
                            $icone = 'ri-notification-3-line';
                            switch (strtolower($notif['tipo'] ?? '')) {
                                case 'treino': $icone = 'ri-dumbbell-line'; break;
                                case 'avaliacao': $icone = 'ri-file-edit-line'; break;
                                case 'aviso': $icone = 'ri-error-warning-line'; break;
                                case 'novo_professor': $icone = 'ri-team-line'; break;
                            }
                            $naoLidaClass = (empty($notif['lida']) || $notif['lida'] == 0) ? 'nao-lida' : '';
                        ?>
                            <div class="notif <?php echo $naoLidaClass; ?>">
                                <i class="<?php echo $icone; ?>"></i>
                                <div>
                                    <h3><?php echo htmlspecialchars($notif['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($notif['mensagem'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <span class="tempo"><?php echo tempoRelativo($notif['segundos']); ?></span>
                                </div>
                            </div>

                            <?php if ($index < count($notificacoes) - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #666;">
                            Você não possui nenhuma notificação no momento.
                        </p>
                    <?php endif; ?>
                </div>

            </article>

        </section>

    </main>

    <script src="../../assets/js/notificacoes.js"></script>
    <script>
        document.getElementById('btnMarcarLidas')?.addEventListener('click', async function() {
            const botao = this;
            botao.disabled = true;

            try {
                const dados = new FormData();
                dados.append('acao', 'ler_todas');
                dados.append('csrf_token', botao.dataset.csrf);

                const resposta = await fetch('../../adm/Controllers/notificacaoController.php', {
                    method: 'POST',
                    body: dados,
                    credentials: 'same-origin'
                });
                const json = await resposta.json();

                if (!resposta.ok || !json.ok) {
                    throw new Error(json.mensagem || 'Não foi possível atualizar.');
                }

                document.querySelectorAll('.notif.nao-lida').forEach(el => el.classList.remove('nao-lida'));

                // Zera a bolinha vermelha do menu
                if (window.atualizarBadgeNotificacoes) window.atualizarBadgeNotificacoes(0);
            } catch (erro) {
                console.error('Erro ao atualizar status:', erro);
                alert(erro.message || 'Erro de conexão. Tente novamente.');
            }

            botao.disabled = false;
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>