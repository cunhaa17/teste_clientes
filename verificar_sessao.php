<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verifica se o tipo de utilizador é permitido na página
$pagina = basename($_SERVER['PHP_SELF']);
$restricoes = [
    'adicionar_cliente.php' => 'admin',
    'gerir_adms.php' => 'admin'
];

if (isset($restricoes[$pagina]) && $_SESSION['usuario_tipo'] !== $restricoes[$pagina]) {
    echo "Acesso negado.";
    exit();
}
?>
