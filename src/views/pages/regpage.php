<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

global $pdo;
$categories = $pdo->query("SELECT * FROM category")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
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
            padding: 20px;
        }
        
        .reg-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }
        
        .reg-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reg-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .reg-header p {
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
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
            margin-top: 10px;
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
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="reg-container">
        <div class="reg-header">
            <h1>Регистрация участника</h1>
            <p>Заполните форму для регистрации</p>
        </div>
        
        <?php
        $errorMessage = '';
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'empty_fields':
                    $errorMessage = 'Пожалуйста, заполните все поля';
                    break;
                case 'invalid_name':
                    $errorMessage = 'Введите полное ФИО (например: Иванов Иван Иванович)';
                    break;
                case 'invalid_age':
                    $errorMessage = 'Возраст должен быть от 14 до 100 лет';
                    break;
                case 'user_exists':
                    $errorMessage = 'Пользователь с таким логином уже существует';
                    break;
                case 'registration_failed':
                    $errorMessage = 'Ошибка регистрации. Попробуйте позже';
                    break;
                default:
                    $errorMessage = 'Ошибка регистрации';
            }
        }
        ?>
        
        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/register">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" placeholder="Введите логин" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" placeholder="Введите пароль" required>
            </div>
            
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="fullname" placeholder="Иванов Иван Иванович" required>
                <div class="hint">Введите фамилию, имя и отчество через пробел</div>
            </div>
            
            <div class="form-group">
                <label>Возраст</label>
                <input type="number" name="age" placeholder="От 14 до 100 лет" min="14" max="100" required>
            </div>
            
            <div class="form-group">
                <label>Город проживания</label>
                <input type="text" name="city" placeholder="Введите ваш город" required>
            </div>
            
            <div class="form-group">
                <label>Категория</label>
                <select name="category" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_ID']; ?>">
                            <?php echo htmlspecialchars($cat['category_Type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <div class="login-link">
            <p>Уже есть аккаунт? <a href="/auth">Войти</a></p>
        </div>
    </div>
</body>
</html>