<?php
session_start();
require_once '../config.php';

// Proteger página: só aluno
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ALUNO') {
    header('Location: ../login.php');
    exit;
}

$login = mysqli_real_escape_string($ligacao, $_SESSION['login']);

// Buscar dados da ficha do aluno
$ficha = mysqli_query($ligacao, "SELECT * FROM ficha_aluno WHERE login = '$login'");
$tem_ficha = mysqli_num_rows($ficha) > 0;
$dados_ficha = $tem_ficha ? mysqli_fetch_assoc($ficha) : null;

// Buscar nome do curso (se houver)
$curso_nome = '';
if ($tem_ficha && !empty($dados_ficha['curso_id'])) {
    $res_curso = mysqli_query($ligacao, "SELECT Nome FROM cursos WHERE ID = " . (int)$dados_ficha['curso_id']);
    if ($res_curso && mysqli_num_rows($res_curso) > 0) {
        $curso = mysqli_fetch_assoc($res_curso);
        $curso_nome = $curso['Nome'];
    }
}

// Buscar pedidos de matrícula do aluno
$pedidos = mysqli_query($ligacao, "
    SELECT p.*, c.Nome as curso_nome 
    FROM pedidos_matricula p
    LEFT JOIN cursos c ON p.curso_id = c.ID
    WHERE p.login_aluno = '$login'
    ORDER BY p.data_pedido DESC
    LIMIT 3
");
$tem_pedidos = mysqli_num_rows($pedidos) > 0;

// Verificar se já existe pedido pendente
$pedido_pendente = mysqli_query($ligacao, "SELECT id FROM pedidos_matricula WHERE login_aluno = '$login' AND estado = 'pendente'");
$tem_pedido_pendente = $pedido_pendente ? mysqli_num_rows($pedido_pendente) > 0 : false;

// Buscar últimas notas lançadas
$notas = mysqli_query($ligacao, "
    SELECT n.*, p.ano_letivo, d.Nome_disc, e.nome as epoca_nome
    FROM notas n
    JOIN pautas p ON n.pauta_id = p.id
    JOIN disciplinas d ON p.disciplina_id = d.ID
    JOIN epocas e ON p.epoca_id = e.id
    WHERE n.aluno_login = '$login'
    ORDER BY n.data_lancamento DESC
    LIMIT 5
");
$tem_notas = mysqli_num_rows($notas) > 0;

// Estatísticas do aluno
$stats_query = mysqli_query($ligacao, "
    SELECT COUNT(*) as total, 
           AVG(nota) as media, 
           MAX(nota) as melhor,
           MIN(nota) as pior,
           SUM(CASE WHEN nota >= 9.5 THEN 1 ELSE 0 END) as aprovadas
    FROM notas WHERE aluno_login = '$login' AND nota IS NOT NULL
");
$stats = mysqli_fetch_assoc($stats_query);
$total_notas = (int)$stats['total'];
$media = $total_notas > 0 ? round($stats['media'], 1) : 0;
$melhor_nota = $total_notas > 0 ? round($stats['melhor'], 1) : 0;
$aprovadas = (int)$stats['aprovadas'];
$taxa_aprovacao = $total_notas > 0 ? round(($aprovadas / $total_notas) * 100) : 0;

// Nome a mostrar
$nome_display = $tem_ficha ? ($dados_ficha['nome_completo'] ?: $_SESSION['login']) : $_SESSION['login'];
$primeiro_nome = explode(' ', $nome_display)[0];

// Hora do dia para saudação
$hora = (int)date('H');
if ($hora < 12) $saudacao = 'Bom dia';
elseif ($hora < 19) $saudacao = 'Boa tarde';
else $saudacao = 'Boa noite';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Painel do Aluno</small></h1>
            </div>
            <nav>
                <a href="../index.php">Início</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- SAUDAÇÃO PERSONALIZADA -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2><?= $saudacao ?>, <?= htmlspecialchars($primeiro_nome) ?>! 👋</h2>
                <p>
                    <?php if ($curso_nome): ?>
                        <?= htmlspecialchars($curso_nome) ?> &mdash;
                    <?php endif; ?>
                    <?php
                    $estado = $tem_ficha ? $dados_ficha['estado'] : 'sem ficha';
                    $badge_class = '';
                    if ($estado == 'aprovada') $badge_class = 'badge-aprovada';
                    elseif ($estado == 'rejeitada') $badge_class = 'badge-rejeitada';
                    elseif ($estado == 'submetida') $badge_class = 'badge-submetida';
                    else $badge_class = 'badge-rascunho';
                    ?>
                    Ficha: <span class="status-badge <?= $badge_class ?>"><?= strtoupper($estado) ?></span>
                </p>
            </div>
            <?php if ($tem_ficha && !empty($dados_ficha['foto'])):
                $foto_path = $dados_ficha['foto'];
                if (!preg_match('#^https?://#i', $foto_path)) {
                    $foto_path = '../' . ltrim($foto_path, '/');
                }
            ?>
                <div class="welcome-photo">
                    <img src="<?= htmlspecialchars($foto_path) ?>" alt="Foto do aluno">
                </div>
            <?php endif; ?>
        </div>

        <!-- STATS RÁPIDOS -->
        <?php if ($total_notas > 0): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">📊</span>
                <div class="stat-value"><?= $media ?></div>
                <div class="stat-label">Média Geral</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">🏆</span>
                <div class="stat-value"><?= $melhor_nota ?></div>
                <div class="stat-label">Melhor Nota</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">✅</span>
                <div class="stat-value"><?= $aprovadas ?>/<?= $total_notas ?></div>
                <div class="stat-label">Aprovações</div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">📈</span>
                <div class="stat-value"><?= $taxa_aprovacao ?>%</div>
                <div class="stat-label">Taxa Aprovação</div>
                <div class="progress-bar">
                    <div class="progress-fill <?= $taxa_aprovacao >= 50 ? 'progress-good' : 'progress-bad' ?>" style="width:<?= $taxa_aprovacao ?>%"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- CARD: FICHA DE ALUNO -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">📋</span>
                    <h3>Minha Ficha</h3>
                </div>
                <?php if ($tem_ficha): ?>
                    <div class="info-row">
                        <span class="info-label">Nome:</span>
                        <span class="info-value"><?= htmlspecialchars($dados_ficha['nome_completo'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= htmlspecialchars($dados_ficha['email'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Curso:</span>
                        <span class="info-value"><?= htmlspecialchars($curso_nome ?: 'Não definido') ?></span>
                    </div>
                    <?php if ($estado == 'rejeitada' && !empty($dados_ficha['observacoes'])): ?>
                        <div class="info-row">
                            <span class="info-label">Observações:</span>
                            <span class="info-value"><?= nl2br(htmlspecialchars($dados_ficha['observacoes'])) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="action-links">
                        <a href="editar_ficha.php" class="btn btn-sm">✏️ Editar Ficha</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nenhuma ficha preenchida.</p>
                        <a href="editar_ficha.php" class="btn btn-sm">Criar Ficha</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CARD: PEDIDOS DE MATRÍCULA -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">📝</span>
                    <h3>Pedidos de Matrícula</h3>
                </div>
                <?php if ($tem_pedidos): ?>
                    <?php while ($pedido = mysqli_fetch_assoc($pedidos)): ?>
                        <div class="info-row">
                            <span class="info-label"><?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?>:</span>
                            <span class="info-value">
                                <?php
                                $est_pedido = $pedido['estado'];
                                $badge_pedido = '';
                                if ($est_pedido == 'aprovado') $badge_pedido = 'badge-aprovada';
                                elseif ($est_pedido == 'rejeitado') $badge_pedido = 'badge-rejeitada';
                                else $badge_pedido = 'badge-pendente';
                                ?>
                                <span class="status-badge <?= $badge_pedido ?>"><?= strtoupper($est_pedido) ?></span>
                                — <?= htmlspecialchars($pedido['curso_nome'] ?? '—') ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                    <?php if ($tem_pedido_pendente): ?>
                        <div class="empty-state">
                            <p><strong>Matrícula em análise.</strong> Aguarde a validação.</p>
                        </div>
                    <?php else: ?>
                        <div class="action-links">
                            <a href="pedido_matricula.php" class="btn btn-sm">📝 Novo Pedido</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Nenhum pedido de matrícula.</p>
                        <?php if ($tem_ficha && $dados_ficha['estado'] == 'aprovada'): ?>
                            <a href="pedido_matricula.php" class="btn btn-sm">Fazer Pedido</a>
                        <?php else: ?>
                            <p style="font-size:0.85rem;">Aguarde a aprovação da ficha.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CARD: ÚLTIMAS NOTAS -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-icon">📊</span>
                    <h3>Últimas Notas</h3>
                </div>
                <?php if ($tem_notas): ?>
                    <?php while ($nota = mysqli_fetch_assoc($notas)): ?>
                        <div class="info-row">
                            <span class="info-label"><?= htmlspecialchars($nota['Nome_disc']) ?></span>
                            <span class="info-value">
                                <strong class="<?= $nota['nota'] >= 9.5 ? 'nota-aprovado' : 'nota-reprovado' ?>">
                                    <?= number_format($nota['nota'], 1) ?>
                                </strong>
                                <small>(<?= $nota['epoca_nome'] ?> — <?= $nota['ano_letivo'] ?>)</small>
                            </span>
                        </div>
                    <?php endwhile; ?>
                    <div class="action-links">
                        <a href="ver_notas.php" class="btn btn-sm">📊 Ver todas as notas</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Ainda não há notas lançadas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- LINKS RÁPIDOS -->
        <div class="quick-actions">
            <h3>⚡ Ações Rápidas</h3>
            <div class="action-links" style="justify-content:center; flex-wrap:wrap; gap:0.75rem;">
                <a href="editar_ficha.php" class="btn">✏️ Editar Ficha</a>
                <?php if ($tem_ficha && $dados_ficha['estado'] == 'aprovada'): ?>
                    <a href="pedido_matricula.php" class="btn">📝 Pedir Matrícula</a>
                <?php endif; ?>
                <a href="ver_notas.php" class="btn">📊 Consultar Notas</a>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>