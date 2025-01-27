<?php
require_once __DIR__.'/../../config/conexao.php';
require_once __DIR__.'/../../models/UserModel.php';
require_once __DIR__ . '/MoodleController.php';

class UserControllers {
    private $conexao;

    public function __construct($conexao) {
        $this->conexao = $conexao;
    }

    public function autenticar($matricula, $senha) {
        $server = 'moodle.canoas.ifrs.edu.br'; 

        $tokenResult = MoodleController::generateToken($matricula, $senha, $server);

        if (isset($tokenResult->token)) {
            $userInfo = MoodleController::getUserInfo($server, $tokenResult->token, $matricula);

            $nome = $userInfo->fullname;  
            $email = $userInfo->email;    
            $profileImageURL = MoodleController::getUserImageURL($userInfo, $tokenResult->token); 

            $userModel = new UserModel($this->conexao);
            $user = $userModel->getUserByMatricula($matricula);

            if (!$user) {
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                $sql = "INSERT INTO usuarios (nome, email, matricula, senha, profile_image_url) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conexao->prepare($sql);
                $stmt->bind_param("sssss", $nome, $email, $matricula, $senhaHash, $profileImageURL);

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Cadastro realizado com sucesso. Por favor, faça o login novamente.";
                    header('Location: ../loginView.php');
                    exit();
                } else {
                    $_SESSION['mensagem'] = "Erro ao cadastrar novo usuário.";
                    header('Location: ../loginView.php');
                    exit();
                }
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nome'] = $user['nome'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['moodle_token'] = $tokenResult->token;
            $_SESSION['user_image'] = $user['profile_image_url']; 

            // Verifica se os campos turma, sexo e dataNASCIMENTO estão preenchidos
            if (empty($user['turma']) || empty($user['sexo']) || empty($user['dataNASCIMENTO'])) {
                header('Location: ../welcome.php'); 
            } elseif ($user['role_id'] == 2) {
                header('Location: ../index.php'); 
            } else {
                header('Location: ../testPage.php'); 
            }
            exit();
        } else {
            $_SESSION['mensagem'] = "Credenciais inválidas no Moodle.";
            header('Location: ../loginView.php');
            exit();
        }
    }

    public function getUserByMatricula($matricula) {
        $userModel = new UserModel($this->conexao);
        return $userModel->getUserByMatricula($matricula);
    }

    public function salvarRespostas($usuario_id, $respostas) {
        $userModel = new UserModel($this->conexao);
        
        foreach ($respostas as $resposta) {
            $questao_id = $resposta['questao_id'];
            $resposta_id = $resposta['resposta_id'];
            $pontuacao_decomposicao = $resposta['pontuacao_decomposicao'];
            $pontuacao_padroes = $resposta['pontuacao_padroes'];
            $pontuacao_abstracao = $resposta['pontuacao_abstracao'];
            $pontuacao_algoritmos = $resposta['pontuacao_algoritmos'];

            $userModel->salvarResposta($usuario_id, $questao_id, $resposta_id, $pontuacao_decomposicao, $pontuacao_padroes, $pontuacao_abstracao, $pontuacao_algoritmos);
        }

        $_SESSION['mensagem'] = 'Respostas salvas com sucesso!';
    }
}
?>
