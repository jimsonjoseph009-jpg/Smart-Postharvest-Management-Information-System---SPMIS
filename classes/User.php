<?php
/**
 * User.php — User Class extending Database and implementing Authenticatable
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/interfaces/Authenticatable.php';

class User extends Database implements Authenticatable {
    protected $id;
    protected $username;
    protected $email;
    protected $password_hash;
    protected $role;
    protected $full_name;
    protected $phone;
    protected $location;
    protected $created_at;

    public function __construct() {
        $this->connect();
    }

    public function getConnection() {
        return $this->conn;
    }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getUsername() { return $this->username; }
    public function setUsername($username) { $this->username = $username; }

    public function getEmail() { return $this->email; }
    public function setEmail($email) { $this->email = $email; }

    public function getPasswordHash() { return $this->password_hash; }
    public function setPasswordHash($password_hash) { $this->password_hash = $password_hash; }

    public function getRole() { return $this->role; }
    public function setRole($role) { $this->role = $role; }

    public function getFullName() { return $this->full_name; }
    public function setFullName($full_name) { $this->full_name = $full_name; }

    public function getPhone() { return $this->phone; }
    public function setPhone($phone) { $this->phone = $phone; }

    public function getLocation() { return $this->location; }
    public function setLocation($location) { $this->location = $location; }

    public function getCreatedAt() { return $this->created_at; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }

    /**
     * Authenticate user login
     */
    public function login($usernameOrEmail, $password) {
        $db = $this->getConnection();
        
        // Generate blind hashes for search lookup
        $searchHash = hash('sha256', strtolower(trim($usernameOrEmail)));
        
        $stmt = $db->prepare("
            SELECT * FROM `users` 
            WHERE `username_hash` = :uhash OR `email_hash` = :ehash 
            LIMIT 1
        ");
        $stmt->execute([
            ':uhash' => $searchHash,
            ':ehash' => $searchHash
        ]);
        $row = $stmt->fetch();
        
        if ($row) {
            // Verify password
            if (password_verify($password, $row['password_hash'])) {
                // Populate this object
                $this->id = $row['id'];
                $this->role = $row['role'];
                $this->password_hash = $row['password_hash'];
                
                // Decrypt fields and log audits
                $this->username = $this->decrypt($row['username']);
                $this->logAudit('users', $row['id'], 'username', 'DECRYPT', $row['username']);
                
                $this->email = $this->decrypt($row['email']);
                $this->logAudit('users', $row['id'], 'email', 'DECRYPT', $row['email']);
                
                $this->full_name = $this->decrypt($row['full_name']);
                $this->logAudit('users', $row['id'], 'full_name', 'DECRYPT', $row['full_name']);
                
                $this->phone = $this->decrypt($row['phone']);
                $this->logAudit('users', $row['id'], 'phone', 'DECRYPT', $row['phone']);
                
                $this->location = $this->decrypt($row['location']);
                $this->logAudit('users', $row['id'], 'location', 'DECRYPT', $row['location']);
                
                // Set Session
                $_SESSION['user_id'] = $this->id;
                $_SESSION['role'] = $this->role;
                $_SESSION['username'] = $this->username;
                $_SESSION['full_name'] = $this->full_name;
                
                $this->logSystemAction($this->id, "Ameingia kwenye mfumo (Logged In)");
                return true;
            }
        }
        
        $this->logSystemAction(null, "Jaribio la kuingia lililofeli kwa: " . htmlspecialchars($usernameOrEmail));
        return false;
    }

    /**
     * Register a new user
     */
    public function register(array $data) {
        $db = $this->getConnection();
        
        // Generate blind hashes
        $usernameHash = hash('sha256', strtolower(trim($data['username'])));
        $emailHash = hash('sha256', strtolower(trim($data['email'])));
        
        // Check for duplicates
        $stmt = $db->prepare("SELECT id FROM `users` WHERE `username_hash` = :u OR `email_hash` = :e LIMIT 1");
        $stmt->execute([':u' => $usernameHash, ':e' => $emailHash]);
        if ($stmt->fetch()) {
            throw new Exception("Jina la mtumiaji au barua pepe tayari inatumika (Username or Email already exists).");
        }
        
        // Encrypt fields
        $encUsername = $this->encrypt($data['username']);
        $encEmail = $this->encrypt($data['email']);
        $encFullName = $this->encrypt($data['full_name']);
        $encPhone = $this->encrypt($data['phone']);
        $encLocation = $this->encrypt($data['location']);
        
        $passHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO `users` 
            (`username`, `email`, `username_hash`, `email_hash`, `password_hash`, `role`, `full_name`, `phone`, `location`)
            VALUES (:u, :e, :uh, :eh, :ph, :r, :fn, :p, :l)
        ");
        
        $stmt->execute([
            ':u' => $encUsername,
            ':e' => $encEmail,
            ':uh' => $usernameHash,
            ':eh' => $emailHash,
            ':ph' => $passHash,
            ':r' => $data['role'],
            ':fn' => $encFullName,
            ':p' => $encPhone,
            ':l' => $encLocation
        ]);
        
        $newId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('users', $newId, 'username', 'ENCRYPT', $encUsername);
        $this->logAudit('users', $newId, 'email', 'ENCRYPT', $encEmail);
        $this->logAudit('users', $newId, 'full_name', 'ENCRYPT', $encFullName);
        $this->logAudit('users', $newId, 'phone', 'ENCRYPT', $encPhone);
        $this->logAudit('users', $newId, 'location', 'ENCRYPT', $encLocation);
        
        $this->logSystemAction($newId, "Amejisajili kwenye mfumo (User Registered)");
        return $newId;
    }

    /**
     * Log user out
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logSystemAction($_SESSION['user_id'], "Ametoka kwenye mfumo (Logged Out)");
        }
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        return true;
    }

    /**
     * Fetch user profile by ID
     */
    public function findById($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `users` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $u = new User();
            $u->setId($row['id']);
            $u->setRole($row['role']);
            $u->setPasswordHash($row['password_hash']);
            
            $u->setUsername($u->decrypt($row['username']));
            $u->logAudit('users', $row['id'], 'username', 'DECRYPT', $row['username']);
            
            $u->setEmail($u->decrypt($row['email']));
            $u->logAudit('users', $row['id'], 'email', 'DECRYPT', $row['email']);
            
            $u->setFullName($u->decrypt($row['full_name']));
            $u->logAudit('users', $row['id'], 'full_name', 'DECRYPT', $row['full_name']);
            
            $u->setPhone($u->decrypt($row['phone']));
            $u->logAudit('users', $row['id'], 'phone', 'DECRYPT', $row['phone']);
            
            $u->setLocation($u->decrypt($row['location']));
            $u->logAudit('users', $row['id'], 'location', 'DECRYPT', $row['location']);
            
            $u->setCreatedAt($row['created_at']);
            
            return $u;
        }
        return null;
    }

    /**
     * Update user profile
     */
    public function updateProfile(array $data) {
        $db = $this->getConnection();
        
        $encFullName = $this->encrypt($data['full_name']);
        $encPhone = $this->encrypt($data['phone']);
        $encLocation = $this->encrypt($data['location']);
        
        $stmt = $db->prepare("
            UPDATE `users` 
            SET `full_name` = :fn, `phone` = :p, `location` = :l 
            WHERE `id` = :id
        ");
        
        $stmt->execute([
            ':fn' => $encFullName,
            ':p' => $encPhone,
            ':l' => $encLocation,
            ':id' => $this->id
        ]);
        
        $this->logAudit('users', $this->id, 'full_name', 'ENCRYPT', $encFullName);
        $this->logAudit('users', $this->id, 'phone', 'ENCRYPT', $encPhone);
        $this->logAudit('users', $this->id, 'location', 'ENCRYPT', $encLocation);
        
        $this->full_name = $data['full_name'];
        $this->phone = $data['phone'];
        $this->location = $data['location'];
        
        $this->logSystemAction($this->id, "Amesasisha wasifu wake (Updated Profile)");
        return true;
    }

    /**
     * Delete user by ID
     */
    public function delete($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("DELETE FROM `users` WHERE `id` = :id");
        return $stmt->execute([':id' => (int)$id]);
    }
}
