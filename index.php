<?php
session_start();
require_once 'config.php';

// Se já estiver logado, redirecionar diretamente para o painel apropriado
if (isset($_SESSION['login'])) {
    if ($_SESSION['tipo'] == 'ALUNO') {
        header('Location: aluno/dashboard.php');
    } else {
        header('Location: admin/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="IPCA - Sistema de Gestão Académica do Instituto Politécnico do Cávado e do Ave">
    <title>IPCA &mdash; Sistema de Gestão Académica</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Sistema Académico</small></h1>
            </div>
            <nav>
                <?php if (isset($_SESSION['login'])): ?>
                    <span>Olá, <?php echo htmlspecialchars($_SESSION['login']); ?></span>
                    <a href="logout.php" class="btn btn-sm">Sair</a>
                <?php else: ?>
                    <a href="cursos.php">Cursos</a>
                    <a href="login.php">Entrar</a>
                    <a href="registo.php" class="btn btn-sm">Registar</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="hero-section">
            <h1>Sistema Académico IPCA</h1>
            <p>Instituto Politécnico do Cávado e do Ave &mdash; Plataforma de gestão académica para estudantes, candidatos e administradores.</p>
        </div>

        <?php if (!isset($_SESSION['login'])): ?>
            <div class="mt-8 text-center">
                <div class="page-header" style="text-align:center;">
                    <h2 style="justify-content:center;">Inscreva-se num Curso</h2>
                    <p>Faça a sua pré-inscrição e candidate-se aos nossos cursos superiores.</p>
                </div>

                <div class="dashboard-buttons" style="display:flex; flex-wrap:wrap; justify-content:center; gap:1rem;">
                    <a href="cursos.php" class="btn btn-lg"><span>📚</span> Ver Cursos Disponíveis</a>
                    <a href="login.php" class="btn btn-lg btn-outline"><span>🔑</span> Entrar no Sistema</a>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-8">
                <div class="page-header">
                    <h2>Bem-vindo de volta, <?php echo htmlspecialchars($_SESSION['login']); ?>!</h2>
                </div>

                <?php if ($_SESSION['tipo'] == 'ADMIN'): ?>
                    <p>Aceda ao painel de administração para gerir o sistema.</p>
                    <a href="admin/dashboard.php" class="btn btn-lg">Painel Admin</a>

                <?php elseif ($_SESSION['tipo'] == 'FUNCIONARIO'): ?>
                    <p>Aceda ao painel de serviços académicos.</p>
                    <a href="admin/dashboard.php" class="btn btn-lg">Painel Funcionário</a>

                <?php elseif ($_SESSION['tipo'] == 'ALUNO'): ?>
                    <p>Aceda à sua área de aluno.</p>
                    <a href="aluno/dashboard.php" class="btn btn-lg">Painel Aluno</a>

                <?php else: ?>
                    <p>Tipo de utilizador desconhecido.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <div class="qr-section">
                <p class="qr-label">📱 Aceda ao sistema no telemóvel:</p>
                <div id="qrcode"></div>
                <p class="qr-url" id="qr-url-text"></p>
            </div>
            <div class="footer-brand">IPCA</div>
            <p>&copy; 2026 Instituto Politécnico do Cávado e do Ave. Todos os direitos reservados.</p>
        </div>
    </footer>
    <?php
    // Obter o IP da rede local do servidor
    $ip_local = gethostbyname(gethostname());
    $porta = $_SERVER['SERVER_PORT'];
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url_rede = $protocolo . '://' . $ip_local . ($porta != 80 && $porta != 443 ? ':' . $porta : '') . '/ipca/';
    ?>
    <script>
    (function(){
        var url = <?= json_encode($url_rede) ?>;
        document.getElementById('qr-url-text').textContent = url;
        var size = 150;
        var img = document.createElement('img');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&data=' + encodeURIComponent(url);
        img.alt = 'QR Code de acesso';
        img.width = size;
        img.height = size;
        document.getElementById('qrcode').appendChild(img);
    })();
    </script>
</body>
</html>