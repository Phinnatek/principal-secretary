<?php
// ============================================================
// Database Configuration & Authentication Logic
// ============================================================

// ✅ System Limits & Error Reporting
set_time_limit(300);
ini_set('memory_limit', '1G');
error_reporting(E_ALL & ~E_DEPRECATED); 
ini_set('display_errors', '0'); // Keeps JSON responses clean

// ✅ Define root directory
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}
  
$autoloadFile = ROOT_DIR . '/assets/libraries/secure/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(ROOT_DIR);
        $dotenv->load();
    } catch (Throwable $e) {
        // Silently handle missing .env
    }
} 

// ============================================================
// Define Configuration Constants
// ============================================================
if (!defined('DB_HOST'))       define('DB_HOST',       $_ENV['DB_HOST'] ?? 'localhost');
if (!defined('DB_USER'))       define('DB_USER',       $_ENV['DB_USER'] ?? 'sedacoe_principal');
if (!defined('DB_PASSWORD'))   define('DB_PASSWORD',   $_ENV['DB_PASSWORD'] ?? 'yram0p**ed0J');
if (!defined('DB_NAME'))       define('DB_NAME',       $_ENV['DB_NAME'] ?? 'sedacoe_principal');
if (!defined('DB_PORT'))       define('DB_PORT',       $_ENV['DB_PORT'] ?? '3306');
if (!defined('DB_DRIVER'))     define('DB_DRIVER',     $_ENV['DB_DRIVER'] ?? 'mysql');
if (!defined('DB_CHARSET'))    define('DB_CHARSET',    $_ENV['DB_CHARSET'] ?? 'utf8mb4'); 

class Auth {
    // Single connection property used throughout the system
    public $con;

    // Constructor automatically hooks constants into PDO
    public function __construct() {
        $this->con = null;
        try {
            // Dynamically build DSN string
            $dsn = sprintf(
                "%s:host=%s;dbname=%s;port=%s;charset=%s",
                DB_DRIVER,
                DB_HOST,
                DB_NAME,
                DB_PORT,
                DB_CHARSET
            );

            $this->con = new PDO($dsn, DB_USER, DB_PASSWORD);
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->con->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Return JSON payload error cleanly if connection breaks
            header("Content-Type: application/json");
            echo json_encode(['status' => 'error', 'message' => "Database Connection Error: " . $exception->getMessage()]);
            exit;
        }
    }

    /**
     * Authenticate system users (Principal, Secretary, Staff)
     */
    public function login($email, $password) {
        $query = "SELECT u.*, r.role_name FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  WHERE u.email = :email LIMIT 1";
                  
        $stmt = $this->con->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['password'])) {
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role_name'];
                return true;
            }
        }
        return false;
    }

    /**
     * RBAC Gatekeeper: Blocks unauthorized access based on user role
     */
    public static function checkAccess($allowed_roles) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
            header("Location: unauthorized.php");
            exit();
        }
    }
}

// Global initialization for functional templates 
$auth = new Auth();
 
$con = $auth->con;
?>