<?php
session_start();
require_once '../config.php';

// Proteger página: só admin
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

// Processar adição de nova associação (plano)
if (isset($_POST['add_plano'])) {
    $curso_id = (int)$_POST['curso_id'];
    $disciplina_id = (int)$_POST['disciplina_id'];
    $semestre = (int)$_POST['semestre'];
    $ano = (int)$_POST['ano'];
    
    // Verificar se já existe (para evitar duplicados)
    $check = mysqli_query($ligacao, "SELECT * FROM plano_estudos WHERE CURSOS = $curso_id AND DISCIPLINA = $disciplina_id");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($ligacao, "INSERT INTO plano_estudos (CURSOS, DISCIPLINA, semestre, ano) VALUES ($curso_id, $disciplina_id, $semestre, $ano)");
    }
    header('Location: gerir_planos.php');
    exit;
}

// Processar eliminação de associação
if (isset($_GET['eliminar_curso']) && isset($_GET['eliminar_disc'])) {
    $curso_id = (int)$_GET['eliminar_curso'];
    $disciplina_id = (int)$_GET['eliminar_disc'];
    mysqli_query($ligacao, "DELETE FROM plano_estudos WHERE CURSOS = $curso_id AND DISCIPLINA = $disciplina_id");
    header('Location: gerir_planos.php');
    exit;
}

// Buscar todos os cursos para o select
$cursos = mysqli_query($ligacao, "SELECT ID, Nome FROM cursos ORDER BY Nome");

// Buscar todas as disciplinas para o select
$disciplinas = mysqli_query($ligacao, "SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc");

// Buscar todas as associações com os nomes (JOIN), agrupadas por curso
$planos = mysqli_query($ligacao, "
    SELECT p.CURSOS, p.DISCIPLINA, p.semestre, p.ano, c.Nome AS nome_curso, d.Nome_disc AS nome_disciplina
    FROM plano_estudos p
    JOIN cursos c ON p.CURSOS = c.ID
    JOIN disciplinas d ON p.DISCIPLINA = d.ID
    ORDER BY c.Nome, p.ano, p.semestre, d.Nome_disc
");

// Organizar por curso
$planos_por_curso = [];
while ($row = mysqli_fetch_assoc($planos)) {
    $planos_por_curso[$row['nome_curso']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Estudo &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Planos de Estudo</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Planos de Estudo</h2>
            <p>Associe disciplinas aos cursos do sistema.</p>
        </div>
        <form method="POST" class="plano-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Curso:</label>
                    <select name="curso_id" required>
                        <option value="">Selecione um curso</option>
                        <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                        <option value="<?= $curso['ID'] ?>"><?= htmlspecialchars($curso['Nome']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Disciplina:</label>
                    <select name="disciplina_id" required>
                        <option value="">Selecione uma disciplina</option>
                        <?php while ($disciplina = mysqli_fetch_assoc($disciplinas)): ?>
                        <option value="<?= $disciplina['ID'] ?>"><?= htmlspecialchars($disciplina['Nome_disc']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ano:</label>
                    <select name="ano" required>
                        <option value="1">1.º Ano</option>
                        <option value="2">2.º Ano</option>
                        <option value="3">3.º Ano</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semestre:</label>
                    <select name="semestre" required>
                        <option value="1">1.º Semestre</option>
                        <option value="2">2.º Semestre</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_plano">Adicionar ao Plano</button>
        </form>

        <h2>Planos por Curso</h2>

        <?php if (empty($planos_por_curso)): ?>
            <div class="empty-state">
                <p>Nenhuma disciplina associada a cursos ainda.</p>
            </div>
        <?php else: ?>
            <?php foreach ($planos_por_curso as $nome_curso => $disciplinas_curso): ?>
                <div class="envelope">
                    <div class="envelope-header" onclick="this.parentElement.classList.toggle('envelope-open')">
                        <span>🎓</span>
                        <h3><?= htmlspecialchars($nome_curso) ?></h3>
                        <span class="envelope-count"><?= count($disciplinas_curso) ?> disciplina(s)</span>
                        <span class="envelope-toggle">▼</span>
                    </div>
                    <div class="envelope-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Disciplina</th>
                                    <th>Ano</th>
                                    <th>Semestre</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($disciplinas_curso as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nome_disciplina']) ?></td>
                                    <td><?= $row['ano'] ?>.º Ano</td>
                                    <td><?= $row['semestre'] ?>.º Semestre</td>
                                    <td>
                                        <a href="?eliminar_curso=<?= $row['CURSOS'] ?>&eliminar_disc=<?= $row['DISCIPLINA'] ?>" 
                                           class="btn btn-sm btn-danger" onclick="return confirm('Remover esta disciplina do plano?')">Remover</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>