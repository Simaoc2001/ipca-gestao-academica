<?php
session_start();
require_once '../config.php';

// Proteger página: só aluno
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ALUNO') {
    header('Location: ../login.php');
    exit;
}

$login = $_SESSION['login'];
$erro = '';
$sucesso = '';

// Buscar dados atuais da ficha (se existir)
$ficha = mysqli_query($ligacao, "SELECT * FROM ficha_aluno WHERE login = '$login'");
$tem_ficha = mysqli_num_rows($ficha) > 0;
$dados = $tem_ficha ? mysqli_fetch_assoc($ficha) : null;

// Buscar lista de cursos para o select
$cursos = mysqli_query($ligacao, "SELECT ID, Nome FROM cursos ORDER BY Nome");

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_completo = mysqli_real_escape_string($ligacao, trim($_POST['nome_completo']));
    $email = mysqli_real_escape_string($ligacao, trim($_POST['email']));
    $telefone = mysqli_real_escape_string($ligacao, trim($_POST['telefone']));
    $morada = mysqli_real_escape_string($ligacao, trim($_POST['morada']));
    $data_nascimento = $_POST['data_nascimento'];
    $curso_id = !empty($_POST['curso_id']) ? (int)$_POST['curso_id'] : 'NULL';
    $estado = $_POST['acao'] == 'submeter' ? 'submetida' : 'rascunho';
    $data_submissao = ($estado == 'submetida') ? date('Y-m-d H:i:s') : 'NULL';

    // Validações básicas
    if (empty($nome_completo) || empty($email) || empty($data_nascimento)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        // Processar upload da foto (se houver)
        $foto_path = $dados['foto'] ?? null; // manter a anterior se não for alterada
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
            if (!in_array($extensao, $extensoes_permitidas)) {
                $erro = "Formato de imagem não permitido (use apenas JPG ou PNG).";
            } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                $erro = "A imagem não pode ter mais de 2MB.";
            } else {
                // Criar pasta se não existir
                $pasta_upload = '../uploads/';
                if (!is_dir($pasta_upload)) {
                    mkdir($pasta_upload, 0777, true);
                }
                $nome_foto = uniqid() . '.' . $extensao;
                $destino = $pasta_upload . $nome_foto;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $foto_path = 'uploads/' . $nome_foto; // guardar caminho relativo
                } else {
                    $erro = "Erro ao fazer upload da imagem.";
                }
            }
        }

        if (empty($erro)) {
            if ($tem_ficha) {
                // Atualizar ficha existente
                $sql = "UPDATE ficha_aluno SET 
                        nome_completo = '$nome_completo',
                        email = '$email',
                        telefone = '$telefone',
                        morada = '$morada',
                        data_nascimento = '$data_nascimento',
                        curso_id = $curso_id,
                        foto = " . ($foto_path ? "'$foto_path'" : "NULL") . ",
                        estado = '$estado',
                        data_submissao = " . ($data_submissao != 'NULL' ? "'$data_submissao'" : "NULL") . "
                        WHERE login = '$login'";
            } else {
                // Inserir nova ficha
                $sql = "INSERT INTO ficha_aluno 
                        (login, nome_completo, email, telefone, morada, data_nascimento, curso_id, foto, estado, data_submissao) 
                        VALUES 
                        ('$login', '$nome_completo', '$email', '$telefone', '$morada', '$data_nascimento', $curso_id, " . ($foto_path ? "'$foto_path'" : "NULL") . ", '$estado', " . ($data_submissao != 'NULL' ? "'$data_submissao'" : "NULL") . ")";
            }

            if (mysqli_query($ligacao, $sql)) {
                $sucesso = "Ficha guardada com sucesso!";
                // Redirecionar para o dashboard após 2 segundos
                header('refresh:2; url=dashboard.php');
            } else {
                $erro = "Erro ao guardar: " . mysqli_error($ligacao);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ficha &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Ficha de Aluno</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Editar Ficha</h2>
            <p>Preencha e atualize os seus dados pessoais.</p>
        </div>
        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="sucesso"><?= $sucesso ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Nome Completo *</label>
            <input type="text" name="nome_completo" value="<?= htmlspecialchars($dados['nome_completo'] ?? '') ?>" required>

            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($dados['email'] ?? '') ?>" required>

            <label>Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">

            <label>Morada</label>
            <textarea name="morada"><?= htmlspecialchars($dados['morada'] ?? '') ?></textarea>

            <label>Data de Nascimento *</label>
            <input type="date" name="data_nascimento" value="<?= $dados['data_nascimento'] ?? '' ?>" required>

            <label>Curso Pretendido</label>
            <select name="curso_id">
                <option value="">-- Selecione um curso --</option>
                <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                    <option value="<?= $curso['ID'] ?>" <?= ($dados['curso_id'] ?? '') == $curso['ID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($curso['Nome']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Fotografia</label>
            <?php if (!empty($dados['foto'])): ?>
                <div>
                    <img src="../<?= $dados['foto'] ?>" class="preview-foto">
                </div>
            <?php endif; ?>
            <input type="file" name="foto" accept=".jpg,.jpeg,.png">

            <hr>
            <button type="submit" name="acao" value="rascunho" class="btn">Guardar como Rascunho</button>
            <button type="submit" name="acao" value="submeter" class="btn btn-success">Submeter para Validação</button>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>