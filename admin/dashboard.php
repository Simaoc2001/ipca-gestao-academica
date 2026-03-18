<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['login']) || ($_SESSION['tipo'] != 'ADMIN' && $_SESSION['tipo'] != 'FUNCIONARIO')) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Painel Administrativo</small></h1>
            </div>
            <div class="header-user">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['login']) ?></span>
                    <span class="user-role"><?= htmlspecialchars($_SESSION['tipo']) ?></span>
                </div>
                <nav>
                    <a href="../index.php">Início</a>
                    <a href="../logout.php" class="btn btn-sm">Sair</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Dashboard</h2>
            <p>Bem-vindo ao painel de gestão. Selecione uma opção abaixo.</p>
        </div>

        <?php if ($_SESSION['tipo'] == 'ADMIN'): ?>
            <h3>Gestão do Sistema</h3>
            <div class="dashboard-buttons">
                <a href="gerir_utilizadores.php" class="btn" title="Gerir Utilizadores"><span>👤</span> Gerir Utilizadores</a>
                <a href="gerir_cursos.php" class="btn" title="Gerir Cursos"><span>🎓</span> Gerir Cursos</a>
                <a href="gerir_disciplinas.php" class="btn" title="Gerir Disciplinas"><span>📚</span> Gerir Disciplinas</a>
                <a href="gerir_planos.php" class="btn" title="Gerir Planos de Estudo"><span>🗂️</span> Planos de Estudo</a>
                <a href="validar_fichas.php" class="btn" title="Validar Fichas de Aluno"><span>✅</span> Validar Fichas</a>
            </div>
        <?php elseif ($_SESSION['tipo'] == 'FUNCIONARIO'): ?>
            <h3>Serviços Académicos</h3>
            <div class="dashboard-buttons">
                <a href="gerir_pedidos.php" class="btn" title="Gerir Pedidos de Matrícula"><span>📝</span> Pedidos de Matrícula</a>
                <a href="gerir_pautas.php" class="btn" title="Pautas de Avaliação"><span>📊</span> Pautas de Avaliação</a>
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