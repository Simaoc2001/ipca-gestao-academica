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
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Fichas &mdash; IPCA</title>
    <link rel="stylesheet" href="../estilo.css">
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
            <h2>Fichas Pendentes</h2>
            <p>Reveja e valide as fichas submetidas pelos alunos.</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="sucesso"><?= $mensagem ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($fichas) == 0): ?>
            <p>Não há fichas pendentes de validação.</p>
        <?php else: ?>
            <?php while ($f = mysqli_fetch_assoc($fichas)): ?>
                <div class="ficha-card">
                    <h3>Ficha de <?= htmlspecialchars($f['nome_completo']) ?> (<?= htmlspecialchars($f['login']) ?>)</h3>
                    <div class="ficha-info">
                        <p><strong>Email:</strong> <?= htmlspecialchars($f['email']) ?></p>
                        <p><strong>Telefone:</strong> <?= htmlspecialchars($f['telefone'] ?? 'Não informado') ?></p>
                        <p><strong>Data Nasc.:</strong> <?= $f['data_nascimento'] ?></p>
                        <p><strong>Curso pretendido:</strong> <?= htmlspecialchars($f['curso_nome'] ?? 'Não especificado') ?></p>
                        <p><strong>Data submissão:</strong> <?= $f['data_submissao'] ?></p>
                    </div>
                    <?php if (!empty($f['foto'])): ?>
                        <div>
                            <img src="../<?= $f['foto'] ?>" class="foto-preview">
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="acoes-form">
                        <input type="hidden" name="id_ficha" value="<?= $f['id'] ?>">
                        <label>Observações (opcional, mas recomendado em caso de rejeição):</label>
                        <textarea name="observacoes" placeholder="Justificação ou comentários..."></textarea>
                        <div class="mt-4">
                            <button type="submit" name="acao" value="aprovar" class="btn btn-success" onclick="return confirm('Aprovar esta ficha?')">Aprovar</button>
                            <button type="submit" name="acao" value="rejeitar" class="btn btn-danger" onclick="return confirm('Rejeitar esta ficha?')">Rejeitar</button>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-content">
            <p>&copy; 2026 IPCA. Todos os direitos reservados.</p>
        </div>
    </footer>
</body>
</html>