<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    exit('Acesso negado');
}

require_once '../includes/db_conexao.php';

// Verificar se o ID do serviço foi enviado
if (!isset($_GET['servico_id']) || empty($_GET['servico_id'])) {
    echo '<div class="alert alert-warning">ID do serviço não fornecido.</div>';
    exit;
}

$servico_id = $conn->real_escape_string($_GET['servico_id']);

// Filter and column settings
$colunas_permitidas = ['nome', 'descricao', 'preco', 'duracao'];
$colunas_selecionadas = isset($_GET['colunas']) ? $_GET['colunas'] : ['nome', 'descricao', 'preco', 'duracao'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'descricao', 'preco', 'duracao'];
}

// Buscar subtipos do serviço
$sql = "SELECT id, nome, descricao, preco, duracao, imagem 
        FROM servico_subtipo 
        WHERE servico_id = '$servico_id' 
        ORDER BY nome";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-hover">';
    echo '<thead class="table-light">';
    echo '<tr>';
    echo '<th>Imagem</th>';
    echo '<th>Nome</th>';
    echo '<th>Descrição</th>';
    echo '<th>Preço</th>';
    echo '<th>Duração</th>';
    echo '<th>Ações</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>';
        if (!empty($row['imagem'])) {
            echo '<img src="../' . htmlspecialchars($row['imagem']) . '" alt="' . htmlspecialchars($row['nome']) . '" class="img-thumbnail" style="max-width: 80px; max-height: 80px; object-fit: cover;">';
        } else {
            echo '<div class="bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 4px;">';
            echo '<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>';
            echo '</div>';
        }
        echo '</td>';
        echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
        echo '<td>' . htmlspecialchars($row['descricao']) . '</td>';
        echo '<td>' . number_format($row['preco'], 2, ',', '.') . ' MZN</td>';
        echo '<td>' . htmlspecialchars($row['duracao']) . ' min</td>';
        echo '<td>';
        echo '<a href="editar_subservico.php?id=' . $row['id'] . '" class="btn btn-warning btn-sm">Editar</a> ';
        echo '<button class="btn btn-danger btn-sm btn-eliminar-subservico" data-id="' . $row['id'] . '">Eliminar</button>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Botão para adicionar novo subtipo
    echo '<div class="mt-3">';
    echo '<a href="adicionar_subservico.php?servico_id=' . $servico_id . '" class="btn btn-success">Adicionar Subtipo</a>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info">Não há subtipos cadastrados para este serviço.</div>';
    echo '<div class="mt-3">';
    echo '<a href="adicionar_subservico.php?servico_id=' . $servico_id . '" class="btn btn-success">Adicionar Subtipo</a>';
    echo '</div>';
}

// JavaScript para o modal de confirmação
echo '<style>
.swal-wide {
    max-width: 600px !important;
    border-radius: 15px !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
}

.swal-wide .swal2-title {
    background: linear-gradient(135deg, #d33, #c82333) !important;
    color: white !important;
    padding: 20px !important;
    margin: -20px -20px 20px -20px !important;
    border-radius: 15px 15px 0 0 !important;
    text-align: center !important;
}

.swal-wide .swal2-html-container {
    margin: 0 !important;
    padding: 0 20px !important;
}

.swal-wide .swal2-actions {
    padding: 20px !important;
    margin: 0 -20px -20px -20px !important;
    background: #f8f9fa !important;
    border-radius: 0 0 15px 15px !important;
}

.swal-wide .swal2-confirm {
    background: linear-gradient(135deg, #d33, #c82333) !important;
    border: none !important;
    padding: 12px 30px !important;
    font-weight: bold !important;
    text-transform: uppercase !important;
    letter-spacing: 1px !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 15px rgba(211, 51, 51, 0.3) !important;
}

.swal-wide .swal2-confirm:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 6px 20px rgba(211, 51, 51, 0.4) !important;
}

.swal-wide .swal2-cancel {
    background: linear-gradient(135deg, #6c757d, #5a6268) !important;
    border: none !important;
    padding: 12px 30px !important;
    font-weight: bold !important;
    transition: all 0.3s ease !important;
}

.swal-wide .swal2-cancel:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3) !important;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.warning-box {
    animation: pulse 2s infinite;
    border: 3px solid #ffc107 !important;
    background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3) !important;
}

.danger-info {
    border-left: 5px solid #d33 !important;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef) !important;
    box-shadow: 0 2px 10px rgba(220, 53, 69, 0.1) !important;
}
</style>';

?>
<script>
// Função para verificar se há reservas associadas
function verificarReservas(id, nome) {
    fetch("verificar_reservas_subtipo.php?subservico_id=" + id)
        .then(response => response.json())
        .then(data => {
            if (data.has_reservas) {
                mostrarConfirmacaoComReservas(id, nome, data.count);
            } else {
                eliminarSubtipo(id, nome);
            }
        })
        .catch(error => {
            console.error("Erro ao verificar reservas:", error);
            // Em caso de erro, mostrar confirmação padrão
            eliminarSubtipo(id, nome);
        });
}

// Função para mostrar confirmação quando há reservas
function mostrarConfirmacaoComReservas(id, nome, count) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: "<div style='color: #d90429; font-size: 2.2rem; font-weight: bold; letter-spacing: 1px; display: flex; flex-direction: column; align-items: center;'>" +
                "<span style='font-size: 4rem; display: block; margin-bottom: 10px;'>⚠️</span>" +
                "<span style='color: #d90429; text-shadow: 1px 1px 8px #fff, 0 0 8px #d90429;'>ATENÇÃO CRÍTICA!</span>" +
            "</div>",
            html: "<div style='text-align: center; margin: 20px 0; font-size: 1.15rem;'>" +
                "<div style='color: #d90429; font-size: 1.3rem; font-weight: bold; margin-bottom: 10px;'>🚨 Esta ação é IRREVERSÍVEL! 🚨</div>" +
                "<div style='margin-bottom: 15px;'>Deseja eliminar o subtipo <span style='color: #d90429; font-weight: bold;'>&quot;" + nome + "&quot;</span>?</div>" +
                "<div style='background: #fff3cd; border: 3px solid #d90429; border-radius: 12px; padding: 15px; margin: 0 auto 15px auto; max-width: 400px; color: #856404; font-weight: bold;'>" +
                    "<span style='font-size: 1.1rem;'>Este subtipo tem <span style='color: #d90429;'>" + count + "</span> reserva(s) associada(s)!</span><br>" +
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
                enviarFormularioEliminacaoComReservas(id);
            }
        });
    } else {
        var mensagem = "⚠️ ATENÇÃO CRÍTICA! ⚠️\\n\\n" +
                        "Deseja eliminar o subtipo \\\"" + nome + "\\\"?\\n\\n" +
                        "🚨 Esta ação é IRREVERSÍVEL! 🚨\\n" +
                        "Este subtipo tem " + count + " reserva(s) associada(s)\\n" +
                        "As alterações NÃO podem ser revertidas!\\n\\n" +
                        "Ao confirmar, todas as reservas associadas também serão eliminadas.\\n\\n" +
                        "Tem certeza absoluta que deseja continuar?";
        if (confirm(mensagem)) {
            enviarFormularioEliminacaoComReservas(id);
        }
    }
}

// Função simplificada para eliminar subtipo (sem reservas)
function eliminarSubtipo(id, nome) {
    if (typeof Swal !== "undefined") {
        Swal.fire({
            title: "Tem certeza?",
            text: `Deseja eliminar o subtipo "${nome}"?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sim, eliminar!",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                enviarFormularioEliminacao(id);
            }
        });
    } else {
        if (confirm(`Deseja eliminar o subtipo "${nome}"?`)) {
            enviarFormularioEliminacao(id);
        }
    }
}

// Função para enviar formulário de eliminação (sem reservas)
function enviarFormularioEliminacao(id) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "eliminar_subservico.php";
    
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "subservico_id";
    input.value = id;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// Função para enviar formulário de eliminação (com reservas)
function enviarFormularioEliminacaoComReservas(id) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "eliminar_subservico.php";
    
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "subservico_id";
    input.value = id;
    
    const confirmInput = document.createElement("input");
    confirmInput.type = "hidden";
    confirmInput.name = "confirmar_com_reservas";
    confirmInput.value = "1";
    
    form.appendChild(input);
    form.appendChild(confirmInput);
    document.body.appendChild(form);
    form.submit();
}

// Adicionar eventos aos botões quando o script for carregado
document.addEventListener("DOMContentLoaded", function() {
    console.log("Script de eliminação carregado");
    adicionarEventosEliminacao();
});

// Função para adicionar eventos de eliminação
function adicionarEventosEliminacao() {
    const buttons = document.querySelectorAll(".btn-eliminar-subservico");
    console.log("Encontrados " + buttons.length + " botões de eliminação");
    
    buttons.forEach(button => {
        button.onclick = function() {
            const id = this.dataset.id;
            const nome = this.closest("tr").querySelector("td:nth-child(2)").textContent.trim();
            console.log("Clicou em eliminar: ID=" + id + ", Nome=" + nome);
            verificarReservas(id, nome);
        };
    });
}

// Executar também após um delay para garantir que funcione
setTimeout(adicionarEventosEliminacao, 100);
</script>
<?php
$conn->close();
?> 