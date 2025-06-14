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
$sql = "SELECT id, nome, descricao, preco, duracao 
        FROM servico_subtipo 
        WHERE servico_id = '$servico_id' 
        ORDER BY nome";

$result = $conn->query($sql);

// Add the delete confirmation modal
echo '<div class="modal fade" id="confirmDeleteSubservicoModal" tabindex="-1" aria-labelledby="confirmDeleteSubservicoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 bg-danger text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title" id="confirmDeleteSubservicoLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Atenção: Eliminação de Subtipo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bi bi-exclamation-circle text-danger" style="font-size: 4rem;"></i>
                <h4 class="mt-3 mb-3">Tem certeza que deseja eliminar este subtipo?</h4>
                <p class="text-muted">Esta ação não pode ser desfeita e todos os dados associados serão permanentemente removidos.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="border-radius: 8px;">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger px-4" id="confirmDeleteSubservicoBtn" style="border-radius: 8px;">
                    <i class="bi bi-trash me-2"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>';

if ($result->num_rows > 0) {
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-hover">';
    echo '<thead class="table-light">';
    echo '<tr>';
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

$conn->close();
?> 