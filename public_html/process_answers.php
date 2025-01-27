<?php
session_start();
require_once __DIR__.'/../config/conexao.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: inativeQuest.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

$opcoes = [
    1 => 'a',
    2 => 'b',
    3 => 'c',
    4 => 'd',
];

$pontos_pilares = [
    'Decomposicao' => 0,
    'Reconhecimento de Padroes' => 0,
    'Abstracao' => 0,
    'Algoritmos' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexao->begin_transaction();

    try {
        foreach ($_POST['respostas'] as $questao_id => $resposta_id) {
            if (is_numeric($questao_id) && isset($opcoes[$resposta_id])) {
                $resposta_letra = $opcoes[$resposta_id];

                $sql = "SELECT id FROM respostas WHERE questao_id = ? AND opcao = ?";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param('is', $questao_id, $resposta_letra);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $resposta_id_correto = $row['id'];

                    $sql = "INSERT INTO resultados (usuario_id, questao_id, resposta_id)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE resposta_id = VALUES(resposta_id)";
                    $stmt = $conexao->prepare($sql);
                    $stmt->bind_param('iii', $usuario_id, $questao_id, $resposta_id_correto);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    throw new Exception("Resposta não encontrada para a questão $questao_id.");
                }

                $sql = "SELECT correct_option FROM perguntas WHERE id = ?";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param('i', $questao_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $correct_option = $row['correct_option'];

                    if ($resposta_letra === $correct_option) {
                        $sql = "SELECT p.nome, qp.pontuacao
                                FROM questao_pilar qp
                                JOIN pilares p ON qp.pilar_id = p.id
                                WHERE qp.questao_id = ?";
                        $stmt = $conexao->prepare($sql);
                        $stmt->bind_param('i', $questao_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $stmt->close();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $pilar_nome = $row['nome'];
                                if (array_key_exists($pilar_nome, $pontos_pilares)) {
                                    $pontos_pilares[$pilar_nome] += $row['pontuacao'];
                                }
                            }
                        }
                    }
                }
            }
        }

        $sqlTentativa = "INSERT INTO tentativas (usuario_id, decomposicao, padroes, abstracao, algoritmos) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmtTentativa = $conexao->prepare($sqlTentativa);
        $stmtTentativa->bind_param('iiiii', 
            $usuario_id, 
            $pontos_pilares['Decomposicao'], 
            $pontos_pilares['Reconhecimento de Padroes'], 
            $pontos_pilares['Abstracao'], 
            $pontos_pilares['Algoritmos']);
        $stmtTentativa->execute();
        $stmtTentativa->close();

        foreach ($pontos_pilares as $pilar_nome => $pontos) {
            if ($pontos > 0) {
                $sql = "INSERT INTO pontos_pilares (usuario_id, pilar_nome, pontos, data_hora)
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                        ON DUPLICATE KEY UPDATE pontos = pontos + VALUES(pontos)";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param('isi', $usuario_id, $pilar_nome, $pontos);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conexao->commit();

        header("Location: completQuest.php");
        exit();
    } catch (Exception $e) {
        $conexao->rollback();
        echo "Erro ao processar as respostas: " . $e->getMessage();
    }
}
?>
