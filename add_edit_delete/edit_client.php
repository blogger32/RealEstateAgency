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
// Оскільки get_single_record не використовується, ми дублюємо логіку тут
// require_once '../db_functions.php'; 

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення.");
}

$id_klient = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : null;
$client_record = null;

// ----------------------------------------------------
// 1. Завантаження даних для форми (SELECT)
// ----------------------------------------------------
if ($id_klient) {
    $sql_select_current = "SELECT * FROM KLIENT WHERE id_klient = '{$id_klient}'";
    $result_current = mysqli_query($link, $sql_select_current);
    
    if (mysqli_num_rows($result_current) == 1) {
        $client_record = mysqli_fetch_assoc($result_current);
    } else {
        die("Клієнт не знайдений.");
    }
} else {
    die("ID клієнта не вказано.");
}

// ----------------------------------------------------
// 2. Обробка POST-запиту (UPDATE)
// ----------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $pib = mysqli_real_escape_string($link, $_POST['pib']);
    $telefon = mysqli_real_escape_string($link, $_POST['telefon']);
    $tip = mysqli_real_escape_string($link, $_POST['tip_klienta']);
    $pasport = mysqli_real_escape_string($link, $_POST['pasportni_dani']);
    
    $sql_update = "UPDATE KLIENT SET 
            pib = '$pib', 
            telefon = '$telefon', 
            tip_klienta = '$tip', 
            pasportni_dani = '$pasport'
            WHERE id_klient = '{$id_klient}'";

    if (mysqli_query($link, $sql_update)) {
        echo "<script>alert('Клієнт успішно оновлений!'); window.location.href='../lists/client_list.php';</script>";
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
    <title>Редагування Клієнта</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Редагування Клієнта ID: <?php echo $id_klient; ?></h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id_klient; ?>" method="post">
        
        <label for="pib">ПІБ:</label><br>
        <input type="text" id="pib" name="pib" value="<?php echo htmlspecialchars($client_record['pib']); ?>" required><br><br>

        <label for="telefon">Телефон:</label><br>
        <input type="text" id="telefon" name="telefon" value="<?php echo htmlspecialchars($client_record['telefon']); ?>" required><br><br>

        <label for="tip_klienta">Тип Клієнта:</label><br>
        <select id="tip_klienta" name="tip_klienta" required>
            <?php
            $types = ['Покупець', 'Орендар'];
            foreach ($types as $type) {
                $selected = ($client_record['tip_klienta'] == $type) ? 'selected' : '';
                echo "<option value='{$type}' {$selected}>{$type}</option>";
            }
            ?>
        </select><br><br>
        
        <label for="pasportni_dani">Паспортні дані/Код ЄДРПОУ:</label><br>
        <input type="text" id="pasportni_dani" name="pasportni_dani" value="<?php echo htmlspecialchars($client_record['pasportni_dani']); ?>"><br><br>

        <button type="submit">Оновити Клієнта</button>
    </form>
    
    <p><a href="../lists/client_list.php">← Повернутися до списку</a></p>
</body>
</html>