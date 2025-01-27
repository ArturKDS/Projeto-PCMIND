<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: loginView.php');
    exit();
}

require_once __DIR__.'/../config/conexao.php';
if ($conexao->connect_error) {
    die("Erro ao conectar ao banco de dados: " . $conexao->connect_error);
}

$sql = "SELECT role_id FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if ($row['role_id'] == 1) {
        header('Location: unauthorized.php'); 
        exit();
    }
} else {
    header('Location: loginView.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    foreach ($_POST['perguntas'] as $id => $data) {
        
        if ($id == 0) { 
            $_SESSION['mensagem'] = "A pergunta exemplo não pode ser editada.";
            header('Location: editQuest.php');
            exit();
        }

        $title = $data['title'];
        $description = $data['description'];
        $correct_option = strtolower($data['correct_option']);  

        $sql_current_image = "SELECT image FROM perguntas WHERE id = ?";
        $stmt_current_image = $conexao->prepare($sql_current_image);
        $stmt_current_image->bind_param('i', $id);
        $stmt_current_image->execute();
        $result_current_image = $stmt_current_image->get_result();
        $currentImage = $result_current_image->fetch_assoc()['image'];

        if (isset($_FILES['perguntas']['name'][$id]['image']) && $_FILES['perguntas']['error'][$id]['image'] === UPLOAD_ERR_OK) {
            $imageTmpPath = $_FILES['perguntas']['tmp_name'][$id]['image'];
            $imageName = basename($_FILES['perguntas']['name'][$id]['image']);
            $imagePath = 'uploads/' . $imageName;
            move_uploaded_file($imageTmpPath, $imagePath);
            $imagePath = $imageName;
        } else {
            $imagePath = $currentImage;
        }

        $sql = "UPDATE perguntas SET title = ?, description = ?, correct_option = ?, image = ? WHERE id = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param('ssssi', $title, $description, $correct_option, $imagePath, $id);        

        if (!$stmt->execute()) {
            die("Erro ao atualizar pergunta: " . $stmt->error);
        }

        $sql_delete_pilares = "DELETE FROM questao_pilar WHERE questao_id = ?";
        $stmt_delete_pilares = $conexao->prepare($sql_delete_pilares);
        $stmt_delete_pilares->bind_param('i', $id);
        $stmt_delete_pilares->execute();

        if (isset($data['pilar_ids'])) {
            foreach ($data['pilar_ids'] as $pilar_id) {
                $sql_insert_pilar = "INSERT INTO questao_pilar (questao_id, pilar_id) VALUES (?, ?)";
                $stmt_insert_pilar = $conexao->prepare($sql_insert_pilar);
                $stmt_insert_pilar->bind_param('ii', $id, $pilar_id);
                $stmt_insert_pilar->execute();
            }
        }

        $sql_update_resposta = "UPDATE respostas SET correta = CASE WHEN opcao = ? THEN 1 ELSE 0 END WHERE questao_id = ?";
        $stmt_update_resposta = $conexao->prepare($sql_update_resposta);
        $stmt_update_resposta->bind_param('si', $correct_option, $id);
        $stmt_update_resposta->execute();
    }

    $_SESSION['mensagem'] = "Perguntas atualizadas com sucesso!";
    header('Location: editQuest.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'] ?? null;
    $description = $_POST['description'] ?? null;
    $correct_option = strtolower($_POST['correct_option'] ?? null);

    if (!$title) {
        die("O título é obrigatório.");
    }

    $imagePath = null;
    if (isset($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageTmpPath = $_FILES['image']['tmp_name'];
        $imageName = basename($_FILES['image']['name']);
        $imagePath = 'uploads/' . $imageName;
        move_uploaded_file($imageTmpPath, $imagePath);
        $imagePath = $imageName;
    }

    $option_a = "A";
    $option_b = "B";
    $option_c = "C";
    $option_d = "D";

    $sql_max_id = "SELECT MAX(id) AS max_id FROM perguntas";
    $result_max_id = $conexao->query($sql_max_id);

    if (!$result_max_id) {
        die("Erro ao buscar o maior ID: " . $conexao->error);
    }

    $row_max_id = $result_max_id->fetch_assoc();
    $next_id = ($row_max_id['max_id'] ?? 0) + 1;

    $sql_insert = "INSERT INTO perguntas (id, title, description, option_a, option_b, option_c, option_d, correct_option, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexao->prepare($sql_insert);
    $stmt->bind_param('issssssss', $next_id, $title, $description, $option_a, $option_b, $option_c, $option_d, $correct_option, $imagePath);

    if (!$stmt->execute()) {
        die("Erro ao adicionar pergunta: " . $stmt->error);
    }

    if (isset($_POST['pilar_ids'])) {
        foreach ($_POST['pilar_ids'] as $pilar_id) {
            $sql_insert_pilar = "INSERT INTO questao_pilar (questao_id, pilar_id) VALUES (?, ?)";
            $stmt_insert_pilar = $conexao->prepare($sql_insert_pilar);
            $stmt_insert_pilar->bind_param('ii', $next_id, $pilar_id);
            $stmt_insert_pilar->execute();
        }
    }

    $options = ['a', 'b', 'c', 'd'];
    foreach ($options as $option) {
        $correta = ($option == $correct_option) ? 1 : 0;
        $sql_insert_resposta = "INSERT INTO respostas (questao_id, opcao, correta) VALUES (?, ?, ?)";
        $stmt_insert_resposta = $conexao->prepare($sql_insert_resposta);
        $stmt_insert_resposta->bind_param('isi', $next_id, $option, $correta);
        $stmt_insert_resposta->execute();
    }

    $_SESSION['mensagem'] = "Pergunta adicionada com sucesso!";
    header('Location: editQuest.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)$_POST['id'];


    if ($id == 0) { 
        $_SESSION['mensagem'] = "A pergunta exemplo não pode ser excluída.";
        header('Location: editQuest.php');
        exit();
    }

    $conexao->begin_transaction();

    try {
        $sql_delete_resultados = "DELETE FROM resultados WHERE questao_id = ?";
        $stmt_delete_resultados = $conexao->prepare($sql_delete_resultados);
        $stmt_delete_resultados->bind_param('i', $id);
        $stmt_delete_resultados->execute();

        $sql_delete_respostas = "DELETE FROM respostas WHERE questao_id = ?";
        $stmt_delete_respostas = $conexao->prepare($sql_delete_respostas);
        $stmt_delete_respostas->bind_param('i', $id);
        $stmt_delete_respostas->execute();

        $sql_delete_pilares = "DELETE FROM questao_pilar WHERE questao_id = ?";
        $stmt_delete_pilares = $conexao->prepare($sql_delete_pilares);
        $stmt_delete_pilares->bind_param('i', $id);
        $stmt_delete_pilares->execute();

        $sql_delete = "DELETE FROM perguntas WHERE id = ?";
        $stmt_delete = $conexao->prepare($sql_delete);
        $stmt_delete->bind_param('i', $id);
        $stmt_delete->execute();

        $conexao->commit();
        $_SESSION['mensagem'] = "Pergunta excluída com sucesso!";
    } catch (Exception $e) {
        $conexao->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir a pergunta: " . $e->getMessage();
    }

    header('Location: editQuest.php');
    exit();
}


$sql = "SELECT * FROM perguntas";
$result = $conexao->query($sql);
if (!$result) {
    die("Erro ao buscar perguntas: " . $conexao->error);
}


$sql_pilares = "SELECT * FROM pilares";
$result_pilares = $conexao->query($sql_pilares);
if (!$result_pilares) {
    die("Erro ao buscar pilares: " . $conexao->error);
}

$pilares = [];
while ($row_pilar = $result_pilares->fetch_assoc()) {
    $pilares[] = $row_pilar;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Questões</title>
    <link rel="stylesheet" href="css/quest.css">
    <link rel="icon" href="img/pcmind.png" type="image/png">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">

    <script>
        function openDeleteModal(id) {
            document.getElementById('delete-question-id').value = id;
            document.getElementById('deleteQuestionModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteQuestionModal').style.display = 'none';
        }

        function openModal() {
            document.getElementById('addQuestionModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addQuestionModal').style.display = 'none';
        }
    </script>
    </head>
    
    <body>
    <div class="content">
        <h1>Editar Questões</h1>
        <form method="post" action="editQuest.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <?php while ($row = $result->fetch_assoc()): ?>
         <div class="question">
        <?php if ($row['is_example'] == 1): ?>
        <h2>Pergunta Exemplo</h2>
        <?php else: ?>
        <h2>Questão</h2>
        <?php endif; ?>

        <label for="title-<?php echo $row['id']; ?>">Título:</label>
        <input type="text" id="title-<?php echo $row['id']; ?>" name="perguntas[<?php echo $row['id']; ?>][title]" value="<?php echo htmlspecialchars($row['title']); ?>" required>

        <label for="description-<?php echo $row['id']; ?>">Descrição:</label>
        <textarea id="description-<?php echo $row['id']; ?>" name="perguntas[<?php echo $row['id']; ?>][description]" required><?php echo htmlspecialchars($row['description']); ?></textarea>

        <label for="image-<?php echo $row['id']; ?>">Imagem:</label>
        <input type="file" id="image-<?php echo $row['id']; ?>" name="perguntas[<?php echo $row['id']; ?>][image]" accept="image/*">
        <?php if (!empty($row['image'])): ?>
        <img src="<?php echo 'uploads/' . htmlspecialchars($row['image']); ?>" alt="Imagem da Pergunta" class="question-image">
        <?php endif; ?>

        <?php if ($row['is_example'] != 1): ?>
            <br>
            <label>Pilares:</label>
            <div class="pilar-container">
            <?php foreach ($pilares as $pilar): ?>
                <div class="pilar-item">
                <input type="checkbox" id="pilar-<?php echo $pilar['id']; ?>-<?php echo $row['id']; ?>" name="perguntas[<?php echo $row['id']; ?>][pilar_ids][]" value="<?php echo $pilar['id']; ?>" 
                <?php
                     $sql_questao_pilar = "SELECT 1 FROM questao_pilar WHERE questao_id = ? AND pilar_id = ?";
                    $stmt_questao_pilar = $conexao->prepare($sql_questao_pilar);
                    $stmt_questao_pilar->bind_param('ii', $row['id'], $pilar['id']);
                    $stmt_questao_pilar->execute();
                    $stmt_questao_pilar->store_result();
                        if ($stmt_questao_pilar->num_rows > 0) {
                            echo 'checked';
                        }
                        ?>>

                    <label for="pilar-<?php echo $pilar['id']; ?>-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($pilar['nome']); ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
             <br>           
            <label>Resposta Correta:</label>
            <div class="correct-option">
                <?php 
                $options = ['A', 'B', 'C', 'D'];
                foreach ($options as $option): ?>
                    <div class="option-item">
                        <input type="radio" id="correct-option-<?php echo $option; ?>-<?php echo $row['id']; ?>" name="perguntas[<?php echo $row['id']; ?>][correct_option]" value="<?php echo strtolower($option); ?>" 
                        <?php if ($row['correct_option'] == strtolower($option)) echo 'checked'; ?>>
                        <label for="correct-option-<?php echo $option; ?>-<?php echo $row['id']; ?>">Opção <?php echo $option; ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <br>
            <button type="button" onclick="openDeleteModal(<?php echo $row['id']; ?>)">Remover Pergunta</button>
        <?php endif; ?>
                <hr>
            </div>
        <?php endwhile; ?>

            <div class="buttons-container">
                <button type="submit">Salvar Alterações</button>
                <button type="button" onclick="openModal()">Adicionar Pergunta</button>
                <a href="index.php"><button type="button" class="back">Voltar</button></a>
            </div>
        </form>
    </div>

    <div id="addQuestionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Adicionar Nova Pergunta</h2>
            <form method="post" action="editQuest.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <label for="new-title">Título:</label>
                <input type="text" id="new-title" name="title" required>

                <label for="new-description">Descrição:</label>
                <textarea id="new-description" name="description" required></textarea>

                <label>Pilares:</label>
                <div class="pilar-container">
                    <?php foreach ($pilares as $pilar): ?>
                        <div class="pilar-item">
                            <input type="checkbox" id="new-pilar-<?php echo $pilar['id']; ?>" name="pilar_ids[]" value="<?php echo $pilar['id']; ?>">
                            <label for="new-pilar-<?php echo $pilar['id']; ?>"><?php echo htmlspecialchars($pilar['nome']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <label>Resposta Correta:</label>
                <div class="correct-option">
                    <?php 
                    $options = ['A', 'B', 'C', 'D'];
                    foreach ($options as $option): ?>
                        <div class="option-item">
                            <input type="radio" id="new-correct-option-<?php echo $option; ?>" name="correct_option" value="<?php echo strtolower($option); ?>" required>
                            <label for="new-correct-option-<?php echo $option; ?>">Opção <?php echo $option; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <label for="new-image"></label>
                <input type="file" id="new-image" name="image" accept="image/*">

                <div class="buttons-container">
                    <button type="submit">Adicionar Pergunta</button>
                    <button type="button" class="back" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    <div id="deleteQuestionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirmar Exclusão</h2>
            <p>Tem certeza de que deseja excluir esta pergunta?</p>
            <form id="delete-form" method="post" action="editQuest.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete-question-id">
                <div class="buttons-container">                  
                    <button type="submit">Excluir</button>
                    <button type="button" class="back" onclick="closeDeleteModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
