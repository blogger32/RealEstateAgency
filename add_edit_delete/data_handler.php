<?php
// Лістинг коду: data_handler.php - Видалення даних 
require_once '../db_config.php';
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення.");
}

// Перевірка дії (action)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_objekt = mysqli_real_escape_string($link, $_GET['id']);

    // SQL-запит на видалення 
    $sql_delete = "DELETE FROM OBJEKT WHERE id_objekt = '$id_objekt'";

    if (mysqli_query($link, $sql_delete)) {
        echo "Запис успішно видалено.";
        // Перенаправлення на головну сторінку
        header("location: ../index.php");
        exit();
    } else {
        echo "Помилка видалення запису: " . mysqli_error($link);
    }
}

mysqli_close($link);
?>