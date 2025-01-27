<?php
session_start();
require_once __DIR__.'/../config/conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: loginView.php");
    exit;
}


$user_id = $_SESSION['user_id'];
$sql = "SELECT nome, turma, sexo, dataNASCIMENTO FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($nome, $turma, $sexo, $dataNASCIMENTO);
$stmt->fetch();
$stmt->close();


if ($turma && $sexo && $dataNASCIMENTO) {
    header("Location: testPage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $turma = $_POST['turma'];
    $sexo = $_POST['sexo'];
    $dataNASCIMENTO = $_POST['dataNASCIMENTO'];


    $sql_update = "UPDATE usuarios SET turma = ?, sexo = ?, dataNASCIMENTO = ? WHERE id = ?";
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param('sssi', $turma, $sexo, $dataNASCIMENTO, $user_id);
    $stmt_update->execute();
    $stmt_update->close();

 
    header("Location: testPage.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
    <title>Bem-vindo!</title>
    <link rel="stylesheet" href="css/welcome.css">
</head>
<body>
    <div class="container">
        <h1>Seja Bem Vindo</h1>
        <?php echo htmlspecialchars($nome); ?></p>
        <p>Por favor preencha as seguintes informações</p>
        <form method="post" action="">
            <label for="turma">Turma:</label>
            <select name="turma" id="turma" required>
                <option value="" disabled selected>Selecione sua turma</option>
                <option value="DS1">DS1</option>
                <option value="ELE1">ELE1</option>
                <option value="ADM1">ADM1</option>
            </select>

            <label for="sexo">Sexo:</label>
            <select name="sexo" id="sexo" required>
                <option value="" disabled selected>Selecione seu sexo</option>
                <option value="Masculino">Masculino</option>
                <option value="Feminino">Feminino</option>
            </select>

            <label for="dataNASCIMENTO">Data de Nascimento:</label>
            <input type="date" name="dataNASCIMENTO" id="dataNASCIMENTO" required>

            <button type="submit" class="button">Continuar</button>
        </form>
    </div>
</body>
</html>
