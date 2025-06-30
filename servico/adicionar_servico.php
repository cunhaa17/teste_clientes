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

// Criar pasta de uploads se não existir
$upload_dir = __DIR__ . '/../../uploads/servicos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    if (empty($nome)) {
        $_SESSION['error'] = "O nome do serviço é obrigatório.";
        header("Location: adicionar_servico.php");
        exit();
    }
    $nome = $conn->real_escape_string($nome);
    
    // Verificar se já existe um serviço com o mesmo nome
    $sql_check = "SELECT id FROM servico WHERE nome = '$nome'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['error'] = "Já existe um serviço com este nome.";
        header("Location: adicionar_servico.php");
        exit();
    }
    
    // Processar upload da imagem (obrigatório)
    if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "A imagem é obrigatória.";
        header("Location: adicionar_servico.php");
        exit();
    }
    
    $file = $_FILES['imagem'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Verificar tipo de arquivo
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['error'] = "Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.";
        header("Location: adicionar_servico.php");
        exit();
    }
    
    // Verificar tamanho do arquivo
    if ($file['size'] > $max_size) {
        $_SESSION['error'] = "Arquivo muito grande. Tamanho máximo: 5MB.";
        header("Location: adicionar_servico.php");
        exit();
    }
    
    // Gerar nome único para o arquivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'servico_' . time() . '_' . uniqid() . '.' . $extension;
    $upload_path = $upload_dir . $filename;
    
    // Mover arquivo para pasta de uploads
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $_SESSION['error'] = "Erro ao fazer upload da imagem.";
        header("Location: adicionar_servico.php");
        exit();
    }
    
    $imagem_path = 'uploads/servicos/' . $filename;
    
    // Inserir o novo serviço
    $sql = "INSERT INTO servico (nome, imagem) VALUES ('$nome', '$imagem_path')";
    
    if ($conn->query($sql)) {
        $_SESSION['success'] = "Serviço adicionado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['error'] = "Erro ao adicionar serviço: " . $conn->error;
        header("Location: adicionar_servico.php");
        exit();
    }
}

$title = 'Adicionar Serviço';

// Your existing code...

// Start output buffering
ob_start();
?>
<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
          <h3>Adicionar Serviço</h3>
        </div>
        <div class="card-body p-4">
          <form action="adicionar_servico.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
              <label for="nome" class="form-label fs-5">Nome do Serviço *</label>
              <input type="text" class="form-control form-control-lg" id="nome" name="nome" required>
            </div>
            
            <div class="mb-4">
              <label for="imagem" class="form-label fs-5">Imagem do Serviço *</label>
              <input type="file" class="form-control form-control-lg" id="imagem" name="imagem" accept="image/*" required>
              <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB</div>
            </div>
            
            <div class="mb-4">
              <div id="preview-container" style="display: none;">
                <label class="form-label fs-5">Preview da Imagem</label>
                <div>
                  <img id="imagem-preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                </div>
              </div>
            </div>
            
            <button type="submit" class="btn btn-success btn-lg w-100 py-3 fs-5">
              <i class="bi bi-plus-circle me-2"></i>Adicionar Serviço
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Preview da imagem
  const imagemInput = document.getElementById('imagem');
  const previewContainer = document.getElementById('preview-container');
  const imagemPreview = document.getElementById('imagem-preview');
  
  imagemInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        imagemPreview.src = e.target.result;
        previewContainer.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      previewContainer.style.display = 'none';
    }
  });

  const toastEl = document.getElementById('successToast') || document.getElementById('errorToast');
  const progressBar = document.getElementById('toastProgressBar');
  if (toastEl && progressBar) {
    let width = 100;
    const duration = 3000; // 3 segundos
    const intervalTime = 30;

    // Mostra o toast
    const toast = new bootstrap.Toast(toastEl, { autohide: false });
    toast.show();

    // Anima a barra
    const interval = setInterval(() => {
      width -= (intervalTime / duration) * 100;
      progressBar.style.width = width + "%";
      if (width <= 0) {
        clearInterval(interval);
        toast.hide();
      }
    }, intervalTime);
  }
});
</script>
<?php
$content = ob_get_clean(); // Capture the output and store it in $content

// Include the layout file
include '../includes/layout.php';
?>
