<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Clientes - Gestão";
include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: clientes.php");
    exit();
}

// Processar exclusão de cliente
if (isset($_POST['delete_client'])) {
    $client_id = $_POST['client_id'];
    
    try {
        // Primeiro, verificar se existem reservas associadas
        $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE cliente_id = $client_id";
        $result = $conn->query($check_sql);
        
        if ($result === false) {
            throw new Exception("Erro ao verificar reservas: " . $conn->error);
        }
        
        $row = $result->fetch_assoc();
        $has_reservas = $row['count'] > 0;
        
        if ($has_reservas) {
            $_SESSION['error'] = "Não é possível excluir este cliente pois existem reservas associadas a ele.";
        } else {
            // Excluir o cliente
            $delete_sql = "DELETE FROM Cliente WHERE id = $client_id";
            $delete_result = $conn->query($delete_sql);
            
            if ($delete_result === false) {
                throw new Exception("Erro ao excluir cliente: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                $_SESSION['success'] = "Cliente excluído com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao excluir cliente.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir cliente: " . $e->getMessage();
    }
    
    header('Location: clientes.php');
    exit();
}

// Mensagens de sucesso/erro
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
} else {
    $success_message = '';
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
} else {
    $error_message = '';
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$colunas_selecionadas = isset($_GET['colunas']) 
    ? (is_array($_GET['colunas']) ? $_GET['colunas'] : explode(',', $_GET['colunas'])) 
    : ['nome', 'email', 'telefone'];
$colunas_permitidas = ['id', 'nome', 'email', 'telefone'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'email', 'telefone'];
}

$colunas_sql = implode(", ", array_unique(array_merge($colunas_selecionadas, ['id'])));
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'ASC';
$colunas_permitidas_ordenacao = ['id', 'nome', 'email', 'telefone'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'id';
}

$ordenar_por = $_GET['ordenar_por'] ?? 'id';
$ordem = $_GET['ordem'] ?? 'ASC';
$colunas_permitidas_ordenacao = ['nome', 'email', 'telefone'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'nome';
}

$ordem = ($ordem === 'ASC') ? 'ASC' : 'DESC';
$sql = "SELECT id, " . implode(", ", $colunas_selecionadas) . " FROM Cliente WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (nome LIKE '%$search%' OR email LIKE '%$search%' OR telefone LIKE '%$search%')";
}

$sql .= " ORDER BY $ordenar_por $ordem";
$resultado = $conn->query($sql);
$clientes = $resultado->fetch_all(MYSQLI_ASSOC);
$conn->close();

ob_start();
?>

<style>
    /* Hide the table initially without affecting layout using opacity */
    #datatablesSimple {
        opacity: 0;
    }

    /* Force dropend dropdown to open to the right */
    .dropdown.dropend .dropdown-menu {
        top: 0;
        left: 100%;
        margin-left: 0.5rem; /* Space between button and menu */
    }
</style>

<div class="container py-4">
    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="clientes.php" id="filterForm" class="row g-3">
                <!-- Pesquisa -->
                <div class="col-md-4">
                    <label for="searchInput" class="form-label fs-5">Pesquisar</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-lg" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nome, email, telefone...">
                </div>
                
                <!-- Botões -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="document.getElementById('filterForm').submit()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                
                <div class="col-12 mt-3">
                    <div class="d-flex gap-2">
                        <a href="clientes.php?clear=1" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle me-2"></i>Limpar Filtros
                        </a>
                        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                            <a href="adicionar_cliente.php" class="btn btn-success btn-lg">
                                <i class="bi bi-plus-lg me-2"></i>Adicionar Cliente
                            </a>
                        <?php } ?>
                        <button class="btn btn-primary btn-lg" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Imprimir
                        </button>
                        <!-- Dropdown com filtros -->
                        <div class="dropdown">
                            <button class="btn btn-outline-dark btn-lg dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-columns-gap me-2"></i>Selecionar Colunas
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkNome" name="colunas[]" value="nome" <?php echo in_array('nome', $colunas_selecionadas) ? 'checked' : ''; ?>> Nome
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkEmail" name="colunas[]" value="email" <?php echo in_array('email', $colunas_selecionadas) ? 'checked' : ''; ?>> Email
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkTelefone" name="colunas[]" value="telefone" <?php echo in_array('telefone', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3">
            <h5 class="mb-0">Clientes</h5>
        </div>
        <div class="card-body p-4">
            <table id="datatablesSimple" class="table table-hover fs-5">
                <thead class="table-dark">
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th class="py-3"><?php echo ucfirst($coluna); ?></th>
                    <?php endforeach; ?> 
                    <th class="py-3">Ações</th>
                </thead>
                <tfoot>
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th class="py-3"><?php echo ucfirst($coluna); ?></th>
                    <?php endforeach; ?>
                    <th class="py-3">Ações</th>
                </tfoot>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <?php foreach ($colunas_selecionadas as $coluna): ?>
                                <td class="py-3"><?php echo htmlspecialchars($cliente[$coluna]); ?></td>
                            <?php endforeach; ?>
                            <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                                <td class="py-3">
                                    <a href="editar_cliente.php?id=<?php echo urlencode($cliente['id']); ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square me-1"></i>Editar
                                    </a>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $cliente['id']; ?>">
                                        <i class="bi bi-trash me-1"></i>Eliminar
                                    </button>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../assets/js/style.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>

<script>
    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        let dataTable;
        if (datatablesSimple) {
            dataTable = new simpleDatatables.DataTable(datatablesSimple, {
                searchable: false,
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                labels: {
                    placeholder: "Pesquisar...",
                    perPage: "Itens por página",
                    noRows: "Nenhum cliente encontrado",
                    info: "Mostrando {start} até {end} de {rows} clientes",
                    noResults: "Nenhum resultado encontrado para {query}"
                }
            });

            datatablesSimple.style.opacity = '1';
        }

        // Handle column selection checkboxes
        document.querySelectorAll('.dropdown-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const form = document.getElementById('filterForm');
                form.submit();
            });
        });

        // Add real-time search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (dataTable) {
                        dataTable.search(this.value);
                    }
                }, 300);
            });
        }

        // Handle delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.dataset.id;
                
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Esta ação não poderá ser revertida!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'clientes.php';
                        
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'client_id';
                        input.value = clientId;
                        
                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = 'delete_client';
                        deleteInput.value = '1';
                        
                        form.appendChild(input);
                        form.appendChild(deleteInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });

        <?php if ($success_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: '<?php echo addslashes($success_message); ?>',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        <?php endif; ?>

        <?php if ($error_message): ?>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '<?php echo addslashes($error_message); ?>',
                showConfirmButton: true,
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6',
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        <?php endif; ?>
    });
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>