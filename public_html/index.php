<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: loginView.php');
    exit();
}

require_once __DIR__.'/../config/conexao.php'; 


$sql = "SELECT u.role_id, r.role_name, u.profile_image_url 
        FROM usuarios u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$role_id = null;
$role_name = null;
$userImageURL = null;

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $role_id = $row['role_id'];
    $role_name = $row['role_name'];
    $userImageURL = $row['profile_image_url'];
} else {
    header('Location: loginView.php');
    exit();
}
$stmt->close();


if ($role_id != 2) {
    header('Location: testPage.php');
    exit();
}


$sql = "SELECT questionario_ativo FROM configuracoes WHERE id = 1";
$result = $conexao->query($sql);
$questionarioAtivo = $result->fetch_assoc()['questionario_ativo'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoStatus = isset($_POST['questionario_ativo']) ? 1 : 0;
    $sql = "UPDATE configuracoes SET questionario_ativo = ? WHERE id = 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('i', $novoStatus);
    $stmt->execute();
    $stmt->close();
    $questionarioAtivo = $novoStatus;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Inicial</title>
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
</head>
<body>
    <div class="top-bar">
        <div class="logo">
            <a href="index.php">
                <img src="img/pcmind.png" alt="PCMIND">
            </a>
        </div>
        <div class="right-buttons">
            <?php if (isset($_SESSION['nome'])): ?>
                <div class="dropdown user-info">
                    <p class="username"><?= htmlspecialchars($_SESSION['nome']); ?></p>
                    <img src="<?= htmlspecialchars($_SESSION['user_image']); ?>" alt="User Icon" class="user-icon dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="usersView.php">Ver Usuários</a></li>
                        <li><a class="dropdown-item" href="editQuest.php">Editar Questionário</a></li>
                        <li><a class="dropdown-item" href="resultados.php">Ver Resultados</a></li>
                        <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="content">
        <div class="test-box" onclick="window.location.href='testPage.php'">
            <img src="img/teste.png" alt="Teste Icon" class="test-icon">
            <p>Questionário</p>
        </div>

        <?php if ($role_id == 2): ?>
            <div class="toggle-container mt-4"> 
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="questionarioAtivo" name="questionario_ativo" onchange="updateQuestionarioStatus(this.checked)" <?= $questionarioAtivo ? 'checked' : ''; ?>>
                    <span class="form-check-label" id="toggleLabel" for="questionarioAtivo">
                        <?= $questionarioAtivo ? 'Questionário Ativo' : 'Questionário Não Ativo'; ?>
                    </span>
                </label>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuestionarioStatus(isActive) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "update_questionario_status.php"); 
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("questionario_ativo=" + (isActive ? 1 : 0));
            
          
            const toggleLabel = document.getElementById("toggleLabel");
            toggleLabel.textContent = isActive ? "Questionário Ativo" : "Questionário Não Ativo";
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
