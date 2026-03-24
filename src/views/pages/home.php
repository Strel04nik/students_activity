<?php
session_start();

use db\database;

if (!database::$pdo) {
    database::connect();
}

$sqlEvents = "SELECT e.event_ID, e.event_Title, e.event_Description, 
                     e.event_DateTimeStart, e.event_DateTimeEnd, e.event_Points,
                     c.coef_Value, c.coef_Difficult,
                     s.status_Type
              FROM events e
              JOIN status_event s ON e.event_Status = s.status_ID
              JOIN coef c ON e.event_Coef = c.coef_ID
              WHERE e.event_Status IN (1, 2, 3)
              ORDER BY e.event_DateTimeStart ASC";
$stmtEvents = database::$pdo->prepare($sqlEvents);
$stmtEvents->execute();
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

$sqlTop = "SELECT pl.participant_UserID, pp.participant_FullName, pl.participant_TotalScore,
                  cat.category_Type
           FROM participant_levelsrate pl
           JOIN participant_profiles pp ON pl.participant_UserID = pp.participant_UserID
           JOIN category cat ON pp.participant_category = cat.category_ID
           ORDER BY pl.participant_TotalScore DESC
           LIMIT 10";
$stmtTop = database::$pdo->prepare($sqlTop);
$stmtTop->execute();
$topParticipants = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Платформа "Движение молодежи"</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
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
            <h2 class="h4 mb-3">Доступные мероприятия</h2>
            <div class="col-lg-8">
                <?php if (empty($events)): ?>
                    <p class="text-muted">Нет доступных мероприятий.</p>
                <?php else: ?>
                    <div class="row g-3" id="events-container">
                        <?php foreach ($events as $event):
                            $statusClass = match ($event['status_Type']) {
                                'Запланировано' => 'bg-secondary',
                                'Регистрация'   => 'bg-primary',
                                'Старт'         => 'bg-success',
                                default         => 'bg-secondary'
                            };
                            $points = $event['event_Points'] * $event['coef_Value'];
                            $tooltip = "Баллы: {$event['event_Points']} баллов × {$event['coef_Value']} (коэффициент за сложность) = " . round($points, 2) . " баллов";
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="/event?id=<?= $event['event_ID'] ?>" class="text-dark text-decoration-none">
                                                <?= htmlspecialchars($event['event_Title']) ?>
                                            </a>
                                            <small class="text-muted ms-2" style="font-size: 0.75rem;">
                                                (сложность: <?= htmlspecialchars($event['coef_Difficult']) ?>)
                                            </small>
                                        </h5>
                                        <p class="card-text small text-muted">
                                            <?= date('d.m.Y H:i', strtotime($event['event_DateTimeStart'])) ?> –
                                            <?= date('d.m.Y H:i', strtotime($event['event_DateTimeEnd'])) ?>
                                        </p>
                                        <p class="card-text"><?= htmlspecialchars(mb_substr($event['event_Description'], 0, 100)) ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($event['status_Type']) ?></span>
                                            <span class="badge bg-light text-dark">
                                                +<?= round($event['event_Points'] * $event['coef_Value'], 2) ?> баллов
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">🏆 Топ-10 участников</h2>
                            <a href="/rating" class="small">Весь рейтинг →</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($topParticipants)): ?>
                            <p class="text-muted p-3">Нет данных.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php $rank = 1;
                                foreach ($topParticipants as $participant): ?>
                                    <div class="list-group-item top-item">
                                        <div class="participant-info">
                                            <span class="fw-bold me-2 ms-4"><?= $rank ?>.</span>
                                            <a href="/profile?id=<?= $participant['participant_UserID'] ?>" class="text-dark">
                                                <?= htmlspecialchars($participant['participant_FullName']) ?>
                                            </a>
                                            <span class="category-badge"><?= htmlspecialchars($participant['category_Type']) ?></span>
                                        </div>
                                        <span class="badge bg-primary rounded-pill me-4"><?= $participant['participant_TotalScore'] ?> баллов</span>
                                    </div>
                                <?php $rank++;
                                endforeach; ?>
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
    <script src="/assets/js/home.js"></script>
</body>

</html>