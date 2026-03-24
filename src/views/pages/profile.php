<?php
session_start();

use db\database;
use modules\User;

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth');
    exit;
}

$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'];

if (!database::$pdo) {
    database::connect();
}

$profile = User::getProfile($profileId);
if (!$profile) {
    header('HTTP/1.0 404 Not Found');
    die('Пользователь не найден');
}

$canEdit = ($currentUserId == $profileId || $currentUserRole == 3);
$categories = User::getAllCategories();

$organizerStats = null;
$organizerEvents = null;
$participantPortfolio = null;
$participantRank = null;
$nextLevelProgress = null;

if ($profile['user_Role'] == 3) {
    $organizerStats = User::getOrganizerStats($profileId);
    $organizerEvents = User::getOrganizerEvents($profileId);
} elseif ($profile['user_Role'] == 1) {
    User::recalculateLevel($profileId);
    $participantPortfolio = User::getParticipantPortfolio($profileId);
    $participantRank = User::getParticipantRank($profileId);
    $nextLevelProgress = User::getNextLevelProgress($profileId);
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $canEdit) {
    $data = [
        'login' => trim($_POST['login'] ?? ''),
        'fullname' => trim($_POST['fullname'] ?? ''),
        'age' => (int)($_POST['age'] ?? 0),
        'city' => trim($_POST['city'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'hr_department' => trim($_POST['hr_department'] ?? ''),
        'role' => $profile['user_Role']
    ];

    if (empty($data['login']) || empty($data['fullname'])) {
        $error = 'Логин и ФИО обязательны';
    } elseif ($profile['user_Role'] == 1 && ($data['age'] < 14 || $data['age'] > 100)) {
        $error = 'Возраст должен быть от 14 до 100 лет';
    } else {
        if (User::updateProfile($profileId, $data)) {
            $message = 'Профиль обновлён';
            if ($currentUserId == $profileId) {
                $_SESSION['user_login'] = $data['login'];
                $_SESSION['user_fullname'] = $data['fullname'];
            }
            $profile = User::getProfile($profileId);
        } else {
            $error = 'Ошибка обновления';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <style>
        .profile-field {
            border-bottom: 1px solid #e9ecef;
            padding: 12px 0;
        }

        .stat-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .event-item {
            border-bottom: 1px solid #e9ecef;
            padding: 8px 0;
        }

        .event-item:last-child {
            border-bottom: none;
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
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Основная информация</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" id="profile-form">
                            <div class="profile-field">
                                <label class="fw-bold">Логин</label>
                                <?php if ($currentUserRole == 3 || $_SESSION['user_id'] == $profileId): ?>
                                    <?php if ($canEdit): ?>
                                        <input type="text" name="login" class="form-control mt-1" value="<?= htmlspecialchars($profile['user_Login']) ?>" required>
                                    <?php else: ?>
                                        <p class="mt-1"><?= htmlspecialchars($profile['user_Login']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="mt-1 text-muted">Скрыто</p>
                                    <?php if ($canEdit): ?>
                                        <input type="hidden" name="login" value="<?= htmlspecialchars($profile['user_Login']) ?>">
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="profile-field">
                                <label class="fw-bold">ФИО</label>
                                <?php if ($canEdit): ?>
                                    <input type="text" name="fullname" class="form-control mt-1" value="<?= htmlspecialchars($profile['fullname']) ?>" required>
                                <?php else: ?>
                                    <p class="mt-1"><?= htmlspecialchars($profile['fullname']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($profile['user_Role'] == 2): ?>
                                <div class="profile-field">
                                    <label class="fw-bold">Отдел кадров</label>
                                    <?php if ($canEdit): ?>
                                        <input type="text" name="hr_department" class="form-control mt-1" value="<?= htmlspecialchars($profile['spec_HRDepartament']) ?>" required>
                                    <?php else: ?>
                                        <p class="mt-1"><?= htmlspecialchars($profile['spec_HRDepartament']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($profile['user_Role'] == 1): ?>
                                <div class="profile-field">
                                    <label class="fw-bold">Возраст</label>
                                    <?php if ($canEdit): ?>
                                        <input type="number" name="age" class="form-control mt-1" value="<?= $profile['age'] ?>" min="14" max="100" required>
                                    <?php else: ?>
                                        <p class="mt-1"><?= $profile['age'] ?> лет</p>
                                    <?php endif; ?>
                                </div>

                                <div class="profile-field">
                                    <label class="fw-bold">Город</label>
                                    <?php if ($canEdit): ?>
                                        <input type="text" name="city" class="form-control mt-1" value="<?= htmlspecialchars($profile['city']) ?>" required>
                                    <?php else: ?>
                                        <p class="mt-1"><?= htmlspecialchars($profile['city']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="profile-field">
                                    <label class="fw-bold">Категория</label>
                                    <?php if ($canEdit): ?>
                                        <select name="category_id" class="form-select mt-1" required>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['category_ID'] ?>" <?= $cat['category_ID'] == $profile['category_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['category_Type']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <p class="mt-1"><?= htmlspecialchars($profile['category_name']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="profile-field">
                                <label class="fw-bold">Роль в системе: </label>
                                <span class="mt-1">
                                    <?php
                                    $roleName = match ((int)$profile['user_Role']) {
                                        1 => 'Участник',
                                        2 => 'Наблюдатель',
                                        3 => 'Организатор',
                                        default => 'Неизвестно'
                                    };
                                    echo $roleName;
                                    ?>
                                </span>
                            </div>

                            <?php if ($canEdit): ?>
                                <div class="mt-4">
                                    <button type="submit" name="update_profile" class="btn btn-primary">Сохранить изменения</button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <?php if ($profile['user_Role'] == 3 && $organizerStats): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Статистика организатора</h2>
                        </div>
                        <div class="card-body">
                            <p><strong>Проведено мероприятий:</strong> <?= $organizerStats['organizer_events'] ?></p>
                            <p><strong>Рейтинг доверия:</strong> <?= number_format($organizerStats['organizer_trust'], 2) ?> / 5.00</p>
                            <p><strong>Часто дарит призы:</strong> <?= htmlspecialchars($organizerStats['most_common_bonus']) ?></p>
                            <hr>
                            <h6>Список мероприятий организатора:</h6>
                            <?php if ($organizerEvents): ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($organizerEvents as $ev): ?>
                                        <li class="event-item">
                                            <a href="/event?id=<?= $ev['event_ID'] ?>"><?= htmlspecialchars($ev['event_Title']) ?></a>
                                            <small class="text-muted">(<?= date('d.m.Y', strtotime($ev['event_DateTimeStart'])) ?>)</small>
                                            <span class="badge bg-secondary">Участников: <?= $ev['participants_count'] ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">Нет созданных мероприятий.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Отзывы участников</h2>
                        </div>
                        <div class="card-body">
                            <?php
                            $reviews = User::getOrganizerReviews($profileId, 10);
                            if (empty($reviews)): ?>
                                <p class="text-muted">Пока нет отзывов.</p>
                            <?php else: ?>
                                <?php foreach ($reviews as $rv): ?>
                                    <div class="mb-3 border-bottom pb-2">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($rv['participant_FullName']) ?></strong>
                                            <span class="text-warning">★ <?= $rv['rating'] ?>/5</span>
                                        </div>
                                        <p class="mb-1"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($rv['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($profile['user_Role'] == 3 && isset($_SESSION['user_id']) && $_SESSION['user_role'] == 1 && $_SESSION['user_id'] != $profileId): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h2 class="h5 mb-0">Ваш отзыв об организаторе</h2>
                            </div>
                            <div class="card-body">
                                <?php
                                $hasReview = User::hasReview($profileId, $_SESSION['user_id']);
                                $existingReview = null;
                                if ($hasReview) {
                                    $existingReview = User::getReview($profileId, $_SESSION['user_id']);
                                }
                                $reviewRating = $existingReview ? $existingReview['rating'] : '';
                                $reviewComment = $existingReview ? $existingReview['comment'] : '';
                                ?>
                                <?php if (isset($_GET['review_success'])): ?>
                                    <div class="alert alert-success">Отзыв успешно сохранён!</div>
                                <?php endif; ?>
                                <?php if (isset($_GET['review_error'])): ?>
                                    <div class="alert alert-danger">Ошибка: <?= htmlspecialchars($_GET['review_error']) ?></div>
                                <?php endif; ?>
                                <form method="POST" action="/add-review">
                                    <input type="hidden" name="organizer_id" value="<?= $profileId ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Оценка (1–5)</label>
                                        <select name="rating" class="form-select" required>
                                            <option value="">Выберите оценку</option>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= $reviewRating == $i ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Комментарий</label>
                                        <textarea name="comment" class="form-control" rows="3" required><?= htmlspecialchars($reviewComment) ?></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-primary"><?= $hasReview ? 'Изменить отзыв' : 'Оставить отзыв' ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($profile['user_Role'] == 1): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Рейтинг и кадровый резерв</h2>
                        </div>
                        <div class="card-body">
                            <p><strong>Текущий рейтинг:</strong> <?= $nextLevelProgress['participant_TotalScore'] ?> баллов за текущий уровень (Всего баллов: <?= $participantRank['score'] ?> )</p>
                            <p><strong>Место в общем зачете:</strong> <?= $participantRank['rank'] ?></p>
                            <?php if ($nextLevelProgress): ?>
                                <p><strong>Текущий уровень:</strong> <?= htmlspecialchars($nextLevelProgress['lvl_Name']) ?></p>
                                <?php
                                $maxLevelId = database::$pdo->query("SELECT MAX(lvl_ID) FROM levelsrezerv")->fetchColumn();
                                if ($nextLevelProgress['participant_missingScore'] == 0 && $nextLevelProgress['participant_Level'] == $maxLevelId) {
                                    echo '<p><strong>Максимальный уровень достигнут</strong> (' . $participantRank['score'] . ' / ' . $nextLevelProgress['lvl_TargetScore'] . ' баллов)</p>';
                                } else {
                                    echo '<p><strong>До следующего уровня:</strong> ' . max(0, $nextLevelProgress['participant_missingScore']) . ' баллов (цель: ' . $nextLevelProgress['lvl_TargetScore'] . ' баллов)</p>';
                                }
                                ?>
                                <div class="progress">
                                    <?php
                                    $progressPercent = ($participantRank['score'] / $nextLevelProgress['lvl_TargetScore']) * 100;
                                    $progressPercent = min(100, $progressPercent);
                                    ?>
                                    <div class="progress-bar" role="progressbar" style="width: <?= $progressPercent ?>%;" aria-valuenow="<?= $progressPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Нет данных об уровнях кадрового резерва.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Портфолио достижений</h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($participantPortfolio)): ?>
                    <p class="text-muted p-3">Нет завершённых мероприятий.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($participantPortfolio as $item): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="/event?id=<?= $item['event_ID'] ?>" class="text-dark fw-bold"><?= htmlspecialchars($item['event_Title']) ?></a>
                                        <br>
                                        <small class="text-muted">
                                            <?= date('d.m.Y', strtotime($item['event_DateTimeStart'])) ?> –
                                            <?= date('d.m.Y', strtotime($item['event_DateTimeEnd'])) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-success"
                                        title="<?= htmlspecialchars("{$item['event_Points']} баллов × {$item['coef_Value']} (коэффициент за сложность) = {$item['earned_points']} баллов") ?>">
                                        +<?= $item['earned_points'] ?> баллов
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
</body>

</html>