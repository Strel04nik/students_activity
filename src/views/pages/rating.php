<?php
session_start();
use db\database;
use modules\User;

if (!database::$pdo) {
    database::connect();
}

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$categories = User::getAllCategories();
$participants = User::getLeaderboard($categoryId, 100);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рейтинг участников | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <link href="/assets/css/rating.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">Движение молодежи</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/events">Мероприятия</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/rating">Рейтинг</a></li>
                    <li class="nav-item"><a class="nav-link" href="/activity">Активность</a></li>
                </ul>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="d-flex align-items-center">
                        <?php if ($_SESSION['user_role'] == 3): ?>
                            <a href="/admin" class="btn btn-outline-warning btn-sm me-2">Панель администратора</a>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_role'] >= 2): ?>
                            <a href="/hr-dashboard" class="btn btn-outline-info btn-sm me-2">Инспекция</a>
                        <?php endif; ?>
                        <a href="/profile" class="text-dark text-decoration-none me-2">
                            <?= htmlspecialchars($_SESSION['user_fullname'] ?? $_SESSION['user_login']) ?>
                        </a>
                        <form method="POST" action="/logout">
                            <button class="btn btn-outline-danger btn-sm">Выйти</button>
                        </form>
                    </div>
                <?php else: ?>
                    <a href="/auth" class="btn btn-outline-secondary btn-sm">Войти</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <h1 class="h3 mb-4">Рейтинг участников</h1>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Фильтр по категориям</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="/rating" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Категория</label>
                        <select name="category" class="form-select">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_ID'] ?>" <?= ($categoryId == $cat['category_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_Type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Применить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Топ-100 участников</h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($participants)): ?>
                    <p class="text-muted p-3">Нет данных.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                    <th>Место</th>
                                    <th>Участник</th>
                                    <th>Город</th>
                                    <th>Категория</th>
                                    <th>Уровень</th>
                                    <th>Баллы</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td class="fw-bold"><?= $p['rank'] ?></td>
                                    <td>
                                        <a href="/profile?id=<?= $p['user_ID'] ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($p['participant_FullName']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($p['participant_City']) ?></td>
                                    <td><?= htmlspecialchars($p['category_Type']) ?></td>
                                    <td><?= htmlspecialchars($p['level_name']) ?></td>
                                    <td class="fw-bold text-primary"><?= $p['participant_TotalScore'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">© 2026 Платформа "Движение молодежи"</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>