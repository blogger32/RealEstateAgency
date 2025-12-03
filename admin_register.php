<?php
// admin_register.php - Додавання нових користувачів
session_start();

// 1. Перевірка: чи це Адмін?
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: index.php");
    exit;
}

require_once 'db_config.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    
    if($link === false){
        die("Помилка підключення: " . mysqli_connect_error());
    }
    
    $username = mysqli_real_escape_string($link, trim($_POST['username']));
    $password = trim($_POST['password']);
    
    // 2. Перевірка: чи існує такий логін?
    $check_sql = "SELECT id FROM USERS WHERE username = '$username'";
    $result = mysqli_query($link, $check_sql);
    
    if (mysqli_num_rows($result) > 0) {
        $message = "<div style='color:red; margin-bottom:15px;'>Помилка: Користувач з логіном <b>'$username'</b> вже існує!</div>";
    } else {
        // 3. ХЕШУВАННЯ ПАРОЛЯ
        // Ми не зберігаємо 'password', ми зберігаємо його 'hash'
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $role = 'user'; // Нові користувачі завжди 'user'
        
        // 4. ВСТАВКА В БД (Зверніть увагу: колонка `hash` замість `password`)
        $sql_insert = "INSERT INTO USERS (username, hash, role) VALUES ('$username', '$password_hash', '$role')";
        
        if (mysqli_query($link, $sql_insert)) {
            $message = "<div style='color:green; margin-bottom:15px;'>Користувача <b>$username</b> успішно створено!</div>";
        } else {
            $message = "<div style='color:red; margin-bottom:15px;'>Помилка SQL: " . mysqli_error($link) . "</div>";
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Створити Нового Користувача</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .container { max-width: 400px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; font-size: 24px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        a { text-decoration: none; color: #007bff; }
    </style>
</head>
<body>

    <h1>Створити Нового Користувача</h1>
    <p style="text-align: center;"><a href="index.php">← На головну</a></p>

    <div class="container">
        <?php echo $message; ?>

        <form method="post">
            <label>Логін нового користувача:</label>
            <input type="text" name="username" required placeholder="Наприклад: user2">
            
            <label>Пароль:</label>
            <input type="text" name="password" required placeholder="Введіть пароль">
            
            <p style="font-size: 13px; color: gray;">
                Роль буде встановлена як "user" автоматично.<br>
                Пароль буде захешовано перед збереженням.
            </p>
            
            <button type="submit">Створити</button>
        </form>
    </div>

</body>
</html>