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

$id_vlasnyk = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : null;
$owner = null;

// 1. Завантаження даних
if ($id_vlasnyk) {
    $sql = "SELECT * FROM VLASNYK WHERE id_vlasnyk = '{$id_vlasnyk}'";
    $result = mysqli_query($link, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $owner = mysqli_fetch_assoc($result);
    } else {
        die("Власника не знайдено.");
    }
} else {
    die("ID не вказано.");
}

// 2. Оновлення даних
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $pib = mysqli_real_escape_string($link, $_POST['pib']);
    $telefon = mysqli_real_escape_string($link, $_POST['telefon']);
    $email = mysqli_real_escape_string($link, $_POST['email']);
    
    $sql_update = "UPDATE VLASNYK SET 
            pib = '$pib', 
            telefon = '$telefon',
            email = '$email'
            WHERE id_vlasnyk = '{$id_vlasnyk}'";

    if (mysqli_query($link, $sql_update)) {
        echo "<script>alert('Дані власника оновлено!'); window.location.href='../lists/owner_list.php';</script>";
    } else {
        echo "Помилка оновлення: " . mysqli_error($link);
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагування Власника</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Редагування Власника: <?php echo htmlspecialchars($owner['pib']); ?></h1>

    <form action="" method="post">
        <label>ПІБ або Назва організації:</label><br>
        <input type="text" name="pib" value="<?php echo htmlspecialchars($owner['pib']); ?>" required style="width: 300px;"><br><br>

        <label>Контактний телефон:</label><br>
        <input type="text" name="telefon" value="<?php echo htmlspecialchars($owner['telefon']); ?>" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($owner['email']); ?>"><br><br>

        <button type="submit">Оновити Дані</button>
    </form>
    
    <p><a href="../lists/owner_list.php">← Повернутися до списку</a></p>
</body>
</html>