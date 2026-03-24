<?php

namespace modules;

use db\database;

class Event
{
    public static function getEvent($eventId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        SELECT e.*, 
               s.status_Type,
               c.coef_Value, c.coef_Difficult,
               cat.category_Type,
               b.bonus_Name,
               op.organizer_fullname,
               u.user_Login as organizer_login
        FROM events e
        LEFT JOIN status_event s ON e.event_Status = s.status_ID
        LEFT JOIN coef c ON e.event_Coef = c.coef_ID
        LEFT JOIN category cat ON e.event_Category = cat.category_ID
        LEFT JOIN bonuses b ON e.event_Bonuses = b.bonus_ID
        LEFT JOIN organizer_profiles op ON e.event_Organizer = op.organizer_ID
        LEFT JOIN users u ON op.organizer_ID = u.user_ID
        WHERE e.event_ID = ?
    ");
        $stmt->execute([$eventId]);
        return $stmt->fetch();
    }


    public static function getParticipants($eventId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT p.participant_UserID, p.participant_FullName, 
                   p.participant_Age, p.participant_City,
                   sp.status_Type as participation_status,
                   ep.participation_ID, ep.status_ID
            FROM event_participation ep
            JOIN participant_profiles p ON ep.participant_ID = p.participant_UserID
            JOIN status_participation sp ON ep.status_ID = sp.status_ID
            WHERE ep.event_ID = ?
            ORDER BY ep.status_ID ASC, p.participant_FullName
        ");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }


    public static function registerParticipant($eventId, $participantId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT participation_ID FROM event_participation 
            WHERE event_ID = ? AND participant_ID = ?
        ");
        $stmt->execute([$eventId, $participantId]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = database::$pdo->prepare("
            INSERT INTO event_participation (event_ID, participant_ID, status_ID)
            VALUES (?, ?, 2)
        ");
        return $stmt->execute([$eventId, $participantId]);
    }


    public static function updateParticipationStatus($participationId, $newStatusId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            UPDATE event_participation 
            SET status_ID = ?
            WHERE participation_ID = ?
        ");
        return $stmt->execute([$newStatusId, $participationId]);
    }

    public static function isEventOrganizer($eventId, $userId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
            SELECT event_ID FROM events 
            WHERE event_ID = ? AND event_Organizer = ?
        ");
        $stmt->execute([$eventId, $userId]);
        return (bool) $stmt->fetch();
    }
    public static function getAllEvents($filters = [])
    {
        if (!database::$pdo) {
            database::connect();
        }
        $sql = "SELECT e.event_ID, e.event_Title, e.event_Description, 
                   e.event_DateTimeStart, e.event_DateTimeEnd, e.event_Points,
                   c.coef_Value, c.coef_Difficult,
                   s.status_Type,
                   cat.category_Type,
                   b.bonus_Name
            FROM events e
            LEFT JOIN status_event s ON e.event_Status = s.status_ID
            LEFT JOIN coef c ON e.event_Coef = c.coef_ID
            LEFT JOIN category cat ON e.event_Category = cat.category_ID
            LEFT JOIN bonuses b ON e.event_Bonuses = b.bonus_ID
            WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND e.event_Status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $sql .= " AND e.event_Category = ?";
            $params[] = $filters['category'];
        }
        if (!empty($filters['difficulty'])) {
            $sql .= " AND e.event_Coef = ?";
            $params[] = $filters['difficulty'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND e.event_DateTimeStart >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND e.event_DateTimeEnd <= ?";
            $params[] = $filters['date_to'];
        }
        $sql .= " ORDER BY e.event_DateTimeStart ASC";

        $stmt = database::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getEventStatuses()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("SELECT status_ID, status_Type FROM status_event");
        return $stmt->fetchAll();
    }

    public static function getCategories()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("SELECT category_ID, category_Type FROM category");
        return $stmt->fetchAll();
    }

    public static function getDifficulties()
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->query("SELECT coef_ID, coef_Difficult, coef_Value FROM coef");
        return $stmt->fetchAll();
    }
    public static function updateEvent($eventId, $data)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        UPDATE events SET 
            event_Title = ?,
            event_Description = ?,
            event_DateTimeStart = ?,
            event_DateTimeEnd = ?,
            event_Points = ?,
            event_Coef = ?,
            event_Status = ?,
            event_Category = ?,
            event_Bonuses = ?
        WHERE event_ID = ?
    ");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['date_start'],
            $data['date_end'],
            $data['points'],
            $data['coef_id'],
            $data['status_id'],
            $data['category_id'],
            $data['bonus_id'] ?: null,
            $eventId
        ]);
    }
    public static function createBonus($data)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Название бонуса не может быть пустым']);
            exit;
        }
        $stmt = database::$pdo->prepare("SELECT bonus_ID FROM bonuses WHERE bonus_Name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Бонус с таким названием уже существует']);
            exit;
        }
        $stmt = database::$pdo->prepare("INSERT INTO bonuses (bonus_Name) VALUES (?)");
        $stmt->execute([$name]);
        $id = database::$pdo->lastInsertId();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name]);
        exit;
    }
    public static function createEvent($data, $organizerId)
    {
        if (!database::$pdo) {
            database::connect();
        }
        $stmt = database::$pdo->prepare("
        INSERT INTO events (
            event_Title, event_Description, event_DateTimeStart, event_DateTimeEnd,
            event_Points, event_Coef, event_Status, event_Category, event_Bonuses, event_Organizer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['date_start'],
            $data['date_end'],
            $data['points'],
            $data['coef_id'],
            $data['status_id'],
            $data['category_id'],
            $data['bonus_id'] ?: null,
            $organizerId
        ]);
    }
}
