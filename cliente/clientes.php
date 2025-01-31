<?php
// Inicia a sessão para permitir o uso de variáveis de sessão
session_start();

// Inclui o ficheiro de conexão com a base de dados
include_once '../includes/db_conexao.php';

// Inclui a barra lateral da interface
include '../includes/sidebar.php';

// Ativa a exibição de erros para facilitar a depuração
ini_set('display_errors', 1); // Ativa a exibição de erros
ini_set('display_startup_errors', 1); // Exibe erros durante a inicialização
error_reporting(E_ALL); // Define o relatório de erros para exibir todos os erros

// Define cabeçalhos HTTP para evitar armazenamento em cache da página
header("Cache-Control: no-cache, no-store, must-revalidate"); // Impede que a página seja armazenada no cache
header("Pragma: no-cache"); // Outra forma de evitar cache
header("Expires: 0"); // Define a expiração imediata do cache

// Verifica se o parâmetro 'clear' foi passado via GET
if (isset($_GET['clear'])) {
    header("Location: clientes.php"); // Redireciona para a página de clientes
    exit(); // Termina a execução do script
}

// Obtém a mensagem da sessão, se existir
$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
unset($_SESSION['mensagem']); // Remove a mensagem da sessão após ser exibida

// Define as colunas padrão a serem selecionadas na consulta
$colunas_selecionadas = isset($_GET['colunas']) ? $_GET['colunas'] : ['id', 'nome', 'email', 'telefone'];
$colunas_permitidas = ['id', 'nome', 'email', 'telefone']; // Lista de colunas permitidas

// Garante que apenas colunas permitidas sejam utilizadas
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

// Se nenhuma coluna for selecionada, define as padrão
if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['id', 'nome', 'email', 'telefone'];
}

// Converte a lista de colunas em uma string para uso na consulta SQL
$colunas_sql = implode(", ", array_unique(array_merge($colunas_selecionadas, ['id'])));

// Obtém os parâmetros de ordenação passados via GET ou usa padrão
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$ordem = isset($_GET['ordem']) ? $_GET['ordem'] : 'DESC';

// Lista de colunas permitidas para ordenação
$colunas_permitidas_ordenacao = ['id', 'nome', 'email', 'telefone'];

// Verifica se a coluna de ordenação é válida, caso contrário, define 'id' como padrão
if (!in_array($ordenar_por, $colunas_permitidas_ordenacao)) {
    $ordenar_por = 'id';
}

// Garante que a ordem seja apenas 'ASC' ou 'DESC'
$ordem = ($ordem == 'ASC') ? 'ASC' : 'DESC';

// Define a cláusula ORDER BY para a consulta SQL
$order_by_clause = " ORDER BY $ordenar_por $ordem";

// Monta a consulta SQL
$sql = "SELECT $colunas_sql FROM Cliente WHERE 1=1";
$types = ''; // Inicializa a string de tipos para consulta preparada
$params = []; // Inicializa a lista de parâmetros para consulta preparada

// Adiciona a cláusula ORDER BY à consulta
$sql .= $order_by_clause;

// Prepara a consulta SQL
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na consulta SQL: " . htmlspecialchars($conn->error)); // Exibe erro e interrompe a execução
}

// Executa a consulta
$stmt->execute();

// Obtém o resultado da consulta
$resultado = $stmt->get_result();

// Converte os resultados para um array associativo
$clientes = $resultado->fetch_all(MYSQLI_ASSOC);

// Fecha a consulta preparada
$stmt->close();

// Fecha a conexão com a base de dados
$conn->close();
?>

<div class="container mt-5"> <!-- Container principal com margem superior -->
    <h2 class="text-center">Lista de Clientes</h2> <!-- Título centralizado da página -->

    <!-- Formulário para pesquisa e filtros -->
    <form method="GET" class="mb-3"> <!-- Método GET para enviar os filtros pela URL -->
        <div class="row align-items-center"> <!-- Linha para alinhar elementos verticalmente -->

            <div class="col-md-4"> <!-- Campo de pesquisa ocupa 4 colunas no layout -->
                <input type="text" id="searchInput" name="search" class="form-control" placeholder="Pesquisar cliente...">
                <!-- Campo de entrada de texto para pesquisar clientes -->
            </div>

            <div class="col-md-4"> <!-- Section for selecting columns to display, occupies 4 columns -->
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        Selecionar Colunas
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <?php foreach ($colunas_permitidas as $coluna): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="colunas[]" value="<?php echo $coluna; ?>" id="<?php echo $coluna; ?>" <?php echo in_array($coluna, $colunas_selecionadas) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $coluna; ?>">
                                    <?php echo ucfirst($coluna); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-2"> <!-- Dropdown para ordenar os resultados, ocupa 2 colunas -->
                <select name="ordem" class="form-control">
                    <option value="ASC" <?php echo $ordem === 'ASC' ? 'selected' : ''; ?>>Ascendente</option>
                    <option value="DESC" <?php echo $ordem === 'DESC' ? 'selected' : ''; ?>>Descendente</option>
                </select>
                <!-- Define a ordenação da tabela (ASC ou DESC) e mantém a opção escolhida -->
            </div>

            <div class="col-md-2 d-flex justify-content-end"> <!-- Botões alinhados à direita -->
                <button type="submit" class="btn btn-primary">Filtrar</button> <!-- Botão de submissão -->
                <a href="adicionar_cliente.php" class="btn btn-success ms-2">Adicionar Cliente</a> <!-- Botão para adicionar cliente -->
            </div>
        </div>
    </form>

    <div class="table-responsive"> <!-- Tabela responsiva -->
        <table class="table table-bordered table-striped"> <!-- Tabela com bordas e listras -->
            <thead class="table-dark"> <!-- Cabeçalho da tabela com fundo escuro -->
                <tr>
                    <?php foreach ($colunas_selecionadas as $coluna): ?>
                        <th><?php echo ucfirst($coluna); ?></th> <!-- Exibe as colunas escolhidas -->
                    <?php endforeach; ?>
                    <th>Ações</th> <!-- Coluna para ações (Editar/Eliminar) -->
                </tr>
            </thead>
            <tbody id="clientesTabela"> <!-- Corpo da tabela -->
                <?php foreach ($clientes as $cliente): ?> <!-- Percorre a lista de clientes -->
                    <tr>
                        <?php foreach ($colunas_selecionadas as $coluna): ?>
                            <td><?php echo htmlspecialchars($cliente[$coluna] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <!-- Exibe os dados do cliente, sanitizando para evitar XSS -->
                        <?php endforeach; ?>
                        <td>
                            <a href="editar_cliente.php?id=<?php echo urlencode($cliente['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                            <!-- Botão para editar cliente -->
                            <a href="#" class="btn btn-danger btn-sm" data-id="<?php echo urlencode($cliente['id']); ?>" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Eliminar</a>
                            <!-- Botão para eliminar cliente, abre modal de confirmação -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de confirmação de eliminação -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Eliminação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja eliminar este cliente?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Inclusão de scripts necessários -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Biblioteca jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap JS -->
<script src="../assets/js/style.js"></script> <!-- Script personalizado -->