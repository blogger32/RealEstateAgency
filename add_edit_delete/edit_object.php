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

// Підключення до БД
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

$id_objekt = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : null;
$object = null; // Змінна для зберігання даних об'єкта

// --- ЧАСТИНА 1: Завантаження поточних даних (SELECT) ---
if ($id_objekt) {
    $sql_select_current = "SELECT * FROM OBJEKT WHERE id_objekt = '$id_objekt'";
    $result_current = mysqli_query($link, $sql_select_current);
    
    if (mysqli_num_rows($result_current) == 1) {
        $object = mysqli_fetch_assoc($result_current);
    } else {
        die("Об'єкт не знайдено.");
    }
} else {
    die("ID об'єкта не вказано.");
}

// --- ЧАСТИНА 2: Обробка POST-запиту (UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $id_objekt) {
    
    // 1. Отримання та очищення даних
    $adresa = mysqli_real_escape_string($link, $_POST['adresa']);
    $ploscha = mysqli_real_escape_string($link, $_POST['ploscha']);
    $kimnat = mysqli_real_escape_string($link, $_POST['kimnat']);
    $cina = mysqli_real_escape_string($link, $_POST['cina']);
    $tip = mysqli_real_escape_string($link, $_POST['tip']);
    $status = mysqli_real_escape_string($link, $_POST['status']);
    $rieltor_id = mysqli_real_escape_string($link, $_POST['rieltor_id']);
    $vlasnyk_id = mysqli_real_escape_string($link, $_POST['vlasnyk_id']);

    // 2. Складання SQL-запиту на оновлення
    $sql_update = "UPDATE OBJEKT SET 
                   adresa = '$adresa', 
                   ploscha_zagalna = '$ploscha', 
                   kilkist_kimnat = '$kimnat', 
                   cina = '$cina', 
                   tip_objekta = '$tip', 
                   status = '$status', 
                   id_rieltor = '$rieltor_id', 
                   id_vlasnyk = '$vlasnyk_id'
                   WHERE id_objekt = '$id_objekt'";

    // 3. Виконання запиту
    if (mysqli_query($link, $sql_update)) {
        echo "<script>alert('Об’єкт успішно оновлено!'); window.location.href='../index.php';</script>";
    } else {
        echo "Помилка оновлення об'єкта: " . mysqli_error($link);
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагування Об'єкта №<?php echo $id_objekt; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], select { width: 300px; padding: 5px; margin-top: 3px; }
        button { margin-top: 20px; padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

    <h1>Редагування Об'єкта Нерухомості №<?php echo $id_objekt; ?></h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_objekt; ?>" method="post">
        
        <label for="adresa">Адреса:</label>
        <input type="text" id="adresa" name="adresa" value="<?php echo htmlspecialchars($object['adresa']); ?>" required>

        <label for="ploscha">Загальна Площа (м²):</label>
        <input type="number" id="ploscha" name="ploscha" step="0.01" min="1" value="<?php echo htmlspecialchars($object['ploscha_zagalna']); ?>" required>

        <label for="kimnat">Кількість кімнат:</label>
        <input type="number" id="kimnat" name="kimnat" min="0" value="<?php echo htmlspecialchars($object['kilkist_kimnat']); ?>" required>

        <label for="cina">Ціна (USD/UAH):</label>
        <input type="number" id="cina" name="cina" step="0.01" min="1" value="<?php echo htmlspecialchars($object['cina']); ?>" required>

        <label for="tip">Тип Об'єкта:</label>
        <select id="tip" name="tip" required>
            <?php
            // Вибір поточного типу
            $types = ['Квартира', 'Будинок', 'Комерція'];
            foreach ($types as $type) {
                $selected = ($object['tip_objekta'] == $type) ? 'selected' : '';
                echo "<option value='{$type}' {$selected}>{$type}</option>";
            }
            ?>
        </select>
        
        <label for="status">Статус:</label>
        <select id="status" name="status" required>
            <?php
            // Вибір поточного статусу
            $statuses = ['Активний', 'Зарезервовано', 'Продано'];
            foreach ($statuses as $status) {
                $selected = ($object['status'] == $status) ? 'selected' : '';
                echo "<option value='{$status}' {$selected}>{$status}</option>";
            }
            ?>
        </select>

        <label for="rieltor_id">Відповідальний Рієлтор:</label>
        <select id="rieltor_id" name="rieltor_id" required>
            <?php
            // Вивід рієлторів, позначення поточного як 'selected'
            mysqli_data_seek($rieltors, 0); // Скидаємо покажчик на початок
            while ($row = mysqli_fetch_assoc($rieltors)) {
                $selected = ($object['id_rieltor'] == $row['id_rieltor']) ? 'selected' : '';
                echo "<option value='" . $row['id_rieltor'] . "' {$selected}>" . $row['pib'] . "</option>";
            }
            ?>
        </select>

        <label for="vlasnyk_id">Власник:</label>
        <select id="vlasnyk_id" name="vlasnyk_id" required>
            <?php
            // Вивід власників, позначення поточного як 'selected'
            mysqli_data_seek($vlasnyky, 0); // Скидаємо покажчик на початок
            while ($row = mysqli_fetch_assoc($vlasnyky)) {
                $selected = ($object['id_vlasnyk'] == $row['id_vlasnyk']) ? 'selected' : '';
                echo "<option value='" . $row['id_vlasnyk'] . "' {$selected}>" . $row['pib'] . "</option>";
            }
            ?>
        </select>

        <button type="submit">Оновити Об'єкт</button>
    </form>
    
    <p><a href="index.php">Повернутися до списку</a></p>

    <?php 
    mysqli_close($link);
    ?>

</body>
</html>