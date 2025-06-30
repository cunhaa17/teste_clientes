<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Funcionários - Gestão";
include_once '../includes/db_conexao.php';

if (isset($_GET['clear'])) {
    header("Location: funcionario.php");
    exit();
}

// Processar exclusão de funcionário
if (isset($_POST['delete_funcionario'])) {
    $funcionario_id = $_POST['funcionario_id'];
    $confirmar_com_reservas = isset($_POST['confirmar_com_reservas']) ? true : false;
    
    try {
        // Primeiro, verificar se existem reservas associadas
        $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE funcionario_id = $funcionario_id";
        $result = $conn->query($check_sql);
        
        if ($result === false) {
            throw new Exception("Erro ao verificar reservas: " . $conn->error);
        }
        
        $row = $result->fetch_assoc();
        $has_reservas = $row['count'] > 0;
        
        if ($has_reservas && !$confirmar_com_reservas) {
            $_SESSION['error'] = "Não é possível excluir este funcionário pois existem reservas associadas a ele.";
        } else {
            // Se tem reservas e confirmou, deletar reservas antes
            if ($has_reservas && $confirmar_com_reservas) {
                $delete_reservas_sql = "DELETE FROM Reserva WHERE funcionario_id = $funcionario_id";
                $conn->query($delete_reservas_sql);
            }
            // Excluir o funcionário
            $delete_sql = "DELETE FROM Funcionario WHERE id = $funcionario_id";
            $delete_result = $conn->query($delete_sql);
            
            if ($delete_result === false) {
                throw new Exception("Erro ao excluir funcionário: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                if ($has_reservas && $confirmar_com_reservas) {
                    $_SESSION['success'] = "Funcionário e todas as reservas associadas excluídos com sucesso!";
                } else {
                    $_SESSION['success'] = "Funcionário excluído com sucesso!";
                }
            } else {
                $_SESSION['error'] = "Erro ao excluir funcionário.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir funcionário: " . $e->getMessage();
    }
    
    header('Location: funcionario.php');
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
// Definir colunas específicas para funcionários
$colunas_selecionadas = isset($_GET['colunas']) 
    ? (is_array($_GET['colunas']) ? $_GET['colunas'] : explode(',', $_GET['colunas'])) 
    : ['nome', 'email', 'localidade', 'telefone1', 'telefone2']; // Colunas padrão para funcionários (removido 'id')
$colunas_permitidas = ['id', 'nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2']; // Colunas permitidas para funcionários
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'email', 'localidade', 'telefone1', 'telefone2']; // Fallback para funcionários (removido 'id')
}

// Constrói a string de colunas para o SELECT da query SQL
// Sempre inclui 'id' para as ações de editar/eliminar
$colunas_sql = implode(", ", array_unique(array_merge($colunas_selecionadas, ['id'])));
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'ASC';
// Definir colunas permitidas para ordenação para funcionários
$colunas_permitidas_ordenacao = ['id', 'nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'id';
}

$ordenar_por = $_GET['ordenar_por'] ?? 'id';
$ordem = $_GET['ordem'] ?? 'ASC';
// Definir colunas permitidas para ordenação para funcionários (repetição, pode ser simplificado)
$colunas_permitidas_ordenacao = ['nome', 'email', 'morada', 'localidade', 'telefone1', 'telefone2'];

if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'nome';
}

$ordem = ($ordem === 'ASC') ? 'ASC' : 'DESC';
// Alterar nome da tabela de Cliente para funcionario e ajustar condição de busca
$sql = "SELECT id, " . implode(", ", $colunas_selecionadas) . " FROM funcionario WHERE 1=1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (nome LIKE '%$search%' OR email LIKE '%$search%' OR morada LIKE '%$search%' OR localidade LIKE '%$search%' OR telefone1 LIKE '%$search%' OR telefone2 LIKE '%$search%')"; // Ajustar campos de busca para funcionários
}

$sql .= " ORDER BY $ordenar_por $ordem";
$resultado = $conn->query($sql);
// Alterar nome da variável de clientes para funcionarios
$funcionarios = $resultado->fetch_all(MYSQLI_ASSOC);
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

    th[data-sortable="true"] .datatable-sorter::before,
    th[data-sortable="true"] .datatable-sorter::after,
    th.datatable-ascending .datatable-sorter::before,
    th.datatable-descending .datatable-sorter::after,
    a.datatable-sorter::before,
    a.datatable-sorter::after {
        color: #fff !important;
        opacity: 1 !important;
    }
</style>

<div class="container-fluid py-4">
    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h5 class="mb-0">Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="funcionario.php" id="filterForm" class="row g-3">
                <!-- Pesquisa -->
                <div class="col-md-4">
                    <label for="searchInput" class="form-label fs-5">Pesquisar</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-lg" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Nome, email, morada, telefone...">
                </div>
                
                <!-- Botões -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-lg w-100" onclick="document.getElementById('filterForm').submit()">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                
                <div class="col-12 mt-3">
                    <div class="d-flex gap-2">
                        <a href="funcionario.php?clear=1" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle me-2"></i>Limpar Filtros
                        </a>
                        <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                        <a href="adicionar_funcionario.php" class="btn btn-success btn-lg">
                            <i class="bi bi-plus-lg me-2"></i>Adicionar Funcionário
                        </a>
                        <?php } ?>
                        <a href="gerar_pdf_funcionarios.php" id="pdfLink" class="btn btn-primary btn-lg" target="_blank">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i>Gerar PDF
                        </a>
                        <!-- Dropdown com filtros -->
                        <div class="dropdown dropend">
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
                                        <input type="checkbox" class="form-check-input me-2" id="checkMorada" name="colunas[]" value="morada" <?php echo in_array('morada', $colunas_selecionadas) ? 'checked' : ''; ?>> Morada
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkLocalidade" name="colunas[]" value="localidade" <?php echo in_array('localidade', $colunas_selecionadas) ? 'checked' : ''; ?>> Localidade
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkTelefone1" name="colunas[]" value="telefone1" <?php echo in_array('telefone1', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone 1
                                    </label>
                                </li>
                                <li>
                                    <label class="dropdown-item fs-5">
                                        <input type="checkbox" class="form-check-input me-2" id="checkTelefone2" name="colunas[]" value="telefone2" <?php echo in_array('telefone2', $colunas_selecionadas) ? 'checked' : ''; ?>> Telefone 2
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
            <h5 class="mb-0">Funcionários</h5>
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
                    <?php foreach ($funcionarios as $funcionario): ?>
                        <tr>
                            <?php foreach ($colunas_selecionadas as $coluna): ?>
                                <td class="py-3"><?php echo htmlspecialchars($funcionario[$coluna] ?? ''); ?></td>
                            <?php endforeach; ?>
                            <?php if ($_SESSION['utilizador_tipo'] == 'admin') { ?>
                                <td class="py-3">
                                    <a href="editar_funcionario.php?id=<?php echo urlencode($funcionario['id']); ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-square me-1"></i>Editar
                                    </a>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $funcionario['id']; ?>">
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
    // Código para mensagens de sucesso e erro
    document.addEventListener('DOMContentLoaded', function() {
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

<script>
    // Código para DataTables e outras funcionalidades
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
                    noRows: "Nenhum funcionário encontrado",
                    info: "Mostrando {start} até {end} de {rows} funcionários",
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
                const funcionarioId = this.dataset.id;
                const funcionarioNome = this.closest('tr').querySelector('td').textContent.trim();
                // Verificar reservas via AJAX
                fetch('verificar_reservas_funcionario.php?funcionario_id=' + funcionarioId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.has_reservas) {
                            mostrarConfirmacaoComReservasFuncionario(funcionarioId, funcionarioNome, data.count);
                        } else {
                            mostrarConfirmacaoSimplesFuncionario(funcionarioId, funcionarioNome);
                        }
                    })
                    .catch(() => {
                        mostrarConfirmacaoSimplesFuncionario(funcionarioId, funcionarioNome);
                    });
            });
        });

        // Função para modal chamativo igual ao subtipo/serviço/cliente
        function mostrarConfirmacaoComReservasFuncionario(id, nome, count) {
            Swal.fire({
                title: "<div style='color: #d90429; font-size: 2.2rem; font-weight: bold; letter-spacing: 1px; display: flex; flex-direction: column; align-items: center;'>" +
                    "<span style='font-size: 4rem; display: block; margin-bottom: 10px;'>⚠️</span>" +
                    "<span style='color: #d90429; text-shadow: 1px 1px 8px #fff, 0 0 8px #d90429;'>ATENÇÃO CRÍTICA!</span>" +
                "</div>",
                html: "<div style='text-align: center; margin: 20px 0; font-size: 1.15rem;'>" +
                    "<div style='color: #d90429; font-size: 1.3rem; font-weight: bold; margin-bottom: 10px;'>🚨 Esta ação é IRREVERSÍVEL! 🚨</div>" +
                    "<div style='margin-bottom: 15px;'>Deseja eliminar o funcionário <span style='color: #d90429; font-weight: bold;'>&quot;" + nome + "&quot;</span>?</div>" +
                    "<div style='background: #fff3cd; border: 3px solid #d90429; border-radius: 12px; padding: 15px; margin: 0 auto 15px auto; max-width: 400px; color: #856404; font-weight: bold;'>" +
                        "<span style='font-size: 1.1rem;'>Este funcionário tem <span style='color: #d90429;'>" + count + "</span> reserva(s) associada(s)!</span><br>" +
                        "<span style='font-size: 1rem; color: #d90429;'>As alterações são <u>PERMANENTES</u>!</span>" +
                    "</div>" +
                    "<div style='background: #f8d7da; border-left: 6px solid #d90429; padding: 10px; border-radius: 8px; color: #721c24; font-size: 1rem;'>" +
                        "<i class='bi bi-info-circle-fill' style='color: #d90429; margin-right: 8px;'></i>" +
                        "Ao confirmar, todas as reservas associadas também serão eliminadas." +
                    "</div>" +
                "</div>",
                icon: false,
                showCancelButton: true,
                confirmButtonColor: "#d90429",
                cancelButtonColor: "#6c757d",
                confirmButtonHtml: "<span style='font-size:1.2rem; font-weight:bold; animation: pulse 1s infinite;'>🚨 SIM, ELIMINAR!</span>",
                cancelButtonText: "Cancelar",
                customClass: {
                    popup: 'swal2-border-strong',
                    confirmButton: 'swal2-animate-pulse'
                },
                buttonsStyling: false,
                width: '520px',
                backdrop: 'rgba(217,4,41,0.15)',
                didOpen: function(el) {
                    var style = document.createElement('style');
                    style.innerHTML = "@keyframes pulse {0% { box-shadow: 0 0 0 0 #d9042940; }70% { box-shadow: 0 0 0 10px #d9042900; }100% { box-shadow: 0 0 0 0 #d9042900; }}.swal2-animate-pulse { animation: pulse 1.2s infinite; }.swal2-border-strong { border: 5px solid #d90429 !important; box-shadow: 0 0 30px #d9042940 !important; border-radius: 18px !important; }";
                    document.head.appendChild(style);
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Enviar formulário com confirmação extra
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'funcionario.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'funcionario_id';
                    input.value = id;
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_funcionario';
                    deleteInput.value = '1';
                    const confirmInput = document.createElement('input');
                    confirmInput.type = 'hidden';
                    confirmInput.name = 'confirmar_com_reservas';
                    confirmInput.value = '1';
                    form.appendChild(input);
                    form.appendChild(deleteInput);
                    form.appendChild(confirmInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        // Modal simples se não houver reservas
        function mostrarConfirmacaoSimplesFuncionario(id, nome) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Deseja eliminar o funcionário '" + nome + "'? Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, eliminar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'funcionario.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'funcionario_id';
                    input.value = id;
                    const deleteInput = document.createElement('input');
                    deleteInput.type = 'hidden';
                    deleteInput.name = 'delete_funcionario';
                    deleteInput.value = '1';
                    form.appendChild(input);
                    form.appendChild(deleteInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    });
</script>

<script>
function atualizarLinkPDF() {
    const search = document.getElementById('searchInput') ? document.getElementById('searchInput').value : '';
    const params = new URLSearchParams({ search: search });
    
    // Adicionar colunas selecionadas
    const colunasCheckboxes = document.querySelectorAll('input[name="colunas[]"]:checked');
    const colunas = Array.from(colunasCheckboxes).map(cb => cb.value);
    if (colunas.length > 0) {
        params.append('colunas', colunas.join(','));
    }
    
    const pdfLink = document.getElementById('pdfLink');
    if (pdfLink) {
        pdfLink.href = 'gerar_pdf_funcionarios.php?' + params.toString();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Atualizar link PDF quando a página carrega
    atualizarLinkPDF();
    
    // Atualizar link PDF quando a pesquisa muda
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', atualizarLinkPDF);
    }
    
    // Atualizar link PDF quando as colunas mudam
    const colunasCheckboxes = document.querySelectorAll('input[name="colunas[]"]');
    colunasCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', atualizarLinkPDF);
    });
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
