<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .auth-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #5a67d8;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #f5c6cb;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Вход в систему</h1>
            <p>Платформа рейтинга активности</p>
        </div>
        
        <?php
        $errorMessage = '';
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'empty_fields':
                    $errorMessage = 'Пожалуйста, заполните все поля';
                    break;
                case 'invalid_credentials':
                    $errorMessage = 'Неверный логин или пароль';
                    break;
                case 'system_error':
                    $errorMessage = 'Системная ошибка. Попробуйте позже';
                    break;
                default:
                    $errorMessage = 'Ошибка авторизации';
            }
        }
        ?>
        
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/login">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" placeholder="Введите ваш логин" autofocus>
            </div>
            
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" placeholder="Введите ваш пароль">
            </div>
            
            <button type="submit">Войти</button>
        </form>
        
        <div class="register-link">
            <p>Нет аккаунта? <a href="/reg">Зарегистрироваться</a></p>
        </div>
    </div>
</body>
</html>