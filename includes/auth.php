<?php
require_once 'config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($email, $password) {
        $query = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        
        return false;
    }
    
    public function register($name, $email, $password) {
        // Check if user already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return false; // User already exists
        }
        
        // Check if this is the first user (make them admin)
        $countQuery = "SELECT COUNT(*) as count FROM users";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute();
        $userCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $role = ($userCount == 0) ? 'admin' : 'user';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertQuery = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $insertStmt = $this->db->prepare($insertQuery);
        
        if ($insertStmt->execute([$name, $email, $hashedPassword, $role])) {
            $userId = $this->db->lastInsertId();
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
}
?>
