<?php

namespace modules;

use db\database;

class Reg
{
    public static function register($data)
    {
        if (!database::$pdo) {
            database::connect();
        }

        $login = trim($data['login'] ?? '');
        $password = $data['password'] ?? '';
        $fullname = trim($data['fullname'] ?? '');
        $age = (int)($data['age'] ?? 0);
        $city = trim($data['city'] ?? '');
        $category = (int)($data['category'] ?? 1);

        if (empty($login) || empty($password) || empty($fullname) || empty($age) || empty($city)) {
            header('Location: /reg?error=empty_fields');
            exit;
        }

        $nameParts = explode(' ', $fullname);
        if (count($nameParts) < 2) {
            header('Location: /reg?error=invalid_name');
            exit;
        }

        if ($age < 14 || $age > 100) {
            header('Location: /reg?error=invalid_age');
            exit;
        }

        $stmt = database::$pdo->prepare("SELECT user_ID FROM users WHERE user_Login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            header('Location: /reg?error=user_exists');
            exit;
        }

        try {
            database::$pdo->beginTransaction();

            $stmt = database::$pdo->prepare("INSERT INTO users (user_Login, user_Password, user_Role) VALUES (?, ?, 1)");
            $stmt->execute([$login, $password]);
            $userId = database::$pdo->lastInsertId();

            $stmt = database::$pdo->prepare("INSERT INTO participant_profiles 
                (participant_UserID, participant_FullName, participant_category, participant_Age, participant_City) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $fullname, $category, $age, $city]);

            $stmt = database::$pdo->prepare("SELECT lvl_TargetScore FROM levelsrezerv WHERE lvl_ID = 1");
            $stmt->execute();
            $target = $stmt->fetch();
            $targetScore = $target ? $target['lvl_TargetScore'] : 100;

            $stmt = database::$pdo->prepare("INSERT INTO participant_levelsrate 
                (participant_UserID, participant_Level, participant_TotalScore, participant_missingScore) 
                VALUES (?, 1, 0, ?)");
            $stmt->execute([$userId, $targetScore]);

            database::$pdo->commit();

            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_login'] = $login;
            $_SESSION['user_role'] = 1;
            $_SESSION['user_fullname'] = $fullname;

            header('Location: /');
            exit;
        } catch (\Exception $e) {
            database::$pdo->rollBack();
            error_log($e->getMessage());
            header('Location: /reg?error=registration_failed');
            exit;
        }
    }
}
