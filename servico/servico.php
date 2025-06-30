<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$title = "Serviços - Gestão";
include_once '../includes/db_conexao.php';

// Initial setup and redirects
if (isset($_GET['clear'])) {
    header("Location: servico.php");
    exit();
}

// Processar exclusão de serviço
if (isset($_POST['delete_servico'])) {
    $servico_id = $_POST['servico_id'];
    $confirmar_com_reservas = isset($_POST['confirmar_com_reservas']) ? true : false;
    
    try {
        // Primeiro, verificar se existem reservas associadas
        $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE servico_id = $servico_id";
        $result = $conn->query($check_sql);
        if ($result === false) {
            throw new Exception("Erro ao verificar reservas: " . $conn->error);
        }
        $row = $result->fetch_assoc();
        $has_reservas = $row['count'] > 0;

        // Verificar se existem subtipos associados
        $check_subtipos_sql = "SELECT id FROM servico_subtipo WHERE servico_id = $servico_id";
        $subtipos_result = $conn->query($check_subtipos_sql);
        $subtipo_ids = [];
        if ($subtipos_result && $subtipos_result->num_rows > 0) {
            while ($sub = $subtipos_result->fetch_assoc()) {
                $subtipo_ids[] = $sub['id'];
            }
        }
        $has_subtipos = count($subtipo_ids) > 0;

        if (($has_reservas || $has_subtipos) && !$confirmar_com_reservas) {
            $_SESSION['error'] = "Não é possível excluir este serviço pois existem reservas ou subtipos associados a ele.";
        } else {
            // Se tem subtipos, excluir reservas e subtipos
            if ($has_subtipos && $confirmar_com_reservas) {
                foreach ($subtipo_ids as $sub_id) {
                    // Excluir reservas do subtipo
                    $conn->query("DELETE FROM Reserva WHERE servico_subtipo_id = $sub_id");
                    // Excluir o subtipo
                    $conn->query("DELETE FROM servico_subtipo WHERE id = $sub_id");
                }
            }
            // Se tem reservas do serviço, excluir
            if ($has_reservas && $confirmar_com_reservas) {
                $conn->query("DELETE FROM Reserva WHERE servico_id = $servico_id");
            }
            // Excluir o serviço
            $delete_sql = "DELETE FROM Servico WHERE id = $servico_id";
            $delete_result = $conn->query($delete_sql);
            if ($delete_result === false) {
                throw new Exception("Erro ao excluir serviço: " . $conn->error);
            }
            if ($conn->affected_rows > 0) {
                if (($has_reservas || $has_subtipos) && $confirmar_com_reservas) {
                    $_SESSION['success'] = "Serviço, subtipos e todas as reservas associadas excluídos com sucesso!";
                } else {
                    $_SESSION['success'] = "Serviço excluído com sucesso!";
                }
            } else {
                $_SESSION['error'] = "Erro ao excluir serviço.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir serviço: " . $e->getMessage();
    }
    header('Location: servico.php');
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

// Filter and column settings
$search = trim($_GET['search'] ?? '');
$colunas_permitidas = ['servico'];
$colunas_selecionadas = ['servico'];

// Sorting settings
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'servico';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'ASC';
$colunas_permitidas_ordenacao = ['servico'];

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

// Database query
$sql = "SELECT id, nome as servico, imagem FROM servico ORDER BY nome";
$result = $conn->query($sql);
$servico = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

ob_start();
?>

<style>
    /* No loading overlay styles */
</style>

<!-- Main Container -->
<div class="container-fluid py-4">
    <!-- Filtros e conteúdo principal -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="adicionar_servico.php" class="btn btn-primary">Adicionar Serviço</a>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Pesquisar..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <a href="gerar_pdf_servicos.php" id="pdfLink" class="btn btn-primary btn-lg" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i>Gerar PDF
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div id="errorToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true" style="background: linear-gradient(45deg, #dc3545, #c82333); border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <div class="d-flex align-items-center p-3">
                    <div class="toast-icon me-3">
                        <i class="bi bi-exclamation-circle-fill fs-4"></i>
                    </div>
                    <div class="toast-body fs-5">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: 100%" id="toastProgressBar"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Services Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover fs-5">
            <thead class="table-dark">
                <tr>
                    <th>Imagem</th>
                    <th data-column="servico">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['ordenar_por' => 'servico', 'ordem' => ($ordem == 'ASC' ? 'DESC' : 'ASC')])); ?>" class="text-white text-decoration-none">
                            Serviço
                            <?php if (isset($_GET['ordenar_por']) && $_GET['ordenar_por'] == 'servico'): ?>
                                <?php echo ($ordem == 'ASC') ? '▲' : '▼'; ?>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servico as $servicos): ?>
                    <tr>
                        <td>
                            <?php
                            // Exibir imagem do serviço
                            if (!empty($servicos['imagem'])) {
                                echo '<img src="/PAP/' . htmlspecialchars($servicos['imagem']) . '" alt="' . htmlspecialchars($servicos['servico']) . '" class="img-thumbnail" style="max-width: 50px; max-height: 50px; object-fit: cover;">';
                            } else {
                                echo '<div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 4px;">';
                                echo '<i class="bi bi-image text-muted"></i>';
                                echo '</div>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($servicos['servico'] ?? ''); ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-expand" data-servico-id="<?php echo $servicos['id']; ?>">+</button>
                            <a href="editar_servico.php?id=<?php echo urlencode($servicos['id']); ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square me-1"></i>Editar
                            </a>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $servicos['id']; ?>">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </td>
                    </tr>
                    <tr class="subservicos-row" id="subservicos-<?php echo $servicos['id']; ?>" style="display: none;">
                        <td colspan="3">
                            <div class="subservicos-content"></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/style.js"></script>

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
                    noRows: "Nenhum serviço encontrado",
                    info: "Mostrando {start} até {end} de {rows} serviços",
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

        // Handle expand buttons
        document.querySelectorAll('.btn-expand').forEach(button => {
            button.addEventListener('click', function() {
                const servicoId = this.dataset.servicoId;
                const row = document.getElementById(`subservicos-${servicoId}`);
                const content = row.querySelector('.subservicos-content');
                
                if (row.style.display === 'none') {
                    // Fazer a requisição AJAX
                    fetch(`get_subtipos.php?servico_id=${servicoId}`)
                        .then(response => response.text())
                        .then(html => {
                            content.innerHTML = html;
                            row.style.display = 'table-row';
                            this.textContent = '-';
                            
                            // Executar scripts após carregar o conteúdo AJAX
                            const scripts = content.querySelectorAll('script');
                            scripts.forEach(script => {
                                const newScript = document.createElement('script');
                                newScript.textContent = script.textContent;
                                document.body.appendChild(newScript);
                            });
                            
                            // Forçar inicialização dos botões de eliminação
                            if (typeof adicionarEventosEliminacao === 'function') {
                                setTimeout(adicionarEventosEliminacao, 100);
                            }
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            content.innerHTML = '<div class="alert alert-danger mt-2">Erro ao carregar subtipos.</div>';
                            row.style.display = 'table-row';
                        });
                } else {
                    row.style.display = 'none';
                    this.textContent = '+';
                }
            });
        });

        // Handle delete buttons
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const servicoId = this.dataset.id;
                const servicoNome = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
                // Verificar reservas via AJAX
                fetch('verificar_reservas_servico.php?servico_id=' + servicoId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.has_reservas) {
                            mostrarConfirmacaoComReservasServico(servicoId, servicoNome, data.count);
                        } else {
                            mostrarConfirmacaoSimplesServico(servicoId, servicoNome);
                        }
                    })
                    .catch(() => {
                        mostrarConfirmacaoSimplesServico(servicoId, servicoNome);
                    });
            });
        });

        // Função para modal chamativo igual ao subtipo
        function mostrarConfirmacaoComReservasServico(id, nome, count) {
            Swal.fire({
                title: "<div style='color: #d90429; font-size: 2.2rem; font-weight: bold; letter-spacing: 1px; display: flex; flex-direction: column; align-items: center;'>" +
                    "<span style='font-size: 4rem; display: block; margin-bottom: 10px;'>⚠️</span>" +
                    "<span style='color: #d90429; text-shadow: 1px 1px 8px #fff, 0 0 8px #d90429;'>ATENÇÃO CRÍTICA!</span>" +
                "</div>",
                html: "<div style='text-align: center; margin: 20px 0; font-size: 1.15rem;'>" +
                    "<div style='color: #d90429; font-size: 1.3rem; font-weight: bold; margin-bottom: 10px;'>🚨 Esta ação é IRREVERSÍVEL! 🚨</div>" +
                    "<div style='margin-bottom: 15px;'>Deseja eliminar o serviço <span style='color: #d90429; font-weight: bold;'>&quot;" + nome + "&quot;</span>?</div>" +
                    "<div style='background: #fff3cd; border: 3px solid #d90429; border-radius: 12px; padding: 15px; margin: 0 auto 15px auto; max-width: 400px; color: #856404; font-weight: bold;'>" +
                        "<span style='font-size: 1.1rem;'>Este serviço tem <span style='color: #d90429;'>" + count + "</span> reserva(s) associada(s)!</span><br>" +
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
                    form.action = 'eliminar_servico.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'servico_id';
                    input.value = id;
                    const confirmInput = document.createElement('input');
                    confirmInput.type = 'hidden';
                    confirmInput.name = 'confirmar_com_reservas';
                    confirmInput.value = '1';
                    form.appendChild(input);
                    form.appendChild(confirmInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        // Modal simples se não houver reservas
        function mostrarConfirmacaoSimplesServico(id, nome) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Deseja eliminar o serviço '" + nome + "'? Esta ação não poderá ser revertida!",
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
                    form.action = 'eliminar_servico.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'servico_id';
                    input.value = id;
                    form.appendChild(input);
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
    const pdfLink = document.getElementById('pdfLink');
    if (pdfLink) {
        pdfLink.href = 'gerar_pdf_servicos.php?' + params.toString();
    }
}
if (document.getElementById('searchInput')) {
    document.getElementById('searchInput').addEventListener('input', atualizarLinkPDF);
}
document.addEventListener('DOMContentLoaded', atualizarLinkPDF);
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
