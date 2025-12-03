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

if ($link === false) { die("Помилка підключення."); }

$id_ugoda = isset($_GET['id']) ? mysqli_real_escape_string($link, $_GET['id']) : null;
$deal = null;

// 1. Завантаження даних угоди
if ($id_ugoda) {
    // Отримуємо дані угоди + адресу об'єкта (для відображення, бо об'єкт міняти не бажано)
    $sql = "SELECT U.*, O.adresa 
            FROM UGODA U 
            JOIN OBJEKT O ON U.id_objekt = O.id_objekt 
            WHERE U.id_ugoda = '$id_ugoda'";
    $result = mysqli_query($link, $sql);
    if (mysqli_num_rows($result) == 1) {
        $deal = mysqli_fetch_assoc($result);
    } else {
        die("Угода не знайдена.");
    }
} else { die("ID не вказано."); }

// 2. Списки для випадаючих меню
$realtors = mysqli_query($link, "SELECT id_rieltor, pib FROM RIELTOR ORDER BY pib");
$clients = mysqli_query($link, "SELECT id_klient, pib FROM KLIENT ORDER BY pib");

// 3. Обробка оновлення
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_ukladennja = mysqli_real_escape_string($link, $_POST['data_ukladennja']);
    $tip_ugody = mysqli_real_escape_string($link, $_POST['tip_ugody']);
    $id_rieltor = mysqli_real_escape_string($link, $_POST['id_rieltor']);
    $id_pokupca = mysqli_real_escape_string($link, $_POST['id_pokupca']);
    $suma = mysqli_real_escape_string($link, $_POST['suma_ugody']);
    $komisija = mysqli_real_escape_string($link, $_POST['komisija_agentstva']);

    $sql_update = "UPDATE UGODA SET 
        data_ukladennja = '$data_ukladennja',
        tip_ugody = '$tip_ugody',
        id_rieltor = '$id_rieltor',
        id_pokupca = '$id_pokupca',
        suma_ugody = '$suma',
        komisija_agentstva = '$komisija'
        WHERE id_ugoda = '$id_ugoda'";

    if (mysqli_query($link, $sql_update)) {
        echo "<script>alert('Угоду оновлено!'); window.location.href='../lists/deal_list.php';</script>";
    } else {
        echo "Помилка оновлення: " . mysqli_error($link);
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагування Угоди</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Редагування Угоди №<?php echo $id_ugoda; ?></h1>
    <h3>Об'єкт: <?php echo htmlspecialchars($deal['adresa']); ?> (Зміна об'єкта заблокована)</h3>

    <form action="" method="post">
        
        <label>Дата угоди:</label><br>
        <input type="date" name="data_ukladennja" value="<?php echo $deal['data_ukladennja']; ?>" required><br><br>

        <label>Тип угоди:</label><br>
        <select name="tip_ugody" required>
            <option value="Продаж" <?php echo ($deal['tip_ugody'] == 'Продаж') ? 'selected' : ''; ?>>Продаж</option>
            <option value="Оренда" <?php echo ($deal['tip_ugody'] == 'Оренда') ? 'selected' : ''; ?>>Оренда</option>
        </select><br><br>

        <label>Рієлтор:</label><br>
        <select name="id_rieltor" required>
            <?php 
            mysqli_data_seek($realtors, 0);
            while ($r = mysqli_fetch_assoc($realtors)): 
                $selected = ($deal['id_rieltor'] == $r['id_rieltor']) ? 'selected' : '';
            ?>
                <option value="<?php echo $r['id_rieltor']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($r['pib']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Клієнт:</label><br>
        <select name="id_pokupca" required>
            <?php 
            mysqli_data_seek($clients, 0);
            while ($k = mysqli_fetch_assoc($clients)): 
                $selected = ($deal['id_pokupca'] == $k['id_klient']) ? 'selected' : '';
            ?>
                <option value="<?php echo $k['id_klient']; ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($k['pib']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label>Сума угоди:</label><br>
        <input type="number" name="suma_ugody" step="0.01" value="<?php echo $deal['suma_ugody']; ?>" required><br><br>

        <label>Комісія агентства:</label><br>
        <input type="number" name="komisija_agentstva" step="0.01" value="<?php echo $deal['komisija_agentstva']; ?>" required><br><br>

        <button type="submit">Оновити</button>
    </form>
    
    <p><a href="../lists/deal_list.php">← Повернутися до списку</a></p>
</body>
</html>
<?php mysqli_close($link); ?>