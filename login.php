<?php
session_start();
require_once 'config.php';

// Se já estiver logado, redireciona diretamente para o painel apropriado
if (isset($_SESSION['login']) && isset($_SESSION['tipo'])) {
    if ($_SESSION['tipo'] === 'ALUNO') {
        header('Location: aluno/dashboard.php');
    } else {
        header('Location: admin/dashboard.php');
    }
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = mysqli_real_escape_string($ligacao, trim($_POST['login'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $erro = 'Por favor preencha login e password.';
    } else {
        $sql = "SELECT u.*, g.GRUPO FROM users u JOIN grupos g ON u.grupo = g.ID WHERE u.login = '$login'";
        $resultado = mysqli_query($ligacao, $sql);

        if ($resultado && mysqli_num_rows($resultado) === 1) {
            $user = mysqli_fetch_assoc($resultado);
            $hash = $user['pwd'] ?? '';

            if ($hash !== '' && (password_verify($password, $hash) || $hash === md5($password))) {
                session_regenerate_id(true);
                $_SESSION['login'] = $user['login'];
                $_SESSION['tipo'] = $user['GRUPO'];

                if ($hash === md5($password)) {
                    $nova_hash = password_hash($password, PASSWORD_DEFAULT);
                    mysqli_query($ligacao, "UPDATE users SET pwd = '$nova_hash' WHERE login = '$login'");
                }

                if ($_SESSION['tipo'] === 'ALUNO') {
                    header('Location: aluno/dashboard.php');
                } else {
                    header('Location: admin/dashboard.php');
                }
                exit;
            }

            $erro = 'Login ou password incorretos!';
        } else {
            $erro = 'Login ou password incorretos!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar &mdash; IPCA</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Autenticação</small></h1>
            </div>
            <nav>
                <a href="index.php">Início</a>
                <a href="registo.php">Registar</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Entrar no sistema</h2>
            <p>Introduza as suas credenciais para aceder à plataforma.</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label>Login</label>
            <input type="text" name="login" placeholder="O seu nome de utilizador" required autofocus>
            
            <label>Password</label>
            <input type="password" name="password" placeholder="A sua password" required>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-lg">Entrar</button>
            </div>
        </form>

        <p class="mt-4 text-secondary">Não tem conta? <a href="registo.php">Criar conta</a></p>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>