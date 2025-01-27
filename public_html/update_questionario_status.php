<?php
session_start();
require_once __DIR__.'/../config/conexao.php'; 

if (isset($_SESSION['user_id']) && isset($_POST['questionario_ativo'])) {
    $novoStatus = $_POST['questionario_ativo'] == '1' ? 1 : 0;
    $sql = "UPDATE configuracoes SET questionario_ativo = ? WHERE id = 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $novoStatus);
    $stmt->execute();
    $stmt->close();
}
?>
