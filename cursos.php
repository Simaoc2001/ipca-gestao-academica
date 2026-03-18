<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos Disponíveis &mdash; IPCA</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <div class="brand-icon">IP</div>
                <h1>IPCA <small>Oferta Formativa</small></h1>
            </div>
            <nav>
                <a href="index.php">Início</a>
                <a href="login.php">Entrar</a>
                <a href="registo.php" class="btn btn-sm">Registar</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="page-header">
            <h2>Cursos Disponíveis</h2>
            <p>Consulte a oferta formativa e inscreva-se no curso pretendido.</p>
        </div>

        <?php
        $cursos = mysqli_query($ligacao, "SELECT ID, Nome, descricao FROM cursos ORDER BY Nome");
        if (mysqli_num_rows($cursos) > 0):
        ?>
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($curso = mysqli_fetch_assoc($cursos)):
                        // Buscar disciplinas deste curso agrupadas por ano/semestre
                        $disc_query = mysqli_query($ligacao, "
                            SELECT d.Nome_disc, pe.ano, pe.semestre
                            FROM plano_estudos pe
                            JOIN disciplinas d ON pe.DISCIPLINA = d.ID
                            WHERE pe.CURSOS = " . (int)$curso['ID'] . "
                            ORDER BY pe.ano, pe.semestre, d.Nome_disc
                        ");
                        $disciplinas_por_sem = [];
                        while ($dc = mysqli_fetch_assoc($disc_query)) {
                            $key = $dc['ano'] . 'º Ano — ' . $dc['semestre'] . 'º Semestre';
                            $disciplinas_por_sem[$key][] = $dc['Nome_disc'];
                        }
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($curso['Nome']) ?>
                            <div class="curso-desc" id="desc-<?= $curso['ID'] ?>" style="display:none;">
                                <div class="curso-desc-text">
                                    <?= htmlspecialchars($curso['descricao'] ?? 'Sem descrição disponível.') ?>
                                </div>
                                <?php if (!empty($disciplinas_por_sem)): ?>
                                <a href="javascript:void(0)" class="mais-detalhes-link" onclick="toggleDetalhes(<?= $curso['ID'] ?>)">Mais detalhes →</a>
                                <div class="curso-detalhes" id="detalhes-<?= $curso['ID'] ?>" style="display:none;">
                                    <?php foreach ($disciplinas_por_sem as $semestre => $discs): ?>
                                        <div class="sem-group">
                                            <strong>📚 <?= htmlspecialchars($semestre) ?></strong>
                                            <ul>
                                                <?php foreach ($discs as $disc_nome): ?>
                                                    <li><?= htmlspecialchars($disc_nome) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline" onclick="toggleDesc(<?= $curso['ID'] ?>)">Descrição</button>
                            <a href="registo.php?curso_id=<?= $curso['ID'] ?>" class="btn btn-sm">Inscrever-me</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <script>
            function toggleDesc(id) {
                var el = document.getElementById('desc-' + id);
                el.style.display = el.style.display === 'none' ? 'block' : 'none';
                // Esconder detalhes quando fechar descrição
                if (el.style.display === 'none') {
                    var det = document.getElementById('detalhes-' + id);
                    if (det) det.style.display = 'none';
                }
            }
            function toggleDetalhes(id) {
                var el = document.getElementById('detalhes-' + id);
                el.style.display = el.style.display === 'none' ? 'block' : 'none';
            }
            </script>
        <?php else: ?>
            <div class="empty-state">
                <p>Nenhum curso disponível de momento.</p>
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