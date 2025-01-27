<?php
session_start(); 
require_once __DIR__.'/../config/conexao.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: loginView.php');
    exit();
}

$sql = "SELECT u.nome, u.profile_image_url FROM usuarios u WHERE u.id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$userName = null;
$userImageURL = null;

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $userName = $row['nome'];
    $userImageURL = $row['profile_image_url'];
} else {
    header('Location: loginView.php');
    exit();
}
$stmt->close();

$sql = "SELECT questionario_ativo FROM configuracoes WHERE id = 1";
$result = $conexao->query($sql);
$questionarioAtivo = $result->fetch_assoc()['questionario_ativo'];

if (!$questionarioAtivo) {
    header('Location: inactiveQuest.php');
    exit();
}

$sql = "SELECT * FROM perguntas";
$result = $conexao->query($sql);

if ($result->num_rows > 0):
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste</title>
    <link rel="stylesheet" href="css/testPage.css">
    <link rel="stylesheet" href="css/moodleImage.css">
    <link rel="icon" href="img/pcmind.png" type="image/png">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
    <script>
        function confirmSubmit() {
            return confirm("Você tem certeza de que deseja enviar suas respostas?");
        }

        function saveAnswer(questionId, answerValue) {
            localStorage.setItem(`resposta_${questionId}`, answerValue);
        }

        function restoreAnswers() {
            document.querySelectorAll('input[type="radio"]').forEach(input => {
                const questionId = input.name.replace('respostas[', '').replace(']', '');
                const savedAnswer = localStorage.getItem(`resposta_${questionId}`);
                if (savedAnswer && input.value === savedAnswer) {
                    input.checked = true;
                }
            });
        }

        function clearSavedAnswers() {
            localStorage.clear();
        }

        document.addEventListener("DOMContentLoaded", () => {
            restoreAnswers();
        });
        
        function openLogoutModal() {
            const logoutModal = document.getElementById('logoutModal');
            const modalBackdrop = document.getElementById('modalBackdrop');
            logoutModal.style.display = 'block';
            modalBackdrop.style.display = 'block';
        }

        function closeLogoutModal() {
            const logoutModal = document.getElementById('logoutModal');
            const modalBackdrop = document.getElementById('modalBackdrop');
            logoutModal.style.display = 'none';
            modalBackdrop.style.display = 'none';
        }
    </script>
</head>
<body>
<div class="top-bar">
    <div class="right-buttons">
        <?php if (isset($userName)): ?>
            <div class="user-info-container">
                <p class="user-name"><?php echo htmlspecialchars($userName); ?></p>
                <img src="<?php echo htmlspecialchars($userImageURL); ?>" alt="Foto do usuário" class="user-image" onclick="openLogoutModal()">
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalBackdrop" class="modal-backdrop" onclick="closeLogoutModal()"></div>
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Tem certeza de que deseja sair?</h5>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeLogoutModal()">Cancelar</button>
            <form action="logout.php" method="post">
                <button type="submit" class="btn btn-primary">Sair</button>
            </form>
        </div>
    </div>
</div>

<div class="content">
    <form action="process_answers.php" method="post" onsubmit="return confirmSubmit() && clearSavedAnswers()">
        <?php while($row = $result->fetch_assoc()): ?>
        <div class="question-box">
            <h3><?php echo htmlspecialchars($row['title']); ?><?php echo ($row['is_example'] == 1) ? " (Exemplo)" : ""; ?></h3>
            <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>

            <?php if (!empty($row['image'])): ?>
                <img src="<?php echo 'uploads/' . htmlspecialchars($row['image']); ?>" alt="Imagem da Pergunta" class="question-image">
            <?php endif; ?>

            <div class="answers">
                <?php if ($row['is_example'] == 1): ?>
                    <label><input type="radio" value="1" onclick="saveAnswer(<?php echo $row['id']; ?>, '1')"><?php echo htmlspecialchars($row['option_a']); ?></label>
                    <label><input type="radio" value="2" onclick="saveAnswer(<?php echo $row['id']; ?>, '2')"><?php echo htmlspecialchars($row['option_b']); ?></label>
                    <label><input type="radio" value="3" onclick="saveAnswer(<?php echo $row['id']; ?>, '3')"><?php echo htmlspecialchars($row['option_c']); ?></label>
                    <label><input type="radio" value="4" onclick="saveAnswer(<?php echo $row['id']; ?>, '4')"><?php echo htmlspecialchars($row['option_d']); ?></label>
                <?php else: ?>
                    <label><input type="radio" name="respostas[<?php echo $row['id']; ?>]" value="1" onclick="saveAnswer(<?php echo $row['id']; ?>, '1')" required><?php echo htmlspecialchars($row['option_a']); ?></label>
                    <label><input type="radio" name="respostas[<?php echo $row['id']; ?>]" value="2" onclick="saveAnswer(<?php echo $row['id']; ?>, '2')"><?php echo htmlspecialchars($row['option_b']); ?></label>
                    <label><input type="radio" name="respostas[<?php echo $row['id']; ?>]" value="3" onclick="saveAnswer(<?php echo $row['id']; ?>, '3')"><?php echo htmlspecialchars($row['option_c']); ?></label>
                    <label><input type="radio" name="respostas[<?php echo $row['id']; ?>]" value="4" onclick="saveAnswer(<?php echo $row['id']; ?>, '4')"><?php echo htmlspecialchars($row['option_d']); ?></label>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>

        <div class="button-container">
            <button type="submit" class="button button-submit">Enviar Respostas</button>
            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2): ?>
                <button type="button" onclick="window.location.href='index.php'" class="button button-back">Voltar</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
else:
    echo "Um erro inesperado aconteceu, tente novamente mais tarde.";
endif;

$conexao->close();
?>
