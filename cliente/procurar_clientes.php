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

if (isset($_GET['q'])) {
    $query = htmlspecialchars($_GET['q']);

    // Conectar ao banco de dados
    $conn = new mysqli("localhost", "usuario", "senha", "banco");

    if ($conn->connect_error) {
        die("Erro na conexão: " . $conn->connect_error);
    }

    // Buscar os resultados
    $sql = "SELECT nome FROM clientes WHERE nome LIKE ? LIMIT 10";
    $stmt = $conn->prepare($sql);
    $param = "%$query%";
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<p>" . htmlspecialchars($row['nome']) . "</p>";
        }
    } else {
        echo "<p>Nenhum resultado encontrado</p>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<p>Erro na requisição</p>";
}
?>
