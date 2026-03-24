<?php
session_start();

use db\database;
use modules\User;
use modules\Event;

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header('Location: /auth');
    exit;
}

if (!database::$pdo) {
    database::connect();
}

$currentUserId = $_SESSION['user_id'];
$currentSubrole = null;
$stmt = database::$pdo->prepare("SELECT organizer_subrole FROM organizer_profiles WHERE organizer_ID = ?");
$stmt->execute([$currentUserId]);
$currentSubrole = $stmt->fetchColumn();

$isMainAdmin = ($currentSubrole == 1);

$categories = Event::getCategories();
$statuses = Event::getEventStatuses();
$coefs = User::getCoefficients();
$bonuses = database::$pdo->query("SELECT bonus_ID, bonus_Name FROM bonuses")->fetchAll();
$organizers = User::getAllOrganizers();
$subroles = User::getAllSubroles();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
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
    if (Event::createEvent($data, $_SESSION['user_id'])) {
        $message = 'Мероприятие успешно создано';
    } else {
        $error = 'Ошибка создания мероприятия';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_organizer_role'])) {
    $userId = (int)$_POST['user_id'];
    $newSubroleId = (int)$_POST['subrole_id'];

    if (!$isMainAdmin) {
        $error = 'У вас недостаточно прав для изменения ролей организаторов';
    } elseif ($userId == $currentUserId) {
        $error = 'Нельзя изменить свою роль';
    } elseif ($newSubroleId == 1 && $userId != $currentUserId) {
        $error = 'Назначение главного администратора другому пользователю запрещено';
    } else {
        if (User::updateOrganizerRole($userId, $newSubroleId)) {
            $message = 'Роль организатора обновлена';
            $organizers = User::getAllOrganizers();
        } else {
            $error = 'Ошибка обновления роли';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coef'])) {
    $coefId = (int)$_POST['coef_id'];
    $value = (float)$_POST['coef_value'];
    $difficult = trim($_POST['coef_difficult']);
    if (User::updateCoefficient($coefId, $value, $difficult)) {
        $message = 'Коэффициент обновлён';
        $coefs = User::getCoefficients();
    } else {
        $error = 'Ошибка обновления коэффициента';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coef'])) {
    $value = (float)$_POST['coef_value_new'];
    $difficult = trim($_POST['coef_difficult_new']);
    if (empty($difficult) || $value <= 0) {
        $error = 'Заполните корректно название и значение коэффициента';
    } else {
        if (User::createCoefficient($value, $difficult)) {
            $message = 'Новый коэффициент добавлен';
            $coefs = User::getCoefficients();
        } else {
            $error = 'Ошибка добавления коэффициента';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
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
        <h1 class="h3 mb-4">Панель администратора</h1>

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
                        <h2 class="h5 mb-0">Создание мероприятия</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Название</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Описание</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Дата начала</label>
                                    <input type="datetime-local" name="date_start" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Дата окончания</label>
                                    <input type="datetime-local" name="date_end" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Баллы за участие</label>
                                    <input type="number" name="points" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Коэффициент сложности</label>
                                    <select name="coef_id" class="form-select">
                                        <?php foreach ($coefs as $c): ?>
                                            <option value="<?= $c['coef_ID'] ?>"><?= htmlspecialchars($c['coef_Difficult']) ?> (<?= $c['coef_Value'] ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Статус мероприятия</label>
                                    <select name="status_id" class="form-select">
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= $s['status_ID'] ?>"><?= htmlspecialchars($s['status_Type']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Категория</label>
                                    <select name="category_id" class="form-select">
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['category_ID'] ?>"><?= htmlspecialchars($cat['category_Type']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Бонус</label>
                                <select name="bonus_id" id="bonus_select" class="form-select">
                                    <option value="">Без бонуса</option>
                                    <?php foreach ($bonuses as $b): ?>
                                        <option value="<?= $b['bonus_ID'] ?>"><?= htmlspecialchars($b['bonus_Name']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="new">- Добавить новый бонус -</option>
                                </select>
                                <div id="new_bonus_field" class="new-bonus-field">
                                    <input type="text" id="new_bonus_name" class="form-control" placeholder="Название бонуса">
                                    <button type="button" id="create_bonus_btn" class="btn btn-sm btn-outline-primary mt-2">Создать бонус</button>
                                    <div id="new_bonus_error" class="text-danger mt-1" style="font-size: 0.875rem;"></div>
                                </div>
                            </div>
                            <button type="submit" name="create_event" class="btn btn-primary">Создать мероприятие</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Настройка весов баллов (коэффициенты сложности)</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm">
                                <thead>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Значение</th>
                                    <th>Действие</th>
                                </thead>
                                <tbody>
                                    <?php foreach ($coefs as $c): ?>
                                        <tr>
                                            <form method="POST" class="row g-2">
                                                <input type="hidden" name="coef_id" value="<?= $c['coef_ID'] ?>">
                                                <td><?= $c['coef_ID'] ?></td>
                                                <td><input type="text" name="coef_difficult" class="form-control form-control-sm" value="<?= htmlspecialchars($c['coef_Difficult']) ?>" required></td>
                                                <td><input type="number" step="0.01" name="coef_value" class="form-control form-control-sm" value="<?= $c['coef_Value'] ?>" required></td>
                                                <td><button type="submit" name="update_coef" class="btn btn-sm btn-outline-primary">Обновить</button></td>
                                            </form>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <h6>Добавить новый коэффициент</h6>
                        <form method="POST">
                            <div class="row g-2">
                                <div class="col-5">
                                    <input type="text" name="coef_difficult_new" class="form-control" placeholder="Название" required>
                                </div>
                                <div class="col-4">
                                    <input type="number" step="0.01" name="coef_value_new" class="form-control" placeholder="Значение" required>
                                </div>
                                <div class="col-3">
                                    <button type="submit" name="create_coef" class="btn btn-sm btn-outline-success w-100">Добавить</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Модерация организаторов</h2>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($organizers)): ?>
                            <p class="text-muted p-3">Нет организаторов.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <th>ID</th>
                                        <th>Логин</th>
                                        <th>ФИО</th>
                                        <th>Текущая роль</th>
                                        <th>Мероприятий</th>
                                        <th>Рейтинг доверия</th>
                                        <th>Действие</th>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($organizers as $org): ?>
                                            <?php
                                            $canEdit = false;
                                            if ($isMainAdmin && $org['user_ID'] != $currentUserId) {
                                                $canEdit = true;
                                            }
                                            ?>
                                            <tr>
                                                <td><?= $org['user_ID'] ?></td>
                                                <td><?= htmlspecialchars($org['user_Login']) ?></td>
                                                <td><?= htmlspecialchars($org['organizer_fullname']) ?></td>
                                                <td><?= htmlspecialchars($org['subrole_name']) ?></td>
                                                <td><?= $org['organizer_events'] ?></td>
                                                <td><?= number_format($org['organizer_trust'], 2) ?></td>
                                                <td>
                                                    <?php if ($canEdit): ?>
                                                        <form method="POST" class="d-flex gap-2">
                                                            <input type="hidden" name="user_id" value="<?= $org['user_ID'] ?>">
                                                            <select name="subrole_id" class="form-select form-select-sm" style="width: auto;">
                                                                <?php foreach ($subroles as $sr): ?>
                                                                    <?php if ($sr['roleOrg_ID'] == 1 && $org['user_ID'] != $currentUserId) continue; ?>
                                                                    <option value="<?= $sr['roleOrg_ID'] ?>" <?= $sr['roleOrg_ID'] == $org['organizer_subrole'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($sr['role_Type']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" name="update_organizer_role" class="btn btn-sm btn-outline-secondary">Обновить</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
    <script src="/assets/js/eventsetting.js"></script>
</body>

</html>