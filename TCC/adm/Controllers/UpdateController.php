<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../adm/Config/database.php';

// 1. Validação de Autenticação
if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
    header("Location: ../../Public/login.php?erro=nao_autorizado");
    exit();
}

$usuario_id   = (int)$_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'] ?? 'ALUNO';

// Define a página de retorno de acordo com o tipo de usuário
$destino = ($tipo_usuario === 'PROFESSOR') 
    ? '../../Private/professor/professor.php' 
    : '../../Private/aluno/aluno.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: {$destino}");
    exit();
}

// 2. Recebe os dados do formulário
$nome            = trim($_POST['nome'] ?? '');
$email           = trim($_POST['email'] ?? '');
$telefone        = trim($_POST['telefone'] ?? '');
$data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;

if (empty($nome) || empty($email)) {
    $_SESSION['msg_erro'] = "Preencha os campos obrigatórios (Nome e E-mail).";
    header("Location: {$destino}");
    exit();
}

try {
    $foto_nome_banco = null;

    // 3. Processamento do Upload da Foto de Perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['foto_perfil']['tmp_name'];
        $fileName      = $_FILES['foto_perfil']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $extensoes_permitidas)) {
            // Gera nome único para o arquivo
            $novoNome = "perfil_" . $usuario_id . "_" . time() . "." . $fileExtension;
            
            // Usa caminho absoluto baseado no diretório atual
            $uploadDir = __DIR__ . '/../../assets/uploads/perfil/';

            // Cria o diretório se ele não existir
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $dest_path = $uploadDir . $novoNome;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $foto_nome_banco = $novoNome;
            } else {
                $_SESSION['msg_erro'] = "Falha ao salvar a imagem no servidor. Verifique as permissões da pasta.";
                header("Location: {$destino}");
                exit();
            }
        } else {
            $_SESSION['msg_erro'] = "Formato de imagem inválido. Use JPG, PNG ou WEBP.";
            header("Location: {$destino}");
            exit();
        }
    }

    // 4. Atualiza os dados principais na tabela Usuarios
    if ($foto_nome_banco) {
        $sqlUpdate = "UPDATE Usuarios 
                      SET nome = :nome, 
                          email = :email, 
                          telefone = :telefone,
                          data_nascimento = :data_nascimento,
                          foto_perfil = :foto
                      WHERE id_usuario = :id";
    } else {
        $sqlUpdate = "UPDATE Usuarios 
                      SET nome = :nome, 
                          email = :email, 
                          telefone = :telefone,
                          data_nascimento = :data_nascimento
                      WHERE id_usuario = :id";
    }

    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':telefone', $telefone, PDO::PARAM_STR);
    $stmt->bindValue(':data_nascimento', $data_nascimento, $data_nascimento ? PDO::PARAM_STR : PDO::PARAM_NULL);
    
    if ($foto_nome_banco) {
        $stmt->bindValue(':foto', $foto_nome_banco, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();

    // 5. Se for PROFESSOR, atualiza a especialização
    if ($tipo_usuario === 'PROFESSOR' && isset($_POST['especializacao'])) {
        $esp = trim($_POST['especializacao']);
        
        $sqlProf = "UPDATE Professores SET especializacao = :esp WHERE id_professor = :id";
        $stmtProf = $pdo->prepare($sqlProf);
        $stmtProf->bindValue(':esp', $esp, PDO::PARAM_STR);
        $stmtProf->bindValue(':id', $usuario_id, PDO::PARAM_INT);
        $stmtProf->execute();

        $_SESSION['especializacao'] = $esp;
    }

    // 6. Atualiza a Sessão para refletir imediatamente na tela
    $_SESSION['usuario_nome']     = $nome;
    $_SESSION['usuario_email']    = $email;
    $_SESSION['usuario_telefone'] = $telefone;

    $_SESSION['msg_sucesso'] = "Dados atualizados com sucesso!";
    header("Location: {$destino}?status=sucesso");
    exit();

} catch (PDOException $e) {
    error_log("Erro no BD ao atualizar perfil: " . $e->getMessage());
    $_SESSION['msg_erro'] = "Erro ao salvar no banco de dados.";
    header("Location: {$destino}?status=erro");
    exit();
}