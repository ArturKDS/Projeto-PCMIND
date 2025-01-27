<?php
session_start();
require_once __DIR__.'/../config/conexao.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['nome'])) {
    header('Location: loginView.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['promover'])) {
        $user_id = $_POST['user_id'];

        $sql = "UPDATE usuarios SET role_id = 2 WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('i', $user_id);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Usuário promovido a administrador com sucesso.";
        } else {
            $_SESSION['mensagem'] = "Erro ao promover usuário.";
        }

        $stmt->close();

    } elseif (isset($_POST['excluir'])) {
        $user_id = $_POST['user_id'];

        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('i', $user_id);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Usuário excluído com sucesso.";
        } else {
            $_SESSION['mensagem'] = "Erro ao excluir usuário.";
        }

        $stmt->close();
    }

    header('Location: usersView.php');
    exit();
} else {
    header('Location: usersView.php');
    exit();
}
