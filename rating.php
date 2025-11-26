
<?php
// Лістинг коду: rating.php - Завдання автоматизації
require_once 'db_config.php';

function generate_periods($start_year = 2023) {
    $periods = [];
    $current_year = date('Y');
    
    // 1. Додавання фільтрів за ЦІЛИЙ РІК
    for ($year = $start_year; $year <= $current_year; $year++) {
        // Визначення періоду для цілого року
        $start_date = "{$year}-01-01";
        $end_date = "{$year}-12-31";
        
        // Ключ формату "year_2023-01-01,2023-12-31"
        $periods["year_{$start_date},{$end_date}"] = "{$year} рік";
    }

    // 2. Додавання фільтрів за КВАРТАЛИ (існуючий функціонал)
    $current_quarter = ceil(date('n') / 3);

    for ($year = $start_year; $year <= $current_year; $year++) {
        for ($q = 1; $q <= 4; $q++) {
            
            $start_month = ($q - 1) * 3 + 1;
            $start_date = "{$year}-" . str_pad($start_month, 2, '0', STR_PAD_LEFT) . "-01";
            
            $end_month = $q * 3;
            $end_day = date('t', strtotime("{$year}-{$end_month}-01"));
            $end_date = "{$year}-" . str_pad($end_month, 2, '0', STR_PAD_LEFT) . "-{$end_day}";
            
            $quarter_label = "{$q} квартал {$year}";
            $quarter_value = "{$start_date},{$end_date}";
            
            // Обмеження: не генеруємо майбутні квартали
            if ($year < $current_year || ($year == $current_year && $q <= $current_quarter)) {
                 $periods[$quarter_value] = $quarter_label;
            }
        }
    }
    return $periods;
}

$available_periods = generate_periods();




// Підключення до БД (з урахуванням порту 3307)
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);

if ($link === false) {
    die("Помилка підключення: " . mysqli_connect_error());
}

$start_date = '2023-01-01'; // Період за замовчуванням
$end_date = date('Y-m-d');  

if (isset($_GET['period'])) {
    $selected_period = mysqli_real_escape_string($link, $_GET['period']);
    
    // Якщо вибрано річний або квартальний період
    $date_string = $selected_period;
    
    // Видаляємо префікс "year_" (якщо є) і розділяємо дати
    if (strpos($date_string, 'year_') === 0) {
        $date_string = substr($date_string, 5);
    }

    list($start_date, $end_date) = explode(',', $date_string);
}

// ----------------------------------------------------
// КРОК 1: Агрегація даних (Фінансовий Обсяг та Час Закриття)
// ----------------------------------------------------
$sql_rating_data = "
SELECT 
    R.id_rieltor,
    R.pib,
    -- 1. Розрахунок Фінансового Обсягу (ФО)
    SUM(U.suma_ugody) AS financial_volume,
    -- 2. Розрахунок Середнього Часу Закриття (Т_середній)
    AVG(DATEDIFF(U.data_ukladennja, O.data_dodavannja)) AS avg_closing_time
FROM 
    RIELTOR R
JOIN 
    UGODA U ON R.id_rieltor = U.id_rieltor
JOIN 
    OBJEKT O ON U.id_objekt = O.id_objekt
WHERE 
    U.data_ukladennja BETWEEN '{$start_date}' AND '{$end_date}'
GROUP BY 
    R.id_rieltor, R.pib
ORDER BY 
    financial_volume DESC;
";

$result = mysqli_query($link, $sql_rating_data);

$ratings = [];
$max_fo = 0; // Максимальний Фінансовий Обсяг
$min_time = PHP_INT_MAX; // Мінімальний час закриття
$data_available = false;

if (mysqli_num_rows($result) > 0) {
    $data_available = true;
    while ($row = mysqli_fetch_assoc($result)) {
        // Зберігаємо результати у масив
        $ratings[] = $row;
        
        // Визначаємо максимуми/мінімуми для нормалізації
        if ($row['financial_volume'] > $max_fo) {
            $max_fo = $row['financial_volume'];
        }
        if ($row['avg_closing_time'] > 0 && $row['avg_closing_time'] < $min_time) {
            $min_time = $row['avg_closing_time'];
        }
    }
}

// ----------------------------------------------------
// КРОК 2: Нормалізація та Обчислення Фінального Балу (PHP-логіка)
// ----------------------------------------------------
$final_ratings = [];
foreach ($ratings as $item) {
    $fo = (float)$item['financial_volume'];
    $time = (float)$item['avg_closing_time'];
    
    // 1. Нормалізація Фінансового Обсягу (НО_фо)
    $normalized_fo = ($max_fo > 0) ? ($fo / $max_fo) : 0;
    
    // 2. Нормалізація Часу Закриття (НО_т): менший час = кращий бал
    $normalized_time = ($time > 0) ? ($min_time / $time) : 0; 
    
    // 3. Фінальний Бал (Р = НО_фо * 0.7 + НО_т * 0.3)
    $final_score = ($normalized_fo * 0.7) + ($normalized_time * 0.3);
    
    $item['normalized_fo'] = round($normalized_fo, 3);
    $item['normalized_time'] = round($normalized_time, 3);
    $item['final_score'] = round($final_score, 3);
    
    $final_ratings[] = $item;
}

// Сортування фінального масиву за балом (високий бал — перший)
usort($final_ratings, function($a, $b) {
    return $b['final_score'] <=> $a['final_score'];
});

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Рейтинг Ефективності Рієлторів</title>
    <style>
        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 10px; text-align: center; }
        .best { background-color: #d4edda; font-weight: bold; }
    </style>
</head>
<body>

    <h1>🏆 Звіт: Рейтинг Ефективності Рієлторів</h1>
    <form method="get" action="rating.php">
        <label for="period">Вибрати Звітний Період:</label>
        <select id="period" name="period" onchange="this.form.submit()">
            <option value="2023-01-01,<?php echo date('Y-m-d'); ?>">Весь Період</option>
            <?php
            // Відображення згенерованих періодів
            foreach ($available_periods as $value => $label) {
                // Визначення, чи був обраний цей період
                $selected = (isset($_GET['period']) && $_GET['period'] == $value) ? 'selected' : '';
                echo "<option value='{$value}' {$selected}>{$label}</option>";
            }
            ?>
        </select>
        <noscript><button type="submit">Фільтрувати</button></noscript>
    </form>

    <p>Розрахунок за період: **<?php echo $start_date . ' — ' . $end_date; ?>**</p>

    <p>Розрахунок базується на Фінансовому Обсязі (70%) та Середньому Часі Закриття Угоди (30%).</p>

    <?php if ($data_available): ?>
    <table>
        <thead>
            <tr>
                <th>Місце</th>
                <th>ПІБ Рієлтора</th>
                <th>Фінансовий Обсяг (USD)</th>
                <th>Середній Час Закриття (Дні)</th>
                <th>Норм. Бал (ФО)</th>
                <th>Норм. Бал (Час)</th>
                <th>Фінальний Бал (Р)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rank = 1;
        foreach ($final_ratings as $item) {
            $class = ($rank == 1) ? 'class="best"' : '';
            echo "<tr {$class}>";
            echo "<td>" . $rank++ . "</td>";
            echo "<td>" . htmlspecialchars($item['pib']) . "</td>";
            echo "<td>" . number_format($item['financial_volume'], 2, '.', ' ') . "</td>";
            echo "<td>" . round($item['avg_closing_time'], 1) . "</td>";
            echo "<td>" . $item['normalized_fo'] . "</td>";
            echo "<td>" . $item['normalized_time'] . "</td>";
            echo "<td>" . $item['final_score'] . "</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Недостатньо даних для розрахунку рейтингу (немає укладених угод).</p>
    <?php endif; ?>

    <p><a href="index.php">Повернутися до головної</a></p>

</body>
</html>