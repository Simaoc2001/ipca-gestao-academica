<?php
session_start();
require_once '../config.php';

// Proteger página: só admin (ou funcionário)
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar criação de nova pauta
if (isset($_POST['criar_pauta'])) {
    $disciplina_id = (int)$_POST['disciplina_id'];
    $epoca_id = (int)$_POST['epoca_id'];
    $ano_letivo = mysqli_real_escape_string($ligacao, $_POST['ano_letivo']);
    $criado_por = $_SESSION['login'];

    // Verificar se já existe pauta para esta combinação
    $check = mysqli_query($ligacao, "SELECT id FROM pautas WHERE disciplina_id=$disciplina_id AND epoca_id=$epoca_id AND ano_letivo='$ano_letivo'");
    if (mysqli_num_rows($check) > 0) {
        $mensagem = "Já existe uma pauta para esta disciplina/época/ano letivo.";
    } else {
        $sql = "INSERT INTO pautas (disciplina_id, epoca_id, ano_letivo, criado_por) VALUES ($disciplina_id, $epoca_id, '$ano_letivo', '$criado_por')";
        if (mysqli_query($ligacao, $sql)) {
            $nova_pauta_id = mysqli_insert_id($ligacao);
            // Agora vamos adicionar os alunos elegíveis (os que têm ficha aprovada? ou inscrições aprovadas?)
            // Por simplicidade, vamos buscar todos os alunos com pedido de matrícula aprovado para cursos que tenham esta disciplina no plano?
            // Mas isso é complexo. Vamos assumir que o funcionário pode selecionar manualmente os alunos ao lançar notas.
            // Por isso, apenas criamos a pauta vazia.
            $mensagem = "Pauta criada com sucesso!";
        } else {
            $mensagem = "Erro ao criar pauta: " . mysqli_error($ligacao);
        }
    }
}

// Processar eliminação de pauta
if (isset($_GET['eliminar_pauta'])) {
    $id_pauta = (int)$_GET['eliminar_pauta'];
    // Eliminar notas associadas primeiro, depois a pauta
    mysqli_query($ligacao, "DELETE FROM notas WHERE pauta_id = $id_pauta");
    mysqli_query($ligacao, "DELETE FROM pautas WHERE id = $id_pauta");
    header('Location: gerir_pautas.php');
    exit;
}

// Buscar listas para selects
$disciplinas = mysqli_query($ligacao, "SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc");
$epocas = mysqli_query($ligacao, "SELECT id, nome FROM epocas ORDER BY id");

// Buscar cursos para filtro
$cursos_filtro = mysqli_query($ligacao, "SELECT ID, Nome FROM cursos ORDER BY Nome");

// Buscar disciplinas para filtro (cópia separada)
$disciplinas_filtro = mysqli_query($ligacao, "SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc");

// Filtros
$filtro_ano = isset($_GET['filtro_ano']) ? mysqli_real_escape_string($ligacao, $_GET['filtro_ano']) : '';
$filtro_disc = isset($_GET['filtro_disc']) ? (int)$_GET['filtro_disc'] : 0;
$filtro_curso = isset($_GET['filtro_curso']) ? (int)$_GET['filtro_curso'] : 0;

// Construir query com filtros
$where = [];
if ($filtro_ano) $where[] = "p.ano_letivo = '$filtro_ano'";
if ($filtro_disc) $where[] = "p.disciplina_id = $filtro_disc";
if ($filtro_curso) {
    $where[] = "p.disciplina_id IN (SELECT DISCIPLINA FROM plano_estudos WHERE CURSOS = $filtro_curso)";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Listar pautas existentes agrupadas por ano letivo
$pautas = mysqli_query($ligacao, "
    SELECT p.*, d.Nome_disc as disciplina_nome, e.nome as epoca_nome, u.login as criador
    FROM pautas p
    JOIN disciplinas d ON p.disciplina_id = d.ID
    JOIN epocas e ON p.epoca_id = e.id
    LEFT JOIN users u ON p.criado_por = u.login
    $where_sql
    ORDER BY p.ano_letivo DESC, d.Nome_disc, e.id
");

$pautas_por_ano = [];
while ($p = mysqli_fetch_assoc($pautas)) {
    $pautas_por_ano[$p['ano_letivo']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pautas de Avaliação &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Pautas de Avaliação</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($mensagem): ?>
            <div class="sucesso"><?= $mensagem ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h2>Pautas</h2>
            <p>Crie e gerencie pautas de avaliação para as disciplinas.</p>
        </div>
        <form method="POST">
            <label>Disciplina:</label>
            <select name="disciplina_id" required>
                <option value="">Selecione</option>
                <?php while ($d = mysqli_fetch_assoc($disciplinas)): ?>
                <option value="<?= $d['ID'] ?>"><?= htmlspecialchars($d['Nome_disc']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Época:</label>
            <select name="epoca_id" required>
                <option value="">Selecione</option>
                <?php while ($e = mysqli_fetch_assoc($epocas)): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Ano Letivo:</label>
            <select name="ano_letivo" required>
                <option value="">Selecione</option>
                <option value="2025/2026">2025/2026</option>
                <option value="2026/2027">2026/2027</option>
            </select>

            <button type="submit" name="criar_pauta" class="btn">Criar Pauta</button>
        </form>

        <h2>Pautas Existentes</h2>

        <!-- FILTROS -->
        <form method="GET" class="filtros-bar">
            <div class="form-row">
                <div class="form-group">
                    <label>Ano Letivo:</label>
                    <select name="filtro_ano">
                        <option value="">Todos</option>
                        <option value="2025/2026" <?= $filtro_ano == '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                        <option value="2026/2027" <?= $filtro_ano == '2026/2027' ? 'selected' : '' ?>>2026/2027</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Disciplina:</label>
                    <select name="filtro_disc">
                        <option value="">Todas</option>
                        <?php while ($df = mysqli_fetch_assoc($disciplinas_filtro)): ?>
                        <option value="<?= $df['ID'] ?>" <?= $filtro_disc == $df['ID'] ? 'selected' : '' ?>><?= htmlspecialchars($df['Nome_disc']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Curso:</label>
                    <select name="filtro_curso">
                        <option value="">Todos</option>
                        <?php while ($cf = mysqli_fetch_assoc($cursos_filtro)): ?>
                        <option value="<?= $cf['ID'] ?>" <?= $filtro_curso == $cf['ID'] ? 'selected' : '' ?>><?= htmlspecialchars($cf['Nome']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="justify-content:flex-end;">
                    <label>&nbsp;</label>
                    <div style="display:flex; gap:0.5rem;">
                        <button type="submit" class="btn btn-sm">🔍 Filtrar</button>
                        <a href="gerir_pautas.php" class="btn btn-sm btn-outline">Limpar</a>
                    </div>
                </div>
            </div>
        </form>

        <?php if (empty($pautas_por_ano)): ?>
            <div class="empty-state"><p>Nenhuma pauta criada ainda.</p></div>
        <?php else: ?>
            <?php foreach ($pautas_por_ano as $ano => $lista_pautas): ?>
                <div class="envelope envelope-open">
                    <div class="envelope-header" onclick="this.parentElement.classList.toggle('envelope-open')">
                        <span>📅</span>
                        <h3><?= htmlspecialchars($ano) ?></h3>
                        <span class="envelope-count"><?= count($lista_pautas) ?> pauta(s)</span>
                        <span class="envelope-toggle">▼</span>
                    </div>
                    <div class="envelope-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Disciplina</th>
                                    <th>Época</th>
                                    <th>Criado por</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_pautas as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['disciplina_nome']) ?></td>
                                    <td><?= htmlspecialchars($p['epoca_nome']) ?></td>
                                    <td><?= htmlspecialchars($p['criador'] ?? 'Sistema') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($p['data_criacao'])) ?></td>
                                    <td>
                                        <a href="lancar_notas.php?pauta_id=<?= $p['id'] ?>" class="btn btn-sm">🎯 Lançar Notas</a>
                                        <a href="?eliminar_pauta=<?= $p['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Eliminar esta pauta e todas as notas associadas?')">🗑 Eliminar</a>
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