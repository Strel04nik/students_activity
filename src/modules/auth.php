<?php
namespace modules;

class Auth
{
    public static function login($data)
    {
        global $pdo;
        
        $login = $data['login'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            header('Location: /auth?error=empty_fields');
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_Login = ? AND user_Password = ?");
        $stmt->execute([$login, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user['user_ID'];
            $_SESSION['user_login'] = $user['user_Login'];
            $_SESSION['user_role'] = $user['user_Role'];
            
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
        header('Location: /auth');
        exit;
    }
}