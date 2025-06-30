<?php
session_start();

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    // Redireciona para a página de login, usando o caminho correto
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);

    if (empty($nome) || empty($email) || empty($telefone)) {
        $_SESSION['error'] = "Todos os campos são obrigatórios.";
        header("Location: adicionar_cliente.php");
        exit();
    }

    // Escapa os valores para evitar SQL injection
    $email = $conn->real_escape_string($email);
    $telefone = $conn->real_escape_string($telefone);

    // Monta a query diretamente com os valores escapados
    $query = "SELECT * FROM cliente WHERE email = '$email' OR telefone = '$telefone'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
        header('Location: adicionar_cliente.php');
        exit();
    }

    // Insere o cliente na base de dados
    $nome = $conn->real_escape_string($nome);
    $sql = "INSERT INTO cliente (nome, email, telefone) VALUES ('$nome', '$email', '$telefone')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Cliente adicionado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar cliente: " . $conn->error;
    }

    $conn->close();

    header("Location: clientes.php");
    exit();
}

$title = 'Adicionar Cliente';

// Start output buffering
ob_start();

$total_clientes = $conn->query("SELECT COUNT(*) FROM cliente")->fetch_row()[0];
$novos_mes = $conn->query("SELECT COUNT(*) FROM cliente WHERE MONTH(data_registo) = MONTH(CURDATE()) AND YEAR(data_registo) = YEAR(CURDATE())")->fetch_row()[0];
$ultimo_cliente = $conn->query("SELECT nome FROM cliente ORDER BY data_registo DESC LIMIT 1")->fetch_row()[0];
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <!-- Form Card -->
      <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
          <h3>Adicionar Cliente</h3>
        </div>
        <div class="card-body p-4">
          <form action="adicionar_cliente.php" method="POST">
            <div class="mb-4">
              <label for="nome" class="form-label fs-5">Nome</label>
              <input type="text" class="form-control form-control-lg" id="nome" name="nome" required>
            </div>
            <div class="mb-4">
              <label for="email" class="form-label fs-5">Email</label>
              <input type="email" class="form-control form-control-lg" id="email" name="email" required>
            </div>
            <div class="mb-4">
              <label for="telefone" class="form-label fs-5">Telefone</label>
              <input type="tel" class="form-control form-control-lg" id="telefone" name="telefone" pattern="[0-9]{9}" required>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100 py-3 fs-5">
              <i class="bi bi-plus-circle me-2"></i>Adicionar Cliente
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>