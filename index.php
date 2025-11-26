<?php
// index.php - Головна навігаційна сторінка
// Забезпечуємо підключення до конфігурації, щоб відповідати вимогам методички
require_once 'db_config.php';

// Підключення до БД (лише для перевірки з'єднання, хоча основний функціонал тут - навігація)
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення до бази даних: " . mysqli_connect_error());
}

// Закриття з'єднання, оскільки тут не потрібна вибірка даних
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Агенство з Нерухомості | Головна</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .nav-container { margin-top: 30px; }
        .nav-button {
            display: inline-block;
            padding: 15px 25px;
            margin: 10px;
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        .nav-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

    <h1>🏡 Агенство з Нерухомості</h1>

    <h2>Основні Сутності:</h2>

    <div class="nav-container">
        <a href="lists/client_list.php" class="nav-button">Клієнти</a>
        
        <a href="lists/property_list.php" class="nav-button">Нерухомість</a> 
        <a href="lists/realtor_list.php" class="nav-button">Ріелтори</a>
        
        <a href="lists/deal_list.php" class="nav-button">Угоди</a>
        
        <a href="lists/owner_list.php" class="nav-button">Власники</a>
    </div>

    <div class="nav-container" style="margin-top: 50px;">
        <h2>Аналітика та Звіти:</h2>
        <a href="rating.php" class="nav-button" style="background-color: #28a745;">🏆 Рейтинг Рієлторів</a>
    </div>

</body>
</html>