<?php
session_start();
require_once '../config.php';

// Proteger página: só admin
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$erro_disc = '';
$mensagem = '';

// Processar adição de nova disciplina
if (isset($_POST['add_disciplina'])) {
    $nome = trim($_POST['nome'] ?? '');
    if (!empty($nome)) {
        $nome_esc = mysqli_real_escape_string($ligacao, $nome);
        $check = mysqli_query($ligacao, "SELECT ID FROM disciplinas WHERE LOWER(Nome_disc) = LOWER('$nome_esc')");
        if ($check && mysqli_num_rows($check) > 0) {
            $erro_disc = "Já existe uma disciplina com esse nome.";
        } else {
            mysqli_query($ligacao, "INSERT INTO disciplinas (Nome_disc) VALUES ('$nome_esc')");
            $mensagem = "Disciplina criada com sucesso.";
        }
    } else {
        $erro_disc = "O nome da disciplina é obrigatório.";
    }
}

// Processar edição de disciplina
if (isset($_POST['editar_disciplina'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome'] ?? '');
    if (!empty($nome)) {
        $nome_esc = mysqli_real_escape_string($ligacao, $nome);
        $check = mysqli_query($ligacao, "SELECT ID FROM disciplinas WHERE LOWER(Nome_disc) = LOWER('$nome_esc') AND ID != $id");
        if ($check && mysqli_num_rows($check) > 0) {
            $erro_disc = "Já existe outra disciplina com esse nome.";
        } else {
            mysqli_query($ligacao, "UPDATE disciplinas SET Nome_disc = '$nome_esc' WHERE ID = $id");
            $mensagem = "Disciplina atualizada com sucesso.";
        }
    } else {
        $erro_disc = "O nome da disciplina é obrigatório.";
    }
}

// Processar eliminação de disciplina
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    mysqli_query($ligacao, "DELETE FROM disciplinas WHERE ID = $id");
    header('Location: gerir_disciplinas.php');
    exit;
}

// Disciplina em modo de edição
$editar = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $res = mysqli_query($ligacao, "SELECT * FROM disciplinas WHERE ID = $id");
    if ($res && mysqli_num_rows($res) == 1) {
        $editar = mysqli_fetch_assoc($res);
    }
}

// Buscar todas as disciplinas
$disciplinas = mysqli_query($ligacao, "SELECT * FROM disciplinas ORDER BY Nome_disc");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Disciplinas &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Gestão de Disciplinas</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Disciplinas</h2>
            <p>Crie, edite ou remova unidades curriculares do sistema.</p>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="sucesso"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>
        <?php if (!empty($erro_disc)): ?>
            <div class="erro"><?= htmlspecialchars($erro_disc) ?></div>
        <?php endif; ?>

        <?php if ($editar): ?>
            <h3>Editar Disciplina</h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editar['ID'] ?>">
                <label>Nome da Disciplina *</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($editar['Nome_disc']) ?>" required>
                <div class="mt-4">
                    <button type="submit" name="editar_disciplina" class="btn">Guardar Alterações</button>
                    <a href="gerir_disciplinas.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <h3>Adicionar Nova Disciplina</h3>
            <form method="POST">
                <label>Nome da Disciplina *</label>
                <input type="text" name="nome" placeholder="Nome da disciplina" required>
                <div class="mt-4">
                    <button type="submit" name="add_disciplina" class="btn">Adicionar</button>
                </div>
            </form>
        <?php endif; ?>
            </div>
        </form>

        <h3>Disciplinas Existentes</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Ações</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($disciplinas)): ?>
            <tr>
                <td><?= $row['ID'] ?></td>
                <td><?= htmlspecialchars($row['Nome_disc']) ?></td>
                <td>
                    <a href="?editar=<?= $row['ID'] ?>" class="btn btn-sm">✏️ Editar</a>
                    <a href="?eliminar=<?= $row['ID'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Eliminar disciplina?')">🗑 Eliminar</a>
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