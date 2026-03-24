<?php

namespace modules;

use db\database;
use PDO;

class User
{
    public static function getProfile($userId)
    {
        if (!database::$pdo) {
            database::connect();
        }

        $stmt = database::$pdo->prepare("
        SELECT u.user_ID, u.user_Login, u.user_Role,
               CASE 
                   WHEN u.user_Role = 1 THEN pp.participant_FullName
                   WHEN u.user_Role = 3 THEN op.organizer_fullname
                   WHEN u.user_Role = 2 THEN sp.spec_FullName
               END as fullname,
               CASE 
                   WHEN u.user_Role = 1 THEN pp.participant_Age
                   WHEN u.user_Role = 3 THEN NULL
                   WHEN u.user_Role = 2 THEN NULL
               END as age,
               CASE 
                   WHEN u.user_Role = 1 THEN pp.participant_City
                   WHEN u.user_Role = 3 THEN NULL
                   WHEN u.user_Role = 2 THEN NULL
               END as city,
               CASE 
                   WHEN u.user_Role = 1 THEN pp.participant_category
                   WHEN u.user_Role = 3 THEN NULL
                   WHEN u.user_Role = 2 THEN NULL
               END as category_id,
               cat.category_Type as category_name,
               op.organizer_subrole,
               sp.spec_HRDepartament
        FROM users u
        LEFT JOIN participant_profiles pp ON u.user_ID = pp.participant_UserID AND u.user_Role = 1
        LEFT JOIN organizer_profiles op ON u.user_ID = op.organizer_ID AND u.user_Role = 3
        LEFT JOIN spectator_profiles sp ON u.user_ID = sp.spec_ID AND u.user_Role = 2
        LEFT JOIN category cat ON pp.participant_category = cat.category_ID
        WHERE u.user_ID = ?
    ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return false;
        }

        return $result;
    }

    public static function updateProfile($userId, $data)
    {
        if (!database::$pdo) {
            database::connect();
        }

        $userRole = $data['role'] ?? null;
        if (!$userRole) {
            $stmt = database::$pdo->prepare("SELECT user_Role FROM users WHERE user_ID = ?");
            $stmt->execute([$userId]);
            $userRole = $stmt->fetchColumn();
        }

        try {
            database::$pdo->beginTransaction();

            if (isset($data['login']) && !empty($data['login'])) {
                $stmt = database::$pdo->prepare("UPDATE users SET user_Login = ? WHERE user_ID = ?");
                $stmt->execute([$data['login'], $userId]);
            }

            if ($userRole == 1 && isset($data['fullname'], $data['age'], $data['city'], $data['category_id'])) {
                $stmt = database::$pdo->prepare("
                    UPDATE participant_profiles 
                    SET participant_FullName = ?, participant_Age = ?, participant_City = ?, participant_category = ?
                    WHERE participant_UserID = ?
                ");
                $stmt->execute([$data['fullname'], $data['age'], $data['city'], $data['category_id'], $userId]);
            } elseif ($userRole == 3 && isset($data['fullname'])) {
                $stmt = database::$pdo->prepare("
                    UPDATE organizer_profiles SET organizer_fullname = ? WHERE organizer_ID = ?
                ");
                $stmt->execute([$data['fullname'], $userId]);
            } elseif ($userRole == 2 && isset($data['fullname'], $data['hr_department'])) {
                $stmt = database::$pdo->prepare("
                    UPDATE spectator_profiles SET spec_FullName = ?, spec_HRDepartament = ? WHERE spec_ID = ?
                ");
                $stmt->execute([$data['fullname'], $data['hr_department'], $userId]);
            }

            database::$pdo->commit();
            return true;
        } catch (\Exception $e) {
            database::$pdo->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public static function getAllCategories()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("SELECT category_ID, category_Type FROM category");
        return $stmt->fetchAll();
    }
    public static function getOrganizerStats($organizerId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT organizer_events, organizer_prizes, organizer_trust,
               (SELECT COUNT(*) FROM events WHERE event_Organizer = ?) as total_events
        FROM organizer_profiles
        WHERE organizer_ID = ?
    ");
        $stmt->execute([$organizerId, $organizerId]);
        $stats = $stmt->fetch();

        $stmt2 = database::$pdo->prepare("
        SELECT b.bonus_Name, COUNT(*) as cnt
        FROM events e
        JOIN bonuses b ON e.event_Bonuses = b.bonus_ID
        WHERE e.event_Organizer = ? AND e.event_Status = 4 AND e.event_Bonuses IS NOT NULL
        GROUP BY b.bonus_Name
        ORDER BY cnt DESC, b.bonus_Name ASC
        LIMIT 1
    ");
        $stmt2->execute([$organizerId]);
        $mostCommon = $stmt2->fetch();
        $stats['most_common_bonus'] = $mostCommon ? $mostCommon['bonus_Name'] : 'Не указано';
        return $stats;
    }


    public static function getOrganizerEvents($organizerId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT event_ID, event_Title, event_DateTimeStart, event_Status,
                   (SELECT COUNT(*) FROM event_participation WHERE event_ID = e.event_ID AND status_ID = 1) as participants_count
            FROM events e
            WHERE event_Organizer = ?
            ORDER BY event_DateTimeStart DESC
        ");
        $stmt->execute([$organizerId]);
        return $stmt->fetchAll();
    }


    public static function getParticipantPortfolio($participantId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT e.event_ID, e.event_Title, e.event_DateTimeStart, e.event_DateTimeEnd,
                   e.event_Points, c.coef_Value, (e.event_Points * c.coef_Value) as earned_points,
                   ep.status_ID, sp.status_Type as participation_status
            FROM event_participation ep
            JOIN events e ON ep.event_ID = e.event_ID
            JOIN coef c ON e.event_Coef = c.coef_ID
            JOIN status_participation sp ON ep.status_ID = sp.status_ID
            WHERE ep.participant_ID = ? AND e.event_Status = 4
            ORDER BY e.event_DateTimeEnd DESC
        ");
        $stmt->execute([$participantId]);
        return $stmt->fetchAll();
    }

    public static function getParticipantRank($participantId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT participant_TotalScore FROM participant_levelsrate WHERE participant_UserID = ?
        ");
        $stmt->execute([$participantId]);
        $score = $stmt->fetchColumn();
        $score = $score ?: 0;

        $stmt = database::$pdo->prepare("
            SELECT COUNT(*) + 1 FROM participant_levelsrate WHERE participant_TotalScore > ?
        ");
        $stmt->execute([$score]);
        $rank = $stmt->fetchColumn();

        return ['score' => $score, 'rank' => $rank];
    }

    public static function getNextLevelProgress($participantId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT pl.participant_Level, pl.participant_TotalScore, pl.participant_missingScore,
                   l.lvl_Name, l.lvl_TargetScore
            FROM participant_levelsrate pl
            JOIN levelsrezerv l ON pl.participant_Level = l.lvl_ID
            WHERE pl.participant_UserID = ?
        ");
        $stmt->execute([$participantId]);
        return $stmt->fetch();
    }

    public static function getAllParticipantsWithFilters($filters)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $sql = "
            SELECT u.user_ID, u.user_Login,
                   pp.participant_FullName, pp.participant_Age, pp.participant_City,
                   cat.category_Type,
                   pl.participant_TotalScore,
                   (SELECT COUNT(*) FROM event_participation ep 
                    WHERE ep.participant_ID = u.user_ID AND ep.status_ID = 1) as events_count,
                   (SELECT COALESCE(AVG(e.event_Points * c.coef_Value), 0) 
                    FROM event_participation ep
                    JOIN events e ON ep.event_ID = e.event_ID
                    JOIN coef c ON e.event_Coef = c.coef_ID
                    WHERE ep.participant_ID = u.user_ID AND ep.status_ID = 1) as avg_score
            FROM users u
            JOIN participant_profiles pp ON u.user_ID = pp.participant_UserID
            LEFT JOIN category cat ON pp.participant_category = cat.category_ID
            LEFT JOIN participant_levelsrate pl ON u.user_ID = pl.participant_UserID
            WHERE u.user_Role = 1
        ";
        $conditions = [];
        $params = [];

        if (!empty($filters['age_min'])) {
            $conditions[] = "pp.participant_Age >= ?";
            $params[] = (int)$filters['age_min'];
        }
        if (!empty($filters['age_max'])) {
            $conditions[] = "pp.participant_Age <= ?";
            $params[] = (int)$filters['age_max'];
        }
        if (!empty($filters['city'])) {
            $conditions[] = "pp.participant_City LIKE ?";
            $params[] = '%' . $filters['city'] . '%';
        }
        if (!empty($filters['events_count_min'])) {
            $conditions[] = "(SELECT COUNT(*) FROM event_participation ep WHERE ep.participant_ID = u.user_ID AND ep.status_ID = 1) >= ?";
            $params[] = (int)$filters['events_count_min'];
        }
        if (!empty($filters['avg_score_min'])) {
            $conditions[] = "(SELECT COALESCE(AVG(e.event_Points * c.coef_Value), 0) 
                              FROM event_participation ep
                              JOIN events e ON ep.event_ID = e.event_ID
                              JOIN coef c ON e.event_Coef = c.coef_ID
                              WHERE ep.participant_ID = u.user_ID AND ep.status_ID = 1) >= ?";
            $params[] = (float)$filters['avg_score_min'];
        }

        if (count($conditions) > 0) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY pl.participant_TotalScore DESC";
        $stmt = database::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public static function getRecentEvents($limit = 10)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $limit = (int)$limit;
        $stmt = database::$pdo->prepare("
        SELECT e.event_ID, e.event_Title, e.event_DateTimeStart, e.event_DateTimeEnd,
               s.status_Type, c.coef_Difficult, cat.category_Type
        FROM events e
        JOIN status_event s ON e.event_Status = s.status_ID
        JOIN coef c ON e.event_Coef = c.coef_ID
        JOIN category cat ON e.event_Category = cat.category_ID
        ORDER BY e.event_DateTimeStart DESC
        LIMIT ?
    ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getUserRatingHistory($userId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT e.event_DateTimeEnd as date, 
               (e.event_Points * c.coef_Value) as points_earned,
               pl.participant_TotalScore as total_score
        FROM event_participation ep
        JOIN events e ON ep.event_ID = e.event_ID
        JOIN coef c ON e.event_Coef = c.coef_ID
        JOIN participant_levelsrate pl ON pl.participant_UserID = ep.participant_ID
        WHERE ep.participant_ID = ? AND ep.status_ID = 1
        ORDER BY e.event_DateTimeEnd ASC
    ");
        $stmt->execute([$userId]);
        $history = $stmt->fetchAll();

        $chartData = [
            'labels' => [],
            'scores' => []
        ];
        $runningTotal = 0;
        foreach ($history as $h) {
            $runningTotal += $h['points_earned'];
            $chartData['labels'][] = date('d.m.Y', strtotime($h['date']));
            $chartData['scores'][] = $runningTotal;
        }
        return $chartData;
    }
    public static function getTopTags($limit = 10)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $limit = (int)$limit;
        $stmt = database::$pdo->prepare("
        SELECT cat.category_ID, cat.category_Type, COUNT(*) as count
        FROM events e
        JOIN category cat ON e.event_Category = cat.category_ID
        GROUP BY cat.category_ID, cat.category_Type
        ORDER BY count DESC
        LIMIT ?
    ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public static function getLeaderboard($categoryId = null, $limit = 100)
    {
        if (!database::$pdo) {
            database::connect();
        }

        $sql = "
        SELECT 
            u.user_ID,
            pp.participant_FullName,
            pp.participant_City,
            cat.category_Type,
            pl.participant_TotalScore,
            pl.participant_Level,
            lvl.lvl_Name as level_name
        FROM participant_levelsrate pl
        JOIN participant_profiles pp ON pl.participant_UserID = pp.participant_UserID
        JOIN users u ON pl.participant_UserID = u.user_ID
        JOIN category cat ON pp.participant_category = cat.category_ID
        JOIN levelsrezerv lvl ON pl.participant_Level = lvl.lvl_ID
        WHERE u.user_Role = 1
    ";

        $params = [];

        if ($categoryId !== null && $categoryId > 0) {
            $sql .= " AND pp.participant_category = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY pl.participant_TotalScore DESC, u.user_ID ASC LIMIT ?";
        $params[] = (int)$limit;

        $stmt = database::$pdo->prepare($sql);
        foreach ($params as $i => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i + 1, $value, $paramType);
        }
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rank = 1;
        foreach ($participants as &$p) {
            $p['rank'] = $rank++;
        }

        return $participants;
    }
    public static function getAllOrganizers()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("
        SELECT u.user_ID, u.user_Login, u.user_Role,
               op.organizer_fullname, op.organizer_subrole, op.organizer_events, op.organizer_trust,
               rt.role_Type as subrole_name,
               CASE WHEN op.organizer_subrole = 1 THEN 1 ELSE 0 END as is_main_admin
        FROM users u
        JOIN organizer_profiles op ON u.user_ID = op.organizer_ID
        LEFT JOIN roleorganizator rt ON op.organizer_subrole = rt.roleOrg_ID
        WHERE u.user_Role = 3
        ORDER BY u.user_ID
    ");
        return $stmt->fetchAll();
    }
    public static function updateOrganizerRole($userId, $subroleId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        UPDATE organizer_profiles SET organizer_subrole = ? WHERE organizer_ID = ?
    ");
        return $stmt->execute([$subroleId, $userId]);
    }

    public static function getAllSubroles()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("SELECT roleOrg_ID, role_Type FROM roleorganizator");
        return $stmt->fetchAll();
    }

    public static function getCoefficients()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("SELECT coef_ID, coef_Value, coef_Difficult FROM coef ORDER BY coef_ID");
        return $stmt->fetchAll();
    }

    public static function updateCoefficient($coefId, $value, $difficult)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        UPDATE coef SET coef_Value = ?, coef_Difficult = ? WHERE coef_ID = ?
    ");
        return $stmt->execute([$value, $difficult, $coefId]);
    }
    public static function createCoefficient($value, $difficult)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("INSERT INTO coef (coef_Value, coef_Difficult) VALUES (?, ?)");
        return $stmt->execute([$value, $difficult]);
    }
    public static function hasReview($organizerId, $participantId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT review_ID FROM reviews 
        WHERE organizer_ID = ? AND participant_ID = ?
    ");
        $stmt->execute([$organizerId, $participantId]);
        return $stmt->fetch() ? true : false;
    }

    public static function getReview($organizerId, $participantId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT rating, comment, created_at FROM reviews 
        WHERE organizer_ID = ? AND participant_ID = ?
    ");
        $stmt->execute([$organizerId, $participantId]);
        return $stmt->fetch();
    }

    public static function addOrUpdateReview($organizerId, $participantId, $rating, $comment)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT review_ID FROM reviews 
        WHERE organizer_ID = ? AND participant_ID = ?
    ");
        $stmt->execute([$organizerId, $participantId]);
        $existing = $stmt->fetch();
        if ($existing) {
            $stmt = database::$pdo->prepare("
            UPDATE reviews SET rating = ?, comment = ? 
            WHERE organizer_ID = ? AND participant_ID = ?
        ");
            return $stmt->execute([$rating, $comment, $organizerId, $participantId]);
        } else {
            $stmt = database::$pdo->prepare("
            INSERT INTO reviews (organizer_ID, participant_ID, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
            return $stmt->execute([$organizerId, $participantId, $rating, $comment]);
        }
    }

    public static function getOrganizerReviews($organizerId, $limit = 10)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT r.rating, r.comment, r.created_at,
               p.participant_FullName
        FROM reviews r
        JOIN participant_profiles p ON r.participant_ID = p.participant_UserID
        WHERE r.organizer_ID = ?
        ORDER BY r.created_at DESC
        LIMIT ?
    ");
        $stmt->bindValue(1, $organizerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public static function addReview($data)
    {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
            header('Location: /auth');
            exit;
        }
        $organizerId = (int)($data['organizer_id'] ?? 0);
        $participantId = $_SESSION['user_id'];
        $rating = (int)($data['rating'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if ($rating < 1 || $rating > 5 || empty($comment)) {
            header('Location: /profile?id=' . $organizerId . '&review_error=Некорректные данные');
            exit;
        }

        if (self::addOrUpdateReview($organizerId, $participantId, $rating, $comment)) {
            header('Location: /profile?id=' . $organizerId . '&review_success=1');
        } else {
            header('Location: /profile?id=' . $organizerId . '&review_error=Ошибка базы данных');
        }
        exit;
    }
    public static function recalculateLevel($userId)
    {
        if (!database::$pdo) {
            database::connect();
        }

        $stmt = database::$pdo->prepare("
        SELECT COALESCE(SUM(e.event_Points * c.coef_Value), 0)
        FROM event_participation ep
        JOIN events e ON ep.event_ID = e.event_ID
        JOIN coef c ON e.event_Coef = c.coef_ID
        WHERE ep.participant_ID = ? AND ep.status_ID = 1
    ");
        $stmt->execute([$userId]);
        $totalScore = $stmt->fetchColumn();

        $stmt = database::$pdo->query("SELECT lvl_ID, lvl_TargetScore FROM levelsrezerv ORDER BY lvl_ID");
        $levels = $stmt->fetchAll();
        if (empty($levels)) return false;

        $currentScore = $totalScore;
        $currentLevel = $levels[0]['lvl_ID'];
        $nextLevel = null;

        foreach ($levels as $index => $level) {
            $target = $level['lvl_TargetScore'];
            if ($currentScore >= $target) {
                $currentScore -= $target;
                $currentLevel = $level['lvl_ID'];
                if ($index == count($levels) - 1) break;
            } else {
                $nextLevel = $level;
                break;
            }
        }
        if ($nextLevel === null && !empty($levels)) {
            $nextLevel = end($levels);
            $currentLevel = $nextLevel['lvl_ID'];
        }

        $missingScore = max(0, $nextLevel['lvl_TargetScore'] - $currentScore);

        $stmt = database::$pdo->prepare("
        UPDATE participant_levelsrate
        SET participant_Level = ?, participant_TotalScore = ?, participant_missingScore = ?
        WHERE participant_UserID = ?
    ");
        return $stmt->execute([$currentLevel, $currentScore, $missingScore, $userId]);
    }
    public static function updateOrganizerMostCommonBonus($organizerId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT b.bonus_Name
        FROM events e
        JOIN bonuses b ON e.event_Bonuses = b.bonus_ID
        WHERE e.event_Organizer = ? AND e.event_Status = 4 AND e.event_Bonuses IS NOT NULL
        GROUP BY b.bonus_Name
        ORDER BY COUNT(*) DESC, b.bonus_Name ASC
        LIMIT 1
    ");
        $stmt->execute([$organizerId]);
        $mostCommon = $stmt->fetch();
        $bonusName = $mostCommon ? $mostCommon['bonus_Name'] : '';
        $stmt2 = database::$pdo->prepare("UPDATE organizer_profiles SET organizer_prizes = ? WHERE organizer_ID = ?");
        return $stmt2->execute([$bonusName, $organizerId]);
    }
}
