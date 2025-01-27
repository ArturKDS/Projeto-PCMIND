<?php
session_start();

require_once __DIR__.'/../../config/conexao.php';
require_once __DIR__.'/../controllers/UserControllers.php';
require_once __DIR__.'/../controllers/AuthController.php'; 
require_once __DIR__.'/../controllers/MoodleController.php'; 

$controller = new UserControllers($conexao);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['login'])) {
            $matricula = $_POST['matricula']; 
            $senha = $_POST['senha'];
            
            $controller->autenticar($matricula, $senha);
        } elseif (isset($_POST['salvar_respostas'])) {
            $usuario_id = $_SESSION['user_id'];
            $respostas = $_POST['respostas'];
           
            $controller->salvarRespostas($usuario_id, $respostas);
            header('Location: ../testPage.php');
            exit();
        }
    } else {
        header('Location: ../loginView.php');
        exit();
    }
} catch (Exception $e) {
    error_log('Erro no roteamento: ' . $e->getMessage());
    $_SESSION['mensagem'] = 'Ocorreu um erro. Por favor, tente novamente mais tarde.';
    header('Location: ../loginView.php');
    exit();
}
