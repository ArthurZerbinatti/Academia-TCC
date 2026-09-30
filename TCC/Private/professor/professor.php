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
$usuario_id = (int) $_SESSION['usuario_id'];
$nome_user = htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8');
$email_user = htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8');
$tel_user = htmlspecialchars($_SESSION['usuario_telefone'] ?? '', ENT_QUOTES, 'UTF-8');
$cref_user = htmlspecialchars($_SESSION['cref'] ?? 'Não informado', ENT_QUOTES, 'UTF-8');
$esp_user = htmlspecialchars($_SESSION['especializacao'] ?? 'Educação Física', ENT_QUOTES, 'UTF-8');

$primeiro_nome = explode(' ', trim($nome_user))[0];

// 5. Consultas para Métricas do Painel e Dados do Usuário
$total_alunos = 0;
$treinos_semana = 0;
$avaliacoes_fisicas = 0;
$notificacoes_recentes = [];
$dadoUsuario = [];

try {
    // Busca dados atualizados do usuário (foto e data de nascimento) diretamente do BD
    $stmtUser = $pdo->prepare("SELECT foto_perfil, data_nascimento FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtUser->execute();
    $dadoUsuario = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];

    // Total de Alunos Vinculados
    $stmtAlunos = $pdo->prepare("SELECT COUNT(*) FROM Professor_Aluno WHERE id_professor = :id_prof");
    $stmtAlunos->bindValue(':id_prof', $usuario_id, PDO::PARAM_INT);
    $stmtAlunos->execute();
    $total_alunos = (int) $stmtAlunos->fetchColumn();

    // Treinos da Semana Atual (Corrigido com JOIN e a data correta)
    $stmtTreinos = $pdo->prepare("SELECT COUNT(*) FROM Itens_Treino i INNER JOIN Fichas_Treino f ON i.id_ficha = f.id_ficha WHERE f.id_professor = :id_prof AND WEEK(f.data_inicio, 1) = WEEK(CURDATE(), 1)");
    $stmtTreinos->bindValue(':id_prof', $usuario_id, PDO::PARAM_INT);
    $stmtTreinos->execute();
    $treinos_semana = (int) $stmtTreinos->fetchColumn();

    // Total de Avaliações Físicas
    $stmtAval = $pdo->query("SELECT COUNT(*) FROM Usuarios u WHERE u.tipo_usuario = 'ALUNO' AND NOT EXISTS (SELECT 1 FROM Avaliacoes a WHERE a.id_aluno = u.id_usuario)");
    $stmtAval->execute();
    $avaliacoes_fisicas = (int) $stmtAval->fetchColumn();

    // Notificações Recentes (Últimas 4)
    $stmtNotif = $pdo->prepare("SELECT titulo, mensagem, tipo FROM Notificacoes WHERE id_usuario = :id_prof ORDER BY data_criacao DESC LIMIT 2");
    $stmtNotif->bindValue(':id_prof', $usuario_id, PDO::PARAM_INT);
    $stmtNotif->execute();
    $notificacoes_recentes = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar dados do painel do professor: " . $e->getMessage());
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lógica para carregar a foto do perfil (com verificação do arquivo e parâmetro anti-cache)
$foto_banco = $dadoUsuario['foto_perfil'] ?? null;

if (!empty($foto_banco) && file_exists("../../assets/uploads/perfil/" . $foto_banco)) {
    $foto_perfil = "../../assets/uploads/perfil/" . $foto_banco . "?v=" . time();
} else {
    $foto_perfil = "../../assets/imagens/perfil-padrao.png";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> PROFESSOR | Academia Espaço Livre </title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pessoa.css">
    <link rel="stylesheet" href="../../assets/css/calendario.css">
    <link rel="stylesheet" href="../../assets/css/modal.css">
    <link rel="stylesheet" href="../../assets/css/notif.css">
    <link rel="stylesheet" href="../../assets/icons/remixicon.css">

    <link rel="shortcut icon" href="../../assets/imagens/favicon/user-fill.svg" type="image/x-icon">
</head>

<body>

    <header>
        <nav>
            <div>
                <img src="../../assets/imagens/Logo.png" alt="Logo">

                <div class="links">
                    <a href="professor.php" class="active"> <i class="ri-home-2-line"></i> INÍCIO </a>
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
                <!-- Imagem dinâmica: exibe a foto do perfil ou a foto padrão -->
                <img src="<?php echo $foto_perfil; ?>" class="icon" alt="Foto do Professor">

                <div>
                    <h2> Olá, <span><?php echo $primeiro_nome; ?></span>! </h2>
                    <p> Professor — <?php echo htmlspecialchars(ucfirst($esp_user), ENT_QUOTES, 'UTF-8'); ?> </p>
                    <button type="button" class="editar" onclick="abrirModal('modal-dados')"> <i
                            class="ri-edit-line"></i> Editar dados </button>
                </div>
            </article>

            <article class="header-info">

                <div class="card-h">
                    <i class="ri-group-3-fill"></i>
                    <div class="card-info">
                        <h2> <?php echo $total_alunos; ?> </h2>
                        <p> Alunos Acompanhados </p>
                    </div>
                </div>

                <div class="card-h">
                    <i class="ri-calendar-2-fill"></i>
                    <div class="card-info">
                        <h2> <?php echo $treinos_semana; ?> </h2>
                        <p> Treinos da Semana </p>
                    </div>
                </div>

                <div class="card-h">
                    <i class="ri-file-edit-fill"></i>
                    <div class="card-info">
                        <h2> <?php echo $avaliacoes_fisicas; ?> </h2>
                        <p> Avaliações Físicas </p>
                    </div>
                </div>

            </article>

        </section>

        <section class="info">
            <article>
                <div class="box">
                    <div class="titulo">
                        <div>
                            <i class="ri-notification-3-line"></i>
                            Notificações
                        </div>
                        <a href="notif-prof.php"> Ver todas </a>
                    </div>

                    <hr>

                    <!-- Mensagens de Feedback de atualização -->
                    <?php if (isset($_SESSION['msg_erro'])): ?>
                        <p style="color: #e74c3c; padding: 0.5rem 0; font-weight: bold;">
                            <?php echo $_SESSION['msg_erro'];
                            unset($_SESSION['msg_erro']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['msg_sucesso'])): ?>
                        <p style="color: #2ecc71; padding: 0.5rem 0; font-weight: bold;">
                            <?php echo $_SESSION['msg_sucesso'];
                            unset($_SESSION['msg_sucesso']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($notificacoes_recentes)): ?>
                        <?php foreach ($notificacoes_recentes as $index => $notif):
                            $icone = 'ri-notification-3-fill';
                            switch (strtolower($notif['tipo'] ?? '')) {
                                case 'falta':
                                    $icone = 'ri-user-unfollow-fill';
                                    break;
                                case 'atraso':
                                    $icone = 'ri-error-warning-fill';
                                    break;
                                case 'novo_aluno':
                                    $icone = 'ri-user-follow-fill';
                                    break;
                                case 'ferias':
                                    $icone = 'ri-plane-fill';
                                    break;
                            }
                            ?>
                            <div class="notif">
                                <i class="<?php echo $icone; ?>"></i>
                                <div>
                                    <h3> <?php echo htmlspecialchars($notif['titulo'], ENT_QUOTES, 'UTF-8'); ?> </h3>
                                    <p> <?php echo htmlspecialchars($notif['mensagem'], ENT_QUOTES, 'UTF-8'); ?> </p>
                                </div>
                            </div>
                            <?php if ($index < count($notificacoes_recentes) - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif">
                            <i class="ri-user-follow-fill"></i>
                            <div>
                                <h3> Bem-vindo! </h3>
                                <p> Seu painel de professor está pronto para uso. </p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </article>

        </section>

    </main>

    <script src="../../assets/js/notificacoes.js"></script>

    <!-- Modal Editar Dados (Com suporte a Upload de Arquivos) -->
    <div class="modal-fundo" id="modal-dados">
        <div class="modal">
            <div class="modal-topo">
                <h2> Editar Dados </h2>
                <button type="button" class="modal-fechar" aria-label="Fechar" onclick="fecharModal('modal-dados')">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <form class="modal-corpo" id="formEditarProf" action="../../adm/Controllers/UpdateController.php"
                method="POST" enctype="multipart/form-data">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div style="text-align: center; margin-bottom: 1rem;">
                    <img id="preview-foto" src="<?php echo $foto_perfil; ?>" alt="Prévia da Foto"
                        style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #ff4d00;">
                </div>

                <label for="edit-foto">
                    FOTO DE PERFIL
                    <div class="campo">
                        <i class="ri-image-edit-line"></i>
                        <input type="file" id="edit-foto" name="foto_perfil"
                            accept="image/png, image/jpeg, image/jpg, image/webp">
                    </div>
                </label>

                <label for="edit-nome">
                    NOME
                    <div class="campo">
                        <i class="ri-user-line"></i>
                        <input type="text" id="edit-nome" name="nome" value="<?php echo $nome_user; ?>" required>
                    </div>
                </label>

                <label for="edit-tel">
                    TELEFONE
                    <div class="campo">
                        <i class="ri-phone-line"></i>
                        <input type="tel" id="edit-tel" name="telefone" value="<?php echo $tel_user; ?>" required>
                    </div>
                </label>

                <label for="edit-email">
                    E-MAIL
                    <div class="campo">
                        <i class="ri-mail-line"></i>
                        <input type="email" id="edit-email" name="email" value="<?php echo $email_user; ?>" required>
                    </div>
                </label>

                <label for="edit-nascimento">
                    DATA DE NASCIMENTO
                    <div class="campo">
                        <i class="ri-cake-2-line"></i>
                        <input type="date" id="edit-nascimento" name="data_nascimento"
                            value="<?php echo $dadoUsuario['data_nascimento'] ?? ''; ?>">
                    </div>
                </label>
            </form>

            <div class="modal-rodape">
                <button type="button" class="modal-cancelar" onclick="fecharModal('modal-dados')"> Cancelar </button>
                <button type="submit" form="formEditarProf" class="modal-salvar"> Salvar Alterações </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/calendario.js"></script>
    <script src="../../assets/js/modal.js"></script>
    <script>
        // Prevenção do Botão "Voltar" do Navegador
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>

</html>