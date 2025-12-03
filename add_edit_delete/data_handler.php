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
// require_once '../db_functions.php';  <-- НЕ ПОТРІБНО, оскільки CRUD-логіка тут

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення: " . mysqli_connect_error());
}

// ----------------------------------------------------
// ЛОГІКА ВИДАЛЕННЯ
// ----------------------------------------------------
if (isset($_GET['action']) && isset($_GET['id'])) {
    
    $action = $_GET['action'];
    $id = mysqli_real_escape_string($link, $_GET['id']);
    $success = false;
    $sql = "";
    $redirect_url = '../index.php';
    
    switch ($action) {
        case 'delete_object': // Видалення Об'єкта Нерухомості (стара назва 'delete')
            $sql = "DELETE FROM OBJEKT WHERE id_objekt = '{$id}'";
            $redirect_url = '../lists/property_list.php';
            break;

        case 'delete_client': // Видалення Клієнта
            $sql = "DELETE FROM KLIENT WHERE id_klient = '{$id}'";
            $redirect_url = '../lists/client_list.php';
            break;
        
        case 'delete_realtor': // НОВИЙ БЛОК: Видалення Рієлтора
            $sql = "DELETE FROM RIELTOR WHERE id_rieltor = '{$id}'";
            $redirect_url = '../lists/realtor_list.php';
            break;

        case 'delete_deal': // Видалення Угоди
            $sql = "DELETE FROM UGODA WHERE id_ugoda = '{$id}'";
            $redirect_url = '../lists/deal_list.php';
            break;
            
        // TODO: Додати 'delete_realtor', 'delete_owner', 'delete_deal'

        case 'delete_owner': // Видалення Власника
            $sql = "DELETE FROM VLASNYK WHERE id_vlasnyk = '{$id}'";
            $redirect_url = '../lists/owner_list.php';
            break;
    }

    if (!empty($sql)) {
        $success = mysqli_query($link, $sql);
    }

    if ($success) {
        header("location: {$redirect_url}");
        exit();
    } else {
        die("Помилка видалення запису. (Помилка SQL: " . mysqli_error($link) . ")");
    }
}

mysqli_close($link);
?>