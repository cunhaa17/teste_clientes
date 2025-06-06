<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include_once '../includes/db_conexao.php';

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $query = $conn->real_escape_string(trim($_GET['q']));

    // Buscar os resultados
    $sql = "SELECT nome FROM servico WHERE nome LIKE '%$query%' LIMIT 10";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p>" . htmlspecialchars($row['nome']) . "</p>";
        }
    } else {
        echo "<p>Nenhum resultado encontrado</p>";
    }
} else {
    echo "<p>Digite algo para pesquisar</p>";
}

$conn->close();
?>