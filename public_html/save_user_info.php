<?php
session_start();
require_once __DIR__.'/../config/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $turma = $_POST['turma'];
    $sexo = $_POST['sexo'];
    $dataNASCIMENTO = $_POST['dataNASCIMENTO'];

    $sql = "UPDATE usuarios SET turma = ?, sexo = ?, dataNASCIMENTO = ? WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('sssi', $turma, $sexo, $dataNASCIMENTO, $userId);

    if ($stmt->execute()) {
        header('Location: testPage.php');
        exit();
    } else {
        echo "Erro ao salvar as informações. Por favor, tente novamente.";
    }
}
?>
