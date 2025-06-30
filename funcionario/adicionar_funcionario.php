<?php
session_start();
// Verifica se a sessão está iniciada corretamente
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
    $morada = trim($_POST['morada']);
    $localidade = trim($_POST['localidade']);
    $telefone1 = trim($_POST['telefone1']);
    $telefone2 = trim($_POST['telefone2']);
    if (empty($nome) || empty($email) || empty($morada) || empty($localidade) || empty($telefone1)) {
        $_SESSION['error'] = "Todos os campos obrigatórios devem ser preenchidos.";
        header("Location: adicionar_funcionario.php");
        exit();
    }
    $email = $conn->real_escape_string($email);
    $telefone1 = $conn->real_escape_string($telefone1);
    $telefone2 = $conn->real_escape_string($telefone2);
    $nome = $conn->real_escape_string($nome);
    $morada = $conn->real_escape_string($morada);
    $localidade = $conn->real_escape_string($localidade);
    $query = "SELECT * FROM funcionario WHERE email = '$email' OR telefone1 = '$telefone1'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'O email ou telefone já estão registados.';
        header('Location: adicionar_funcionario.php');
        exit();
    }
    $sql = "INSERT INTO funcionario (nome, email, morada, localidade, telefone1, telefone2) VALUES ('$nome', '$email', '$morada', '$localidade', '$telefone1', '$telefone2')";
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Funcionário adicionado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao adicionar funcionário: " . $conn->error;
    }
    $conn->close();
    header("Location: funcionario.php");
    exit();
}    

$title = 'Adicionar Funcionário';

// Start output buffering
ob_start();
?>
<body style="background: linear-gradient(135deg, #f8fafc 0%, #e9ecef 100%); min-height: 100vh;">
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card shadow">
          <div class="card-header bg-primary text-white text-center">
            <h3>Adicionar Funcionário</h3>
          </div>
          <div class="card-body p-4">
            <?php 
            if (isset($_SESSION['error'])) {
                echo '
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erro:</strong> ' . htmlspecialchars($_SESSION['error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
                unset($_SESSION['error']);
            }

        if (isset($_SESSION['success'])) {
            echo '
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Sucesso:</strong> ' . htmlspecialchars($_SESSION['success']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="adicionar_funcionario.php" method="POST">
          <div class="mb-4">
            <label for="nome" class="form-label fs-5">Nome</label>
            <input type="text" class="form-control form-control-lg" id="nome" name="nome" required>
          </div>
          <div class="mb-4">
            <label for="email" class="form-label fs-5">Email</label>
            <input type="email" class="form-control form-control-lg" id="email" name="email" required>
          </div>
          <div class="mb-4">
            <label for="morada" class="form-label fs-5">Morada</label>
            <input type="text" class="form-control form-control-lg" id="morada" name="morada" required>
          </div>
          <div class="mb-4">
            <label for="localidade" class="form-label fs-5">Localidade</label>
            <input type="text" class="form-control form-control-lg" id="localidade" name="localidade" required>
          </div>
          <div class="mb-4">
            <label for="telefone1" class="form-label fs-5">Telefone 1</label>
            <input type="tel" class="form-control form-control-lg" id="telefone1" name="telefone1" pattern="[0-9]{9}" required>
          </div>
          <div class="mb-4">
            <label for="telefone2" class="form-label fs-5">Telefone 2</label>
            <input type="tel" class="form-control form-control-lg" id="telefone2" name="telefone2" pattern="[0-9]{9}">
          </div>
          <button type="submit" class="btn btn-success btn-lg w-100 py-3 fs-5">
            <i class="bi bi-plus-circle me-2"></i>Adicionar Funcionário
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>
