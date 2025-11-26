<?php
// client_list.php - Список клієнтів з пошуком, сортуванням та фільтрацією
require_once '../db_config.php'; // Шлях до конфігурації

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення до бази даних: " . mysqli_connect_error());
}

$search_where = "";
$filter_where = "";
$sort_order_clause = "";

// ----------------------------------------------------
// 1. ЛОГІКА ПОШУКУ (за ПІБ, Телефоном)
// ----------------------------------------------------
if (isset($_GET['search_term']) && $_GET['search_term'] !== '') {
    $term = mysqli_real_escape_string($link, $_GET['search_term']);
    $field = mysqli_real_escape_string($link, $_GET['search_field']);
    
    // Допустимі поля для пошуку
    $allowed_fields = ['pib', 'telefon']; 
    
    if (in_array($field, $allowed_fields)) {
        $search_where = "WHERE K.{$field} LIKE '%{$term}%'";
    }
}

// ----------------------------------------------------
// 2. ЛОГІКА ФІЛЬТРАЦІЇ (за Типом Клієнта)
// ----------------------------------------------------
if (isset($_GET['filter_type']) && $_GET['filter_type'] !== '' && $_GET['filter_type'] !== 'all') {
    $type = mysqli_real_escape_string($link, $_GET['filter_type']);
    
    // Визначаємо, чи потрібен префікс WHERE чи AND
    $prefix = ($search_where === "") ? "WHERE" : "AND";
    
    $filter_where = "{$prefix} K.tip_klienta = '{$type}'";
}

// ----------------------------------------------------
// 3. ЛОГІКА СОРТУВАННЯ
// ----------------------------------------------------
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($link, $_GET['sort_by']) : 'id_klient';
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'DESC' ? 'DESC' : 'ASC';

$sort_order_clause = "ORDER BY K.{$sort_by} {$sort_order}";

// ----------------------------------------------------
// СКЛАДАННЯ ФІНАЛЬНОГО SQL-ЗАПИТУ
// ----------------------------------------------------
$sql_select = "
    SELECT 
        K.id_klient, K.pib, K.telefon, K.tip_klienta, K.pasportni_dani
    FROM KLIENT K
    {$search_where} 
    {$filter_where}
    {$sort_order_clause}
";

$result = mysqli_query($link, $sql_select);
$current_sort_order = $sort_order;
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Агентство Нерухомості - Клієнти</title>
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }
    </style>
</head>
<body>

<h1>Список Клієнтів</h1>

    <p><a href="../index.php">← На головну</a></p>

    <form method="get" action="client_list.php" style="margin-bottom: 10px;">
        
        <label for="search_field">Шукати за:</label>
        <select id="search_field" name="search_field">
            <option value="pib" <?php echo (isset($_GET['search_field']) && $_GET['search_field'] == 'pib') ? 'selected' : ''; ?>>ПІБ</option>
            <option value="telefon" <?php echo (isset($_GET['search_field']) && $_GET['search_field'] == 'telefon') ? 'selected' : ''; ?>>Телефон</option>
        </select>
        <input type="text" name="search_term" placeholder="Введіть значення..." 
               value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>">
        
        <label for="filter_type" style="margin-left: 15px;">Фільтрувати за типом:</label>
        <select id="filter_type" name="filter_type">
            <option value="all" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == 'all') ? 'selected' : ''; ?>>Усі типи</option>
            <option value="Покупець" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == 'Покупець') ? 'selected' : ''; ?>>Покупець</option>
            <option value="Орендар" <?php echo (isset($_GET['filter_type']) && $_GET['filter_type'] == 'Орендар') ? 'selected' : ''; ?>>Орендар</option>
        </select>
        
        <label for="sort_by" style="margin-left: 15px;">Сортувати за:</label>
        <select id="sort_by" name="sort_by">
            <option value="pib" <?php echo ($sort_by == 'pib') ? 'selected' : ''; ?>>ПІБ</option>
            <option value="telefon" <?php echo ($sort_by == 'telefon') ? 'selected' : ''; ?>>Телефон</option>
            <option value="tip_klienta" <?php echo ($sort_by == 'tip_klienta') ? 'selected' : ''; ?>>Тип Клієнта</option>
            <option value="id_klient" <?php echo ($sort_by == 'id_klient') ? 'selected' : ''; ?>>ID</option>
        </select>

        <select id="sort_order" name="sort_order">
            <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>За зростанням</option>
            <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>За спаданням</option>
        </select>

        <button type="submit" style="background-color: #e5944d; color: white;">Сортувати</button>
        <a href="client_list.php">Скинути все</a>
    </form>
    
    <p>
        <a href="../add_edit_delete/add_client.php">Додати нового клієнта</a>
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ПІБ</th>
                <th>Телефон</th>
                <th>Тип Клієнта</th>
                <th>Паспортні дані</th>
                <th>Дія</th>
            </tr>
        </thead>
        <tbody>
            
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id_klient'] . "</td>";
                echo "<td>" . htmlspecialchars($row['pib']) . "</td>";
                echo "<td>" . $row['telefon'] . "</td>";
                echo "<td>" . $row['tip_klienta'] . "</td>";
                echo "<td>" . htmlspecialchars($row['pasportni_dani']) . "</td>";
                echo "<td>";
                // Посилання на редагування та видалення
                echo "<a href='../add_edit_delete/edit_client.php?id=" . $row['id_klient'] . "'>Редагувати</a> | ";
                // Примітка: Видалення має посилатися на data_handler у батьківській папці
                echo "<a href='../add_edit_delete/data_handler.php?action=delete_client&id=" . $row['id_klient'] . "' onclick='return confirm(\"Видалити?\")'>Видалити</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Немає клієнтів, що відповідають умовам пошуку.</td></tr>";
        }
        ?>
        
        </tbody>
    </table>

    <?php
    mysqli_close($link);
    ?>

</body>
</html>