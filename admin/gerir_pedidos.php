<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'FUNCIONARIO') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar aprovação/rejeição de pedido
if (isset($_POST['acao']) && isset($_POST['id_pedido'])) {
    $id_pedido = (int)$_POST['id_pedido'];
    $observacoes = mysqli_real_escape_string($ligacao, trim($_POST['observacoes']));
    $data_decisao = date('Y-m-d H:i:s');
    $decisor = $_SESSION['login'];

    if ($_POST['acao'] == 'aceitar_curso' && isset($_POST['curso_id_escolhido'])) {
        $curso_id_escolhido = (int)$_POST['curso_id_escolhido'];
        $pedido = mysqli_query($ligacao, "SELECT login_aluno, curso_id, curso_id2, curso_id3 FROM pedidos_matricula WHERE id = $id_pedido AND estado = 'pendente'");

        if ($pedido && mysqli_num_rows($pedido) == 1) {
            $dados = mysqli_fetch_assoc($pedido);
            $login_aluno = $dados['login_aluno'];
            $opcoes_validas = [
                (int)$dados['curso_id'],
                (int)$dados['curso_id2'],
                (int)$dados['curso_id3']
            ];

            if (in_array($curso_id_escolhido, $opcoes_validas) && $curso_id_escolhido > 0) {
                $sql = "UPDATE pedidos_matricula SET 
                        estado = 'aprovado',
                        observacoes = '$observacoes',
                        data_decisao = '$data_decisao',
                        decisor_login = '$decisor'
                        WHERE id = $id_pedido AND estado = 'pendente'";

                if (mysqli_query($ligacao, $sql)) {
                    mysqli_query($ligacao, "UPDATE ficha_aluno SET curso_id = $curso_id_escolhido, estado = 'aprovada' WHERE login = '" . mysqli_real_escape_string($ligacao, $login_aluno) . "'");
                    $mensagem = "Pedido aprovado. Curso selecionado salvo na ficha do aluno.";
                } else {
                    $mensagem = "Erro ao aprovar pedido: " . mysqli_error($ligacao);
                }
            } else {
                $mensagem = "Curso escolhido não corresponde às prioridades do pedido.";
            }
        } else {
            $mensagem = "Pedido não encontrado ou já processado.";
        }
    } elseif ($_POST['acao'] == 'rejeitar') {
        $sql = "UPDATE pedidos_matricula SET 
                estado = 'rejeitado',
                observacoes = '$observacoes',
                data_decisao = '$data_decisao',
                decisor_login = '$decisor'
                WHERE id = $id_pedido AND estado = 'pendente'";

        if (mysqli_query($ligacao, $sql)) {
            $mensagem = "Pedido rejeitado com sucesso.";
        } else {
            $mensagem = "Erro ao rejeitar: " . mysqli_error($ligacao);
        }
    }
}

// Buscar pedidos pendentes
$pendentes = mysqli_query($ligacao, "
    SELECT p.*, u.login, c1.Nome AS curso_nome_1, c2.Nome AS curso_nome_2, c3.Nome AS curso_nome_3
    FROM pedidos_matricula p
    JOIN users u ON p.login_aluno = u.login
    LEFT JOIN cursos c1 ON p.curso_id = c1.ID
    LEFT JOIN cursos c2 ON p.curso_id2 = c2.ID
    LEFT JOIN cursos c3 ON p.curso_id3 = c3.ID
    WHERE p.estado = 'pendente'
    ORDER BY p.data_pedido ASC
");

// Buscar últimos pedidos processados (opcional)
$processados = mysqli_query($ligacao, "
    SELECT p.*, u.login, c1.Nome as curso_nome_1, c2.Nome as curso_nome_2, c3.Nome as curso_nome_3, d.login as decisor_nome
    FROM pedidos_matricula p
    JOIN users u ON p.login_aluno = u.login
    LEFT JOIN cursos c1 ON p.curso_id = c1.ID
    LEFT JOIN cursos c2 ON p.curso_id2 = c2.ID
    LEFT JOIN cursos c3 ON p.curso_id3 = c3.ID
    LEFT JOIN users d ON p.decisor_login = d.login
    WHERE p.estado != 'pendente'
    ORDER BY p.data_decisao DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos de Matrícula &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Pedidos de Matrícula</small></h1>
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
            <h2>Pedidos Pendentes</h2>
            <p>Reveja e processe os pedidos de matrícula dos alunos.</p>
        </div>
        <?php if (mysqli_num_rows($pendentes) == 0): ?>
            <p>Não há pedidos pendentes.</p>
        <?php else: ?>
            <?php while ($p = mysqli_fetch_assoc($pendentes)): ?>
                <div class="pedido-card">
                    <p><strong>Aluno:</strong> <?= htmlspecialchars($p['login']) ?></p>
                    <p><strong>1ª prioridade:</strong> <?= htmlspecialchars($p['curso_nome_1'] ?? '—') ?></p>
                    <p><strong>2ª prioridade:</strong> <?= htmlspecialchars($p['curso_nome_2'] ?? '—') ?></p>
                    <p><strong>3ª prioridade:</strong> <?= htmlspecialchars($p['curso_nome_3'] ?? '—') ?></p>
                    <p><strong>Data do pedido:</strong> <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?></p>
                    
                    <form method="POST" class="acoes-form">
                        <input type="hidden" name="id_pedido" value="<?= $p['id'] ?>">
                        <label>Observações (opcional):</label>
                        <textarea name="observacoes"></textarea>
                        <div class="mt-4" style="display:grid; gap:8px;">
                            <?php if ($p['curso_id']): ?>
                                <button type="submit" name="acao" value="aceitar_curso" formaction="" class="btn btn-success" onclick="this.form.curso_id_escolhido.value='<?= $p['curso_id'] ?>'; return confirm('Aceitar 1ª prioridade?')">✅ Aceitar 1ª: <?= htmlspecialchars($p['curso_nome_1'] ?? '---') ?></button>
                            <?php endif; ?>
                            <?php if ($p['curso_id2']): ?>
                                <button type="submit" name="acao" value="aceitar_curso" formaction="" class="btn btn-success" onclick="this.form.curso_id_escolhido.value='<?= $p['curso_id2'] ?>'; return confirm('Aceitar 2ª prioridade?')">✅ Aceitar 2ª: <?= htmlspecialchars($p['curso_nome_2'] ?? '---') ?></button>
                            <?php endif; ?>
                            <?php if ($p['curso_id3']): ?>
                                <button type="submit" name="acao" value="aceitar_curso" formaction="" class="btn btn-success" onclick="this.form.curso_id_escolhido.value='<?= $p['curso_id3'] ?>'; return confirm('Aceitar 3ª prioridade?')">✅ Aceitar 3ª: <?= htmlspecialchars($p['curso_nome_3'] ?? '---') ?></button>
                            <?php endif; ?>
                            <input type="hidden" name="curso_id_escolhido" value="">
                            <button type="submit" name="acao" value="rejeitar" class="btn btn-danger" onclick="return confirm('Rejeitar pedido inteiro?')">❌ Rejeitar pedido</button>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <hr>
        <h2>Últimos Pedidos Processados</h2>
        <?php if (mysqli_num_rows($processados) == 0): ?>
            <p>Nenhum pedido processado ainda.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Aluno</th>
                    <th>1ª prioridade</th>
                    <th>2ª prioridade</th>
                    <th>3ª prioridade</th>
                    <th>Data pedido</th>
                    <th>Estado</th>
                    <th>Observações</th>
                    <th>Decisor</th>
                    <th>Data decisão</th>
                </tr>
                <?php while ($p = mysqli_fetch_assoc($processados)): ?>
                <tr>
                    <td><?= htmlspecialchars($p['login']) ?></td>
                    <td><?= htmlspecialchars($p['curso_nome_1'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['curso_nome_2'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['curso_nome_3'] ?? '—') ?></td>
                    <td><?= date('d/m/Y', strtotime($p['data_pedido'])) ?></td>
                    <td><?= strtoupper($p['estado']) ?></td>
                    <td><?= nl2br(htmlspecialchars($p['observacoes'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($p['decisor_nome'] ?? '') ?></td>
                    <td><?= $p['data_decisao'] ? date('d/m/Y H:i', strtotime($p['data_decisao'])) : '' ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2026 IPCA</p>
    </footer>
</body>
</html>