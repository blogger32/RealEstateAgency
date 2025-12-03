<?php
// login.php - Вхід через HASH
session_start();
require_once 'db_config.php';

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

$username = $password = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    
    $username = mysqli_real_escape_string($link, $_POST['username']);
    $entered_password = $_POST['password'];

    // Вибираємо hash замість password
    $sql = "SELECT id, username, hash, role FROM USERS WHERE username = '$username'";
    $result = mysqli_query($link, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // Перевіряємо хеш
        if (password_verify($entered_password, $row['hash'])) {
            session_start();
            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $row['id'];
            $_SESSION["username"] = $row['username'];
            $_SESSION["role"] = $row['role'];
            
            header("location: index.php");
            exit;
        } else {
            $error = "Невірний пароль.";
        }
    } else {
        $error = "Користувача не знайдено.";
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вхід</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; padding-top: 100px; background-color: #f4f4f4; }
        form { background: white; padding: 30px; border-radius: 8px; width: 300px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <form method="post">
        <h2 style="text-align:center">Вхід</h2>
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        <label>Логін:</label>
        <input type="text" name="username" required>
        <label>Пароль:</label>
        <input type="password" name="password" required>
        <button type="submit">Увійти</button>
    </form>
</body>
</html>