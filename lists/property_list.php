<?php
// property_list.php - Список об'єктів (Виправлено сортування та пошук)
session_start();

// 1. Перевірка авторизації
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

require_once '../db_config.php';

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) { die("Помилка підключення: " . mysqli_connect_error()); }

$search_where = "";
$price_filter = ""; 
$sort_order_clause = "";

// --- 1. ЛОГІКА ПОШУКУ ---
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

// --- 2. ЛОГІКА ФІЛЬТРАЦІЇ ЦІНИ ---
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

if ($min_price !== null || $max_price !== null) {
    $prefix = ($search_where === "") ? "WHERE" : "AND";
    if ($min_price !== null) {
        $price_filter .= " {$prefix} O.cina >= {$min_price}";
        $prefix = "AND";
    }
    if ($max_price !== null) {
        $price_filter .= " {$prefix} O.cina <= {$max_price}";
    }
}

// --- 3. ЛОГІКА СОРТУВАННЯ ---
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($link, $_GET['sort_by']) : 'id_objekt';
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'DESC' ? 'DESC' : 'ASC';

if ($sort_by === 'rieltor_pib') {
    $sort_order_clause = "ORDER BY R.pib {$sort_order}";
} else {
    $sort_field_prefix = (in_array($sort_by, ['id_objekt', 'adresa', 'ploscha_zagalna', 'cina', 'status', 'tip_objekta'])) ? 'O.' : 'O.';
    $sort_order_clause = "ORDER BY {$sort_field_prefix}{$sort_by} {$sort_order}";
}

// --- ВИКОНАННЯ ЗАПИТУ ---
$sql_select = "
    SELECT O.id_objekt, O.adresa, O.ploscha_zagalna, O.cina, O.status, O.tip_objekta, R.pib AS rieltor_pib
    FROM OBJEKT O
    JOIN RIELTOR R ON O.id_rieltor = R.id_rieltor
    {$search_where} {$price_filter} {$sort_order_clause}
";

$result = mysqli_query($link, $sql_select);

// --- ФУНКЦІЯ ДЛЯ ГЕНЕРАЦІЇ ПОСИЛАНЬ СОРТУВАННЯ ---
function sort_link($field, $label, $current_by, $current_order) {
    // Визначаємо новий напрямок: якщо поле співпадає, міняємо ASC <-> DESC
    $new_order = ($current_by === $field && $current_order === 'ASC') ? 'DESC' : 'ASC';
    
    // Іконка стрілочки
    $icon = '';
    if ($current_by === $field) {
        $icon = ($current_order === 'ASC') ? ' ▲' : ' ▼';
    }

    // Зберігаємо поточні параметри пошуку (щоб не збивались при сортуванні)
    $params = $_GET;
    $params['sort_by'] = $field;
    $params['sort_order'] = $new_order;
    
    // Будуємо URL
    $query_string = http_build_query($params);
    
    return "<a href='property_list.php?{$query_string}' class='sort-link'>{$label}{$icon}</a>";
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Нерухомість | Панель управління</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        h1 { margin: 0; color: #2c3e50; font-size: 28px; }
        .back-link { text-decoration: none; color: #6c757d; font-weight: 500; padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px; transition: all 0.2s; }
        .back-link:hover { background-color: #e9ecef; color: #333; }

        /* Стилі фільтрів */
        .filter-panel { background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; margin-bottom: 25px; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 13px; font-weight: 600; color: #555; text-transform: uppercase; }
        select, input[type="text"], input[type="number"] { padding: 10px; border: 1px solid #ced4da; border-radius: 5px; font-size: 14px; }
        
        /* Кнопки */
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 500; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; margin-right: 5px; }
        .btn-edit { background-color: #ffc107; color: #212529; }
        .btn-delete { background-color: #dc3545; color: white; }

        /* Таблиця */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background-color: #343a40; color: white; }
        th { padding: 15px; text-align: left; font-weight: 500; }
        td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        tbody tr:nth-of-type(even) { background-color: #f8f9fa; }
        tbody tr:hover { background-color: #e9ecef; }

        /* Посилання сортування в заголовках */
        .sort-link { color: white; text-decoration: none; display: block; width: 100%; height: 100%; }
        .sort-link:hover { color: #ffc107; }

        /* Статуси */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; text-transform: uppercase;}
        .status-active { background-color: #28a745; }
        .status-sold { background-color: #dc3545; }
        .status-rented { background-color: #17a2b8; }
        .status-reserved { background-color: #ffc107; color: black; }
        .price-tag { font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-row">
            <h1>🏠 Нерухомість</h1>
            <a href="../index.php" class="back-link">← На головну</a>
        </div>

        <form method="get" action="property_list.php" class="filter-panel">
            
            <div class="filter-group">
                <label>Пошук за атрибутом</label>
                <div style="display: flex; gap: 5px;">
                    <select name="search_field" style="width: 130px;">
                        <option value="adresa" <?= (isset($_GET['search_field']) && $_GET['search_field'] == 'adresa') ? 'selected' : '' ?>>Адреса</option>
                        <option value="tip_objekta" <?= (isset($_GET['search_field']) && $_GET['search_field'] == 'tip_objekta') ? 'selected' : '' ?>>Тип</option>
                        <option value="status" <?= (isset($_GET['search_field']) && $_GET['search_field'] == 'status') ? 'selected' : '' ?>>Статус</option>
                        <option value="rieltor_pib" <?= (isset($_GET['search_field']) && $_GET['search_field'] == 'rieltor_pib') ? 'selected' : '' ?>>Рієлтор</option>
                    </select>
                    <input type="text" name="search_term" placeholder="Введіть текст..." 
                           value="<?= isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : '' ?>">
                </div>
            </div>

            <div class="filter-group">
                <label>Ціна (USD)</label>
                <div style="display: flex; gap: 5px; align-items: center;">
                    <input type="number" name="min_price" placeholder="Від" style="width: 80px;"
                           value="<?= isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : '' ?>">
                    <span>-</span>
                    <input type="number" name="max_price" placeholder="До" style="width: 80px;"
                           value="<?= isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : '' ?>">
                </div>
            </div>

            <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sort_order) ?>">

            <div>
                <button type="submit" class="btn btn-primary">🔍 Знайти</button>
                <a href="property_list.php" class="btn btn-secondary">Скинути</a>
            </div>
        </form>

        <?php if ($is_admin): ?>
            <div style="margin-bottom: 20px;">
                <a href="../add_edit_delete/add_object.php" class="btn btn-success">+ Додати Об'єкт</a>
            </div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th width="5%"><?= sort_link('id_objekt', 'ID', $sort_by, $sort_order) ?></th>
                        <th width="25%"><?= sort_link('adresa', 'Адреса', $sort_by, $sort_order) ?></th>
                        <th width="10%"><?= sort_link('ploscha_zagalna', 'Площа', $sort_by, $sort_order) ?></th>
                        <th width="15%"><?= sort_link('cina', 'Ціна', $sort_by, $sort_order) ?></th>
                        <th width="10%"><?= sort_link('status', 'Статус', $sort_by, $sort_order) ?></th>
                        <th width="10%"><?= sort_link('tip_objekta', 'Тип', $sort_by, $sort_order) ?></th>
                        <th width="15%"><?= sort_link('rieltor_pib', 'Рієлтор', $sort_by, $sort_order) ?></th>
                        <?php if ($is_admin): ?> <th width="10%">Дії</th> <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $statusClass = 'badge';
                        switch($row['status']) {
                            case 'Активний': $statusClass .= ' status-active'; break;
                            case 'Продано': $statusClass .= ' status-sold'; break;
                            case 'Здано': $statusClass .= ' status-rented'; break;
                            default: $statusClass .= ' status-reserved';
                        }

                        echo "<tr>";
                        echo "<td>#" . $row['id_objekt'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['adresa']) . "</td>";
                        echo "<td>" . $row['ploscha_zagalna'] . " м²</td>";
                        echo "<td class='price-tag'>" . number_format($row['cina'], 0, '.', ' ') . " $</td>";
                        echo "<td><span class='{$statusClass}'>" . $row['status'] . "</span></td>";
                        echo "<td>" . $row['tip_objekta'] . "</td>";
                        echo "<td style='font-size: 0.9em; color: #555;'>" . htmlspecialchars($row['rieltor_pib']) . "</td>";
                        
                        if ($is_admin) {
                            echo "<td>";
                            echo "<a href='../add_edit_delete/edit_object.php?id=" . $row['id_objekt'] . "' class='btn btn-sm btn-edit' title='Редагувати'>✎</a>";
                            echo "<a href='../add_edit_delete/data_handler.php?action=delete_object&id=" . $row['id_objekt'] . "' class='btn btn-sm btn-delete' onclick='return confirm(\"Видалити цей об\'єкт?\")' title='Видалити'>🗑</a>";
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    $cols = $is_admin ? 8 : 7;
                    echo "<tr><td colspan='{$cols}' style='text-align:center; padding: 30px; color: #888;'>Немає записів.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php mysqli_close($link); ?>

</body>
</html>