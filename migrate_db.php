<?php
// migrate_db.php - Міграція на безпечні паролі
require_once 'db_config.php';

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
if ($link === false) { die("Помилка підключення: " . mysqli_connect_error()); }

echo "<h2>Початок міграції...</h2>";

// 1. Додаємо колонку 'hash', якщо її немає
$check_col = mysqli_query($link, "SHOW COLUMNS FROM USERS LIKE 'hash'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($link, "ALTER TABLE USERS ADD COLUMN hash VARCHAR(255) NOT NULL AFTER username");
    echo "Колонка 'hash' додана.<br>";
}

// 2. Хешуємо існуючі паролі
$result = mysqli_query($link, "SELECT id, password FROM USERS");
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Якщо пароль ще не захешований (короткий або не починається з $)
        if (!empty($row['password'])) {
            $new_hash = password_hash($row['password'], PASSWORD_DEFAULT);
            $id = $row['id'];
            mysqli_query($link, "UPDATE USERS SET hash = '$new_hash' WHERE id = '$id'");
            echo "Пароль для ID $id захешовано.<br>";
        }
    }
}

// 3. Видаляємо колонку 'password', якщо вона є
$check_pass = mysqli_query($link, "SHOW COLUMNS FROM USERS LIKE 'password'");
if (mysqli_num_rows($check_pass) > 0) {
    mysqli_query($link, "ALTER TABLE USERS DROP COLUMN password");
    echo "Колонка 'password' успішно видалена.<br>";
}

echo "<h3>Міграція завершена! Тепер таблиця має структуру: username, hash, role.</h3>";
mysqli_close($link);
?>