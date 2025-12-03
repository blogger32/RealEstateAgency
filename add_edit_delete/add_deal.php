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

// 1. Отримання списків для випадаючих меню
// Вибираємо тільки АКТИВНІ об'єкти, щоб уникнути дублювання
$objects = mysqli_query($link, "SELECT id_objekt, adresa, cina, tip_objekta FROM OBJEKT WHERE status = 'Активний' ORDER BY adresa");
$realtors = mysqli_query($link, "SELECT id_rieltor, pib FROM RIELTOR ORDER BY pib");
$clients = mysqli_query($link, "SELECT id_klient, pib, tip_klienta FROM KLIENT ORDER BY pib");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $data_ukladennja = mysqli_real_escape_string($link, $_POST['data_ukladennja']);
    $tip_ugody = mysqli_real_escape_string($link, $_POST['tip_ugody']);
    $id_objekt = mysqli_real_escape_string($link, $_POST['id_objekt']);
    $id_rieltor = mysqli_real_escape_string($link, $_POST['id_rieltor']);
    $id_pokupca = mysqli_real_escape_string($link, $_POST['id_pokupca']);
    $suma = mysqli_real_escape_string($link, $_POST['suma_ugody']);
    $komisija = mysqli_real_escape_string($link, $_POST['komisija_agentstva']);

    // Транзакція: Додаємо угоду AND Оновлюємо статус об'єкта
    mysqli_begin_transaction($link);

    try {
        // 1. Вставка угоди
        $sql_insert = "INSERT INTO UGODA (data_ukladennja, suma_ugody, komisija_agentstva, tip_ugody, id_objekt, id_rieltor, id_pokupca) 
                       VALUES ('$data_ukladennja', '$suma', '$komisija', '$tip_ugody', '$id_objekt', '$id_rieltor', '$id_pokupca')";
        
        if (!mysqli_query($link, $sql_insert)) {
            throw new Exception("Помилка вставки угоди: " . mysqli_error($link));
        }

        // 2. Оновлення статусу об'єкта (Продано або Здано)
        $new_status = ($tip_ugody == 'Продаж') ? 'Продано' : 'Здано';
        $sql_update_obj = "UPDATE OBJEKT SET status = '$new_status' WHERE id_objekt = '$id_objekt'";

        if (!mysqli_query($link, $sql_update_obj)) {
            throw new Exception("Помилка оновлення статусу об'єкта.");
        }

        // Якщо все ок, фіксуємо зміни
        mysqli_commit($link);
        echo "<script>alert('Угоду успішно укладено!'); window.location.href='../lists/deal_list.php';</script>";

    } catch (Exception $e) {
        mysqli_rollback($link);
        echo "Сталася помилка: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Нова Угода</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Укладання Нової Угоди</h1>

    <form action="" method="post">
        
        <label>Дата угоди:</label><br>
        <input type="date" name="data_ukladennja" required value="<?php echo date('Y-m-d'); ?>"><br><br>

        <label>Тип угоди:</label><br>
        <select name="tip_ugody" id="tip_ugody" required onchange="updateStatusPreview()">
            <option value="Продаж">Продаж</option>
            <option value="Оренда">Оренда</option>
        </select><br><br>

        <label>Об'єкт (тільки Активні):</label><br>
        <select name="id_objekt" required>
            <option value="">-- Виберіть об'єкт --</option>
            <?php while ($obj = mysqli_fetch_assoc($objects)): ?>
                <option value="<?php echo $obj['id_objekt']; ?>">
                    <?php echo htmlspecialchars($obj['adresa']) . " (" . $obj['tip_objekta'] . ") - " . $obj['cina']; ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Рієлтор:</label><br>
        <select name="id_rieltor" required>
            <option value="">-- Виберіть рієлтора --</option>
            <?php while ($r = mysqli_fetch_assoc($realtors)): ?>
                <option value="<?php echo $r['id_rieltor']; ?>"><?php echo htmlspecialchars($r['pib']); ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Клієнт (Покупець/Орендар):</label><br>
        <select name="id_pokupca" required>
            <option value="">-- Виберіть клієнта --</option>
            <?php while ($k = mysqli_fetch_assoc($clients)): ?>
                <option value="<?php echo $k['id_klient']; ?>">
                    <?php echo htmlspecialchars($k['pib']) . " (" . $k['tip_klienta'] . ")"; ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Сума угоди:</label><br>
        <input type="number" name="suma_ugody" step="0.01" required><br><br>

        <label>Комісія агентства:</label><br>
        <input type="number" name="komisija_agentstva" step="0.01" required><br><br>

        <button type="submit">Зберегти та Провести Угоду</button>
    </form>
    
    <p><a href="../lists/deal_list.php">← Повернутися до списку</a></p>
</body>
</html>
<?php mysqli_close($link); ?>