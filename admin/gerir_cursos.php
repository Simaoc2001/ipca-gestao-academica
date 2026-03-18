<?php
session_start();
require_once '../config.php';

// Proteger página: só admin
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';
$erro = '';

// Processar adição de novo curso
if (isset($_POST['add_curso'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    if (empty($nome)) {
        $erro = "O nome do curso é obrigatório.";
    } else {
        $nome_esc = mysqli_real_escape_string($ligacao, $nome);
        $desc_esc = mysqli_real_escape_string($ligacao, $descricao);
        if (mysqli_query($ligacao, "INSERT INTO cursos (Nome, descricao) VALUES ('$nome_esc', '$desc_esc')")) {
            $mensagem = "Curso criado com sucesso.";
        } else {
            $erro = "Erro ao criar curso: " . mysqli_error($ligacao);
        }
    }
}

// Processar edição de curso
if (isset($_POST['editar_curso'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    if (empty($nome)) {
        $erro = "O nome do curso é obrigatório.";
    } else {
        $nome_esc = mysqli_real_escape_string($ligacao, $nome);
        $desc_esc = mysqli_real_escape_string($ligacao, $descricao);
        if (mysqli_query($ligacao, "UPDATE cursos SET Nome = '$nome_esc', descricao = '$desc_esc' WHERE ID = $id")) {
            $mensagem = "Curso atualizado com sucesso.";
        } else {
            $erro = "Erro ao atualizar: " . mysqli_error($ligacao);
        }
    }
}

// Processar desativar/ativar curso
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    mysqli_query($ligacao, "UPDATE cursos SET ativo = IF(ativo=1, 0, 1) WHERE ID = $id");
    header('Location: gerir_cursos.php');
    exit;
}

// Curso em modo de edição
$editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $res = mysqli_query($ligacao, "SELECT * FROM cursos WHERE ID = $id");
    if ($res && mysqli_num_rows($res) == 1) {
        $editar = mysqli_fetch_assoc($res);
    }
}

// Buscar todos os cursos
$cursos = mysqli_query($ligacao, "SELECT * FROM cursos ORDER BY Nome");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Cursos &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Gestão de Cursos</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($mensagem): ?><div class="sucesso"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

        <div class="page-header">
            <h2>Cursos</h2>
            <p>Crie, edite e ative/desative cursos do sistema.</p>
        </div>

        <?php if ($editar): ?>
            <h3>Editar Curso</h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editar['ID'] ?>">
                <label>Nome do Curso *</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($editar['Nome']) ?>" required>
                <label>Descrição</label>
                <textarea name="descricao"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea>
                <div class="mt-4">
                    <button type="submit" name="editar_curso" class="btn">Guardar Alterações</button>
                    <a href="gerir_cursos.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <h3>Adicionar Novo Curso</h3>
            <form method="POST">
                <label>Nome do Curso *</label>
                <input type="text" name="nome" placeholder="Nome do curso" required>
                <label>Descrição</label>
                <textarea name="descricao" placeholder="Descrição do curso (opcional)"></textarea>
                <div class="mt-4">
                    <button type="submit" name="add_curso" class="btn">Adicionar</button>
                </div>
            </form>
        <?php endif; ?>

        <h3>Cursos Existentes</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($cursos)): ?>
            <tr style="<?= $row['ativo'] ? '' : 'opacity:0.5;' ?>">
                <td><?= $row['ID'] ?></td>
                <td><?= htmlspecialchars($row['Nome']) ?></td>
                <td><?= $row['ativo'] ? '<span style="color:var(--success)">Ativo</span>' : '<span style="color:var(--danger)">Inativo</span>' ?></td>
                <td>
                    <a href="?editar=<?= $row['ID'] ?>" class="btn btn-sm">✏️ Editar</a>
                    <a href="?toggle=<?= $row['ID'] ?>" class="btn btn-sm btn-outline"><?= $row['ativo'] ? '⏸ Desativar' : '▶ Ativar' ?></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>