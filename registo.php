<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['login'])) {
    header('Location: index.php');
    exit;
}

$erro = '';
$sucesso = '';

// Capturar curso_id da URL
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
$curso_nome = '';
if ($curso_id > 0) {
    $res = mysqli_query($ligacao, "SELECT Nome FROM cursos WHERE ID = $curso_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $curso = mysqli_fetch_assoc($res);
        $curso_nome = $curso['Nome'];
    } else {
        $curso_id = 0; // reset se curso inválido
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = mysqli_real_escape_string($ligacao, trim($_POST['login']));
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];
    $nome_completo = mysqli_real_escape_string($ligacao, trim($_POST['nome_completo']));
    $email = mysqli_real_escape_string($ligacao, trim($_POST['email']));
    $curso_selecionado = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;

    // Validações
    if (empty($login) || empty($password) || empty($nome_completo) || empty($email)) {
        $erro = "Todos os campos são obrigatórios.";
    } elseif ($password != $confirmar_password) {
        $erro = "As passwords não coincidem.";
    } elseif (strlen($password) < 4) {
        $erro = "A password deve ter pelo menos 4 caracteres.";
    } else {
        // Verificar se login já existe
        $check = mysqli_query($ligacao, "SELECT login FROM users WHERE login = '$login'");
        if (mysqli_num_rows($check) > 0) {
            $erro = "Este nome de utilizador já está em uso.";
        } else {
            // Verificar se email já existe
            $check_email = mysqli_query($ligacao, "SELECT id FROM ficha_aluno WHERE email = '$email'");
            if ($check_email && mysqli_num_rows($check_email) > 0) {
                $erro = "Este email já está registado no sistema.";
            } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserir utilizador (grupo 2 = ALUNO)
            $sql_user = "INSERT INTO users (login, pwd, grupo) VALUES ('$login', '$password_hash', 2)";
            if (mysqli_query($ligacao, $sql_user)) {
                // Inserir ficha com curso (se veio da URL ou do POST)
                $curso_final = $curso_selecionado > 0 ? $curso_selecionado : 'NULL';
                $sql_ficha = "INSERT INTO ficha_aluno (login, nome_completo, email, curso_id, estado) 
                              VALUES ('$login', '$nome_completo', '$email', $curso_final, 'rascunho')";
                if (mysqli_query($ligacao, $sql_ficha)) {
                    $sucesso = "Registo efetuado com sucesso! Já pode fazer login.";
                } else {
                    $erro = "Erro ao criar ficha: " . mysqli_error($ligacao);
                }
            } else {
                $erro = "Erro ao registar: " . mysqli_error($ligacao);
            }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo &mdash; IPCA</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Registo de Aluno</small></h1>
            </div>
            <nav>
                <a href="index.php">Início</a>
                <a href="login.php">Entrar</a>
                <a href="cursos.php">Cursos</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Criar nova conta</h2>
            <p>Preencha os seus dados para criar uma conta de aluno.</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Login (nome de utilizador)</label>
            <input type="text" name="login" placeholder="Escolha um nome de utilizador" required>

            <label>Password</label>
            <input type="password" name="password" placeholder="Mínimo 4 caracteres" required>

            <label>Confirmar Password</label>
            <input type="password" name="confirmar_password" placeholder="Repita a password" required>

            <label>Nome Completo</label>
            <input type="text" name="nome_completo" placeholder="O seu nome completo" required>

            <label>Email</label>
            <input type="email" name="email" placeholder="exemplo@email.com" required>

            <?php if ($curso_id > 0): ?>
                <input type="hidden" name="curso_id" value="<?= $curso_id ?>">
                <div class="mt-4">
                    <span class="badge badge-info">Curso pretendido: <?= htmlspecialchars($curso_nome) ?></span>
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-lg">Registar</button>
            </div>
        </form>

        <p class="mt-4 text-secondary">Já tem conta? <a href="login.php">Entrar</a></p>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>