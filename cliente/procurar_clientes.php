<?php
include_once '../includes/db_conexao.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$colunas_selecionadas = isset($_GET['colunas']) ? $_GET['colunas'] : ['id', 'nome', 'email', 'telefone'];
$colunas_sql = implode(", ", $colunas_selecionadas);

$sql = "SELECT $colunas_sql FROM Cliente WHERE 1=1";
$types = '';
$params = [];

if (!empty($search)) {
    $search_conditions = [];
    foreach ($colunas_selecionadas as $coluna) {
        $search_conditions[] = "$coluna LIKE ?";
    }
    $sql .= " AND (" . implode(" OR ", $search_conditions) . ")";
    
    $search_param = '%' . $search . '%';
    $params = array_fill(0, count($colunas_selecionadas), $search_param);
    $types = str_repeat('s', count($colunas_selecionadas));
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na consulta SQL: " . htmlspecialchars($conn->error));
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();
$clientes = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

foreach ($clientes as $cliente) {
    echo "<tr>";
    foreach ($colunas_selecionadas as $coluna) {
        echo "<td>" . htmlspecialchars($cliente[$coluna] ?? 'N/A', ENT_QUOTES, 'UTF-8') . "</td>";
    }
    echo "<td>
            <a href='editar_cliente.php?id=" . urlencode($cliente['id']) . "' class='btn btn-warning btn-sm'>Editar</a>
            <a href='eliminar_cliente.php?id=" . urlencode($cliente['id']) . "' class='btn btn-danger btn-sm'>Eliminar</a>
          </td>";
    echo "</tr>";
}
?>
