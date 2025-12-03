<?php

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}
// Якщо не адмін - зупинити виконання
if ($_SESSION['role'] !== 'admin') {
    die("Доступ заборонено! Тільки адміністратор може виконувати цю дію. <a href='../index.php'>На головну</a>");
}
require_once '../db_config.php';

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення.");
}

$id_rieltor = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : null;
$record = null;

// 1. Завантаження даних
if ($id_rieltor) {
    $sql = "SELECT * FROM RIELTOR WHERE id_rieltor = '{$id_rieltor}'";
    $result = mysqli_query($link, $sql);
    if (mysqli_num_rows($result) == 1) {
        $record = mysqli_fetch_assoc($result);
    } else {
        die("Рієлтор не знайдений.");
    }
} else {
    die("ID не вказано.");
}

// 2. Оновлення даних (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pib = mysqli_real_escape_string($link, $_POST['pib']);
    $posada = mysqli_real_escape_string($link, $_POST['posada']);
    $data_prijnjattya = mysqli_real_escape_string($link, $_POST['data_prijnjattya']);
    $vidcotok = mysqli_real_escape_string($link, $_POST['vidcotok_komisii']);
    $telefon = mysqli_real_escape_string($link, $_POST['telefon']);
    $email = mysqli_real_escape_string($link, $_POST['email']);

    $sql_update = "UPDATE RIELTOR SET 
            pib = '$pib', 
            posada = '$posada', 
            data_prijnjattya = '$data_prijnjattya',
            vidcotok_komisii = '$vidcotok',
            telefon = '$telefon',
            email = '$email'
            WHERE id_rieltor = '{$id_rieltor}'";

    if (mysqli_query($link, $sql_update)) {
        echo "<script>alert('Дані рієлтора оновлено!'); window.location.href='../lists/realtor_list.php';</script>";
    } else {
        echo "Помилка оновлення: " . mysqli_error($link);
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагування Рієлтора</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Редагування Рієлтора: <?php echo htmlspecialchars($record['pib']); ?></h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_rieltor; ?>" method="post">
        
        <label for="pib">ПІБ:</label><br>
        <input type="text" id="pib" name="pib" value="<?php echo htmlspecialchars($record['pib']); ?>" required style="width: 300px;"><br><br>

        <label for="posada">Посада:</label><br>
        <select id="posada" name="posada" required style="width: 300px;">
            <?php
            $positions = ['Рієлтор', 'Менеджер', 'Адміністратор'];
            foreach ($positions as $pos) {
                $selected = ($record['posada'] == $pos) ? 'selected' : '';
                echo "<option value='{$pos}' {$selected}>{$pos}</option>";
            }
            ?>
        </select><br><br>

        <label for="data_prijnjattya">Дата прийняття:</label><br>
        <input type="date" id="data_prijnjattya" name="data_prijnjattya" value="<?php echo $record['data_prijnjattya']; ?>" required><br><br>

        <label for="vidcotok_komisii">Відсоток комісії (%):</label><br>
        <input type="number" id="vidcotok_komisii" name="vidcotok_komisii" step="0.01" value="<?php echo $record['vidcotok_komisii']; ?>" required><br><br>

        <label for="telefon">Телефон:</label><br>
        <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($record['telefon']); ?>" required><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($record['email']); ?>"><br><br>

        <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; border: none;">Оновити</button>
    </form>
    
    <p><a href="../lists/realtor_list.php">← Повернутися до списку</a></p>
</body>
</html>