<?php
session_start();

use db\database;
use modules\Event;

if (!database::$pdo) {
    database::connect();
}

$statuses = Event::getEventStatuses();
$categories = Event::getCategories();
$difficulties = Event::getDifficulties();

$filters = [];
if (isset($_GET['status']) && $_GET['status'] !== '') $filters['status'] = (int)$_GET['status'];
if (isset($_GET['category']) && $_GET['category'] !== '') $filters['category'] = (int)$_GET['category'];
if (isset($_GET['difficulty']) && $_GET['difficulty'] !== '') $filters['difficulty'] = (int)$_GET['difficulty'];
if (isset($_GET['date_from']) && $_GET['date_from'] !== '') $filters['date_from'] = $_GET['date_from'];
if (isset($_GET['date_to']) && $_GET['date_to'] !== '') $filters['date_to'] = $_GET['date_to'];

$events = Event::getAllEvents($filters);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все мероприятия | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <style>
        .filter-card {
            margin-bottom: 20px;
        }

        .event-card-link {
            text-decoration: none;
            color: inherit;
        }

        .event-card-link:hover .card {
            transform: translateY(-2px);
            transition: transform 0.2s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            font-size: 0.75rem;
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
        <h1 class="h3 mb-4">Все мероприятия</h1>

        <!-- Форма фильтрации -->
        <div class="card filter-card shadow-sm">
            <div class="card-body">
                <form method="GET" action="/events" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Статус</label>
                        <select name="status" class="form-select">
                            <option value="">Все</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['status_ID'] ?>" <?= (isset($filters['status']) && $filters['status'] == $status['status_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['status_Type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Категория</label>
                        <select name="category" class="form-select">
                            <option value="">Все</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_ID'] ?>" <?= (isset($filters['category']) && $filters['category'] == $cat['category_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_Type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Сложность</label>
                        <select name="difficulty" class="form-select">
                            <option value="">Все</option>
                            <?php foreach ($difficulties as $diff): ?>
                                <option value="<?= $diff['coef_ID'] ?>" <?= (isset($filters['difficulty']) && $filters['difficulty'] == $diff['coef_ID']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($diff['coef_Difficult']) ?> (<?= $diff['coef_Value'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Дата от</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Дата до</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Применить</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Список мероприятий -->
        <?php if (empty($events)): ?>
            <div class="alert alert-info">Нет мероприятий, соответствующих фильтрам.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($events as $event):
                    $points = $event['event_Points'] * $event['coef_Value'];
                    $statusClass = match ($event['status_Type']) {
                        'Запланировано' => 'bg-secondary',
                        'Регистрация'   => 'bg-primary',
                        'Старт'         => 'bg-success',
                        'Завершено'     => 'bg-dark',
                        default         => 'bg-secondary'
                    };
                ?>
                    <div class="col-md-6 col-lg-4">
                        <a href="/event?id=<?= $event['event_ID'] ?>" class="event-card-link">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($event['event_Title']) ?></h5>
                                    <p class="card-text small text-muted">
                                        <?= date('d.m.Y H:i', strtotime($event['event_DateTimeStart'])) ?> –
                                        <?= date('d.m.Y H:i', strtotime($event['event_DateTimeEnd'])) ?>
                                    </p>
                                    <p class="card-text"><?= htmlspecialchars(mb_substr($event['event_Description'], 0, 80)) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge <?= $statusClass ?> badge-status"><?= htmlspecialchars($event['status_Type']) ?></span>
                                        <span class="badge bg-light text-dark">+<?= round($points, 2) ?> баллов</span>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">Категория: <?= htmlspecialchars($event['category_Type']) ?></small><br>
                                        <small class="text-muted">Сложность: <?= htmlspecialchars($event['coef_Difficult']) ?></small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">© 2026 Платформа "Движение молодежи"</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>