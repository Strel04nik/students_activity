<?php
namespace modules;

class Reg
{
    public static function register($data)
    {
        global $pdo;
        
        $login = trim($data['login'] ?? '');
        $password = $data['password'] ?? '';
        $fullname = trim($data['fullname'] ?? '');
        $age = (int)($data['age'] ?? 0);
        $city = trim($data['city'] ?? '');
        $category = (int)($data['category'] ?? 1);
        
        if (empty($login) || empty($password) || empty($fullname) || empty($age) || empty($city)) {
            header('Location: /regpage?error=empty_fields');
            exit;
        }
        
        $nameParts = explode(' ', $fullname);
        if (count($nameParts) < 2) {
            header('Location: /regpage?error=invalid_name');
            exit;
        }
        
        if ($age < 14 || $age > 100) {
            header('Location: /regpage?error=invalid_age');
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT user_ID FROM users WHERE user_Login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            header('Location: /regpage?error=user_exists');
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO users (user_Login, user_Password, user_Role) VALUES (?, ?, 1)");
            $stmt->execute([$login, $password]);
            $userId = $pdo->lastInsertId();
            
            $targetScore = 100;
            
            $stmt = $pdo->prepare("INSERT INTO participant_profiles 
                (participant_UserID, participant_FullName, participant_category, participant_Age, participant_City, participant_TargetScore) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $fullname, $category, $age, $city, $targetScore]);
            
            $pdo->commit();
            
            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_login'] = $login;
            $_SESSION['user_role'] = 1;
            
            header('Location: /');
            exit;
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            header('Location: /regpage?error=registration_failed');
            exit;
        }
    }
}