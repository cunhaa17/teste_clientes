<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

require_once '../includes/db_conexao.php';
$title = 'Editar Serviço';

// Criar pasta de uploads se não existir
$upload_dir = __DIR__ . '/../../uploads/servicos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Validar e obter ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    $_SESSION['mensagem'] = "ID inválido.";
    header("Location: servico.php");
    exit();
}

$id = intval($_GET['id']);

// Buscar dados do serviço
$sql = "SELECT * FROM servico WHERE id = '" . $conn->real_escape_string($id) . "'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $_SESSION['mensagem'] = "Serviço não encontrado.";
    header("Location: servico.php");
    exit();
}

$servico = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar se o ID foi enviado
    if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $_SESSION['mensagem'] = "ID inválido no formulário.";
        header("Location: servico.php");
        exit();
    }
    
    $id_post = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    
    if (empty($nome)) {
        $_SESSION['mensagem'] = "O nome do serviço é obrigatório.";
        header("Location: editar_servico.php?id=" . $id);
        exit();
    }
    
    // Verificar se já existe um serviço com o mesmo nome
    $sql_check = "SELECT id FROM servico WHERE nome = '" . $conn->real_escape_string($nome) . "' 
                  AND id != '" . $conn->real_escape_string($id_post) . "'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Já existe um serviço com este nome.";
        header("Location: editar_servico.php?id=" . $id);
        exit();
    }
    
    // Processar upload da imagem (obrigatório se não tiver imagem atual)
    $imagem_path = $servico['imagem']; // Manter imagem atual se não for enviada nova
    $imagem_obrigatoria = empty($servico['imagem']); // Se não tem imagem, é obrigatório
    
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['imagem'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Verificar tipo de arquivo
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['mensagem'] = "Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.";
            header("Location: editar_servico.php?id=" . $id);
            exit();
        }
        
        // Verificar tamanho do arquivo
        if ($file['size'] > $max_size) {
            $_SESSION['mensagem'] = "Arquivo muito grande. Tamanho máximo: 5MB.";
            header("Location: editar_servico.php?id=" . $id);
            exit();
        }
        
        // Gerar nome único para o arquivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'servico_' . time() . '_' . uniqid() . '.' . $extension;
        $upload_path = $upload_dir . $filename;
        
        // Mover arquivo para pasta de uploads
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Deletar imagem antiga se existir
            if (!empty($servico['imagem']) && file_exists('../' . $servico['imagem'])) {
                unlink('../' . $servico['imagem']);
            }
            $imagem_path = 'uploads/servicos/' . $filename;
        } else {
            $_SESSION['mensagem'] = "Erro ao fazer upload da imagem.";
            header("Location: editar_servico.php?id=" . $id);
            exit();
        }
    } elseif ($imagem_obrigatoria) {
        // Se não tem imagem atual e não foi enviada nova, mostrar erro
        $_SESSION['mensagem'] = "A imagem é obrigatória para este serviço.";
        header("Location: editar_servico.php?id=" . $id);
        exit();
    }
    
    // Atualizar o serviço
    $sql_update = "UPDATE servico SET nome = '" . $conn->real_escape_string($nome) . "', 
                   imagem = '" . $conn->real_escape_string($imagem_path) . "' 
                   WHERE id = '" . $conn->real_escape_string($id_post) . "'";
    
    if ($conn->query($sql_update)) {
        $_SESSION['success'] = "Serviço atualizado com sucesso!";
        header("Location: servico.php");
        exit();
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar serviço: " . $conn->error;
        header("Location: editar_servico.php?id=" . $id);
        exit();
    }
}

$conn->close();

ob_start();
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Editar Serviço</h1>
        <a href="servico.php" class="btn btn-secondary">Voltar</a>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['mensagem']);
            unset($_SESSION['mensagem']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="editar_servico.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome do Serviço *</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?php echo htmlspecialchars($servico['nome']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="imagem" class="form-label">
                        Imagem do Serviço 
                        <?php if (empty($servico['imagem'])): ?>
                            <span class="text-danger">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*" 
                           <?php if (empty($servico['imagem'])): ?>required<?php endif; ?>>
                    <div class="form-text">
                        <?php if (empty($servico['imagem'])): ?>
                            <span class="text-danger">A imagem é obrigatória para este serviço.</span><br>
                        <?php else: ?>
                            Deixe em branco para manter a imagem atual.<br>
                        <?php endif; ?>
                        Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 5MB
                    </div>
                </div>

                <?php if (!empty($servico['imagem'])): ?>
                <div class="mb-3">
                    <label class="form-label">Imagem Atual</label>
                    <div>
                        <img src="../<?php echo htmlspecialchars($servico['imagem']); ?>" alt="Imagem atual" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <div id="preview-container" style="display: none;">
                        <label class="form-label">Nova Imagem (Preview)</label>
                        <div>
                            <img id="imagem-preview" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Atualizar Serviço</button>
                <a href="servico.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Sucesso!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                <p class="mt-3 mb-0">Serviço atualizado com sucesso!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
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
    
    // Validação do formulário
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const imagemInput = document.getElementById('imagem');
        const imagemAtual = <?php echo !empty($servico['imagem']) ? 'true' : 'false'; ?>;
        
        // Se não tem imagem atual e não foi selecionada uma nova
        if (!imagemAtual && (!imagemInput.files || imagemInput.files.length === 0)) {
            e.preventDefault();
            alert('A imagem é obrigatória para este serviço.');
            imagemInput.focus();
            return false;
        }
    });
    
    // Verifica se há mensagem de sucesso na sessão
    <?php if (isset($_SESSION['success'])): ?>
        const successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
});
</script>

<?php
$content = ob_get_clean();
include '../includes/layout.php';
?>
