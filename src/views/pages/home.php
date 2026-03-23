<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <style>
        body {
            font-family: Arial;
            text-align: center;
            padding: 50px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .user-info {
            background: #e9ecef;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <h2>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_login']); ?>!</h2>
                <p>Роль: <?php echo $_SESSION['user_role'] == 3 ? 'Организатор' : 'Участник'; ?></p>
                <p>ID: <?php echo $_SESSION['user_id']; ?></p>
            </div>
            <form method="POST" action="/logout">
                <button type="submit" class="logout-btn">Выйти</button>
            </form>
        <?php else: ?>
            <h2>Вы не авторизованы</h2>
            <p><a href="/auth">Войти</a></p>
        <?php endif; ?>
    </div>
</body>
</html>