<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionário Inativo</title>
    <link rel="stylesheet" href="css/mostad.css">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
</head>
<body>
    <div class="content">
        <h2>Não há questionário ativo no momento.</h2>
        <p>Por favor, verifique mais tarde.</p>
        <div class="button-container">
            <button onclick="window.location.href='logout.php'" class="button button-back">Sair</button>
        </div>
    </div>
</body>
</html>
