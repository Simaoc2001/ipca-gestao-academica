<?php
session_start();
require_once '../config.php';

// Proteger página: só admin
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['pauta_id'])) {
    header('Location: gerir_pautas.php');
    exit;
}

$pauta_id = (int)$_GET['pauta_id'];

// Buscar dados da pauta
$pauta = mysqli_query($ligacao, "
    SELECT p.*, d.Nome_disc as disciplina_nome, e.nome as epoca_nome
    FROM pautas p
    JOIN disciplinas d ON p.disciplina_id = d.ID
    JOIN epocas e ON p.epoca_id = e.id
    WHERE p.id = $pauta_id
");
if (mysqli_num_rows($pauta) == 0) {
    header('Location: gerir_pautas.php');
    exit;
}
$pauta_data = mysqli_fetch_assoc($pauta);

// Processar actualização de notas
if (isset($_POST['guardar_notas'])) {
    foreach ($_POST['nota'] as $aluno_login => $valor_nota) {
        $aluno_login = mysqli_real_escape_string($ligacao, $aluno_login);
        $nota = $valor_nota === '' ? 'NULL' : (float)$valor_nota;
        $lancado_por = $_SESSION['login'];

        // Verificar se já existe registo para este aluno nesta pauta
        $check = mysqli_query($ligacao, "SELECT id FROM notas WHERE pauta_id = $pauta_id AND aluno_login = '$aluno_login'");
        if (mysqli_num_rows($check) > 0) {
            // Update
            $sql = "UPDATE notas SET nota = $nota, lancado_por = '$lancado_por' WHERE pauta_id = $pauta_id AND aluno_login = '$aluno_login'";
        } else {
            // Insert
            $sql = "INSERT INTO notas (pauta_id, aluno_login, nota, lancado_por) VALUES ($pauta_id, '$aluno_login', $nota, '$lancado_por')";
        }
        mysqli_query($ligacao, $sql);
    }
    $mensagem = "Notas guardadas com sucesso!";
}

// Buscar alunos elegíveis: alunos com matrícula aprovada num curso que inclua esta disciplina no plano de estudos
$disc_id = $pauta_data['disciplina_id'];
$alunos = mysqli_query($ligacao, "
    SELECT DISTINCT u.login, f.nome_completo 
    FROM ficha_aluno f
    JOIN users u ON f.login = u.login
    JOIN pedidos_matricula pm ON pm.login_aluno = f.login AND pm.estado = 'aprovado'
    JOIN plano_estudos pe ON pe.CURSOS = f.curso_id AND pe.DISCIPLINA = $disc_id
    WHERE f.estado = 'aprovada'
    ORDER BY f.nome_completo
");

// Buscar notas já lançadas para esta pauta
$notas_lancadas = [];
$result_notas = mysqli_query($ligacao, "SELECT aluno_login, nota FROM notas WHERE pauta_id = $pauta_id");
while ($row = mysqli_fetch_assoc($result_notas)) {
    $notas_lancadas[$row['aluno_login']] = $row['nota'];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lançar Notas &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Lançamento de Notas</small></h1>
            </div>
            <nav>
                <a href="gerir_pautas.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><?= htmlspecialchars($pauta_data['disciplina_nome']) ?> – <?= htmlspecialchars($pauta_data['epoca_nome']) ?> – <?= $pauta_data['ano_letivo'] ?></h2>

        <?php if (isset($mensagem)): ?>
            <div class="sucesso"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST">
            <table>
                <tr>
                    <th>Aluno</th>
                    <th>Nota (0-20)</th>
                </tr>
                <?php while ($aluno = mysqli_fetch_assoc($alunos)): ?>
                <tr>
                    <td><?= htmlspecialchars($aluno['nome_completo']) ?> (<?= $aluno['login'] ?>)</td>
                    <td>
                        <input type="number" step="0.1" min="0" max="20" 
                               name="nota[<?= $aluno['login'] ?>]" 
                               value="<?= isset($notas_lancadas[$aluno['login']]) ? htmlspecialchars($notas_lancadas[$aluno['login']]) : '' ?>">
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            <button type="submit" name="guardar_notas" class="btn">Guardar Notas</button>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>