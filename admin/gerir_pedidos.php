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
    } elseif ($_POST['acao'] == 'eliminar') {
        $sql = "DELETE FROM pedidos_matricula WHERE id = $id_pedido AND estado IN ('aprovado', 'rejeitado')";
        if (mysqli_query($ligacao, $sql) && mysqli_affected_rows($ligacao) > 0) {
            $mensagem = "Pedido eliminado com sucesso.";
        } else {
            $mensagem = "Erro ao eliminar pedido.";
        }
    }
}

// Buscar pedidos pendentes (com nome do aluno)
$pendentes = mysqli_query($ligacao, "
    SELECT p.*, u.login, fa.nome_completo, fa.email as aluno_email, fa.telefone as aluno_tel,
           c1.Nome AS curso_nome_1, c2.Nome AS curso_nome_2, c3.Nome AS curso_nome_3
    FROM pedidos_matricula p
    JOIN users u ON p.login_aluno = u.login
    LEFT JOIN ficha_aluno fa ON p.login_aluno = fa.login
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
    <style>
        .envelopes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.25rem; margin-top: 1.5rem; }
        .envelope {
            background: white;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 12px;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
            cursor: pointer;
        }
        .envelope:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .envelope-front {
            padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            user-select: none;
        }
        .envelope-icon {
            width: 50px; height: 50px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .envelope-info { flex: 1; min-width: 0; }
        .envelope-name { font-weight: 700; font-size: 1rem; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .envelope-meta { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; }
        .envelope-arrow { font-size: 1.3rem; color: #94a3b8; transition: transform 0.2s; }
        .envelope.open .envelope-arrow { transform: rotate(180deg); }
        .envelope-body {
            display: none;
            padding: 0 1.5rem 1.5rem;
            border-top: 1px solid #f1f5f9;
        }
        .envelope.open .envelope-body { display: block; }
        .prioridade-list { list-style: none; padding: 0; margin: 1rem 0; }
        .prioridade-list li {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.6rem 0; border-bottom: 1px solid #f8fafc;
        }
        .prioridade-num {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.8rem; flex-shrink: 0;
        }
        .prio-1 { background: #fef3c7; color: #92400e; }
        .prio-2 { background: #e0e7ff; color: #3730a3; }
        .prio-3 { background: #f1f5f9; color: #475569; }
        .envelope-actions { display: grid; gap: 0.5rem; margin-top: 1rem; }
        .envelope-actions .btn { font-size: 0.85rem; padding: 0.5rem 1rem; text-align: left; }
        .envelope.open .envelope-front { background: #fafbfc; }

        /* Processados com envelope compacto */
        .processados-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .envelope-mini {
            background: white; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 1rem 1.25rem; position: relative;
        }
        .envelope-mini .estado-tag {
            position: absolute; top: 1rem; right: 1rem;
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            padding: 0.2rem 0.6rem; border-radius: 20px; letter-spacing: 0.04em;
        }
        .tag-aprovado { background: #d1fae5; color: #065f46; }
        .tag-rejeitado { background: #fee2e2; color: #991b1b; }
    </style>
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
            <h2>📬 Pedidos Pendentes <span style="font-weight:400; font-size:0.9rem; color:#64748b;">(<?= mysqli_num_rows($pendentes) ?>)</span></h2>
            <p>Clique num envelope para ver os detalhes e decidir.</p>
        </div>

        <?php if (mysqli_num_rows($pendentes) == 0): ?>
            <div style="text-align:center; padding:3rem 0; color:#94a3b8;">
                <div style="font-size:3rem; margin-bottom:0.5rem;">📭</div>
                <p>Não há pedidos pendentes.</p>
            </div>
        <?php else: ?>
            <div class="envelopes-grid">
                <?php while ($p = mysqli_fetch_assoc($pendentes)): ?>
                <div class="envelope" onclick="this.classList.toggle('open')">
                    <div class="envelope-front">
                        <div class="envelope-icon">✉️</div>
                        <div class="envelope-info">
                            <div class="envelope-name"><?= htmlspecialchars($p['nome_completo'] ?? $p['login']) ?></div>
                            <div class="envelope-meta">
                                <?= htmlspecialchars($p['login']) ?> &bull; <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?>
                            </div>
                        </div>
                        <span class="envelope-arrow">▼</span>
                    </div>
                    <div class="envelope-body" onclick="event.stopPropagation()">
                        <?php if (!empty($p['aluno_email']) || !empty($p['aluno_tel'])): ?>
                        <div style="font-size:0.8rem; color:#64748b; margin-bottom:0.5rem;">
                            <?php if (!empty($p['aluno_email'])): ?>📧 <?= htmlspecialchars($p['aluno_email']) ?><?php endif; ?>
                            <?php if (!empty($p['aluno_tel'])): ?> &bull; 📱 <?= htmlspecialchars($p['aluno_tel']) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <ul class="prioridade-list">
                            <li>
                                <span class="prioridade-num prio-1">1ª</span>
                                <span><?= htmlspecialchars($p['curso_nome_1'] ?? '—') ?></span>
                            </li>
                            <li>
                                <span class="prioridade-num prio-2">2ª</span>
                                <span><?= htmlspecialchars($p['curso_nome_2'] ?? '—') ?></span>
                            </li>
                            <li>
                                <span class="prioridade-num prio-3">3ª</span>
                                <span><?= htmlspecialchars($p['curso_nome_3'] ?? '—') ?></span>
                            </li>
                        </ul>

                        <form method="POST">
                            <input type="hidden" name="id_pedido" value="<?= $p['id'] ?>">
                            <input type="hidden" name="curso_id_escolhido" value="">
                            <label style="font-size:0.85rem; font-weight:600;">Observações:</label>
                            <textarea name="observacoes" rows="2" style="font-size:0.85rem;"></textarea>
                            <div class="envelope-actions">
                                <?php if ($p['curso_id']): ?>
                                <button type="submit" name="acao" value="aceitar_curso" class="btn btn-success"
                                    onclick="this.form.curso_id_escolhido.value='<?= $p['curso_id'] ?>'; return confirm('Aceitar 1ª prioridade: <?= htmlspecialchars(addslashes($p['curso_nome_1'] ?? '')) ?>?')">
                                    ✅ Aceitar 1ª — <?= htmlspecialchars($p['curso_nome_1'] ?? '') ?>
                                </button>
                                <?php endif; ?>
                                <?php if ($p['curso_id2']): ?>
                                <button type="submit" name="acao" value="aceitar_curso" class="btn btn-success"
                                    onclick="this.form.curso_id_escolhido.value='<?= $p['curso_id2'] ?>'; return confirm('Aceitar 2ª prioridade: <?= htmlspecialchars(addslashes($p['curso_nome_2'] ?? '')) ?>?')">
                                    ✅ Aceitar 2ª — <?= htmlspecialchars($p['curso_nome_2'] ?? '') ?>
                                </button>
                                <?php endif; ?>
                                <?php if ($p['curso_id3']): ?>
                                <button type="submit" name="acao" value="aceitar_curso" class="btn btn-success"
                                    onclick="this.form.curso_id_escolhido.value='<?= $p['curso_id3'] ?>'; return confirm('Aceitar 3ª prioridade: <?= htmlspecialchars(addslashes($p['curso_nome_3'] ?? '')) ?>?')">
                                    ✅ Aceitar 3ª — <?= htmlspecialchars($p['curso_nome_3'] ?? '') ?>
                                </button>
                                <?php endif; ?>
                                <button type="submit" name="acao" value="rejeitar" class="btn btn-danger"
                                    onclick="return confirm('Rejeitar pedido de <?= htmlspecialchars(addslashes($p['nome_completo'] ?? $p['login'])) ?>?')">
                                    ❌ Rejeitar Pedido
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <hr style="margin:2.5rem 0;">
        <h2>📋 Últimos Pedidos Processados</h2>
        <?php if (mysqli_num_rows($processados) == 0): ?>
            <p style="color:#94a3b8;">Nenhum pedido processado ainda.</p>
        <?php else: ?>
            <div class="processados-grid">
                <?php while ($p = mysqli_fetch_assoc($processados)): ?>
                <div class="envelope-mini">
                    <span class="estado-tag <?= $p['estado'] == 'aprovado' ? 'tag-aprovado' : 'tag-rejeitado' ?>">
                        <?= strtoupper($p['estado']) ?>
                    </span>
                    <div style="font-weight:700;"><?= htmlspecialchars($p['login']) ?></div>
                    <div style="font-size:0.8rem; color:#64748b; margin:0.25rem 0;">
                        1ª <?= htmlspecialchars($p['curso_nome_1'] ?? '—') ?>
                        &bull; 2ª <?= htmlspecialchars($p['curso_nome_2'] ?? '—') ?>
                        &bull; 3ª <?= htmlspecialchars($p['curso_nome_3'] ?? '—') ?>
                    </div>
                    <?php if (!empty($p['observacoes'])): ?>
                        <div style="font-size:0.8rem; color:#475569; margin-top:0.25rem;">💬 <?= htmlspecialchars($p['observacoes']) ?></div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:0.5rem;">
                        <span style="font-size:0.75rem; color:#94a3b8;">
                            <?= $p['data_decisao'] ? date('d/m/Y H:i', strtotime($p['data_decisao'])) : '' ?>
                            <?php if (!empty($p['decisor_nome'])): ?> — por <?= htmlspecialchars($p['decisor_nome']) ?><?php endif; ?>
                        </span>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="id_pedido" value="<?= $p['id'] ?>">
                            <input type="hidden" name="observacoes" value="">
                            <button type="submit" name="acao" value="eliminar" 
                                style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.8rem; padding:0.2rem 0.4rem; border-radius:4px;"
                                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'"
                                onclick="return confirm('Eliminar este pedido?')">
                                🗑️ Eliminar
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2026 IPCA</p>
    </footer>
</body>
</html>