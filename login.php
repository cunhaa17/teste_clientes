<?php
session_start();

include 'includes/db_conexao.php'; // Arquivo que conecta ao banco de dados

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];

    // Verifica se o utilizador existe
    $sql = "SELECT * FROM adms WHERE email = '$email' LIMIT 1";
    $resultado = $conn->query($sql);

    if ($resultado->num_rows > 0) {
        $utilizador = $resultado->fetch_assoc();

        // Verifica a senha
        if (password_verify($senha, $utilizador['senha'])) {
            $_SESSION['utilizador_id'] = $utilizador['id'];
            $_SESSION['utilizador_nome'] = $utilizador['nome'];
            $_SESSION['utilizador_tipo'] = $utilizador['tipo'];
            $_SESSION['ultima_atividade'] = time();

            // Redireciona para a dashboard
            header("Location: index.php");
            exit();
        }
    }

    // Mensagem de erro genérica
    $erro = "Uma das credenciais está errada.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LotusSPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #A36A07;
            --primary-dark: #805306;
            --primary-light: #FFB52E;
            --secondary: #FFF8EC;
            --dark: #2C1810;
            --light: #FFF8EC;
            --accent: #D4973D;
            --tertiary: #62816C;
            --quaternary: #8BA893;
        }

        body { 
            font-family: 'Poppins', sans-serif;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3)),
                        url('../goncalo_pap/site_pap/images/about-us.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
            margin: 0;
        }

        .login-container { 
            background: rgba(255, 255, 255, 0.95);
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2),
                        0 0 0 1px rgba(255, 255, 255, 0.1); 
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-container:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3),
                        0 0 0 1px rgba(255, 255, 255, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 3px;
        }

        .login-header img {
            height: 70px;
            margin-bottom: 25px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .login-header img:hover {
            transform: scale(1.08) rotate(2deg);
            filter: drop-shadow(0 6px 8px rgba(0, 0, 0, 0.15));
        }

        .login-header h2 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 2.2rem;
            background: linear-gradient(45deg, var(--dark), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-header p {
            color: var(--tertiary);
            font-size: 1.15rem;
            margin-bottom: 0;
            opacity: 0.9;
            letter-spacing: 0.3px;
        }

        .form-floating {
            margin-bottom: 20px;
            position: relative;
        }

        .form-floating > .form-control {
            padding: 1.2rem 0.75rem;
            height: calc(3.8rem + 2px);
            font-size: 1.05rem;
        }

        .form-floating > label {
            padding: 1.2rem 0.75rem;
            font-size: 1.05rem;
            color: var(--tertiary);
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transform: scale(0.85) translateY(-1rem) translateX(0.15rem);
            color: var(--primary);
        }

        .form-control {
            border: 2px solid var(--secondary);
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(163, 106, 7, 0.15);
            background: #fff;
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(163, 106, 7, 0.2);
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--primary-dark), var(--primary));
            transition: all 0.3s ease;
        }

        .btn-primary:hover::before {
            left: 0;
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(163, 106, 7, 0.4);
        }

        .btn-primary span {
            position: relative;
            z-index: 1;
        }

        .erro {
            background: rgba(220, 53, 69, 0.08);
            color: #dc3545;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
            border: 1px solid rgba(220, 53, 69, 0.2);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .input-group-text {
            background: transparent;
            border: 2px solid var(--secondary);
            border-right: none;
            border-radius: 12px 0 0 12px;
        }

        .password-toggle {
            cursor: pointer;
            color: var(--tertiary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.2rem;
            opacity: 0.7;
        }

        .password-toggle:hover {
            color: var(--primary);
            opacity: 1;
            transform: scale(1.1);
        }

        /* Animação de entrada */
        .animate__animated {
            animation-duration: 0.8s;
        }

        /* Responsividade */
        @media (max-width: 576px) {
            .login-container {
                padding: 30px 20px;
            }

            .login-header h2 {
                font-size: 1.75rem;
            }

            .login-header p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container animate__animated animate__fadeIn">
    <div class="login-header">
        <img src="../goncalo_pap/site_pap/images/logo.svg" alt="LotusSPA Logo" class="animate__animated animate__fadeInDown">
        <h2 class="animate__animated animate__fadeInUp">Bem-vindo de volta</h2>
        <p class="animate__animated animate__fadeInUp" style="animation-delay: 0.2s">Acesse sua conta para continuar</p>
    </div>

    <?php if (isset($_SESSION['mensagem'])): ?>
        <div class="alert alert-<?php echo $_SESSION['tipo_mensagem']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['mensagem'];
            unset($_SESSION['mensagem']);
            unset($_SESSION['tipo_mensagem']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($erro)) { 
        echo "<div class='erro animate__animated animate__fadeIn'>
                <i class='bi bi-exclamation-circle me-2'></i>$erro
              </div>"; 
    } ?>

    <form method="POST" action="" class="animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
            <label for="email"><i class="bi bi-envelope me-2"></i>Email</label>
        </div>
        <div class="form-floating mb-4">
            <input type="password" class="form-control" id="senha" name="senha" placeholder="Password" required>
            <label for="senha"><i class="bi bi-lock me-2"></i>Senha</label>
            <i class="bi bi-eye password-toggle position-absolute end-0 top-50 translate-middle-y me-3" 
               onclick="togglePassword()" style="cursor: pointer;"></i>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <span><i class="bi bi-box-arrow-in-right me-2"></i>Entrar</span>
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword() {
        const senhaInput = document.getElementById('senha');
        const toggleIcon = document.querySelector('.password-toggle');
        
        if (senhaInput.type === 'password') {
            senhaInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            senhaInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }

    // Adiciona efeito de hover nos inputs
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('animate__animated', 'animate__pulse');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate__animated', 'animate__pulse');
        });
    });
</script>
</body>
</html>