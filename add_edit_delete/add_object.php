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

// Підключення до БД (з урахуванням порту 3307)
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення: " . mysqli_connect_error());
}

// Функції для вибору списків (для випадаючих меню)
function get_rieltors($link) {
    $sql = "SELECT id_rieltor, pib FROM RIELTOR ORDER BY pib";
    return mysqli_query($link, $sql);
}

function get_vlasnyky($link) {
    $sql = "SELECT id_vlasnyk, pib FROM VLASNYK ORDER BY pib";
    return mysqli_query($link, $sql);
}

$rieltors = get_rieltors($link);
$vlasnyky = get_vlasnyky($link);

// Обробка POST-запиту
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Отримання та очищення даних
    $adresa = mysqli_real_escape_string($link, $_POST['adresa']);
    $ploscha = mysqli_real_escape_string($link, $_POST['ploscha']);
    $kimnat = mysqli_real_escape_string($link, $_POST['kimnat']);
    $cina = mysqli_real_escape_string($link, $_POST['cina']);
    $tip = mysqli_real_escape_string($link, $_POST['tip']);
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $rieltor_id = mysqli_real_escape_string($link, $_POST['rieltor_id']);
    $vlasnyk_id = mysqli_real_escape_string($link, $_POST['vlasnyk_id']);
    $data_dodavannja = date("Y-m-d"); // Фіксуємо поточну дату

    // 2. Складання SQL-запиту на вставку
    $sql_insert = "INSERT INTO OBJEKT (adresa, ploscha_zagalna, kilkist_kimnat, cina, tip_objekta, status, data_dodavannja, id_rieltor, id_vlasnyk) 
                   VALUES ('$adresa', '$ploscha', '$kimnat', '$cina', '$tip', '$status', '$data_dodavannja', '$rieltor_id', '$vlasnyk_id')";

    // 3. Виконання запиту
    if (mysqli_query($link, $sql_insert)) {
        echo "<script>alert('Об’єкт успішно додано!'); window.location.href='../index.php';</script>";
    } else {
        echo "Помилка додавання об'єкта: " . mysqli_error($link);
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Додати Новий Об'єкт</title>
    <link rel="stylesheet" href="style.css">
    <style>
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], select { width: 300px; padding: 5px; margin-top: 3px; }
        button { margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <h1>Додавання Нового Об'єкта Нерухомості</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        
        <label for="adresa">Адреса:</label>
        <input type="text" id="adresa" name="adresa" required>

        <label for="ploscha">Загальна Площа (м²):</label>
        <input type="number" id="ploscha" name="ploscha" step="0.01" min="1" required>

        <label for="kimnat">Кількість кімнат:</label>
        <input type="number" id="kimnat" name="kimnat" min="0" required>

        <label for="cina">Ціна (USD/UAH):</label>
        <input type="number" id="cina" name="cina" step="0.01" min="1" required>

        <label for="tip">Тип Об'єкта:</label>
        <select id="tip" name="tip" required>
            <option value="Квартира">Квартира</option>
            <option value="Будинок">Будинок</option>
            <option value="Комерція">Комерція</option>
        </select>
        
        <label for="status">Статус:</label>
        <select id="status" name="status" required>
            <option value="Активний">Активний</option>
            <option value="Зарезервовано">Зарезервовано</option>
        </select>

        <label for="rieltor_id">Відповідальний Рієлтор:</label>
        <select id="rieltor_id" name="rieltor_id" required>
            <?php
            // Вивід рієлторів з БД
            while ($row = mysqli_fetch_assoc($rieltors)) {
                echo "<option value='" . $row['id_rieltor'] . "'>" . $row['pib'] . "</option>";
            }
            ?>
        </select>

        <label for="vlasnyk_id">Власник:</label>
        <select id="vlasnyk_id" name="vlasnyk_id" required>
            <?php
            // Вивід власників з БД
            while ($row = mysqli_fetch_assoc($vlasnyky)) {
                echo "<option value='" . $row['id_vlasnyk'] . "'>" . $row['pib'] . "</option>";
            }
            ?>
        </select>

        <button type="submit">Зберегти Об'єкт</button>
    </form>
    
    <p><a href="../index.php">Повернутися до списку</a></p>

    <?php 
    mysqli_close($link);
    ?>

</body>
</html>