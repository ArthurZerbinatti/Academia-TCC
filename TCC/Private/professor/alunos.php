<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Validação de Autenticação
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

// Check exclusivo de permissão: Apenas PROFESSOR pode acessar esta área
if (($_SESSION['tipo_usuario'] ?? '') !== 'PROFESSOR') {
    header("Location: ../aluno/aluno.php");
    exit();
}

// 2. Cabeçalhos Anti-Cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../../adm/Config/database.php';

// 3. Sanitização dos Dados da Sessão do Professor
$usuario_id = (int) $_SESSION['usuario_id'];
$nome_user = htmlspecialchars($_SESSION['usuario_nome'] ?? '', ENT_QUOTES, 'UTF-8');
$email_user = htmlspecialchars($_SESSION['usuario_email'] ?? '', ENT_QUOTES, 'UTF-8');
$tel_user = htmlspecialchars($_SESSION['usuario_telefone'] ?? '', ENT_QUOTES, 'UTF-8');
$tipo_user = $_SESSION['tipo_usuario'] ?? 'PROFESSOR';

$primeiro_nome = explode(' ', trim($nome_user))[0];

// Busca a foto de perfil do professor logado
$dadoProfessor = [];
try {
    $stmtProf = $pdo->prepare("SELECT foto_perfil FROM Usuarios WHERE id_usuario = :id LIMIT 1");
    $stmtProf->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmtProf->execute();
    $dadoProfessor = $stmtProf->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log("Erro ao buscar foto do professor: " . $e->getMessage());
}

$foto_prof_banco = $dadoProfessor['foto_perfil'] ?? null;
if (!empty($foto_prof_banco) && file_exists("../../assets/uploads/perfil/" . $foto_prof_banco)) {
    $foto_professor = "../../assets/uploads/perfil/" . $foto_prof_banco;
} else {
    $foto_professor = "../../assets/imagens/perfil-padrao.png";
}

// 4. Consulta para Buscar os Alunos Cadastrados
$lista_alunos = [];
try {
    $sqlAlunos = "SELECT 
                u.id_usuario,
                u.nome,
                u.email,
                u.telefone,
                u.foto_perfil,

                (
                    SELECT COUNT(*)
                    FROM Avaliacoes a
                    WHERE a.id_aluno = u.id_usuario
                ) AS total_avaliacoes

              FROM Usuarios u

              WHERE u.tipo_usuario = 'ALUNO'

              AND (
                  -- Aluno ainda não possui nenhuma avaliação
                  NOT EXISTS (
                      SELECT 1
                      FROM Avaliacoes a
                      WHERE a.id_aluno = u.id_usuario
                  )

                  OR

                  -- Aluno já foi avaliado pelo professor logado
                  EXISTS (
                      SELECT 1
                      FROM Avaliacoes a
                      WHERE a.id_aluno = u.id_usuario
                        AND a.id_professor = :id_prof
                  )
              )

              ORDER BY
                  CASE
                      WHEN NOT EXISTS (
                          SELECT 1
                          FROM Avaliacoes a
                          WHERE a.id_aluno = u.id_usuario
                      )
                      THEN 0
                      ELSE 1
                  END ASC,

                  u.nome ASC";

    $stmtAlunos = $pdo->prepare($sqlAlunos);

    $stmtAlunos->bindValue(
        ':id_prof',
        $usuario_id,
        PDO::PARAM_INT
    );

    $stmtAlunos->execute();

    $lista_alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar lista de alunos: " . $e->getMessage());
}

// 5. Consulta para Buscar os Exercícios Cadastrados
$listaExercicios = [];
try {
    $stmtEx = $pdo->query("SELECT id_exercicio, nome, grupo_muscular FROM Exercicios ORDER BY grupo_muscular ASC, nome ASC");
    $listaExercicios = $stmtEx->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar exercícios: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALUNOS | Academia Espaço Livre</title>

    <!-- Ficheiros CSS do Projeto -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/pessoa.css">
    <link rel="stylesheet" href="../../assets/css/alunos.css">
    <link rel="stylesheet" href="../../assets/css/modal.css">
    <link rel="stylesheet" href="../../assets/css/notif.css">
    <link rel="stylesheet" href="../../assets/css/modal-treino.css">
    <link rel="stylesheet" href="../../assets/icons/remixicon.css">
    <link rel="shortcut icon" href="../../assets/imagens/favicon/group-fill.svg" type="image/x-icon">
</head>

<body>

    <header>
        <nav>
            <div>
                <img src="../../assets/imagens/Logo.png" alt="Logo">

                <div class="links">
                    <a href="professor.php"> <i class="ri-home-2-line"></i> INÍCIO </a>
                    <a href="cronograma.php"> <i class="ri-calendar-line"></i> CRONOGRAMA </a>
                    <a href="alunos.php" class="active"> <i class="ri-team-line"></i> ALUNOS </a>
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
                    <i class="ri-logout-box-r-line"></i> SAIR
                </a>
            </div>
        </nav>
    </header>

    <main>
        <section class="header">
            <article class="ola">
                <img src="<?php echo $foto_professor; ?>" class="icon" alt="Foto de Perfil">
                <div>
                    <h2> Meus <span>Alunos</span> </h2>
                    <p> Acompanhe os alunos cadastrados na academia </p>
                </div>
            </article>
        </section>

        <section class="info-alunos">
            <div class="busca">
                <div class="campo-busca">
                    <i class="ri-search-line"></i>
                    <input type="text" id="inputBusca" placeholder="Buscar aluno pelo nome...">
                </div>
            </div>

            <div class="lista-alunos" id="listaAlunos">
                <?php if (!empty($lista_alunos)): ?>
                    <?php foreach ($lista_alunos as $aluno):
                        $foto_aluno_banco = $aluno['foto_perfil'] ?? null;
                        if (!empty($foto_aluno_banco) && file_exists("../../assets/uploads/perfil/" . $foto_aluno_banco)) {
                            $foto_aluno = "../../assets/uploads/perfil/" . $foto_aluno_banco;
                        } else {
                            $foto_aluno = "../../assets/imagens/perfil-padrao.png";
                        }
                        ?>
                        <article class="aluno-card" style="cursor: pointer;" data-id="<?php echo (int) $aluno['id_usuario']; ?>"
                            data-nome="<?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?>">

                            <img src="<?php echo htmlspecialchars($foto_aluno, ENT_QUOTES, 'UTF-8'); ?>" class="icon-pequeno"
                                alt="Foto do Aluno">

                            <div class="aluno-info">
                                <h3><?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><i class="ri-mail-line"></i>
                                    <?php echo htmlspecialchars($aluno['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>

                            <div class="aluno-freq">
                                <span><?php echo !empty($aluno['telefone']) ? htmlspecialchars($aluno['telefone'], ENT_QUOTES, 'UTF-8') : 'Sem tel'; ?></span>
                                <p>Contato</p>
                            </div>

                            <?php if ((int) ($aluno['total_avaliacoes'] ?? 0) > 0): ?>
                                <span class="status ok">Avaliado</span>
                            <?php else: ?>
                                <span class="status pendente">Pendente</span>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 2rem; color: #666;">
                        Nenhum aluno encontrado no sistema.
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="../../assets/js/notificacoes.js"></script>

    <!-- 1. MODAL HUB (Escolha de Ação) -->
    <div class="modal-fundo" id="modal-escolha">
        <div class="modal" style="max-width: 420px;">
            <div class="modal-topo">
                <h2 id="hub-nome-aluno"> Aluno </h2>
                <button type="button" class="modal-fechar" onclick="fecharModal('modal-escolha')">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <div class="modal-corpo" style="display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem 0;">
                <p style="color: #a0aec0; font-size: 0.9rem; text-align: center; margin-bottom: 0.5rem;">
                    Selecione a ação desejada para este aluno:
                </p>

                <button type="button" id="btn-ir-avaliacao"
                    style="background-color: #2563eb; color: #fff; padding: 0.85rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i class="ri-heart-pulse-line"></i> Nova Avaliação Física
                </button>

                <button type="button" id="btn-ir-treino"
                    style="background-color: #e50914; color: #fff; padding: 0.85rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i class="ri-dumbbell-line"></i> Montar Treino
                </button>
            </div>

            <div class="modal-rodape">
                <button type="button" class="modal-cancelar" onclick="fecharModal('modal-escolha')"> Fechar </button>
            </div>
        </div>
    </div>

    <!-- 2. MODAL FORMULÁRIO DE AVALIAÇÃO FÍSICA -->
    <div class="modal-fundo" id="modal-avaliacao">
        <div class="modal">
            <div class="modal-topo">
                <h2> Nova Avaliação Física </h2>
                <button type="button" class="modal-fechar" onclick="fecharModal('modal-avaliacao')">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <form class="modal-corpo" id="formAvaliacao" action="../../adm/Controllers/avaliacaoController.php"
                method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="id_aluno" id="modal-id-aluno">

                <p style="margin-bottom: 1rem; color: #a0aec0;">
                    Aluno: <strong id="modal-nome-aluno" style="color: #fff;">-</strong>
                </p>

                <label for="input-peso">
                    PESO (kg)
                    <div class="campo">
                        <i class="ri-scales-3-line"></i>
                        <input type="number" step="0.01" id="input-peso" name="peso" placeholder="Ex: 75.50" required>
                    </div>
                </label>

                <label for="input-altura">
                    ALTURA (m) — Ex: 1.75
                    <div class="campo">
                        <i class="ri-ruler-line"></i>
                        <input type="number" step="0.02" id="input-altura" name="altura" placeholder="Ex: 1.75"
                            required>
                    </div>
                </label>

                <label for="input-gordura">
                    % DE GORDURA (Opcional)
                    <div class="campo">
                        <i class="ri-percent-line"></i>
                        <input type="number" step="0.01" id="input-gordura" name="percentual_gordura"
                            placeholder="Ex: 18.5">
                    </div>
                </label>

                <label for="input-obs">
                    OBSERVAÇÕES
                    <div class="campo">
                        <i class="ri-file-text-line"></i>
                        <input type="text" id="input-obs" name="observacoes"
                            placeholder="Ex: Foco em hipertrofia, sem lesões.">
                    </div>
                </label>
            </form>

            <div class="modal-rodape">
                <button type="button" class="modal-cancelar" onclick="fecharModal('modal-avaliacao')"> Cancelar
                </button>
                <button type="submit" form="formAvaliacao" class="modal-salvar"> Salvar Avaliação </button>
            </div>
        </div>
    </div>

    <!-- 3. MODAL MONTAR FICHA DE TREINO -->
    <div class="modal-fundo" id="modalTreino">
        <div class="modal">
            <div class="modal-topo">
                <h2> Montar Treino - <span id="nomeAlunoTreino" style="color: #e50914;"></span> </h2>
                <button type="button" class="modal-fechar" onclick="fecharModal('modalTreino')">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <form class="modal-corpo form-treino" id="formTreino" action="../../adm/Controllers/treinoController.php"
                method="POST">
                <input type="hidden" name="id_aluno" id="idAlunoTreino">

                <div class="form-grupo">
                    <label>Título da Ficha</label>
                    <input type="text" name="titulo" placeholder="Ex: Treino ABC - Hipertrofia" required>
                </div>

                <div class="form-linha">
                    <div class="form-grupo">
                        <label>Data de Início</label>
                        <input type="date" name="data_inicio" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-grupo">
                        <label>Data Fim (Opcional)</label>
                        <input type="date" name="data_fim">
                    </div>
                </div>

                <div class="treino-exercicios-header">
                    <h3>Exercícios do Treino</h3>
                    <button type="button" class="btn-adicionar-ex" onclick="adicionarLinhaExercicio()">
                        <i class="ri-add-line"></i> Adicionar
                    </button>
                </div>

                <div class="exercicios-grelha-cabecalho">
                    <div style="flex: 2;">Exercício</div>
                    <div style="flex: 1;">Dia</div>
                    <div style="width: 65px; text-align: center;">Séries</div>
                    <div style="width: 95px;">Reps</div>
                    <div style="width: 75px; text-align: center;">Carga</div>
                    <div style="width: 75px; text-align: center;">Desc.</div>
                    <div style="width: 36px;"></div>
                </div>

                <div id="containerExercicios" class="container-exercicios"></div>
            </form>

            <div class="modal-rodape">
                <button type="button" class="modal-cancelar" onclick="fecharModal('modalTreino')"> Cancelar </button>
                <button type="submit" form="formTreino" class="btn-salvar-treino"> Salvar Ficha de Treino </button>
            </div>
        </div>
    </div>

    <!-- Envia a lista de exercícios para o JS -->
    <script>
        window.listaExercicios = <?= json_encode($listaExercicios ?? []); ?>;
    </script>

    <!-- Ficheiro JavaScript Principal do Projeto -->
    <script src="../../assets/js/modal.js"></script>

    <script>
        // Filtro em tempo real para a barra de busca
        document.getElementById('inputBusca')?.addEventListener('input', function (e) {
            const termo = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.aluno-card');

            cards.forEach(card => {
                const nome = card.querySelector('.aluno-info h3').textContent.toLowerCase();
                card.style.display = nome.includes(termo) ? 'flex' : 'none';
            });
        });

        // Prevenção do Botão "Voltar" do Navegador
        window.addEventListener('pageshow', function (event) {
            if (event.persisted || (typeof window.performance !== 'undefined' && window.performance.navigation.type === 2)) {
                window.location.reload();
            }
        });

        // Configuração dos eventos de clique nos cards dos alunos
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.aluno-card');
            const hubNome = document.getElementById('hub-nome-aluno');
            const btnAvaliacao = document.getElementById('btn-ir-avaliacao');
            const btnTreino = document.getElementById('btn-ir-treino');

            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const idAluno = card.dataset.id;
                    const nomeAluno = card.dataset.nome;

                    if (idAluno && nomeAluno) {
                        if (hubNome) hubNome.textContent = nomeAluno;

                        if (btnTreino) {
                            btnTreino.onclick = () => {
                                fecharModal('modal-escolha');
                                abrirModalTreino(idAluno, nomeAluno);
                            };
                        }

                        if (btnAvaliacao) {
                            btnAvaliacao.onclick = () => {
                                fecharModal('modal-escolha');
                                abrirModalAvaliacao(idAluno, nomeAluno);
                            };
                        }

                        abrirModal('modal-escolha');
                    }
                });
            });
        });
    </script>
</body>

</html>