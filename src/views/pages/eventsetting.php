<?php
session_start();

use db\database;
use modules\User;
use modules\Event;

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header('Location: /auth');
    exit;
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$eventId) {
    header('HTTP/1.0 404 Not Found');
    die('Мероприятие не найдено');
}

if (!database::$pdo) {
    database::connect();
}

if ($_SESSION['user_role'] != 3) {
    die('Доступ запрещён');
}

$event = Event::getEvent($eventId);
if (!$event) {
    header('HTTP/1.0 404 Not Found');
    die('Мероприятие не найдено');
}

$participants = Event::getParticipants($eventId);

$coefs = database::$pdo->query("SELECT coef_ID, coef_Value, coef_Difficult FROM coef")->fetchAll();
$statuses = database::$pdo->query("SELECT status_ID, status_Type FROM status_event")->fetchAll();
$categories = database::$pdo->query("SELECT category_ID, category_Type FROM category")->fetchAll();
$bonuses = database::$pdo->query("SELECT bonus_ID, bonus_Name FROM bonuses")->fetchAll();
$participationStatuses = database::$pdo->query("SELECT status_ID, status_Type FROM status_participation")->fetchAll();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_event'])) {
        $oldStatus = $event['event_Status'];
        $data = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description']),
            'date_start' => $_POST['date_start'],
            'date_end' => $_POST['date_end'],
            'points' => (int)$_POST['points'],
            'coef_id' => (int)$_POST['coef_id'],
            'status_id' => (int)$_POST['status_id'],
            'category_id' => (int)$_POST['category_id'],
            'bonus_id' => !empty($_POST['bonus_id']) ? (int)$_POST['bonus_id'] : null
        ];
        if (Event::updateEvent($eventId, $data)) {
            $message = 'Мероприятие обновлено';
            $event = Event::getEvent($eventId);
            if ($data['status_id'] == 4 && $oldStatus != 4) {
                $participants = Event::getParticipants($eventId);
                foreach ($participants as $p) {
                    if ($p['status_ID'] == 1) {
                        User::recalculateLevel($p['participant_UserID']);
                    }
                }
                $message .= '. Баллы начислены участникам.';
                User::updateOrganizerMostCommonBonus($event['event_Organizer']);
            }
        } else {
            $error = 'Ошибка обновления';
        }
    } elseif (isset($_POST['update_status'])) {
        $participationId = (int)$_POST['participation_id'];
        $newStatusId = (int)$_POST['new_status'];
        $participantData = null;
        foreach ($participants as $p) {
            if ($p['participation_ID'] == $participationId) {
                $participantData = $p;
                break;
            }
        }
        Event::updateParticipationStatus($participationId, $newStatusId);
        $participants = Event::getParticipants($eventId);
        $message = 'Статус участника обновлён';

        if ($newStatusId == 1 && $event['event_Status'] == 4 && $participantData) {
            User::recalculateLevel($participantData['participant_UserID']);
            $message .= '. Баллы начислены.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление мероприятием | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <link href="/assets/css/eventsetting.css" rel="stylesheet">
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
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <h1 class="h4 mb-3">Управление мероприятием: <?= htmlspecialchars($event['event_Title']) ?></h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Редактирование мероприятия</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="event_form">
                            <div class="mb-3">
                                <label class="form-label">Название</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['event_Title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Описание</label>
                                <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($event['event_Description']) ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Дата начала</label>
                                    <input type="datetime-local" name="date_start" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['event_DateTimeStart'])) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Дата окончания</label>
                                    <input type="datetime-local" name="date_end" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['event_DateTimeEnd'])) ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Баллы за участие</label>
                                    <input type="number" name="points" class="form-control" value="<?= $event['event_Points'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Коэффициент сложности</label>
                                    <select name="coef_id" class="form-select">
                                        <?php foreach ($coefs as $c): ?>
                                            <option value="<?= $c['coef_ID'] ?>" <?= $c['coef_ID'] == $event['event_Coef'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['coef_Difficult']) ?> (<?= $c['coef_Value'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Статус мероприятия</label>
                                    <select name="status_id" class="form-select">
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= $s['status_ID'] ?>" <?= $s['status_ID'] == $event['event_Status'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['status_Type']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Категория</label>
                                    <select name="category_id" class="form-select">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['category_ID'] ?>" <?= $cat['category_ID'] == $event['event_Category'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['category_Type']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Бонус (приз)</label>
                                <select name="bonus_id" id="bonus_select" class="form-select">
                                    <option value="">Без бонуса</option>
                                    <?php foreach ($bonuses as $b): ?>
                                        <option value="<?= $b['bonus_ID'] ?>" <?= $event['event_Bonuses'] == $b['bonus_ID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b['bonus_Name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="new">- Добавить новый бонус -</option>
                                </select>
                                <div id="new_bonus_field" class="new-bonus-field">
                                    <input type="text" id="new_bonus_name" class="form-control" placeholder="Название бонуса">
                                    <button type="button" id="create_bonus_btn" class="btn btn-sm btn-outline-primary mt-2">Создать бонус</button>
                                    <div id="new_bonus_error" class="text-danger mt-1" style="font-size: 0.875rem;"></div>
                                </div>
                            </div>
                            <button type="submit" name="update_event" class="btn btn-primary">Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Участники мероприятия</h2>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($participants)): ?>
                            <p class="text-muted p-3">Нет зарегистрированных участников.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($participants as $p): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <a href="/profile?id=<?= $p['participant_UserID'] ?>" class="text-dark text-decoration-none fw-bold">
                                                    <?= htmlspecialchars($p['participant_FullName']) ?>
                                                </a>
                                                <br>
                                                <small><?= htmlspecialchars($p['participant_City']) ?>, <?= $p['participant_Age'] ?> лет</small>
                                            </div>
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="participation_id" value="<?= $p['participation_ID'] ?>">
                                                <select name="new_status" class="form-select form-select-sm" style="width: auto;">
                                                    <?php foreach ($participationStatuses as $ps): ?>
                                                        <option value="<?= $ps['status_ID'] ?>" <?= $ps['status_ID'] == $p['status_ID'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($ps['status_Type']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-outline-secondary">Обновить</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">© 2026 Платформа "Движение молодежи"</p>
        </div>
    </footer>
    <script src="/assets/js/eventsetting.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>