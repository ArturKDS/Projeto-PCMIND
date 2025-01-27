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

$role_id = null;

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $role_id = $row['role_id'];
}
$stmt->close();

if ($role_id != 2) {
    echo "Acesso negado.";
    exit();
}

$nome_matricula = isset($_GET['nome_matricula']) ? $_GET['nome_matricula'] : '';

$sql = "SELECT id, nome, email, matricula, data_cadastro, role_id, sexo, turma, dataNASCIMENTO, profile_image_url 
        FROM usuarios 
        WHERE nome LIKE ? OR matricula LIKE ?";
$stmt = $conexao->prepare($sql);
$searchTerm = "%$nome_matricula%";
$stmt->bind_param('ss', $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuários</title>
    <link rel="stylesheet" href="css/users.css">
    <link rel="icon" href="img/pcmind.png" type="image/png">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
</head>
<body>
    <h1>Lista de Usuários</h1>

    <form id="search-form" method="GET">
        <div class="search-bar">
            <button type="submit">
                <img src="img/search.png" alt="Pesquisar">
            </button>
            <input type="text" id="nome-matricula" name="nome_matricula" placeholder="Nome ou Matrícula" value="<?= htmlspecialchars($nome_matricula) ?>">
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Matrícula</th>
                <th>Cadastro</th>
                <th>Sexo</th>
                <th>Turma</th>
                <th>Nascimento</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='nome-usuario'><img src='" . htmlspecialchars($row['profile_image_url']) . "' alt='Imagem do usuário' class='user-image' />" . htmlspecialchars($row['nome']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['matricula']) . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($row['data_cadastro'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['sexo']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['turma']) . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($row['dataNASCIMENTO'])) . "</td>";
                    echo "<td>";
                    
                    if ($row['id'] != $_SESSION['user_id']) {
                        if ($row['role_id'] != 2) {
                            echo '<form method="post" action="actions.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="' . $row['id'] . '">
                                    <button type="submit" name="promover" onclick="return confirm(\'Tem certeza que deseja promover este usuário a administrador?\')">Tornar Administrador</button>
                                  </form>';
                        } else {
                            echo '<form method="post" action="removerAdmin.php" style="display:inline;">
                                    <input type="hidden" name="user_id" value="' . $row['id'] . '">
                                    <button type="submit" name="remover" onclick="return confirm(\'Tem certeza que deseja remover o cargo de administrador deste usuário?\')">Remover Administrador</button>
                                  </form>';
                        }

                        echo '<form method="post" action="actions.php" style="display:inline;">
                                <input type="hidden" name="user_id" value="' . $row['id'] . '">
                                <button type="submit" name="excluir" onclick="return confirm(\'Tem certeza que deseja excluir este usuário?\')">Excluir</button>
                              </form>';
                    } else {
                        echo "";
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8'>Nenhum resultado encontrado.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <br>
    <button onclick="window.location.href='index.php'">Voltar</button>
</body>
</html>

<?php
$stmt->close();
$conexao->close();
?>
