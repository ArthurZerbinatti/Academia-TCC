<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('pcre.jit', 0);

session_start();

// 1. Validação de Autenticação e Nível de Acesso
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

if (($_SESSION['tipo_usuario'] ?? '') !== 'ALUNO') {
    header("Location: ../professor/professor.php");
    exit();
}

// 2. Cabeçalhos Anti-Cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../../adm/Config/database.php';

// 3. Sanitização dos Dados da Sessão
$usuario_id = (int) $_SESSION['usuario_id'];
$nome_user = htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8');
$email_user = htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8');
$tel_user = htmlspecialchars($_SESSION['usuario_telefone'] ?? '', ENT_QUOTES, 'UTF-8');

$primeiro_nome = explode(' ', trim($nome_user))[0];

// 4. Busca de Dados Adicionais no Banco
$idade_texto = "Não informada";
$treinos_feitos = 0;
$horas_totais = 0;
$frequencia_pct = 0;
$conquistas_cnt = 0;
$foto_perfil = "../../assets/imagens/perfil-padrao.png";
$dias_com_treino_json = "[]";

try {
    // Dados Cadastrais do Aluno
    $stmtUser = $pdo->prepare("SELECT data_nascimento, foto_perfil FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtUser->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtUser->execute();
    $dadoUsuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($dadoUsuario) {
        if (!empty($dadoUsuario['data_nascimento'])) {
            $nascimento = new DateTime($dadoUsuario['data_nascimento']);
            $hoje = new DateTime();
            $idade_texto = $hoje->diff($nascimento)->y . " anos";
        }

        $foto_banco = $dadoUsuario['foto_perfil'] ?? null;
        if (!empty($foto_banco) && file_exists("../../assets/uploads/perfil/" . $foto_banco)) {
            $foto_perfil = "../../assets/uploads/perfil/" . $foto_banco;
        }
    }

    // Dias da semana que possuem treino cadastrado para o aluno
    $stmtDias = $pdo->prepare("
        SELECT DISTINCT it.dia_semana
        FROM Itens_Treino it
        INNER JOIN Fichas_Treino ft ON it.id_ficha = ft.id_ficha
        WHERE ft.id_aluno = :id_aluno
          AND (ft.data_fim IS NULL OR ft.data_fim >= CURDATE())
    ");
    $stmtDias->bindValue(':id_aluno', $usuario_id, PDO::PARAM_INT);
    $stmtDias->execute();

    $diasTreino = $stmtDias->fetchAll(PDO::FETCH_COLUMN);
    $dias_com_treino_json = json_encode($diasTreino ?: []);

} catch (PDOException $e) {
    error_log("Erro ao carregar dados do aluno: " . $e->getMessage());
}

// Marcações do calendário: início da ficha ativa e datas de treino concluídas
$inicio_treino_json = "null";
$datas_concluidas_json = "[]";

try {
    $stmtIni = $pdo->prepare("SELECT MIN(data_inicio) FROM Fichas_Treino
                              WHERE id_aluno = :id AND (data_fim IS NULL OR data_fim >= CURDATE())");
    $stmtIni->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtIni->execute();
    $inicio_treino_json = json_encode($stmtIni->fetchColumn() ?: null);

    $stmtConc = $pdo->prepare("SELECT data_treino FROM Treinos_Concluidos WHERE id_aluno = :id");
    $stmtConc->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtConc->execute();
    $datas_concluidas_json = json_encode($stmtConc->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {
    error_log("Erro ao carregar marcações do calendário: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALUNO | Academia Espaço Livre</title>

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
                    <a href="aluno.php" class="active"> <i class="ri-home-2-line"></i> INÍCIO </a>
                    <a href="treinos.php"> <i class="ri-calendar-line"></i> TREINOS </a>
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
                <img src="<?php echo $foto_perfil; ?>" class="icon" id="foto-cabecalho" alt="Foto de Perfil">

                <div>
                    <h2> Olá, <span><?php echo $primeiro_nome; ?></span>! </h2>
                    <p> Aqui está o seu resumo: </p>
                </div>
            </article>
        </section>

        <section class="info">

            <article class="boxes">

                <div class="box">
                    <div class="titulo">
                        <div class="titulo-text">
                            <i class="ri-user-fill"></i>
                            <h2> Meus Dados </h2>
                        </div>
                        <button type="button" class="editar" onclick="abrirModal('modal-dados')">
                            <i class="ri-edit-line"></i> Editar dados
                        </button>
                    </div>

                    <hr>

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

                    <div class="linha">
                        <div> <i class="ri-user-line"></i> Nome </div>
                        <p> <?php echo $nome_user; ?> </p>
                    </div>

                    <hr>

                    <div class="linha">
                        <div> <i class="ri-cake-2-fill"></i> Idade </div>
                        <p> <?php echo $idade_texto; ?> </p>
                    </div>

                    <hr>

                    <div class="linha">
                        <div> <i class="ri-mail-line"></i> E-mail </div>
                        <p> <?php echo $email_user; ?> </p>
                    </div>

                    <hr>

                    <div class="linha">
                        <div> <i class="ri-phone-line"></i> Telefone </div>
                        <p> <?php echo !empty($tel_user) ? $tel_user : 'Não informado'; ?> </p>
                    </div>
                </div>

            </article>

            <article class="calendario-area">
                <div class="box">
                    <div class="titulo2">
                        <i class="ri-calendar-2-line"></i>
                        <div class="titulo-text2">
                            <h2> Meus Dias na Academia </h2>
                            <p> Acompanhe os dias que você vem à academia </p>
                        </div>
                    </div>

                    <hr>

                    <!-- Barra de presenças por dia da semana -->
                    <div class="semana checks-semana">
                        <div class="sem" data-dia="1">
                            <h3> SEG </h3>
                            <i class="ri-subtract-line"></i>
                        </div>
                        <div class="sem" data-dia="2">
                            <h3> TER </h3>
                            <i class="ri-subtract-line"></i>
                        </div>
                        <div class="sem" data-dia="3">
                            <h3> QUA </h3>
                            <i class="ri-subtract-line"></i>
                        </div>
                        <div class="sem" data-dia="4">
                            <h3> QUI </h3>
                            <i class="ri-subtract-line"></i>
                        </div>
                        <div class="sem" data-dia="5">
                            <h3> SEX </h3>
                            <i class="ri-subtract-line"></i>
                        </div>
                        <div class="sem" data-dia="6">
                            <h3> SÁB </h3>
                            <i class="ri-subtract-line"></i>
                        </div>
                    </div>

                    <hr>

                    <div class="trocar">
                        <button id="btnMes" class="btn-troca ativo"> MÊS </button>
                        <button id="btnSemana" class="btn-troca"> SEMANA </button>
                        <button id="btnDia" class="btn-troca"> DIA </button>
                    </div>

                    <div class="calendario">
                        <div class="mes">
                            <button id="anterior" class="btn-mes"> <i class="ri-arrow-left-s-fill"></i> </button>
                            <h2 id="titulo"></h2>
                            <button id="proximo" class="btn-mes"> <i class="ri-arrow-right-s-fill"></i> </button>
                        </div>

                        <div id="calendario"></div>
                    </div>
                </div>
            </article>

        </section>

    </main>

    <script src="../../assets/js/notificacoes.js"></script>

    <!-- Modal para Edição de Dados -->
    <div class="modal-fundo" id="modal-dados">
        <div class="modal">
            <div class="modal-topo">
                <h2> Editar Dados </h2>
                <button type="button" class="modal-fechar" aria-label="Fechar" onclick="fecharModal('modal-dados')">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <form class="modal-corpo" id="formEditarAluno" action="../../adm/Controllers/UpdateController.php"
                method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                <div class="modal-foto">
                    <img id="preview-foto" src="<?php echo $foto_perfil; ?>" alt="Prévia da Foto" class="edit-foto">

                    <label for="edit-foto">
                        FOTO DE PERFIL

                        <input class="upload-foto" type="file" id="edit-foto" name="foto_perfil" accept="image/*">  
                    </label>

                </div>

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
                <button type="submit" form="formEditarAluno" class="modal-salvar"> Salvar Alterações </button>
            </div>
        </div>
    </div>

    <!-- Passa os dias de presença do PHP para o JavaScript -->
    <script>
        window.DIAS_TREINO_ALUNO = <?php echo $dias_com_treino_json; ?>;
        window.INICIO_TREINO = <?php echo $inicio_treino_json; ?>;
        window.DATAS_CONCLUIDAS = <?php echo $datas_concluidas_json; ?>;
    </script>
    <script src="../../assets/js/calendario.js"></script>
    <script src="../../assets/js/modal.js"></script>
    <script>
        document.getElementById('edit-foto')?.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (evt) {
                    const preview = document.getElementById('preview-foto');
                    const fotoCabecalho = document.getElementById('foto-cabecalho');
                    if (preview) preview.src = evt.target.result;
                    if (fotoCabecalho) fotoCabecalho.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });
    </script>
</body>

</html>