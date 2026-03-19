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

// Verificar se o aluno já tem uma ficha aprovada
$ficha = mysqli_query($ligacao, "SELECT * FROM ficha_aluno WHERE login = '$login' AND estado = 'aprovada'");
$erro_ficha = '';
$curso_id = null;
if (mysqli_num_rows($ficha) == 0) {
    $erro_ficha = 'É necessário ter a ficha aprovada antes de fazer pedido de matrícula.';
    $dados_ficha = mysqli_query($ligacao, "SELECT * FROM ficha_aluno WHERE login = '$login'");
    $dados_ficha = $dados_ficha ? mysqli_fetch_assoc($dados_ficha) : [];
    $curso_id = $dados_ficha['curso_id'] ?? null;
} else {
    $dados_ficha = mysqli_fetch_assoc($ficha);
    $curso_id = $dados_ficha['curso_id'];
}

// Buscar todos os cursos para escolha de prioridades
$cursos = mysqli_query($ligacao, "SELECT ID, Nome FROM cursos WHERE ativo = 1 ORDER BY Nome");

$curso_nome = 'Curso não definido';
if (!empty($curso_id)) {
    $res_curso = mysqli_query($ligacao, "SELECT Nome FROM cursos WHERE ID = $curso_id LIMIT 1");
    if ($res_curso && mysqli_num_rows($res_curso) > 0) {
        $curso_row = mysqli_fetch_assoc($res_curso);
        $curso_nome = $curso_row['Nome'];
    }
}

// Verificar se já existe pedido pendente ou aprovado para este aluno
$pedido_existente = mysqli_query($ligacao, "SELECT * FROM pedidos_matricula WHERE login_aluno = '$login' AND estado IN ('pendente', 'aprovado')");
$tem_pedido_bloqueante = mysqli_num_rows($pedido_existente) > 0;
$pedido_bloqueante = $tem_pedido_bloqueante ? mysqli_fetch_assoc($pedido_existente) : null;

// Processar submissão do pedido
if (isset($_POST['fazer_pedido'])) {
    if ($tem_pedido_bloqueante) {
        $erro = $pedido_bloqueante['estado'] == 'aprovado' 
            ? "Já está matriculado. Não pode submeter outro pedido." 
            : "Já existe um pedido pendente. Aguarde a validação.";
    } else {
        $curso_1 = (int) ($_POST['curso_1'] ?? 0);
        $curso_2 = (int) ($_POST['curso_2'] ?? 0);
        $curso_3 = (int) ($_POST['curso_3'] ?? 0);

        if (!$curso_1 || !$curso_2 || !$curso_3) {
            $erro = "Escolha os três cursos por ordem de prioridade.";
        } elseif ($curso_1 == $curso_2 || $curso_1 == $curso_3 || $curso_2 == $curso_3) {
            $erro = "Escolha cursos diferentes para cada prioridade.";
        } else {
            $sql = "INSERT INTO pedidos_matricula (login_aluno, curso_id, curso_id2, curso_id3) VALUES ('$login', $curso_1, $curso_2, $curso_3)";
            if (mysqli_query($ligacao, $sql)) {
                header('Location: pedido_matricula.php?sucesso=1');
                exit;
            } else {
                $erro = "Erro ao fazer pedido: " . mysqli_error($ligacao);
            }
        }
    }
}

// Checar mensagens de sucesso/erro via GET
if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1') {
    $sucesso = "Pedido de matrícula realizado com sucesso!";
}

// Buscar pedidos anteriores do aluno
$pedidos = mysqli_query($ligacao, "
    SELECT p.*, c1.Nome as curso_nome_1, c2.Nome as curso_nome_2, c3.Nome as curso_nome_3
    FROM pedidos_matricula p
    LEFT JOIN cursos c1 ON p.curso_id = c1.ID
    LEFT JOIN cursos c2 ON p.curso_id2 = c2.ID
    LEFT JOIN cursos c3 ON p.curso_id3 = c3.ID
    WHERE p.login_aluno = '$login'
    ORDER BY p.data_pedido DESC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido de Matrícula &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Pedido de Matrícula</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>
        <?php if (!empty($erro_ficha)): ?>
            <div class="erro"><?= htmlspecialchars($erro_ficha) ?></div>
        <?php endif; ?>

        <h2>Curso pretendido: <?= htmlspecialchars($dados_ficha['nome_curso'] ?? 'Curso não definido na ficha') ?></h2>

        <?php if (!empty($erro_ficha)): ?>
            <p>Complete e aprove sua ficha antes de fazer pedido de matrícula.</p>
        <?php elseif ($tem_pedido_bloqueante && $pedido_bloqueante['estado'] == 'aprovado'): ?>
            <div class="sucesso">✅ Já está matriculado. Não é possível submeter outro pedido.</div>
        <?php elseif ($tem_pedido_bloqueante && $pedido_bloqueante['estado'] == 'pendente'): ?>
            <p>Já possui um pedido pendente. Aguarde a validação pelos serviços académicos.</p>
        <?php else: ?>
            <form method="POST">
                <p>Escolha os três cursos por ordem de prioridade (1 = mais importante).</p>
                <label>1ª Prioridade</label>
                <select name="curso_1" required>
                    <option value="">-- Selecionar curso --</option>
                    <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                        <option value="<?= $curso['ID'] ?>" <?= ($curso['ID'] == $curso_id) ? 'selected' : '' ?>><?= htmlspecialchars($curso['Nome']) ?></option>
                    <?php endwhile; ?>
                </select>
                <?php mysqli_data_seek($cursos,0); ?>
                <label>2ª Prioridade</label>
                <select name="curso_2" required>
                    <option value="">-- Selecionar curso --</option>
                    <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                        <option value="<?= $curso['ID'] ?>"><?= htmlspecialchars($curso['Nome']) ?></option>
                    <?php endwhile; ?>
                </select>
                <?php mysqli_data_seek($cursos,0); ?>
                <label>3ª Prioridade</label>
                <select name="curso_3" required>
                    <option value="">-- Selecionar curso --</option>
                    <?php while ($curso = mysqli_fetch_assoc($cursos)): ?>
                        <option value="<?= $curso['ID'] ?>"><?= htmlspecialchars($curso['Nome']) ?></option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="fazer_pedido" class="btn">Solicitar Matrícula</button>
            </form>
        <?php endif; ?>

        <hr>
        <h3>Histórico de Pedidos</h3>
        <?php if (mysqli_num_rows($pedidos) > 0): ?>
            <table>
                <tr>
                    <th>Data</th>
                    <th>1ª Prioridade</th>
                    <th>2ª Prioridade</th>
                    <th>3ª Prioridade</th>
                    <th>Estado</th>
                    <th>Observações</th>
                </tr>
                <?php while ($p = mysqli_fetch_assoc($pedidos)): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?></td>
                    <td><?= htmlspecialchars($p['curso_nome_1'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['curso_nome_2'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['curso_nome_3'] ?? '—') ?></td>
                    <td>
                        <?php
                        $cor = $p['estado'] == 'aprovado' ? 'green' : ($p['estado'] == 'rejeitado' ? 'red' : 'orange');
                        echo "<span style='color:" . $cor . ";'>" . strtoupper($p['estado']) . "</span>";
                        ?>
                    </td>
                    <td><?= nl2br(htmlspecialchars($p['observacoes'] ?? '')) ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Nenhum pedido realizado até ao momento.</p>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>