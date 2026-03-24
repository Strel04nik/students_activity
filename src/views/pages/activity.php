<?php
session_start();
use db\database;
use modules\User;

if (!database::$pdo) {
    database::connect();
}

$recentEvents = User::getRecentEvents(10);
$topTags = User::getTopTags();

$userRatingHistory = null;
$currentUserScore = null;
$currentUserRank = null;
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 1) {
    $userRatingHistory = User::getUserRatingHistory($_SESSION['user_id']);
    $rankData = User::getParticipantRank($_SESSION['user_id']);
    $currentUserScore = $rankData['score'];
    $currentUserRank = $rankData['rank'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Активность | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <link href="/assets/css/activity.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">Движение молодежи</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/events">Мероприятия</a></li>
                    <li class="nav-item"><a class="nav-link" href="/rating">Рейтинг</a></li>
                    <li class="nav-item"><a class="nav-link active" href="/activity">Активность</a></li>
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
        <h1 class="h3 mb-4">Активность</h1>

        <div class="row">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 1 && $userRatingHistory && count($userRatingHistory['labels']) > 0): ?>
                <div class="col-lg-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="h5 mb-0">График активности (рост рейтинга)</h2>
                                <div>
                                    <span class="badge bg-primary me-2">Рейтинг: <?= $currentUserScore ?> баллов</span>
                                    <span class="badge bg-info">Место: <?= $currentUserRank ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="ratingChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Лента последних мероприятий</h2>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentEvents)): ?>
                            <p class="text-muted p-3">Нет мероприятий.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentEvents as $event): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <a href="/event?id=<?= $event['event_ID'] ?>" class="text-dark fw-bold">
                                                    <?= htmlspecialchars($event['event_Title']) ?>
                                                </a>
                                                <br>
                                                <small class="text-muted">
                                                    <?= date('d.m.Y H:i', strtotime($event['event_DateTimeStart'])) ?>
                                                </small>
                                                <div class="mt-1">
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($event['status_Type']) ?></span>
                                                    <span class="badge bg-info text-dark ms-1"><?= htmlspecialchars($event['category_Type']) ?></span>
                                                    <span class="badge bg-light text-dark ms-1">Сложность: <?= htmlspecialchars($event['coef_Difficult']) ?></span>
                                                </div>
                                            </div>
                                            <?php if (strtotime($event['event_DateTimeStart']) > time() && $event['status_Type'] == 'Регистрация'): ?>
                                                <a href="/event?id=<?= $event['event_ID'] ?>" class="btn btn-sm btn-outline-primary">Записаться</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Облако тегов</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topTags)): ?>
                            <p class="text-muted">Нет данных.</p>
                        <?php else: ?>
                            <div class="tag-cloud">
                                <?php 
                                $maxCount = max(array_column($topTags, 'count'));
                                foreach ($topTags as $tag): 
                                    $size = 12 + (($tag['count'] / $maxCount) * 24);
                                ?>
                                    <a href="/events?category=<?= $tag['category_ID'] ?>" 
                                       class="tag-link" 
                                       style="font-size: <?= round($size) ?>px; display: inline-block; margin: 5px; text-decoration: none; color: #0d6efd;">
                                        <?= htmlspecialchars($tag['category_Type']) ?>
                                        <small>(<?= $tag['count'] ?>)</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 1 && $userRatingHistory && count($userRatingHistory['labels']) > 0): ?>
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Статистика</h2>
                        </div>
                        <div class="card-body">
                            <p><strong>Текущий рейтинг:</strong> <?= $currentUserScore ?> баллов</p>
                            <p><strong>Место в рейтинге:</strong> <?= $currentUserRank ?></p>
                            <p><strong>Участий в мероприятиях:</strong> <?= count($userRatingHistory['labels']) ?></p>
                            <p><strong>Средний балл за мероприятие:</strong> 
                                <?= count($userRatingHistory['labels']) > 0 ? round($currentUserScore / count($userRatingHistory['labels']), 2) : 0 ?>
                            </p>
                        </div>
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
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 1 && $userRatingHistory && count($userRatingHistory['labels']) > 0): ?>
    <script>
        const ctx = document.getElementById('ratingChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($userRatingHistory['labels']) ?>,
                datasets: [{
                    label: 'Рейтинг',
                    data: <?= json_encode($userRatingHistory['scores']) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Рейтинг: ' + context.raw + ' баллов';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Баллы'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Дата'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>