<?php
session_start();
session_regenerate_id(true);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticação</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/media.css">
    <link rel="stylesheet" href="css/wel.css">
    <link rel="icon" href="img/pcmind.png" type="image/png">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="js/main.js" defer></script>
</head>
<body>
<div id="container">
    <div class="banner">
        <img src="img/pcmind.png" alt="imagem-login">
    </div>

    <div class="box-login">
        <div class="box-account">
            <h2>
                Autenticação via 
                <img src="img/moodle.png" alt="Moodle Logo" class="moodle-logo">
            </h2>
            <br>
            <?php
            if (isset($_SESSION['mensagem'])) {
                echo "<p class='error-message'>{$_SESSION['mensagem']}</p>";
                unset($_SESSION['mensagem']);
            }
            ?>
            <form method="post" action="routes/web.php">
                <input type="text" name="matricula" id="matricula" placeholder="Número de Matrícula" required autocomplete="off">
                <input type="password" name="senha" id="senha" placeholder="Senha" required autocomplete="current-password">
                <br>
                <br>
                <div class="button-container">
                    <button type="submit" name="login" class="login-button">Autenticar</button>
                </div>
            </form>
        </div>
        <br>
    </div>
</div>
</body>
</html>
