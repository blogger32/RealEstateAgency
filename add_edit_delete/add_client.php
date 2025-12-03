<?php
// add_client.php - Додавання нового клієнта
session_start();

// Перевірка прав доступу
if (!isset($_SESSION["loggedin"]) || $_SESSION['role'] !== 'admin') {
    die("Доступ заборонено!");
}

require_once '../db_config.php';

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Отримання даних з форми
    $pib = mysqli_real_escape_string($link, $_POST['pib']);
    $telefon = mysqli_real_escape_string($link, $_POST['telefon']);
    $tip = mysqli_real_escape_string($link, $_POST['tip_klienta']);
    $pasport = mysqli_real_escape_string($link, $_POST['pasportni_dani']);

    // Формування SQL запиту
    $sql_insert = "INSERT INTO KLIENT (pib, telefon, tip_klienta, pasportni_dani) 
                   VALUES ('$pib', '$telefon', '$tip', '$pasport')";

    // Виконання запиту
    if (mysqli_query($link, $sql_insert)) {
        echo "<script>alert('Клієнт успішно доданий!'); window.location.href='../lists/client_list.php';</script>";
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
    <title>Додати Клієнта</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>

    <div class="form-container">
        <h1>👤 Новий Клієнт</h1>
        
        <form action="" method="post">
            
            <label>ПІБ:</label>
            <input type="text" name="pib" required placeholder="Прізвище Ім'я По-батькові">

            <label>Телефон:</label>
            <input type="text" name="telefon" required placeholder="+380...">

            <label>Тип Клієнта:</label>
            <select name="tip_klienta" required>
                <option value="Покупець">Покупець</option>
                <option value="Орендар">Орендар</option>
            </select>

            <label>Паспортні дані / Код:</label>
            <input type="text" name="pasportni_dani" placeholder="Серія та номер паспорта">

            <button type="submit" class="btn-submit">Зберегти</button>
        </form>

        <a href="../lists/client_list.php" class="back-link">Скасувати та повернутися</a>
    </div>

</body>
</html>