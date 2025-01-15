<?php
session_start(); // Inicia a sessão

$servername = "localhost";
$username = "root"; // Seu usuário do MySQL
$password = ""; // Sua senha do MySQL
$dbname = "confirapix";

// Conexão segura com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se a conexão foi bem-sucedida
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Filtra as entradas do usuário para evitar caracteres indesejados
    $user = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $pass = $_POST['password']; // Senha não deve ser sanitizada

    // Consulta SQL atualizada para verificar status
    $stmt = $conn->prepare("SELECT password, BancoEscolhido, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->store_result();

    // Verifica se o usuário existe no banco de dados
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password, $BancoEscolhido, $status);
        $stmt->fetch();

        // Verifica se o status é "Ativo"
        if ($status == "Bloqueado") {
            echo "<script>alert('Usuário Bloqueado por falta de pagamento. Entre em contato com o suporte.');</script>";
        } elseif ($status == "Pendente") {
            header('Location: padrao.html');
        } else {
            // Verifica se a senha corresponde
            if (password_verify($pass, $hashed_password)) {
                // Se o login for bem-sucedido, armazena o nome de usuário e a hora do login na sessão
                $_SESSION['login'] = htmlspecialchars($user); // Evita XSS
                $_SESSION['login_time'] = time(); // Hora do login em timestamp
                
                // Redireciona com base no valor de 'banco'
                if ($BancoEscolhido == 'Mercado-Pago') {
                    header('Location: mercado-pago/dashboard.php'); // Página para usuários com banco = N1
                } elseif ($BancoEscolhido == 'Banco-do-Brasil') {
                    header('Location: Banco-do-Brasil/dashboard.php'); // Página para usuários com banco = N2
                } elseif ($BancoEscolhido == 'Inter') {
                    header('Location: Inter/dashboard.php'); // Página para usuários com banco = N3
                } else {
                    // Redireciona para uma página padrão ou de erro, se necessário
                    header('Location: padrao.html');
                }
                exit;
            } else {
                // Mensagem de erro para senha incorreta
                echo "Senha incorreta.";
            }
        }
    } else {
        echo "Usuário não encontrado.";
    }

    $stmt->close();
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Confira PIX</title>
    <link rel="shortcut icon" href="img/site.png" type="image/x-icon">
    <link rel="stylesheet" href="style/login.css">
</head>

<body>
    <div class="nav-top">
    <a href="./index.html"><img src="img/logo-site.png" class="logo"></a>
    </div>

    <div class="container">
        <div class="userform">
            <p class="titlelogin">Login</p>
            <form class="form" action="login.php" method="POST">
                <div class="userlogin">
                    <span>Usuario</span><br>
                    <input type="text" name="username" placeholder="Digite o Usuário" id="username" required /><br>
                </div>
                <div class="userlogin">
                    <span>Senha</span><br>
                    <input type="password" name="password" id="password" placeholder="Digite a Senha" required /><br>
                </div>
                <div class="bnt-login">
                    <span><a href="reset.php">Esqueceu a Senha?</a></span><br>
                    <input class="submit" type="submit" value="Entrar" /><br>
                    <span><a href="Cadastro.html">Não tem Conta? Crie Aqui</a></span>
                </div>

                <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <div class="banco-cad">
            <img src="img/banco.png" class="bancologin">
        </div>
    </div>


    <div class="footer">
        <div class="footer-aling">
    <div class="text-footer">
        <a href="">Pagina Inical</a>
    </div>
    <div class="text-footer">
        <a href="./Termo-de-Uso.html">Termos e Condições</a>
    </div>
    <div class="text-footer">
        <h4>Contato</h4>
        <a href="#">E-mail<br>Whatsapp</a>
    </div>
    </div>
    <footer>ConfiraPix - &copy; 57.774.809 VICTOR FERNANDO DOS SANTOS</footer>
</div>

</body>

</html>