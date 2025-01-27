<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['nome'])) {
    header('Location: loginView.php');
    exit();
}

require_once __DIR__.'/../config/conexao.php'; 

$nome_matricula = isset($_GET['nome_matricula']) ? $_GET['nome_matricula'] : '';
$turma = isset($_GET['turma']) ? $_GET['turma'] : 'todos';
$sexo = isset($_GET['sexo']) ? $_GET['sexo'] : 'todos';
$ano_nascimento = isset($_GET['ano_nascimento']) ? $_GET['ano_nascimento'] : '';
$data_realizacao_inicio = isset($_GET['data_realizacao_inicio']) ? $_GET['data_realizacao_inicio'] : '';
$data_realizacao_fim = isset($_GET['data_realizacao_fim']) ? $_GET['data_realizacao_fim'] : '';

$sql = "
    SELECT 
        u.nome,
        u.matricula,
        u.sexo,
        u.turma,
        u.dataNASCIMENTO,
        u.profile_image_url,
        t.data_hora,
        t.decomposicao AS pontuacao_decomposicao,
        t.padroes AS pontuacao_padroes,
        t.abstracao AS pontuacao_abstracao,
        t.algoritmos AS pontuacao_algoritmos,

        -- Pontuação total possível para cada pilar registrada na tentativa
        (SELECT COALESCE(SUM(qp.pontuacao), 0) FROM questao_pilar qp 
         JOIN pilares pl ON qp.pilar_id = pl.id WHERE pl.nome = 'Decomposicao') AS total_decomposicao,
        (SELECT COALESCE(SUM(qp.pontuacao), 0) FROM questao_pilar qp 
         JOIN pilares pl ON qp.pilar_id = pl.id WHERE pl.nome = 'Reconhecimento de Padroes') AS total_padroes,
        (SELECT COALESCE(SUM(qp.pontuacao), 0) FROM questao_pilar qp 
         JOIN pilares pl ON qp.pilar_id = pl.id WHERE pl.nome = 'Abstracao') AS total_abstracao,
        (SELECT COALESCE(SUM(qp.pontuacao), 0) FROM questao_pilar qp 
         JOIN pilares pl ON qp.pilar_id = pl.id WHERE pl.nome = 'Algoritmos') AS total_algoritmos
    FROM 
        usuarios u
    JOIN 
        tentativas t ON u.id = t.usuario_id
    WHERE 1=1
";

$params = [];
if (!empty($nome_matricula)) {
    $sql .= " AND (u.nome LIKE ? OR u.matricula LIKE ?)";
    $params[] = "%$nome_matricula%";
    $params[] = "%$nome_matricula%";
}
if ($turma != 'todos') {
    $sql .= " AND u.turma = ?";
    $params[] = $turma;
}
if ($sexo != 'todos') {
    $sql .= " AND u.sexo = ?";
    $params[] = $sexo;
}
if (!empty($ano_nascimento)) {
    $sql .= " AND YEAR(u.dataNASCIMENTO) = ?";
    $params[] = $ano_nascimento;
}
if (!empty($data_realizacao_inicio) && !empty($data_realizacao_fim)) {
    $sql .= " AND t.data_hora BETWEEN ? AND ?";
    $params[] = "$data_realizacao_inicio 00:00:00";
    $params[] = "$data_realizacao_fim 23:59:59";
}

$sql .= " GROUP BY u.id, t.data_hora";

$stmt = $conexao->prepare($sql);

if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conexao->error);
}

if (!empty($params)) {
    $types = str_repeat('s', count($params)); 
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$result = $stmt->get_result();

$soma_decomposicao = 0;
$soma_padroes = 0;
$soma_abstracao = 0;
$soma_algoritmos = 0;
$total_alunos = 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados</title>
    <link rel="stylesheet" href="css/result.css">
    <link rel="icon" href="img/pcmind.png" type="image/png">
    <link rel="shortcut icon" href="img/pcmind.png" type="image/png">
</head>
<body>
    <h1>Resultados dos Alunos</h1>
    
    <form id="search-form" method="GET">
        <div class="search-bar">
            <button type="submit">
                <img src="img/search.png" alt="Pesquisar">
            </button>
            <input type="text" id="nome-matricula" name="nome_matricula" placeholder="Nome ou Matrícula">
        </div>
        
        <div class="filters">
            <div class="filter-group">
                <label for="turma">Turma</label>
                <select id="turma" name="turma">
                    <option value="todos">Todos</option>
                    <option value="DS1">DS1</option>
                    <option value="ELE1">ELE1</option>
                    <option value="ADM1">ADM1</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sexo">Sexo</label>
                <select id="sexo" name="sexo">
                    <option value="todos">Todos</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Feminino">Feminino</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="ano-nascimento">Ano de Nascimento</label>
                <input type="text" id="ano-nascimento" name="ano_nascimento" placeholder="yyyy">
            </div>

            <div class="filter-group">
                <label for="data-realizacao">Período</label>
                <div id="data-realizacao">
                    <input type="date" id="data_realizacao_inicio" name="data_realizacao_inicio">
                    <span>-</span>
                    <input type="date" id="data_realizacao_fim" name="data_realizacao_fim">
                </div>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Sexo</th>
                <th>Turma</th>
                <th>Nascimento</th>
                <th>Data da Realização</th>
                <th>Pontuação Decomposição</th>
                <th>Pontuação Padrões</th>
                <th>Pontuação Abstração</th>
                <th>Pontuação Algoritmos</th>
            </tr>
        </thead>
        <tbody>
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dataNascimento = date('d/m/Y', strtotime($row['dataNASCIMENTO']));
            $data = date('d/m/Y', strtotime($row['data_hora']));
            $hora = date('H:i:s', strtotime($row['data_hora']));
           
            $percent_decomposicao = $row['total_decomposicao'] > 0 ? ($row['pontuacao_decomposicao'] / $row['total_decomposicao']) * 100 : 0;
            $percent_padroes = $row['total_padroes'] > 0 ? ($row['pontuacao_padroes'] / $row['total_padroes']) * 100 : 0;
            $percent_abstracao = $row['total_abstracao'] > 0 ? ($row['pontuacao_abstracao'] / $row['total_abstracao']) * 100 : 0;
            $percent_algoritmos = $row['total_algoritmos'] > 0 ? ($row['pontuacao_algoritmos'] / $row['total_algoritmos']) * 100 : 0;

            $percent_decomposicao = min($percent_decomposicao, 100);
            $percent_padroes = min($percent_padroes, 100);
            $percent_abstracao = min($percent_abstracao, 100);
            $percent_algoritmos = min($percent_algoritmos, 100);

            $soma_decomposicao += $percent_decomposicao;
            $soma_padroes += $percent_padroes;
            $soma_abstracao += $percent_abstracao;
            $soma_algoritmos += $percent_algoritmos;
             $total_alunos++;

            echo "<tr>";
            echo "<td class='nome-usuario'><img src='" . htmlspecialchars($row['profile_image_url']) . "' alt='Imagem do usuário' class='user-image' />" . htmlspecialchars($row['nome']) . "<span class='matricula'> (" . htmlspecialchars($row['matricula']) . ")</span></td>";
            echo "<td>" . htmlspecialchars($row['sexo']) . "</td>";
            echo "<td>" . htmlspecialchars($row['turma']) . "</td>";
            echo "<td>" . htmlspecialchars($dataNascimento) . "</td>";
            echo "<td class='data-hora'><span class='data'>" . htmlspecialchars($data) . "</span><span class='hora'>" . htmlspecialchars($hora) . "</span></td>";
            echo "<td class='pontuacao'>" . round($percent_decomposicao, 2) . "%</td>";
            echo "<td class='pontuacao'>" . round($percent_padroes, 2) . "%</td>";
            echo "<td class='pontuacao'>" . round($percent_abstracao, 2) . "%</td>";
            echo "<td class='pontuacao'>" . round($percent_algoritmos, 2) . "%</td>";            
            echo "</tr>";
        }
        $media_decomposicao = $total_alunos > 0 ? $soma_decomposicao / $total_alunos : 0;
        $media_padroes = $total_alunos > 0 ? $soma_padroes / $total_alunos : 0;
        $media_abstracao = $total_alunos > 0 ? $soma_abstracao / $total_alunos : 0;
        $media_algoritmos = $total_alunos > 0 ? $soma_algoritmos / $total_alunos : 0;

        echo "<tr class='media-pilares'>";
        echo "<td colspan='5' style='font-weight: bold; text-align: right;'>Média Geral:</td>";
        echo "<td class='pontuacao'>" . round($media_decomposicao, 2) . "%</td>";
        echo "<td class='pontuacao'>" . round($media_padroes, 2) . "%</td>";
        echo "<td class='pontuacao'>" . round($media_abstracao, 2) . "%</td>";
        echo "<td class='pontuacao'>" . round($media_algoritmos, 2) . "%</td>";
        echo "</tr>";

    } else {
        echo "<tr><td colspan='9'>Nenhum resultado encontrado.</td></tr>";
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
