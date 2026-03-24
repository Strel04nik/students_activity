<?php
session_start();

use db\database;
use modules\Event;

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$eventId) {
    header('HTTP/1.0 404 Not Found');
    die('Мероприятие не найдено');
}

if (!database::$pdo) {
    database::connect();
}

$event = Event::getEvent($eventId);
if (!$event) {
    header('HTTP/1.0 404 Not Found');
    die('Мероприятие не найдено');
}

$canRegister = ($event['event_Status'] == 2);

$participants = [];
$canViewParticipants = false;
$isOrganizer = false;
$userParticipated = false;
$participantStatusId = null;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];

    $isOrganizer = ($userRole == 3);

    $stmt = database::$pdo->prepare("
        SELECT status_ID FROM event_participation
        WHERE event_ID = ? AND participant_ID = ?
    ");
    $stmt->execute([$eventId, $userId]);
    $userParticipation = $stmt->fetch();
    $userParticipated = (bool)$userParticipation;
    if ($userParticipated) {
        $participantStatusId = $userParticipation['status_ID'];
    }

    $canViewParticipants = ($isOrganizer || $userRole == 2);

    if ($canViewParticipants) {
        $participants = Event::getParticipants($eventId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_event'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if ($_SESSION['user_role'] != 1) {
        $error = 'Только участники могут регистрироваться на мероприятия';
    } elseif (!$canRegister) {
        $error = 'Регистрация на это мероприятие закрыта. (доступна только в статусе "Регистрация")';
    } else {
        $success = Event::registerParticipant($eventId, $_SESSION['user_id']);
        if ($success) {
            $message = 'Вы успешно зарегистрированы на мероприятие. Статус: "На рассмотрении"';
            $userParticipated = true;
            $participantStatusId = 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['event_Title']) ?> | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <style>
        .participant-list .list-group-item {
            border-left: none;
            border-right: none;
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
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h1 class="h3"><?= htmlspecialchars($event['event_Title']) ?></h1>
                        <p class="text-muted">
                            Организатор:
                            <a href="/profile?id=<?= $event['event_Organizer'] ?>">
                                <?= htmlspecialchars($event['organizer_fullname'] ?? $event['organizer_login']) ?>
                            </a>
                        </p>    
                        <div class="mb-2">
                            <span class="badge bg-secondary"><?= htmlspecialchars($event['status_Type']) ?></span>
                            <span class="badge bg-info text-dark ms-2">Сложность: <?= htmlspecialchars($event['coef_Difficult']) ?></span>
                            <?php if ($event['bonus_Name']): ?>
                                <span class="badge bg-warning text-dark ms-2">Бонус: <?= htmlspecialchars($event['bonus_Name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted">
                            <?= date('d.m.Y H:i', strtotime($event['event_DateTimeStart'])) ?> –
                            <?= date('d.m.Y H:i', strtotime($event['event_DateTimeEnd'])) ?>
                        </p>
                        <p><?= nl2br(htmlspecialchars($event['event_Description'])) ?></p>
                        <div class="alert alert-light">
                            <strong>Баллы за участие:</strong> <?= $event['event_Points'] ?> × <?= $event['coef_Value'] ?> =
                            <strong><?= $event['event_Points'] * $event['coef_Value'] ?></strong> баллов
                        </div>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info">
                                <a href="/auth?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="alert-link">Войдите</a>, чтобы зарегистрироваться на мероприятие.
                            </div>
                        <?php elseif ($_SESSION['user_role'] == 1 && !$userParticipated && $canRegister): ?>
                            <form method="POST">
                                <button type="submit" name="join_event" class="btn btn-primary">Записаться на мероприятие</button>
                            </form>
                        <?php elseif ($_SESSION['user_role'] == 1 && $userParticipated): ?>
                            <div class="alert alert-secondary">
                                Вы уже зарегистрированы.
                                <?php if ($participantStatusId == 2): ?>
                                    Статус: <span class="badge bg-warning text-dark">На рассмотрении</span>
                                <?php elseif ($participantStatusId == 1): ?>
                                    Статус: <span class="badge bg-success">Подтверждено</span>
                                <?php elseif ($participantStatusId == 3): ?>
                                    Статус: <span class="badge bg-danger">Отказано</span>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($_SESSION['user_role'] == 1 && !$canRegister && !$userParticipated): ?>
                            <div class="alert alert-warning">
                                Регистрация на это мероприятие закрыта (доступна только в статусе "Регистрация").
                            </div>
                        <?php endif; ?>

                        <?php if ($isOrganizer): ?>
                            <div class="mt-3">
                                <a href="/eventsetting?id=<?= $eventId ?>" class="btn btn-outline-warning">Управление мероприятием</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Участники мероприятия</h2>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!$canViewParticipants): ?>
                            <p class="text-muted p-3">Список участников доступен только организатору и наблюдателям.</p>
                        <?php elseif (empty($participants)): ?>
                            <p class="text-muted p-3">Пока нет зарегистрированных участников.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush participant-list">
                                <?php foreach ($participants as $p): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($p['participant_FullName']) ?></strong><br>
                                                <small><?= htmlspecialchars($p['participant_City']) ?>, <?= $p['participant_Age'] ?> лет</small>
                                            </div>
                                            <span class="badge <?= $p['status_ID'] == 1 ? 'bg-success' : ($p['status_ID'] == 2 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                                <?= htmlspecialchars($p['participation_status']) ?>
                                            </span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>