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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $pib = mysqli_real_escape_string($link, $_POST['pib']);
    $telefon = mysqli_real_escape_string($link, $_POST['telefon']);
    $email = mysqli_real_escape_string($link, $_POST['email']);

    $sql_insert = "INSERT INTO VLASNYK (pib, telefon, email) 
                   VALUES ('$pib', '$telefon', '$email')";

    if (mysqli_query($link, $sql_insert)) {
        echo "<script>alert('Власника успішно додано!'); window.location.href='../lists/owner_list.php';</script>";
    } else {
        echo "Помилка додавання: " . mysqli_error($link);
    }
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Додати Власника</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Додавання Нового Власника</h1>

    <form action="" method="post">
        <label>ПІБ або Назва організації:</label><br>
        <input type="text" name="pib" required style="width: 300px;"><br><br>

        <label>Контактний телефон:</label><br>
        <input type="text" name="telefon" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email"><br><br>

        <button type="submit">Зберегти Власника</button>
    </form>
    
    <p><a href="../lists/owner_list.php">← Повернутися до списку</a></p>
</body>
</html>