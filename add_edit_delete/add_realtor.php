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
    
    // Отримання даних
    $pib = mysqli_real_escape_string($link, $_POST['pib']);
    $posada = mysqli_real_escape_string($link, $_POST['posada']);
    $data_prijnjattya = mysqli_real_escape_string($link, $_POST['data_prijnjattya']);
    $vidcotok = mysqli_real_escape_string($link, $_POST['vidcotok_komisii']);
    $telefon = mysqli_real_escape_string($link, $_POST['telefon']);
    $email = mysqli_real_escape_string($link, $_POST['email']);

    // SQL Insert
    $sql_insert = "INSERT INTO RIELTOR (pib, posada, data_prijnjattya, vidcotok_komisii, telefon, email) 
                   VALUES ('$pib', '$posada', '$data_prijnjattya', '$vidcotok', '$telefon', '$email')";

    if (mysqli_query($link, $sql_insert)) {
        echo "<script>alert('Рієлтор успішно доданий!'); window.location.href='../lists/realtor_list.php';</script>";
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
    <title>Додати Рієлтора</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Додавання Нового Рієлтора</h1>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        
        <label for="pib">ПІБ:</label><br>
        <input type="text" id="pib" name="pib" required style="width: 300px;"><br><br>

        <label for="posada">Посада:</label><br>
        <select id="posada" name="posada" required style="width: 300px;">
            <option value="Рієлтор">Рієлтор</option>
            <option value="Менеджер">Менеджер</option>
            <option value="Адміністратор">Адміністратор</option>
        </select><br><br>

        <label for="data_prijnjattya">Дата прийняття на роботу:</label><br>
        <input type="date" id="data_prijnjattya" name="data_prijnjattya" required><br><br>

        <label for="vidcotok_komisii">Відсоток комісії (%):</label><br>
        <input type="number" id="vidcotok_komisii" name="vidcotok_komisii" step="0.01" min="0" max="100" required><br><br>

        <label for="telefon">Телефон:</label><br>
        <input type="text" id="telefon" name="telefon" required><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"><br><br>

        <button type="submit" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none;">Зберегти</button>
    </form>
    
    <p><a href="../lists/realtor_list.php">← Повернутися до списку</a></p>
</body>
</html>