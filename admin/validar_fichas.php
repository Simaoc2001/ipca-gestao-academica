<?php
session_start();
require_once '../config.php';

// Proteger página: só admin (gestor pedagógico)
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';

// Processar aprovação/rejeição
if (isset($_POST['acao']) && isset($_POST['id_ficha'])) {
    $id_ficha = (int)$_POST['id_ficha'];
    $observacoes = mysqli_real_escape_string($ligacao, trim($_POST['observacoes']));
    $novo_estado = $_POST['acao'] == 'aprovar' ? 'aprovada' : 'rejeitada';
    $data_validacao = date('Y-m-d H:i:s');
    $validado_por = $_SESSION['login'];

    $sql = "UPDATE ficha_aluno SET 
            estado = '$novo_estado',
            observacoes = '$observacoes',
            data_validacao = '$data_validacao',
            validado_por = '$validado_por'
            WHERE id = $id_ficha AND estado = 'submetida'";
    
    if (mysqli_query($ligacao, $sql)) {
        $mensagem = "Ficha atualizada com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar: " . mysqli_error($ligacao);
    }
}

// Buscar todas as fichas submetidas (pendentes)
$fichas = mysqli_query($ligacao, "
    SELECT f.*, u.login, c.Nome as curso_nome 
    FROM ficha_aluno f
    JOIN users u ON f.login = u.login
    LEFT JOIN cursos c ON f.curso_id = c.ID
    WHERE f.estado = 'submetida'
    ORDER BY f.data_submissao DESC
");

// Buscar fichas já processadas (aprovadas/rejeitadas)
$processadas = mysqli_query($ligacao, "
    SELECT f.*, u.login, c.Nome as curso_nome 
    FROM ficha_aluno f
    JOIN users u ON f.login = u.login
    LEFT JOIN cursos c ON f.curso_id = c.ID
    WHERE f.estado IN ('aprovada', 'rejeitada')
    ORDER BY f.data_validacao DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Fichas &mdash; IPCA</title>
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
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            overflow: hidden;
        }
        .envelope-icon.icon-default { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .envelope-icon img { width: 100%; height: 100%; object-fit: cover; }
        .envelope-info { flex: 1; min-width: 0; }
        .envelope-name { font-weight: 700; font-size: 1rem; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .envelope-meta { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; }
        .envelope-arrow { font-size: 1.3rem; color: #94a3b8; transition: transform 0.2s; }
        .envelope.open .envelope-arrow { transform: rotate(180deg); }
        .envelope-body {
            padding: 0 1.5rem;
            border-top: 1px solid #f1f5f9;
            max-height: 0;
            overflow: hidden;
            transition: none;
        }
        .envelope.open .envelope-body {
            max-height: 2000px;
            padding: 1rem 1.5rem 1.5rem;
            overflow: visible;
        }
        .envelope.open .envelope-front { background: #fafbfc; }

        .ficha-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem 1.5rem; margin: 1rem 0; font-size: 0.88rem; }
        .ficha-detail .label { color: #64748b; font-weight: 600; font-size: 0.8rem; }
        .ficha-detail .value { color: #0f172a; }

        .foto-container { text-align: center; margin: 1rem 0; }
        .foto-container img { max-width: 120px; max-height: 120px; border-radius: 10px; object-fit: cover; border: 2px solid #e2e8f0; }

        .envelope-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .envelope-actions .btn { flex: 1; font-size: 0.85rem; padding: 0.5rem 1rem; text-align: center; }

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
        .tag-aprovada { background: #d1fae5; color: #065f46; }
        .tag-rejeitada { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Validação de Fichas</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>📬 Fichas Pendentes <span style="font-weight:400; font-size:0.9rem; color:#64748b;">(<?= mysqli_num_rows($fichas) ?>)</span></h2>
            <p>Clique num envelope para ver os detalhes e validar.</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="sucesso"><?= $mensagem ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($fichas) == 0): ?>
            <div style="text-align:center; padding:3rem 0; color:#94a3b8;">
                <div style="font-size:3rem; margin-bottom:0.5rem;">📭</div>
                <p>Não há fichas pendentes de validação.</p>
            </div>
        <?php else: ?>
            <div class="envelopes-grid">
                <?php while ($f = mysqli_fetch_assoc($fichas)): ?>
                <div class="envelope" onclick="this.classList.toggle('open')">
                    <div class="envelope-front">
                        <div class="envelope-icon <?= empty($f['foto']) ? 'icon-default' : '' ?>">
                            <?php if (!empty($f['foto'])): ?>
                                <img src="../<?= htmlspecialchars($f['foto']) ?>" alt="Foto">
                            <?php else: ?>
                                👤
                            <?php endif; ?>
                        </div>
                        <div class="envelope-info">
                            <div class="envelope-name"><?= htmlspecialchars($f['nome_completo']) ?></div>
                            <div class="envelope-meta">
                                <?= htmlspecialchars($f['login']) ?> &bull; <?= $f['data_submissao'] ? date('d/m/Y H:i', strtotime($f['data_submissao'])) : '—' ?>
                            </div>
                        </div>
                        <span class="envelope-arrow">▼</span>
                    </div>
                    <div class="envelope-body" onclick="event.stopPropagation()">
                        <div class="ficha-detail">
                            <div><span class="label">Email</span><div class="value"><?= htmlspecialchars($f['email']) ?></div></div>
                            <div><span class="label">Telefone</span><div class="value"><?= htmlspecialchars($f['telefone'] ?? 'Não informado') ?></div></div>
                            <div><span class="label">Data Nascimento</span><div class="value"><?= $f['data_nascimento'] ? date('d/m/Y', strtotime($f['data_nascimento'])) : '—' ?></div></div>
                            <div><span class="label">Curso Pretendido</span><div class="value"><?= htmlspecialchars($f['curso_nome'] ?? 'Não especificado') ?></div></div>
                            <div style="grid-column: 1/-1;"><span class="label">Morada</span><div class="value"><?= htmlspecialchars($f['morada'] ?? 'Não informada') ?></div></div>
                        </div>

                        <?php if (!empty($f['foto'])): ?>
                        <div class="foto-container">
                            <img src="../<?= htmlspecialchars($f['foto']) ?>" alt="Foto do aluno">
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="id_ficha" value="<?= $f['id'] ?>">
                            <label style="font-size:0.85rem; font-weight:600;">Observações:</label>
                            <textarea name="observacoes" rows="2" style="font-size:0.85rem;" placeholder="Justificação ou comentários..."></textarea>
                            <div class="envelope-actions">
                                <button type="submit" name="acao" value="aprovar" class="btn btn-success"
                                    onclick="return confirm('Aprovar a ficha de <?= htmlspecialchars(addslashes($f['nome_completo'])) ?>?')">
                                    ✅ Aprovar
                                </button>
                                <button type="submit" name="acao" value="rejeitar" class="btn btn-danger"
                                    onclick="return confirm('Rejeitar a ficha de <?= htmlspecialchars(addslashes($f['nome_completo'])) ?>?')">
                                    ❌ Rejeitar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <hr style="margin:2.5rem 0;">
        <h2>📋 Fichas Processadas</h2>
        <?php if (mysqli_num_rows($processadas) == 0): ?>
            <p style="color:#94a3b8;">Nenhuma ficha processada ainda.</p>
        <?php else: ?>
            <div class="processados-grid">
                <?php while ($f = mysqli_fetch_assoc($processadas)): ?>
                <div class="envelope-mini">
                    <span class="estado-tag <?= $f['estado'] == 'aprovada' ? 'tag-aprovada' : 'tag-rejeitada' ?>">
                        <?= strtoupper($f['estado']) ?>
                    </span>
                    <div style="font-weight:700;"><?= htmlspecialchars($f['nome_completo'] ?? $f['login']) ?></div>
                    <div style="font-size:0.8rem; color:#64748b; margin:0.25rem 0;">
                        <?= htmlspecialchars($f['login']) ?> &bull; <?= htmlspecialchars($f['curso_nome'] ?? 'Sem curso') ?>
                    </div>
                    <?php if (!empty($f['observacoes'])): ?>
                        <div style="font-size:0.8rem; color:#475569; margin-top:0.25rem;">💬 <?= htmlspecialchars($f['observacoes']) ?></div>
                    <?php endif; ?>
                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.5rem;">
                        <?= $f['data_validacao'] ? date('d/m/Y H:i', strtotime($f['data_validacao'])) : '' ?>
                        <?php if (!empty($f['validado_por'])): ?> — por <?= htmlspecialchars($f['validado_por']) ?><?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>