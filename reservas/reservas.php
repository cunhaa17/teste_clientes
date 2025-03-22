<?php 
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é do tipo admin ou funcionario
if ($_SESSION['utilizador_tipo'] !== 'admin' && $_SESSION['utilizador_tipo'] !== 'funcionario') {
    header("Location: ../index.php");
    exit();
}
?>


<!-- Scripts -->
<script src="../assets/js/style.js"></script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>