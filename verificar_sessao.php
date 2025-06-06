<?php
// Garantir que não há saída antes do session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir o timeout para 2 minutos (120 segundos)
$timeout = 120;

// Obter o caminho base do dashboard
$base_path = '/PAP/dashboard_pap/';

// Verificar se existe uma última atividade registrada
/* if (isset($_SESSION['ultima_atividade'])) {
    $tempo_decorrido = time() - $_SESSION['ultima_atividade'];
    
    if ($tempo_decorrido > $timeout) {
        // Limpar todas as variáveis da sessão
        $_SESSION = array();
        
        // Destruir o cookie da sessão
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destruir a sessão
        session_destroy();
        
        // Redirecionar para a página de login
        header("Location: " . $base_path . "login.php");
        exit();
    }
} */

// Atualizar o timestamp da última atividade
$_SESSION['ultima_atividade'] = time();

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: " . $base_path . "login.php");
    exit();
}

// Verifica se o tipo de utilizador é permitido na página
$pagina = basename($_SERVER['PHP_SELF']);
$restricoes = [
    'adicionar_cliente.php' => 'admin',
    'gerir_adms.php' => 'admin'
];

if (isset($restricoes[$pagina]) && $_SESSION['utilizador_tipo'] !== $restricoes[$pagina]) {
    header("Location: " . $base_path . "index.php");
    exit();
}
?>
