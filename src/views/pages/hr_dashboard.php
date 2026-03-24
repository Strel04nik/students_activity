<?php
session_start();

use db\database;
use modules\User;

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] < 2) {
    header('Location: /auth');
    exit;
}

if (!database::$pdo) {
    database::connect();
}

$filters = [
    'age_min' => $_GET['age_min'] ?? '',
    'age_max' => $_GET['age_max'] ?? '',
    'city' => $_GET['city'] ?? '',
    'events_count_min' => $_GET['events_count_min'] ?? '',
    'avg_score_min' => $_GET['avg_score_min'] ?? '',
];

$participants = User::getAllParticipantsWithFilters($filters);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кадровый резерв | Движение молодежи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/home.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .filter-card {
            background-color: #f8f9fa;
            border: none;
            margin-bottom: 20px;
        }

        .table-responsive {
            overflow-x: auto;
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
        <h1 class="h4 mb-4">Кадровый резерв</h1>

        <div class="card filter-card">
            <div class="card-body">
                <form method="GET" class="row g-3" id="filter-form">
                    <div class="col-md-2">
                        <label class="form-label">Возраст от</label>
                        <input type="number" name="age_min" class="form-control" value="<?= htmlspecialchars($filters['age_min']) ?>" min="14" max="100">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">до</label>
                        <input type="number" name="age_max" class="form-control" value="<?= htmlspecialchars($filters['age_max']) ?>" min="14" max="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Город</label>
                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($filters['city']) ?>" placeholder="например, Красноярск">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Мероприятий ≥</label>
                        <input type="number" name="events_count_min" class="form-control" value="<?= htmlspecialchars($filters['events_count_min']) ?>" min="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ср. балл ≥</label>
                        <input type="number" step="0.01" name="avg_score_min" class="form-control" value="<?= htmlspecialchars($filters['avg_score_min']) ?>" min="0">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Применить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Список кандидатов</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <th>ФИО</th>
                            <th>Возраст</th>
                            <th>Город</th>
                            <th>Категория</th>
                            <th>Кол-во мероприятий</th>
                            <th>Средний балл</th>
                            <th>Общий рейтинг</th>
                            <th>Действие</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Нет участников, соответствующих фильтрам.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($participants as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['participant_FullName']) ?></td>
                                        <td><?= $p['participant_Age'] ?></td>
                                        <td><?= htmlspecialchars($p['participant_City']) ?></td>
                                        <td><?= htmlspecialchars($p['category_Type']) ?></td>
                                        <td><?= $p['events_count'] ?></td>
                                        <td><?= round($p['avg_score'], 2) ?></td>
                                        <td><?= $p['participant_TotalScore'] ?></td>
                                        <td>
                                            <a href="/profile?id=<?= $p['user_ID'] ?>" class="btn btn-sm btn-outline-primary me-1">Профиль</a>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="exportCandidatePDF(<?= htmlspecialchars(json_encode($p)) ?>)">Скачать PDF</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="mb-0">© 2026 Платформа "Движение молодежи"</p>
        </div>
    </footer>

    <script>
        function exportCandidatePDF(candidate) {
            const div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.left = '-9999px';
            div.style.top = '-9999px';
            div.style.width = '800px';
            div.style.padding = '20px';
            div.style.fontFamily = 'sans-serif';
            div.innerHTML = `
                <h2>Отчет по кандидату</h2>
                <p><strong>ФИО:</strong> ${escapeHtml(candidate.participant_FullName)}</p>
                <p><strong>Возраст:</strong> ${candidate.participant_Age}</p>
                <p><strong>Город:</strong> ${escapeHtml(candidate.participant_City)}</p>
                <p><strong>Категория:</strong> ${escapeHtml(candidate.category_Type)}</p>
                <p><strong>Количество мероприятий:</strong> ${candidate.events_count}</p>
                <p><strong>Средний балл:</strong> ${parseFloat(candidate.avg_score).toFixed(2)}</p>
                <p><strong>Общий рейтинг:</strong> ${candidate.participant_TotalScore}</p>
                <hr>
                <p><small>Дата формирования: ${new Date().toLocaleString()}</small></p>
            `;
            document.body.appendChild(div);
            const originalTitle = document.title;
            document.title = `Отчет_${candidate.participant_FullName}_${new Date().toISOString().slice(0,19)}`;
            html2canvas(div, {
                scale: 2,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const {
                    jsPDF
                } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 190;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
                pdf.save(`candidate_${candidate.participant_UserID}.pdf`);
                document.title = originalTitle;
                document.body.removeChild(div);
            }).catch(() => {
                document.body.removeChild(div);
            });
        }

        function escapeHtml(str) {
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>

</html>