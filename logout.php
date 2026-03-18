<?php
// logout.php - Terminar sessão
session_start();
session_destroy();
header('Location: index.php');
exit;
?>