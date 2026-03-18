<?php
require_once 'config.php';

// Array com os logins e as passwords em texto puro que queres usar
$utilizadores = [
    ['login' => 'gestor1', 'password' => 'ola'],
    ['login' => 'aluno1', 'password' => 'ola'],
    ['login' => 'func1', 'password' => 'ola'],
];

foreach ($utilizadores as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $login = $u['login'];
    $sql = "UPDATE users SET pwd = '$hash' WHERE login = '$login'";
    if (mysqli_query($ligacao, $sql)) {
        echo "Password atualizada para $login<br>";
    } else {
        echo "Erro ao atualizar $login: " . mysqli_error($ligacao) . "<br>";
    }
}
?>