<?php
session_start();
require_once '../config.php';

// Proteger página: só aluno
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ALUNO') {
    header('Location: ../login.php');
    exit;
}

$login = mysqli_real_escape_string($ligacao, $_SESSION['login']);

// Buscar anos letivos disponíveis para este aluno
$anos_query = mysqli_query($ligacao, "
    SELECT DISTINCT p.ano_letivo
    FROM notas n
    JOIN pautas p ON n.pauta_id = p.id
    WHERE n.aluno_login = '$login'
    ORDER BY p.ano_letivo DESC
");

$anos_disponiveis = [];
while ($a = mysqli_fetch_assoc($anos_query)) {
    $anos_disponiveis[] = $a['ano_letivo'];
}

// Ano letivo selecionado (por defeito o mais recente)
$ano_selecionado = isset($_GET['ano']) ? $_GET['ano'] : (!empty($anos_disponiveis) ? $anos_disponiveis[0] : '');
$ano_selecionado_esc = mysqli_real_escape_string($ligacao, $ano_selecionado);

// Buscar notas filtradas por ano letivo e agrupadas por época
$notas_normal = null;
$notas_recurso = null;
$notas_especial = null;

if (!empty($ano_selecionado)) {
    $notas_normal = mysqli_query($ligacao, "
        SELECT n.nota, n.data_lancamento, d.Nome_disc, e.nome as epoca_nome
        FROM notas n
        JOIN pautas p ON n.pauta_id = p.id
        JOIN disciplinas d ON p.disciplina_id = d.ID
        JOIN epocas e ON p.epoca_id = e.id
        WHERE n.aluno_login = '$login' AND p.ano_letivo = '$ano_selecionado_esc' AND p.epoca_id = 1
        ORDER BY d.Nome_disc
    ");

    $notas_recurso = mysqli_query($ligacao, "
        SELECT n.nota, n.data_lancamento, d.Nome_disc, e.nome as epoca_nome
        FROM notas n
        JOIN pautas p ON n.pauta_id = p.id
        JOIN disciplinas d ON p.disciplina_id = d.ID
        JOIN epocas e ON p.epoca_id = e.id
        WHERE n.aluno_login = '$login' AND p.ano_letivo = '$ano_selecionado_esc' AND p.epoca_id = 2
        ORDER BY d.Nome_disc
    ");

    $notas_especial = mysqli_query($ligacao, "
        SELECT n.nota, n.data_lancamento, d.Nome_disc, e.nome as epoca_nome
        FROM notas n
        JOIN pautas p ON n.pauta_id = p.id
        JOIN disciplinas d ON p.disciplina_id = d.ID
        JOIN epocas e ON p.epoca_id = e.id
        WHERE n.aluno_login = '$login' AND p.ano_letivo = '$ano_selecionado_esc' AND p.epoca_id = 3
        ORDER BY d.Nome_disc
    ");
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Notas &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Consulta de Notas</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Minhas Notas</h2>
            <p>Consulte as suas notas, organizadas por ano letivo e época.</p>
        </div>

        <?php if (empty($anos_disponiveis)): ?>
            <div class="erro">Ainda não foram lançadas notas para si.</div>
        <?php else: ?>
            <!-- SELETOR DE ANO LETIVO -->
            <div class="ano-selector">
                <span class="ano-label">Ano Letivo:</span>
                <div class="ano-buttons">
                    <?php foreach ($anos_disponiveis as $ano): ?>
                        <a href="?ano=<?= urlencode($ano) ?>" 
                           class="btn btn-sm <?= ($ano == $ano_selecionado) ? '' : 'btn-outline' ?>">
                            <?= htmlspecialchars($ano) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            function render_notas_table($result, $titulo, $icone) {
                if (!$result || mysqli_num_rows($result) == 0) return;
                ?>
                <div class="envelope envelope-open">
                    <div class="envelope-header" onclick="this.parentElement.classList.toggle('envelope-open')">
                        <span><?= $icone ?></span>
                        <h3><?= $titulo ?></h3>
                        <span class="envelope-toggle">▼</span>
                    </div>
                    <div class="envelope-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Disciplina</th>
                                    <th>Nota</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($n = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($n['Nome_disc']) ?></td>
                                    <td>
                                        <?php if ($n['nota'] !== null): ?>
                                            <strong class="<?= $n['nota'] >= 9.5 ? 'nota-aprovado' : 'nota-reprovado' ?>">
                                                <?= number_format($n['nota'], 1) ?>
                                            </strong>
                                        <?php else: ?>
                                            <em>Não lançada</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($n['data_lancamento'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
            }

            render_notas_table($notas_normal, 'Época Normal', '📗');
            render_notas_table($notas_recurso, 'Época de Recurso', '📙');
            render_notas_table($notas_especial, 'Época Especial', '📕');

            $total = 0;
            if ($notas_normal) { mysqli_data_seek($notas_normal, 0); $total += mysqli_num_rows($notas_normal); }
            if ($notas_recurso) { mysqli_data_seek($notas_recurso, 0); $total += mysqli_num_rows($notas_recurso); }
            if ($notas_especial) { mysqli_data_seek($notas_especial, 0); $total += mysqli_num_rows($notas_especial); }
            if ($total == 0): ?>
                <div class="empty-state">
                    <p>Sem notas registadas para o ano letivo <?= htmlspecialchars($ano_selecionado) ?>.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>