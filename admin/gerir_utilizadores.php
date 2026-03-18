<?php
// admin/gerir_utilizadores.php
session_start();
require_once '../config.php';

// Verificar se é admin
if (!isset($_SESSION['login']) || $_SESSION['tipo'] != 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

// Processar eliminação de utilizador
if (isset($_GET['eliminar'])) {
    $login = mysqli_real_escape_string($ligacao, $_GET['eliminar']);
    mysqli_query($ligacao, "DELETE FROM users WHERE login = '$login'");
    header('Location: gerir_utilizadores.php');
    exit;
}

// Buscar todos os utilizadores com o nome do grupo
$resultado = mysqli_query($ligacao, "
    SELECT u.*, g.GRUPO 
    FROM users u 
    JOIN grupos g ON u.grupo = g.ID 
    ORDER BY u.login
");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Utilizadores &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Gestão de Utilizadores</small></h1>
            </div>
            <nav>
                <a href="dashboard.php">Voltar</a>
                <a href="../logout.php" class="btn btn-sm">Sair</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Utilizadores</h2>
            <p>Gestão de contas de utilizadores do sistema.</p>
        </div>

        <table>
            <tr>
                <th>Login</th>
                <th>Grupo</th>
                <th>Ações</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['login']); ?></td>
                <td><?php echo htmlspecialchars($row['GRUPO']); ?></td>
                <td>
                    <a href="?eliminar=<?php echo urlencode($row['login']); ?>" 
                       onclick="return confirm('Tem a certeza?')">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>