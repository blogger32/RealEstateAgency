<?php
// property_list.php - Список об'єктів з пошуком, фільтрацією та сортуванням
require_once '../db_config.php';

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення до бази даних: " . mysqli_connect_error());
}

$search_where = "";
$price_filter = ""; 
$sort_order_clause = "";

// ЛОГІКА ПОШУКУ
if (isset($_GET['search_term']) && $_GET['search_term'] !== '') {
    $term = mysqli_real_escape_string($link, $_GET['search_term']);
    $field = mysqli_real_escape_string($link, $_GET['search_field']);
    $allowed_fields = ['adresa', 'tip_objekta', 'status', 'cina']; 
    
    if (in_array($field, $allowed_fields)) {
        $search_where = "WHERE O.{$field} LIKE '%{$term}%'";
    } else if ($field === 'rieltor_pib') {
        $search_where = "WHERE R.pib LIKE '%{$term}%'";
    }
}

// ЛОГІКА ФІЛЬТРАЦІЇ ЦІНИ
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

if ($min_price !== null || $max_price !== null) {
    $prefix = ($search_where === "") ? "WHERE" : "AND";

    if ($min_price !== null) {
        $min_price_safe = mysqli_real_escape_string($link, $min_price);
        $price_filter .= " {$prefix} O.cina >= {$min_price_safe}";
        $prefix = "AND";
    }

    if ($max_price !== null) {
        $max_price_safe = mysqli_real_escape_string($link, $max_price);
        $price_filter .= " {$prefix} O.cina <= {$max_price_safe}";
    }
}

// ЛОГІКА СОРТУВАННЯ (Змінні приходять з форми)
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($link, $_GET['sort_by']) : 'id_objekt';
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'DESC' ? 'DESC' : 'ASC';

if ($sort_by === 'rieltor_pib') {
    $sort_order_clause = "ORDER BY R.pib {$sort_order}";
} else {
    $sort_field_prefix = (in_array($sort_by, ['id_objekt', 'adresa', 'ploscha_zagalna', 'cina', 'status', 'tip_objekta'])) ? 'O.' : 'O.';
    $sort_order_clause = "ORDER BY {$sort_field_prefix}{$sort_by} {$sort_order}";
}

// СКЛАДАННЯ ФІНАЛЬНОГО SQL-ЗАПИТУ
$sql_select = "
    SELECT 
        O.id_objekt, O.adresa, O.ploscha_zagalna, O.cina, O.status, O.tip_objekta,
        R.pib AS rieltor_pib
    FROM OBJEKT O
    JOIN RIELTOR R ON O.id_rieltor = R.id_rieltor
    {$search_where} 
    {$price_filter} 
    {$sort_order_clause}
";

$result = mysqli_query($link, $sql_select);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Агентство Нерухомості - Об'єкти</title>
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }
        .filter-panel { 
            border: 1px solid #ccc; 
            padding: 15px; 
            margin-bottom: 20px; 
            background-color: #f9f9f9; 
            text-align: left;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }
    </style>
</head>
<body>

<h1>Список Об'єктів Нерухомості</h1>

    <p><a href="../index.php">← На головну</a></p>

    <form method="get" action="property_list.php" class="filter-panel">
        
        <div>
            <label for="search_field" style="font-weight: bold;">Пошук за атрибутом:</label><br>
            <select id="search_field" name="search_field">
                <option value="adresa" <?php echo (isset($_GET['search_field']) && $_GET['search_field'] == 'adresa') ? 'selected' : ''; ?>>Адреса</option>
                <option value="tip_objekta" <?php echo (isset($_GET['search_field']) && $_GET['search_field'] == 'tip_objekta') ? 'selected' : ''; ?>>Тип Об'єкта</option>
                <option value="status" <?php echo (isset($_GET['search_field']) && $_GET['search_field'] == 'status') ? 'selected' : ''; ?>>Статус</option>
                <option value="rieltor_pib" <?php echo (isset($_GET['search_field']) && $_GET['search_field'] == 'rieltor_pib') ? 'selected' : ''; ?>>Рієлтор (ПІБ)</option>
            </select>
            <input type="text" name="search_term" placeholder="Введіть значення..." 
                   value="<?php echo isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : ''; ?>" style="padding: 5px;">
        </div>

        <div>
            <label style="font-weight: bold;">Фільтрація: Ціна (USD):</label><br>
            <input type="number" name="min_price" placeholder="Мін. ціна" min="0" style="width: 100px; padding: 5px;"
                   value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
            -
            <input type="number" name="max_price" placeholder="Макс. ціна" min="0" style="width: 100px; padding: 5px;"
                   value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
        </div>
        
        <div>
            <label for="sort_by" style="font-weight: bold;">Сортувати за:</label><br>
            <select id="sort_by" name="sort_by" style="padding: 5px;">
                <option value="adresa" <?php echo ($sort_by == 'adresa') ? 'selected' : ''; ?>>Адреса</option>
                <option value="cina" <?php echo ($sort_by == 'cina') ? 'selected' : ''; ?>>Ціна</option>
                <option value="ploscha_zagalna" <?php echo ($sort_by == 'ploscha_zagalna') ? 'selected' : ''; ?>>Площа</option>
                <option value="rieltor_pib" <?php echo ($sort_by == 'rieltor_pib') ? 'selected' : ''; ?>>Рієлтор</option>
                <option value="id_objekt" <?php echo ($sort_by == 'id_objekt') ? 'selected' : ''; ?>>ID</option>
            </select>
            <select id="sort_order" name="sort_order" style="padding: 5px;">
                <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>За зростанням</option>
                <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>За спаданням</option>
            </select>
        </div>

        <div>
            <button type="submit" style="padding: 8px 15px; background-color: #007bff; color: white; border: none;">Фільтр/Сорт</button>
            <a href="property_list.php" style="padding: 8px 15px; background-color: #6c757d; color: white; border-radius: 4px; text-decoration: none;">Скинути</a>
        </div>
    </form>

    <p><a href="../add_edit_delete/add_object.php">Додати новий об'єкт</a></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Адреса</th>
                <th>Площа (м²)</th>
                <th>Ціна</th>
                <th>Статус</th>
                <th>Тип</th>
                <th>Рієлтор</th>
                <th>Дія</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id_objekt'] . "</td>";
                echo "<td>" . htmlspecialchars($row['adresa']) . "</td>";
                echo "<td>" . $row['ploscha_zagalna'] . "</td>";
                echo "<td>" . $row['cina'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "<td>" . $row['tip_objekta'] . "</td>";
                echo "<td>" . htmlspecialchars($row['rieltor_pib']) . "</td>";
                echo "<td>";
                echo "<a href='../add_edit_delete/edit_object.php?id=" . $row['id_objekt'] . "'>Редагувати</a> | ";
                echo "<a href='../add_edit_delete/data_handler.php?action=delete&id=" . $row['id_objekt'] . "' onclick='return confirm(\"Видалити?\")'>Видалити</a>";
                echo "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8'>Немає даних, що відповідають умовам пошуку/фільтрації.</td></tr>";
        }
        ?>
        
        </tbody>
    </table>

    <?php
    mysqli_close($link);
    ?>

</body>
</html>