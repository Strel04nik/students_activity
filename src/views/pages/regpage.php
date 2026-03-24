<?php
session_start();
use db\database;

if (isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

if (!database::$pdo) {
    database::connect();
}

$sqlCategories = "SELECT category_ID, category_Type FROM category";
$stmt = database::$pdo->prepare($sqlCategories);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация участника</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">Движение молодежи</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/events">Мероприятия</a></li>
                    <li class="nav-item"><a class="nav-link" href="/rating">Рейтинг</a></li>
                    <li class="nav-item"><a class="nav-link" href="/activity">Активность</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <div class="form-container">
            <h2 class="h4 mb-3">Регистрация участника</h2>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php
                    $error = $_GET['error'];
                    $messages = [
                        'empty_fields' => 'Пожалуйста, заполните все поля.',
                        'invalid_name' => 'Введите полное имя (фамилию и имя).',
                        'invalid_age' => 'Возраст должен быть от 14 до 100 лет.',
                        'user_exists' => 'Пользователь с таким логином уже существует.',
                        'registration_failed' => 'Ошибка регистрации. Попробуйте позже.',
                    ];
                    echo htmlspecialchars($messages[$error] ?? 'Неизвестная ошибка.');
                    ?>
                </div>
            <?php endif; ?>

            <form action="/register" method="POST">
                <div class="mb-3">
                    <label for="login" class="form-label">Логин</label>
                    <input type="text" class="form-control" id="login" name="login" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="fullname" class="form-label">ФИО</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Иванов Иван Иванович" required>
                </div>
                <div class="mb-3">
                    <label for="age" class="form-label">Возраст</label>
                    <input type="number" class="form-control" id="age" name="age" min="14" max="100" required>
                </div>
                <div class="mb-3">
                    <label for="city" class="form-label">Город</label>
                    <input type="text" class="form-control" id="city" name="city" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Категория</label>
                    <select class="form-select" id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_ID'] ?>"><?= htmlspecialchars($cat['category_Type']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
            </form>
            <p class="mt-3 text-center">
                Уже есть аккаунт? <a href="/auth">Войдите</a>
            </p>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">© 2026 Платформа "Движение молодежи"</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/reg.js"></script>
</body>
</html>