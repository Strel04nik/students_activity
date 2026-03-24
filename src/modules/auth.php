<?php

namespace modules;

use db\database;

class Auth
{
    public static function login($data)
    {
        if (!database::$pdo) {
            database::connect();
        }

        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($login) || empty($password)) {
            header('Location: /auth?error=empty_fields');
            exit;
        }

        $stmt = database::$pdo->prepare("SELECT * FROM users WHERE user_Login = ? AND user_Password = ?");
        $stmt->execute([$login, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $fullname = '';
            if ($user['user_Role'] == 1) {
                $stmt = database::$pdo->prepare("SELECT participant_FullName FROM participant_profiles WHERE participant_UserID = ?");
                $stmt->execute([$user['user_ID']]);
                $fullname = $stmt->fetchColumn();
            } elseif ($user['user_Role'] == 3) {
                $stmt = database::$pdo->prepare("SELECT organizer_fullname FROM organizer_profiles WHERE organizer_ID = ?");
                $stmt->execute([$user['user_ID']]);
                $fullname = $stmt->fetchColumn();
            } elseif ($user['user_Role'] == 2) {
                $stmt = database::$pdo->prepare("SELECT spec_FullName FROM spectator_profiles WHERE spec_ID = ?");
                $stmt->execute([$user['user_ID']]);
                $fullname = $stmt->fetchColumn();
            }
            $_SESSION['user_fullname'] = $fullname ?: $user['user_Login'];
            session_start();
            $_SESSION['user_id'] = $user['user_ID'];
            $_SESSION['user_login'] = $user['user_Login'];
            $_SESSION['user_role'] = $user['user_Role'];
            $_SESSION['user_fullname'] = $fullname;

            header('Location: /');
            exit;
        } else {
            header('Location: /auth?error=invalid_credentials');
            exit;
        }
    }

    public static function logout($data)
    {
        session_start();
        session_destroy();
        header('Location: /');
        exit;
    }
}
