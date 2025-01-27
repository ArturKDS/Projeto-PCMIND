<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['nome'])) {
    header('Location: loginView.php');
    exit();
}

require_once __DIR__.'/../config/conexao.php';

$sql = "SELECT role_id FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$role_id = $result->fetch_assoc()['role_id'];
$stmt->close();

if ($role_id != 2) {
    echo "Acesso negado.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];

    $sql = "UPDATE usuarios SET role_id = 1 WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Cargo de administrador removido com sucesso.";
    } else {
        $_SESSION['mensagem'] = "Erro ao remover o cargo de administrador.";
    }
    $stmt->close();
}

header('Location: usersView.php');
exit();
?>
