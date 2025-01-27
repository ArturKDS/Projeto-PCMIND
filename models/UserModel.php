<?php
class UserModel {
    private $conexao;

    public function __construct($conexao) {
        $this->conexao = $conexao;
    }

    public function salvarResposta($usuario_id, $questao_id, $resposta_id, $pontuacao_decomposicao, $pontuacao_padroes, $pontuacao_abstracao, $pontuacao_algoritmos) {
        $sql = "INSERT INTO resultados (usuario_id, questao_id, resposta_id, pontuacao_decomposicao, pontuacao_padroes, pontuacao_abstracao, pontuacao_algoritmos) VALUES (?, ?, ?, ?, ?, ?, ?)"; 
        $stmt = $this->conexao->prepare($sql);
        
        if (!$stmt) {
            die("Erro na preparação da consulta: " . $this->conexao->error);
        }

        $stmt->bind_param('iiiiiii', $usuario_id, $questao_id, $resposta_id, $pontuacao_decomposicao, $pontuacao_padroes, $pontuacao_abstracao, $pontuacao_algoritmos);
    
        if (!$stmt->execute()) {
            die("Erro ao salvar resposta: " . $stmt->error);
        }
    
        $stmt->close();
    }    

    public function getUserByEmail($email) {
        $sql = "SELECT id, nome, senha, role_id FROM usuarios WHERE email = ?"; 
        $stmt = $this->conexao->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $this->conexao->error);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $result = $stmt->get_result();
        
        if ($result === false) {
            throw new Exception("Erro na execução da consulta: " . $stmt->error);
        }

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $stmt->close();
            return $user; 
        }

        $stmt->close();
        return null;
    }

    public function getUserByMatricula($matricula) {
        $sql = "SELECT * FROM usuarios WHERE matricula = ?"; 
        $stmt = $this->conexao->prepare($sql);

        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $this->conexao->error);
        }

        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("Erro na execução da consulta: " . $stmt->error);
        }

        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ? $user : null; 
    }
}
?>
