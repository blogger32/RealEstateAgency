<?php
// index.php - Фінальна версія дизайну
session_start();

// 1. Перевірка: чи користувач залогінений
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
require_once 'db_config.php';
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Агенство з Нерухомості | Головна</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --bg-color: #f4f7fa;      /* Світло-сірий фон сторінки */
            --card-bg: #ffffff;       /* Білий фон карток */
            --text-dark: #333e48;     /* Темний текст */
            
            /* Нові кольори кнопок */
            --btn-blue: #0d6efd;      /* Яскравий синій (як "Знайти") */
            --btn-blue-hover: #0b5ed7;
            
            --btn-red: #dc3545;       /* Червоний для виходу */
            --btn-red-hover: #bb2d3b;
            
            --btn-dark: #212529;      /* Темний для Адміна (як шапка таблиці) */
            --btn-dark-hover: #424649;
        }

        body { 
            font-family: 'Roboto', Arial, sans-serif; 
            background-color: var(--bg-color);
            margin: 0; 
            padding: 20px;
            color: #555;
            min-height: 100vh;
        }

        /* Контейнер */
        .main-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* Тінь трохи чіткіша */
            padding: 40px;
            max-width: 1100px;
            margin: 0 auto; 
        }
        
        /* Хедер */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-panel {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info {
            text-align: right;
            font-size: 14px;
            line-height: 1.4;
        }
        .user-info b { color: var(--text-dark); font-size: 15px; }

        /* Кнопка ВИЙТИ (Червона) */
        .logout-btn {
            background-color: var(--btn-red);
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .logout-btn:hover { background-color: var(--btn-red-hover); }

        /* Секція Адміна */
        .admin-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        /* Кнопка АДМІНА (Темна) */
        .admin-btn {
            background-color: var(--btn-dark);
            color: #fff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 15px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .admin-btn:hover { 
            background-color: var(--btn-dark-hover); 
            transform: translateY(-1px);
        }

        /* Навігація */
        .section-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        /* Кнопки НАВІГАЦІЇ (Сині) */
        .nav-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            text-decoration: none;
            
            /* Синій фон - білий текст */
            background-color: var(--btn-blue);
            color: white;
            
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.2);
        }
        
        .nav-button i {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.9;
        }

        .nav-button:hover {
            background-color: var(--btn-blue-hover);
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(13, 110, 253, 0.3);
        }

        /* Кнопка Рейтингу (Зелена - для акценту, або можна залишити синьою) */
        .rating-btn {
            background-color: #198754; /* Зелений Bootstrap */
            box-shadow: 0 4px 6px rgba(25, 135, 84, 0.2);
        }
        .rating-btn:hover {
            background-color: #157347;
            box-shadow: 0 8px 15px rgba(25, 135, 84, 0.3);
        }

    </style>
</head>
<body>

    <div class="main-card">
        
        <div class="card-header">
            <div class="page-title">
                <i class="fa-solid fa-house-chimney" style="color: var(--btn-blue);"></i> 
                Агентство Нерухомості
            </div>
            
            <div class="user-panel">
                <div class="user-info">
                    Привіт, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b><br>
                    <span style="color: #888;"><?php echo htmlspecialchars($_SESSION["role"]); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i> Вийти
                </a>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <div class="admin-section">
                <a href="admin_register.php" class="admin-btn">
                    <i class="fa-solid fa-user-plus"></i> Додати нового користувача
                </a>
            </div>
        <?php endif; ?>

        <div class="section-label">Керування системою</div>
        
        <div class="nav-grid">
            <a href="lists/client_list.php" class="nav-button">
                <i class="fa-solid fa-users"></i>
                Клієнти
            </a>
            
            <a href="lists/property_list.php" class="nav-button">
                <i class="fa-solid fa-city"></i>
                Нерухомість
            </a>
            
            <a href="lists/realtor_list.php" class="nav-button">
                <i class="fa-solid fa-user-tie"></i>
                Рієлтори
            </a>
            
            <a href="lists/deal_list.php" class="nav-button">
                <i class="fa-solid fa-file-contract"></i>
                Угоди
            </a>
            
            <a href="lists/owner_list.php" class="nav-button">
                <i class="fa-solid fa-key"></i>
                Власники
            </a>
        </div>

        <div class="section-label" style="margin-top: 30px;">Звіти та аналітика</div>
        <div class="nav-grid">
            <a href="rating.php" class="nav-button rating-btn">
                <i class="fa-solid fa-chart-line"></i>
                Рейтинг Ефективності
            </a>
            </div>

    </div>

</body>
</html>