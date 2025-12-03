<?php
// client_list.php - Стилізований список клієнтів
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

require_once '../db_config.php';

$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
if ($link === false) { die("Помилка підключення: " . mysqli_connect_error()); }

$search_where = "";
$filter_where = "";
$sort_order_clause = "";

// --- ЛОГІКА ЗАПИТІВ ---
if (isset($_GET['search_term']) && $_GET['search_term'] !== '') {
    $term = mysqli_real_escape_string($link, $_GET['search_term']);
    $field = mysqli_real_escape_string($link, $_GET['search_field']);
    $allowed_fields = ['pib', 'telefon']; 
    if (in_array($field, $allowed_fields)) {
        $search_where = "WHERE K.{$field} LIKE '%{$term}%'";
    }
}

if (isset($_GET['filter_type']) && $_GET['filter_type'] !== '' && $_GET['filter_type'] !== 'all') {
    $type = mysqli_real_escape_string($link, $_GET['filter_type']);
    $prefix = ($search_where === "") ? "WHERE" : "AND";
    $filter_where = "{$prefix} K.tip_klienta = '{$type}'";
}

$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($link, $_GET['sort_by']) : 'id_klient';
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
$sort_order_clause = "ORDER BY K.{$sort_by} {$sort_order}";

$sql_select = "SELECT K.id_klient, K.pib, K.telefon, K.tip_klienta, K.pasportni_dani 
               FROM KLIENT K {$search_where} {$filter_where} {$sort_order_clause}";

$result = mysqli_query($link, $sql_select);

// Функція сортування
function sort_link($field, $label, $current_by, $current_order) {
    $new_order = ($current_by === $field && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $icon = ($current_by === $field) ? ($current_order === 'ASC' ? ' ▲' : ' ▼') : '';
    $params = $_GET;
    $params['sort_by'] = $field;
    $params['sort_order'] = $new_order;
    $query_string = http_build_query($params);
    return "<a href='client_list.php?{$query_string}' class='sort-link'>{$label}{$icon}</a>";
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Клієнти | Панель управління</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        h1 { margin: 0; color: #2c3e50; font-size: 28px; }
        .back-link { text-decoration: none; color: #6c757d; font-weight: 500; padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px; transition: all 0.2s; }
        .back-link:hover { background-color: #e9ecef; color: #333; }
        .filter-panel { background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; margin-bottom: 25px; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 13px; font-weight: 600; color: #555; text-transform: uppercase; }
        select, input[type="text"] { padding: 10px; border: 1px solid #ced4da; border-radius: 5px; font-size: 14px; min-width: 150px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: 500; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; margin-right: 5px; }
        .btn-edit { background-color: #ffc107; color: #212529; }
        .btn-delete { background-color: #dc3545; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background-color: #343a40; color: white; }
        th { padding: 15px; text-align: left; font-weight: 500; }
        td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; vertical-align: middle; }
        tbody tr:nth-of-type(even) { background-color: #f8f9fa; }
        tbody tr:hover { background-color: #e9ecef; }
        .sort-link { color: white; text-decoration: none; display: block; width: 100%; height: 100%; }
        .sort-link:hover { color: #ffc107; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; text-transform: uppercase;}
        .type-buyer { background-color: #17a2b8; } /* Блакитний */
        .type-renter { background-color: #6610f2; } /* Фіолетовий */
    </style>
</head>
<body>
    <div class="container">
        <div class="header-row">
            <h1>👥 Клієнти</h1>
            <a href="../index.php" class="back-link">← На головну</a>
        </div>

        <form method="get" action="client_list.php" class="filter-panel">
            <div class="filter-group">
                <label>Пошук</label>
                <div style="display: flex; gap: 5px;">
                    <select name="search_field" style="width: 120px;">
                        <option value="pib" <?= (isset($_GET['search_field']) && $_GET['search_field'] == 'pib') ? 'selected' : '' ?>>ПІБ</option>
                        <option value="telefon" <?= (isset($_GET['search_field']) && $_GET['search_field'] == 'telefon') ? 'selected' : '' ?>>Телефон</option>
                    </select>
                    <input type="text" name="search_term" placeholder="Введіть текст..." value="<?= isset($_GET['search_term']) ? htmlspecialchars($_GET['search_term']) : '' ?>">
                </div>
            </div>
            <div class="filter-group">
                <label>Тип клієнта</label>
                <select name="filter_type">
                    <option value="all">Усі</option>
                    <option value="Покупець" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'Покупець') ? 'selected' : '' ?>>Покупець</option>
                    <option value="Орендар" <?= (isset($_GET['filter_type']) && $_GET['filter_type'] == 'Орендар') ? 'selected' : '' ?>>Орендар</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">🔍 Знайти</button>
                <a href="client_list.php" class="btn btn-secondary">Скинути</a>
            </div>
        </form>

        <?php if ($is_admin): ?>
            <div style="margin-bottom: 20px;">
                <a href="../add_edit_delete/add_client.php" class="btn btn-success">+ Додати Клієнта</a>
            </div>
        <?php endif; ?>

        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th width="5%"><?= sort_link('id_klient', 'ID', $sort_by, $sort_order) ?></th>
                        <th width="30%"><?= sort_link('pib', 'ПІБ', $sort_by, $sort_order) ?></th>
                        <th width="15%"><?= sort_link('telefon', 'Телефон', $sort_by, $sort_order) ?></th>
                        <th width="15%"><?= sort_link('tip_klienta', 'Тип', $sort_by, $sort_order) ?></th>
                        <th width="20%">Паспортні дані</th>
                        <?php if ($is_admin): ?> <th width="15%">Дії</th> <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $badgeClass = ($row['tip_klienta'] == 'Покупець') ? 'type-buyer' : 'type-renter';
                        echo "<tr>";
                        echo "<td>#" . $row['id_klient'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['pib']) . "</td>";
                        echo "<td>" . $row['telefon'] . "</td>";
                        echo "<td><span class='badge {$badgeClass}'>" . $row['tip_klienta'] . "</span></td>";
                        echo "<td>" . htmlspecialchars($row['pasportni_dani']) . "</td>";
                        if ($is_admin) {
                            echo "<td>";
                            echo "<a href='../add_edit_delete/edit_client.php?id=" . $row['id_klient'] . "' class='btn btn-sm btn-edit'>✎</a>";
                            echo "<a href='../add_edit_delete/data_handler.php?action=delete_client&id=" . $row['id_klient'] . "' class='btn btn-sm btn-delete' onclick='return confirm(\"Видалити?\")'>🗑</a>";
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='" . ($is_admin ? 6 : 5) . "' style='text-align:center; padding:30px; color:#888;'>Немає записів.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php mysqli_close($link); ?>
</body>
</html>