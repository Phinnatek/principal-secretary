<?php



function backupDatabase($file, $con) {
    $dump = "-- MySQL Dump Clone\n";
    $dump .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
    $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    try {
        /* ============================
         * 0. DATABASE CREATION
         * ============================ */
        $dbRes = $con->query("SELECT DATABASE() AS dbname");
        $dbName = $dbRes->fetch(PDO::FETCH_ASSOC)['dbname'];

        $dbInfo = $con->query("
            SELECT DEFAULT_CHARACTER_SET_NAME AS charset,
           DEFAULT_COLLATION_NAME AS `collation`
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = '$dbName'
        ")->fetch(PDO::FETCH_ASSOC);

  

              $dump .= "CREATE DATABASE IF NOT EXISTS `$dbName` "
      . "CHARACTER SET " . $dbInfo['charset']
      . " COLLATE " . $dbInfo['collation'] . ";\n";


        $dump .= "USE `$dbName`;\n\n";
        

        /* ============================
         * 1. TABLES + DATA
         * ============================ */
        $tables = [];
        $result = $con->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $dump .= "DROP TABLE IF EXISTS `$table`;\n";

            // Get CREATE TABLE
            $row2 = $con->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $createTable = $row2['Create Table'];

            // Preserve AUTO_INCREMENT
            $aiRes = $con->query("
                SELECT AUTO_INCREMENT
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = '$table'
            ")->fetch(PDO::FETCH_ASSOC);
            if (!empty($aiRes['AUTO_INCREMENT'])) {
                $createTable .= " AUTO_INCREMENT=" . $aiRes['AUTO_INCREMENT'];
            }

            // Fetch table comment
            $tableCommentRes = $con->query("
                SELECT TABLE_COMMENT 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = '$table'
            ")->fetch(PDO::FETCH_ASSOC);
            $tableComment = $tableCommentRes['TABLE_COMMENT'] ?? '';
            if (!empty($tableComment)) {
                $createTable .= " COMMENT=" . $con->quote($tableComment); 
            }
 

            $dump .= $createTable . ";\n\n";

            // Dump data
            $result2 = $con->query("SELECT * FROM `$table`");
            $rows = $result2->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                foreach ($rows as $row3) {
                    $values = [];
                    foreach ($row3 as $val) { 
                        // If $val is null, it returns "NULL".  
                        $values[] = ($val === null) ? "NULL" : $con->quote($val);
                    }
                    $dump .= "INSERT INTO `$table` VALUES(" . implode(",", $values) . ");\n";
                }
                $dump .= "\n";
            }
        }

        /* ============================
         * 2. VIEWS
         * ============================ */
        $views = [];
        $res = $con->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        while ($row = $res->fetch(PDO::FETCH_NUM)) {
            $views[] = $row[0];
        }
        foreach ($views as $view) {
            $row = $con->query("SHOW CREATE VIEW `$view`")->fetch(PDO::FETCH_ASSOC);
            $dump .= "DROP VIEW IF EXISTS `$view`;\n";
            $dump .= $row['Create View'] . ";\n\n";
        }

        /* ============================
         * 3. TRIGGERS
         * ============================ */
        $triggers = $con->query("SHOW TRIGGERS");
        while ($trig = $triggers->fetch(PDO::FETCH_ASSOC)) {
            $triggerName = $trig['Trigger'];
            $row = $con->query("SHOW CREATE TRIGGER `$triggerName`")->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['SQL Original Statement'])) {
                $dump .= "DROP TRIGGER IF EXISTS `$triggerName`;\n";
                $dump .= "DELIMITER ;;\n";
                $dump .= $row['SQL Original Statement'] . ";;\n";
                $dump .= "DELIMITER ;\n\n";
            }
        }

        /* ============================
         * 4. PROCEDURES & FUNCTIONS
         * ============================ */
        $routines = $con->query("SELECT ROUTINE_TYPE, ROUTINE_NAME 
                                 FROM INFORMATION_SCHEMA.ROUTINES 
                                 WHERE ROUTINE_SCHEMA = DATABASE()");
        while ($routine = $routines->fetch(PDO::FETCH_ASSOC)) {
            $type = $routine['ROUTINE_TYPE'];
            $name = $routine['ROUTINE_NAME'];
            $row = $con->query("SHOW CREATE $type `$name`")->fetch(PDO::FETCH_ASSOC);
            $stmt = $row["Create $type"];
            $dump .= "DROP $type IF EXISTS `$name`;\n";
            $dump .= "DELIMITER ;;\n";
            $dump .= $stmt . ";;\n";
            $dump .= "DELIMITER ;\n\n";
        }

        /* ============================
         * 5. EVENTS
         * ============================ */
        $events = $con->query("SHOW EVENTS WHERE Db = DATABASE()");
        while ($ev = $events->fetch(PDO::FETCH_ASSOC)) {
            $eventName = $ev['Name'];
            $row = $con->query("SHOW CREATE EVENT `$eventName`")->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['Create Event'])) {
                $dump .= "DROP EVENT IF EXISTS `$eventName`;\n";
                $dump .= "DELIMITER ;;\n";
                $dump .= $row['Create Event'] . ";;\n";
                $dump .= "DELIMITER ;\n\n";
            }
        }
 

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $file_content = file_put_contents($file, $dump);
        return [
                'status' => 'success',
                'file'   => $file_content,
                'message'   => 'Successfully created'

            ];

    } catch (Throwable $e) {
        $dump = date("Y-m-d H:i:s") . " - Backup Error in backupDatabase: " . $e->getMessage();
        $file_content = file_put_contents($file, $dump);
        return [
                'status' => 'error',
                'file'   => $file_content,
                'message'   =>  $e->getMessage()
            ];

        error_log(date("Y-m-d H:i:s") . " - Backup Error in backupDatabase: " . $e->getMessage() . "\n", 3, "error.log");
    }

}


function createBackup($con, $uploadDir) {
    try {
        // $backupResult = createBackup($con, $uploadDir); // Use default directory, or
        $backupDirectory = $uploadDir.'backup/'; 

        // Create base backup directory if missing
        if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0777, true)) {
            throw new Exception("Could not create backup directory: " . $backupDirectory);
        }
        if (!is_writable($backupDirectory)) {
            throw new Exception("Backup directory is not writable: " . $backupDirectory);
        }

        // === Clean up old backups (older than 365 days) ===
        $now = time();
        $daysToKeep = 365;
        foreach (glob($backupDirectory . "*", GLOB_ONLYDIR) as $dir) {
            $folderDate = basename($dir); // e.g., "2025-09-18"
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderDate)) {
                $folderTime = strtotime($folderDate);
                if ($folderTime !== false && ($now - $folderTime) > ($daysToKeep * 86400)) {
                    // Delete old folder and contents
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST
                    );
                    foreach ($files as $file) {
                        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                    }
                    rmdir($dir);
                }
            }
        }

        // Create date-based subdirectory (e.g., backup/2025-09-18/)
        $dateDir = $backupDirectory . date('Y-m-d') . '/';
        if (!is_dir($dateDir) && !mkdir($dateDir, 0755, true)) {
            throw new Exception("Could not create date directory: " . $dateDir);
        }

        // Generate timestamped filenames
        $timestamp = date('H-i-s'); // only time since date is already in folder
        $sqlFile = $dateDir . 'backup_' . $timestamp . '.sql';
        $zipFile = $dateDir . 'backup_' . $timestamp . '.zip';

        // Step 1: Write SQL dump
        $backup = backupDatabase($sqlFile, $con);

        if ($backup["status"] == 'error') {
            throw new Exception("Error creating SQL dump. Error message: " . $backup["message"]);
        }

        // Step 2: Compress with password
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            throw new Exception("Could not create ZIP archive.");
        }

        $zip->addFile($sqlFile, basename($sqlFile));
        $zip->setEncryptionName(basename($sqlFile), ZipArchive::EM_AES_256, "Nana Yaw");
        $zip->close();

        // Delete plain SQL file (leave only encrypted zip)
        unlink($sqlFile);

        return ["status" => 'success', "message" => "Backup created successfully: " . $zipFile];

    } catch (Throwable $e) {
        error_log(date("Y-m-d H:i:s") . " - Backup Error: " . $e->getMessage() . "\n", 3, "error.log");
        return ["status" => 'error', "message" => $e->getMessage()];
    }
}
 

function get_system_info(): array
{
    try {

        if (session_status() === PHP_SESSION_NONE) session_start();

        $infoFile = '../assets/backend/software_info.json';

        if (!file_exists($infoFile)) {
            throw new RuntimeException('software_info.json not found');
        }

        $content = file_get_contents($infoFile);

        if ($content === false) {
            throw new RuntimeException('Failed to read software_info.json');
        }

        $info = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }

        $school_id = $_POST['school_id'] ?? ($_SESSION['school_id'] ?? 'no_id');

        $software_name = strtolower($info['software_name'] ?? 'application');
        $phinnatek_favicon = $info['software_favicon'] ?? '';
        $phinnatek_logo = $info['software_logo'] ?? '';

        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $serverPort = $_SERVER['SERVER_PORT'] ?? '';

        $exeoutput = $serverName === 'heserver' && $httpHost === 'heserver' && (string)$serverPort === '443';
        // $exeoutput = $serverName !== 'heserver' || $httpHost === 'heserver' && (string)$serverPort === '443';
        $data = [
                    'status' => 'success',
                    'school_id' => $school_id,
                    'software_name' => $software_name,
                    'software_favicon' => $phinnatek_favicon,
                    'software_logo' => $phinnatek_logo,
                    'exeoutput' => $exeoutput,
                    'raw_data' => $info,
                ];
        // error_log('GET_SYSTEM_INFO data: ' . print_r($data, true));

        return $data;

    } catch (Throwable $e) {

        error_log('GET_SYSTEM_INFO ERROR: ' . $e->getMessage());

        return [
            'status' => 'error',
            'message' => $e->getMessage(),
        ];
    }
}

 

function get_system_file($dbPath)
{
    try { 

        $default_avatar = '../assets/img/avatars/avatar.png';

        if (empty($dbPath)) return $default_avatar;

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['file_exists_cache'])) {
            $_SESSION['file_exists_cache'] = [];
        }

        $cleanPath = ltrim($dbPath, './');

        $system = get_system_info();

        if (($system['status'] ?? 'error') !== 'success') return $default_avatar;

        $exeoutput = $system['exeoutput'];
        $software_name = $system['software_name'];
        $school_id = $system['school_id'];

        $file_path = '';
        $check_path = '';

        if ($exeoutput) {

            $appData = getenv('APPDATA') ?: sys_get_temp_dir();

            // Remove leading "server_files/<school_id>/" if already present
            $cleanPath = preg_replace('#^server_files/' . preg_quote((string)$school_id, '#') . '/#', '', $cleanPath);
            $cleanPath = preg_replace('#^server_files/#', '', $cleanPath);

            // Real filesystem path
            $check_path = $appData . DIRECTORY_SEPARATOR . $software_name . DIRECTORY_SEPARATOR . 'server_files' . DIRECTORY_SEPARATOR . $school_id . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cleanPath);

            // Browser path
            $file_path = 'file:///' . str_replace('\\', '/', $check_path);

            error_log('EXE CHECK PATH: ' . $check_path);
            error_log('EXE SRC PATH: ' . $file_path);

        } else {

            if (strpos($cleanPath, 'assets/') === 0) {
                $file_path = '../' . $cleanPath;
            } else {
                $file_path = SYSTEM_MEDIA_BASE . $cleanPath;
            }

            $check_path = $file_path;
        }

        if (isset($_SESSION['file_exists_cache'][$check_path])) {
            return $_SESSION['file_exists_cache'][$check_path] ? $file_path : $default_avatar;
        }

        if (!$exeoutput && filter_var($file_path, FILTER_VALIDATE_URL)) {

            $headers = @get_headers($file_path);
            $isValid = $headers && strpos($headers[0], '200') !== false;

        } else {

            $isValid = file_exists($check_path);
        }

        $_SESSION['file_exists_cache'][$check_path] = $isValid;

        return $isValid ? $file_path : $default_avatar;

    } catch (Throwable $e) {

        error_log('GET_SYSTEM_FILE ERROR: ' . $e->getMessage());

        return $default_avatar;
    }
}

 

function decodeLogin($encoded) {
    $login_encript = 'mySuperSecretKey123!';
    if (empty($encoded)) return false;
    $data = base64_decode((string)$encoded, true);
    if ($data === false) return false;
    $key = hash('sha256', $login_encript, true);
    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    if (strlen($data) <= $ivLength) return false;
    $iv = substr($data, 0, $ivLength);
    $ciphertext = substr($data, $ivLength);
    $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    if ($plaintext === false) return false;
    $maybeJson = json_decode($plaintext, true); 
// Debug log
if ($maybeJson === null) {
    // error_log("Decrypted raw plaintext:\n" . $plaintext);
    // error_log("JSON decode error: " . json_last_error_msg());
} else {
    // error_log("Decrypted data (array):\n" . print_r($maybeJson, true));
}

    return (json_last_error() === JSON_ERROR_NONE) ? $maybeJson : $plaintext;
}
 



/**
 * Commits a structural system log record to the database audit trail table,
 * safely short-circuiting administrative testing nodes.
 * 
 * @param PDO $con Active system database connection reference
 * @param string $module Target system panel being mutated (e.g., 'Documents', 'Appointments')
 * @param string $action Explicit operation taxonomy keyword code (e.g., 'INSERT_LEDGER')
 * @param string $description Compliant descriptive auditing narrative tracking parameters
 * @return bool True on flawless entry generation execution, false on failure boundaries
 */
function recordSystemActivityLog(PDO $con, string $module, string $action, string $description): bool {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        
        // FIXED: Replaced illegal 'continue' with clean execution short-circuit return 
        // to bypass log flooding from your supervisor/developer testing ID context node
        if ($userId === 6) {
            return true; 
        }
        
        // Dynamic audit footprint table generation if not exists (Universal general collation alignment)
        $con->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `module` VARCHAR(50) NOT NULL,
            `action_type` VARCHAR(50) NOT NULL,
            `narrative` TEXT NOT NULL,
            `ip_address` VARCHAR(45) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        // Escape systemic strings keywords inside clean backticks to prevent reserved keyword collissions
        $query = "INSERT INTO `activity_logs` (`user_id`, `module`, `action_type`, `narrative`, `ip_address`) 
                  VALUES (:user_id, :module, :action, :narrative, :ip)";
                  
        $stmt = $con->prepare($query);
        return $stmt->execute([
            ':user_id'   => $userId,
            ':module'    => $module,
            ':action'    => $action,
            ':narrative' => $description,
            ':ip'        => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
        
    } catch (\Throwable $e) {
        error_log("❌ Critical Failure inside recordSystemActivityLog Engine: " . $e->getMessage());
        return false;
    }
}

/**
 * Compiles and retrieves all structural appointments datasets, lookup parameters,
 * and relational dictionary collections based on active role permissions for TODAY only.
 *
 * @param PDO $con Active system database connection reference
 * @return array Standardized operational array bundle containing data collections
 */
function getAppointmentsDashboardDataset(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = $_SESSION['role'] ?? '';
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        // 1. Fetch Dynamic Appointment Types for Select Dropdown Forms Generation
        $typeOptStmt = $con->query("SELECT id, type_name FROM appointment_types ORDER BY type_name ASC");
        $appointmentTypeOptions = $typeOptStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Compile Core Structural Lookup Options Lists
        $visitorTypeOptions = ['Student', 'Staff', 'Parent', 'External Guest'];
        $statusOptions      = ['Pending', 'Approved', 'Rejected', 'Completed'];
        $statusBadges       = [
            'Pending'   => 'warning', 
            'Approved'  => 'success', 
            'Rejected'  => 'danger', 
            'Completed' => 'info'
        ];

        // 3. Updated SQL Master Query restricted to CURDATE() [27-Jun-2026]
        $baseQuery = "SELECT a.*, u.name AS scheduler_name, t.type_name AS appointment_type_label 
                      FROM appointments a 
                      LEFT JOIN users u ON a.scheduled_by = u.id 
                      LEFT JOIN appointment_types t ON a.appointment_type_id = t.id
                      WHERE a.appointment_date = CURDATE()"; // HARD BOUNDARY: Today only

        if (in_array($userRole, ['Principal', 'Secretary'])) {
            $query = $baseQuery . " ORDER BY a.start_time ASC";
            $stmt = $con->prepare($query);
            $stmt->execute();
        } else {
            $query = $baseQuery . " AND a.scheduled_by = :user_id ORDER BY a.start_time ASC";
            $stmt = $con->prepare($query);
            $stmt->execute([':user_id' => $userId]);
        }

        $appointmentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status'                 => 'success',
            'appointments_list'       => $appointmentsList,
            'appointment_type_options' => $appointmentTypeOptions,
            'visitor_type_options'     => $visitorTypeOptions,
            'status_options'           => $statusOptions,
            'status_badges'            => $statusBadges
        ];

    } catch (\Throwable $e) {
        error_log("❌ Failure inside getAppointmentsDashboardDataset Helper: " . $e->getMessage());
        return [
            'status'                 => 'error',
            'message'                => $e->getMessage(),
            'appointments_list'       => [],
            'appointment_type_options' => [],
            'visitor_type_options'     => [],
            'status_options'           => [],
            'status_badges'            => []
        ];
    }
}

 /**
 * Compiles and retrieves all structural document datasets based on role visibility permissions,
 * automatically decoding the document_number to append file_index_code mapping markers.
 *
 * @param PDO $con Active system database connection reference
 * @return array Standardized operational array bundle containing data collections
 */
function getDocumentsDashboardDataset(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = $_SESSION['role'] ?? '';
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        $baseQuery = "SELECT d.*, u.name AS recorder_name 
                      FROM documents d 
                      LEFT JOIN users u ON d.logged_by = u.id";

        if (in_array($userRole, ['Principal', 'Secretary'])) {
            $query = $baseQuery . " ORDER BY d.created_at DESC";
            $stmt = $con->prepare($query);
            $stmt->execute();
        } else {
            $query = $baseQuery . " WHERE d.logged_by = :user_id ORDER BY d.created_at DESC";
            $stmt = $con->prepare($query);
            $stmt->execute([':user_id' => $userId]);
        }
        
        $rawRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $masterFileIndex = getInstitutionalFileIndexMap();

        // =========================================================================
        // REVERSE-ENGINEER DOCUMENT NUMBERS TO EXTRACT DYNAMIC INDEX KEYS
        // =========================================================================
        foreach ($rawRecords as $index => $row) {
            $docNumber = trim((string)$row['document_number']);
            $detectedIndexCode = '';
            $detectedCategoryName = 'General Document';

            // Regex breakdown to target padded index number chunks (e.g. SDACOE-ACADEMIC-01-2026-XXXX)
            // Matches text between separators to target the 3rd section containing digits
            if (preg_match('/^SDACOE-[A-Z0-9]+-(\d+)-\d+-/i', $docNumber, $matches)) {
                $detectedIndexCode = (int)$matches[1]; // Drops leading zeros dynamically (e.g., '01' -> 1)
            }

            // Fallback lookup scanner loop in case tracking code formats deviate from defaults
            if (empty($detectedIndexCode)) {
                foreach ($masterFileIndex as $letterGroup => $codes) {
                    foreach ($codes as $codeNum => $description) {
                        // Extract first word text snippet safely to test matching bounds
                        $rawWords = explode(' ', str_replace(['/', '-', '.'], ' ', $description));
                        $keywordMatch = !empty($rawWords[0]) ? strtoupper($rawWords[0]) : '';
                        
                        if (!empty($keywordMatch) && str_contains($docNumber, "-{$keywordMatch}-")) {
                            $detectedIndexCode = $codeNum;
                            break 2;
                        }
                    }
                }
            }

            // Map the resolved description label from your master dictionary registry array matrix
            if (!empty($detectedIndexCode)) {
                foreach ($masterFileIndex as $letterGroup => $codes) {
                    if (isset($codes[$detectedIndexCode])) {
                        $detectedCategoryName = $codes[$detectedIndexCode];
                        break;
                    }
                }
            }

            // Append variables on-the-fly straight into the array data stream rows payload contract
            $rawRecords[$index]['file_index_code'] = $detectedIndexCode; 
            $rawRecords[$index]['file_index_category_name'] = $detectedCategoryName; 
        }

        return [
            'status' => 'success',
            'documents_list' => $rawRecords
        ];
        
    } catch (\Throwable $e) {
        error_log("❌ Failure inside getDocumentsDashboardDataset: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage(), 'documents_list' => []];
    }
}


/**
 * Compiles and retrieves all college staff members and active system roles.
 *
 * @param PDO $con Active system database connection reference
 * @return array Standardized operational array bundle containing data collections
 */
function getStaffDashboardDataset(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Fetch Active System Roles for Dropdown generation
        $rolesStmt = $con->query("SELECT id, role_name FROM roles ORDER BY id ASC");
        $rolesList = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Fetch Master Staff Directory with Role Names Joined
        $query = "SELECT u.id, u.name, u.email, u.employee_id, u.department, u.designation, u.phone_number, u.status, u.role_id, r.role_name 
                  FROM users u 
                  JOIN roles r ON u.role_id = r.id WHERE u.id <> 6
                  ORDER BY u.department ASC, u.name ASC";
        $stmt = $con->query($query);
        $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Static dropdown configurations
        $departmentOptions = ['Administration', 'Computer Science', 'Mathematics', 'Humanities', 'Engineering', 'Finance Office'];
        $statusOptions = ['Active', 'Inactive'];

        return [
            'status'             => 'success',
            'staff_list'         => $staffList,
            'roles_list'         => $rolesList,
            'department_options' => $departmentOptions,
            'status_options'     => $statusOptions
        ];
    } catch (\Throwable $e) {
        error_log("❌ Failure inside getStaffDashboardDataset Helper: " . $e->getMessage());
        return [
            'status'             => 'error',
            'message'            => $e->getMessage(),
            'staff_list'         => [],
            'roles_list'         => [],
            'department_options' => [],
            'status_options'     => []
        ];
    }
}


/**
 * Compiles and retrieves visitor log datasets for the Principal office logbook.
 */
function getVisitorsDashboardDataset(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $statusOptions = ['Inside Office', 'Completed', 'Banned / Flagged'];
        
        // Fetch rows matching today's server date natively by default
        $query = "SELECT v.*, u.name as recorder_name 
                  FROM visitors_log v 
                  JOIN users u ON v.logged_by = u.id 
                  WHERE v.log_date = CURDATE() 
                  ORDER BY v.entry_time DESC";
        $stmt = $con->query($query);
        
        return [
            'status' => 'success',
            'visitors_list' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'status_options' => $statusOptions
        ];
    } catch (\Throwable $e) {
        error_log("❌ Failure in getVisitorsDashboardDataset: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage(), 'visitors_list' => []];
    }
}


/**
 * Compiles and retrieves memo registry logs for global bulletin board delivery.
 *
 * @param PDO $con Active system database connection reference
 * @return array Standardized dataset packet containing logs matrix
 */
function getMemosDashboardDataset(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $query = "SELECT m.*, u.name as sender_name 
                  FROM memos m 
                  JOIN users u ON m.sender_id = u.id 
                  ORDER BY m.memo_date DESC, m.created_at DESC";
        $stmt = $con->query($query);
        $memosList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status'     => 'success',
            'memos_list' => $memosList
        ];
    } catch (\Throwable $e) {
        error_log("❌ Failure in getMemosDashboardDataset Helper: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage(), 'memos_list' => []];
    }
}



/**
 * Compiles aggregated real-time metric counter statistics across all system tables 
 * using type-safe prepared queries and backtick-isolated SQL columns.
 * 
 * @param PDO $con Active system database connection reference
 * @return array Standardized metrics array package contract
 */
function getUnifiedDashboardMetrics(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) { 
            session_start(); 
        }
        $userRole = $_SESSION['role'] ?? '';
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        // =========================================================================
        // 1. APPOINTMENTS METRICS (TODAY ONLY RECEPTIONIST WINDOW)
        // =========================================================================
        $apptQuery = "SELECT 
                        COUNT(*) AS `total_today`,
                        SUM(CASE WHEN `status` = 'Pending' THEN 1 ELSE 0 END) AS `pending_today`,
                        SUM(CASE WHEN `status` = 'Approved' THEN 1 ELSE 0 END) AS `active_today`,
                        SUM(CASE WHEN `status` = 'Completed' THEN 1 ELSE 0 END) AS `finished_today`
                      FROM `appointments` WHERE `appointment_date` = CURDATE()";
        
        if ($userRole === 'Staff') { 
            $apptQuery .= " AND `scheduled_by` = :user_id"; 
        }
        
        $apptStmt = $con->prepare($apptQuery);
        if ($userRole === 'Staff') {
            $apptStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $apptStmt->execute();
        $apptMetrics = $apptStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_today' => 0, 'pending_today' => 0, 'active_today' => 0, 'finished_today' => 0
        ];

        // =========================================================================
        // 2. DOCUMENT LEDGER COUNTER MATRIX (DECOUPLED FROM DOC_TYPE)
        // =========================================================================
        $docQuery = "SELECT 
                        COUNT(*) AS `total_docs`,
                        SUM(CASE WHEN `status` = 'Pending Review' THEN 1 ELSE 0 END) AS `pending_docs`,
                        SUM(CASE WHEN `document_number` LIKE 'SDACOE-INCOMING-%' THEN 1 ELSE 0 END) AS `incoming_docs`
                     FROM `documents`";
        
        if ($userRole === 'Staff') { 
            $docQuery .= " WHERE `logged_by` = :user_id"; 
        }
        
        $docStmt = $con->prepare($docQuery);
        if ($userRole === 'Staff') {
            $docStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $docStmt->execute();
        $docMetrics = $docStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_docs' => 0, 'pending_docs' => 0, 'incoming_docs' => 0
        ];

        // =========================================================================
        // 3. VISITOR TRACKING SYSTEM METRICS (TODAY ONLY REGISTERS)
        // =========================================================================
        $visitorQuery = "SELECT 
                            COUNT(*) AS `total_visitors`,
                            SUM(CASE WHEN `status` = 'Inside Office' THEN 1 ELSE 0 END) AS `active_inside`
                         FROM `visitors_log` WHERE `log_date` = CURDATE()";
        
        $visitorStmt = $con->prepare($visitorQuery);
        $visitorStmt->execute();
        $visitorMetrics = $visitorStmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_visitors' => 0, 'active_inside' => 0
        ];

        // =========================================================================
        // 4. HR STAFF METRICS (GLOBAL OPERATIONAL PARAMETER)
        // =========================================================================
        $staffStmt = $con->prepare("SELECT COUNT(*) AS `active_staff` FROM `users` WHERE `status` = 'Active'");
        $staffStmt->execute();
        $staffCount = (int)$staffStmt->fetchColumn();

        // =========================================================================
        // 5. RECENT ACTIVITY LOGS TRAIL PIPELINE (LIMITED TO TOP 5 EVENTS)
        // =========================================================================
        $logQuery = "SELECT l.*, u.name AS `operator_name` 
                     FROM `activity_logs` l 
                     LEFT JOIN `users` u ON l.user_id = u.id 
                     ORDER BY l.created_at DESC LIMIT 5";
        
        $logStmt = $con->prepare($logQuery);
        $logStmt->execute();
        $activityLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Return unified status response matrix envelope
        return [
            'status'         => 'success',
            'appointments'   => $apptMetrics,
            'documents'      => $docMetrics,
            'visitors'       => $visitorMetrics,
            'staff_count'    => $staffCount,
            'activity_logs'  => $activityLogs
        ];

    } catch (\Throwable $e) {
        error_log("❌ Dashboard aggregation failure: " . $e->getMessage());
        return [
            'status'   => 'error',
            'message'  => 'Dashboard Engine Cache Exception: ' . $e->getMessage(),
            'appointments' => ['total_today' => 0, 'pending_today' => 0, 'active_today' => 0, 'finished_today' => 0],
            'documents'    => ['total_docs' => 0, 'pending_docs' => 0, 'incoming_docs' => 0],
            'visitors'     => ['total_visitors' => 0, 'active_inside' => 0],
            'staff_count'  => 0,
            'activity_logs'=> []
        ];
    }
}



/**
 * Retrieves the complete profile dataset for the actively logged-in session account node.
 * 
 * @param PDO $con Active system database connection reference
 * @return array Standardized profile query packet array
 */
function getActiveUserProfileData(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $sessionUserId = $_SESSION['user_id'] ?? 0;

        $stmt = $con->prepare("SELECT u.*, r.role_name 
                               FROM users u 
                               JOIN roles r ON u.role_id = r.id 
                               WHERE u.id = :id  LIMIT 1");
        $stmt->execute([':id' => $sessionUserId]);
        $profileRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profileRow) {
            return ['status' => 'error', 'message' => 'Identity node profile record could not be discovered.'];
        }

        return ['status' => 'success', 'data' => $profileRow];
    } catch (\Throwable $e) {
        error_log("❌ Profile extraction database crash: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}


/**
 * Compiles and retrieves system activity audit logs based on RBAC access parameters.
 * 
 * @param PDO $con Active system database connection reference
 * @return array Standardized log list and filtration metrics options package array
 */
function getActivityLogsDashboardDataset(PDO $con): array {
    try {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $userRole = $_SESSION['role'] ?? '';

        // RBAC enforcement boundary guard: standard staff nodes are blocked from global system audit lines
        if (!in_array($userRole, ['Principal', 'Secretary'])) {
            return ['status' => 'error', 'message' => 'Privilege Fault: Security profile lacks audit log access clearances.'];
        }

        // Extract distinct module categories to populate filtration drop-down boxes dynamically
        $moduleStmt = $con->query("SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL AND module != '' ORDER BY module ASC");
        $distinctModules = $moduleStmt->fetchAll(PDO::FETCH_COLUMN);

        // Extract default log metrics timeline list bounded to today's operations context loop
        $query = "SELECT l.*, u.name as operator_name, u.employee_id 
                  FROM activity_logs l 
                  LEFT JOIN users u ON l.user_id = u.id 
                  WHERE DATE(l.created_at) = CURDATE()
                  ORDER BY l.created_at DESC";
        $stmt = $con->query($query);
        $logsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status' => 'success',
            'logs_list' => $logsList,
            'distinct_modules' => $distinctModules,
            'action_types' => ['INSERT_VISITOR', 'UPDATE_VISITOR', 'CHECKOUT_VISITOR', 'INSERT_CORRESPONDENCE', 'UPDATE_CORRESPONDENCE', 'DELETE_CORRESPONDENCE', 'READ_RECEIPT', 'UPDATE_INFO', 'OVERRIDE_PASSWORD']
        ];
    } catch (\Throwable $e) {
        error_log("❌ Failure in getActivityLogsDashboardDataset: " . $e->getMessage());
        return ['status' => 'error', 'message' => $e->getMessage(), 'logs_list' => [], 'distinct_modules' => []];
    }
}


 /**
 * Scans file-bearing tables directly to locate rows with unsynced files,
 * downloading them via web URLs using an isolated cURL channel before encoding.
 * 
 * @param PDO $con Active system database connection reference
 * @return array Standardized status package containing either the Base64 file items array or an explicit error matrix
 */
function compilePendingBinaryFiles(PDO $con): array {
    try {
        $binaryFilesPayload = []; 

        // 1. Establish the base local web server URL root parameter path
        // Adjust 'principal/' to match your exact Laragon project folder directory name
        $baseServerUrl = 'https://localhost/';

        // =========================================================================
        // 1. SCAN USERS REGISTER FOR PENDING AVATARS
        // =========================================================================
        $userStmt = $con->prepare("SELECT `id`, `user_pic` FROM `users` WHERE `file_sync_status` = 'Pending' FOR UPDATE");
        $userStmt->execute();
        $pendingUsers = $userStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingUsers as $user) {
            $dbPathValue = trim((string)$user['user_pic']);
            
            if (empty($dbPathValue) || $dbPathValue === 'default_avatar.png') {
                continue;
            }

            // Construct the clean web URL and extract the pure filename element
            $absoluteUrl = $baseServerUrl . ltrim($dbPathValue, '/');
            $fileNameClean = basename($absoluteUrl);

            // Fetch binary data over HTTP using our robust cURL utility channel
            $fileRawBytes = fetchRemoteFileContentViaUrl($absoluteUrl);

            if ($fileRawBytes !== false && !empty($fileRawBytes)) {
                $binaryFilesPayload[] = [
                    'source_table' => 'users',
                    'record_id'    => (int)$user['id'],
                    'file_name'    => $fileNameClean,
                    'sub_folder'   => 'staff_images/',
                    'base64_data'  => base64_encode($fileRawBytes) // Encodes the downloaded bytes chunk
                ];
            } else {
                error_log("⚠️ Sync Warning: Local HTTP request failed or returned empty payload on avatar URL: [{$absoluteUrl}]");
            }
        }

        // =========================================================================
        // 2. SCAN DOCUMENTS REGISTER FOR PENDING ATTACHMENTS
        // =========================================================================
        $docStmt = $con->prepare("SELECT `id`, `file_path` FROM `documents` WHERE `file_sync_status` = 'Pending' FOR UPDATE");
        $docStmt->execute();
        $pendingDocs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingDocs as $doc) {
            $dbPathValue = trim((string)$doc['file_path']);
            
            if (empty($dbPathValue)) {
                continue;
            }

            // Construct the clean web URL and extract the pure filename element
            $absoluteUrl = $baseServerUrl . ltrim($dbPathValue, '/');
            $fileNameClean = basename($absoluteUrl);

            // Fetch binary data over HTTP using our robust cURL utility channel
            $fileRawBytes = fetchRemoteFileContentViaUrl($absoluteUrl);

            if ($fileRawBytes !== false && !empty($fileRawBytes)) {
                $binaryFilesPayload[] = [
                    'source_table' => 'documents',
                    'record_id'    => (int)$doc['id'],
                    'file_name'    => $fileNameClean,
                    'sub_folder'   => 'documents/',
                    'base64_data'  => base64_encode($fileRawBytes)
                ];
            } else {
                error_log("⚠️ Sync Warning: Local HTTP request failed or returned empty payload on document URL: [{$absoluteUrl}]");
            }
        }

        return [
            'status'  => 'success',
            'data'    => $binaryFilesPayload,
            'message' => 'Pending file assets extracted via web URLs and encoded successfully.'
        ];

    } catch (\Throwable $fileSystemException) {
        error_log("❌ URL Media File Sync Encoding Failure: " . $fileSystemException->getMessage());
        return [
            'status'  => 'error',
            'data'    => [],
            'message' => 'URL Stream System Buffer Exception: ' . $fileSystemException->getMessage()
        ];
    }
}

/**
 * Scans all registered administrative operational tables to compile database rows 
 * marked as 'Pending' for cloud synchronization, wrapping exceptions in safe array envelopes.
 * 
 * @param PDO $con Active local system database connection reference
 * @param array $tablesList Numeric array containing explicit target table names to parse
 * @return array Standardized array response contract containing the status, data bundle, and explicit message
 */
function compilePendingDatabaseChanges(PDO $con, array $tablesList): array {
    try {
        // Initialize the master multi-dimensional collection layer
        $payloadBundle = [];
        
        foreach ($tablesList as $table) {
            // Sanitize the table name token to prevent any structural SQL parsing variations
            $cleanTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            if (empty($cleanTableName)) {
                continue;
            }

            // Enforce a write-intent row lock constraint to avoid processing data modifications mid-stream
            $queryStr = "SELECT * FROM `$cleanTableName` WHERE `sync_status` = 'Pending' FOR UPDATE";
            $stmt = $con->prepare($queryStr);
            $stmt->execute();
            
            $pendingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If outstanding rows exist, capture them under their explicit table name index key
            if (!empty($pendingRows)) {
                $payloadBundle[$cleanTableName] = $pendingRows;
            }
        }
        
        return [
            'status'  => 'success',
            'data'    => $payloadBundle,
            'message' => 'Pending data ledger records compiled successfully.'
        ];

    } catch (\Throwable $databaseScanException) {
        // Log the exception context to server diagnostic error logs automatically
        error_log("❌ Database Sync Compilation Fault: " . $databaseScanException->getMessage());
        
        return [
            'status'  => 'error',
            'data'    => [],
            'message' => 'Database Scan Interrupt Error: ' . $databaseScanException->getMessage()
        ];
    }
}

/**
 * Robust cURL sub-helper to fetch remote or local HTTP/HTTPS file bytes 
 * safely ignoring self-signed SSL verification barriers.
 * 
 * @param string $targetUrl Complete URL address to fetch
 * @return string|bool Raw file contents string or false on failure
 */
function fetchRemoteFileContentViaUrl(string $targetUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    // CRITICAL FOR LARAGON LOCALHOST: Bypass SSL certificate verification barriers 
    // to prevent cURL from dropping requests made to self-signed domains
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $rawOutput = curl_exec($ch);
    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatusCode !== 200) {
        return false;
    }
    
    return $rawOutput;
}
 


/**
 * Decodes a remote Base64 compressed SQL schema string, temporarily bypasses trigger locks,
 * executes an absolute local database overwrite transaction, and restores safety triggers.
 * 
 * @param PDO $con Active system database connection reference
 * @param string $base64SqlDump Raw Base64 string payload returned from the Cloud API response
 * @return array Standardized array response contract reflecting execution properties
 */
function executeLocalDatabaseOverwriteFromCloud(PDO $con, string $base64SqlDump): array {
    try {
        if (empty($base64SqlDump)) {
            throw new Exception("Master SQL transmission token string is empty.");
        }

        // 1. Decode the compressed Base64 stream string back into plain SQL commands text
        $decodedSqlStatementsText = base64_decode($base64SqlDump);
        if ($decodedSqlStatementsText === false || empty(trim($decodedSqlStatementsText))) {
            throw new Exception("Cryptographic Fault: Base64 byte array decoding routine failed.");
        }

        // 2. TEMPORARILY DECOUPLE MANUAL BYPASS PROTECTION SIGNATURE FOR RAW EXECUTION
        // This grants the active thread permission to drop and overwrite system tables
        $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

        // 3. EXPLODE THE SQL STREAM INTO INDIVIDUAL QUERIES BY STRIPPING TERMINATORS
        // Splitting by semicolon allows the PDO line cursor to execute queries sequentially without crashing
        $queryArray = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $decodedSqlStatementsText);

        $con->beginTransaction();

        $executedQueriesCount = 0;
        foreach ($queryArray as $rawSqlLine) {
            $cleanSqlLine = trim($rawSqlLine);
            
            // Skip comments or blank newline characters inside the payload data stream
            if (empty($cleanSqlLine) || str_starts_with($cleanSqlLine, '--') || str_starts_with($cleanSqlLine, '/*')) {
                continue;
            }

            // Execute raw statement query lines onto local disk registers
            $con->exec($cleanSqlLine);
            $executedQueriesCount++;
        }
 
        // =========================================================================
        // 4. ROBUST ENFORCEMENT LAYER: REBUILD ALL PROTECTIVE & SYNC TRIGGERS
        // =========================================================================
        // Array of operational tables requiring both Security Locks & Sync Handshakes
        $tablesToProtect = ['users', 'visitors_log', 'memos', 'appointments', 'documents', 'activity_logs'];
        
        foreach ($tablesToProtect as $table) {
            // A. CLEAN UP ALL PRE-EXISTING TRIGGERS FIRST TO AVOID COMPILATION COLLISIONS
            $con->exec("DROP TRIGGER IF EXISTS `trg_{$table}_bypass_update_lock`");
            $con->exec("DROP TRIGGER IF EXISTS `trg_{$table}_bypass_delete_lock`");
            $con->exec("DROP TRIGGER IF EXISTS `trg_{$table}_insert_sync`");
            $con->exec("DROP TRIGGER IF EXISTS `trg_{$table}_update_sync`");
            $con->exec("DROP TRIGGER IF EXISTS `trg_{$table}_delete_sync`");

            // =====================================================================
            // MODULE I: ANTI-TAMPERING phpMyAdmin BYPASS SECURITY BLOCKS
            // =====================================================================
            // Build structural UPDATE manual entry barrier
            $con->exec("CREATE TRIGGER `trg_{$table}_bypass_update_lock` BEFORE UPDATE ON `{$table}` FOR EACH ROW 
                BEGIN
                    IF (@application_auth_context IS NULL OR @application_auth_context != 'SECURE_CAMPUS_DESK_TOKEN_2026') THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security Violation: Direct manual mutations via phpMyAdmin are strictly prohibited.';
                    END IF;
                END");

            // Build structural DELETE manual entry barrier
            $con->exec("CREATE TRIGGER `trg_{$table}_bypass_delete_lock` BEFORE DELETE ON `{$table}` FOR EACH ROW 
                BEGIN
                    IF (@application_auth_context IS NULL OR @application_auth_context != 'SECURE_CAMPUS_DESK_TOKEN_2026') THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security Violation: Direct manual record deletions via phpMyAdmin are strictly prohibited.';
                    END IF;
                END");

            // =====================================================================
            // MODULE II: AUTOMATED SYNCHRONIZATION QUEUE FLIPPERS
            // =====================================================================
            // Build INSERT tracker: Auto-flags sync_status to 'Pending' on new records
            $con->exec("CREATE TRIGGER `trg_{$table}_insert_sync` BEFORE INSERT ON `{$table}` FOR EACH ROW 
                BEGIN
                    SET NEW.sync_status = 'Pending';
                END");

            // Build UPDATE tracker: Auto-flags sync_status to 'Pending' on data alterations
            $con->exec("CREATE TRIGGER `trg_{$table}_update_sync` BEFORE UPDATE ON `{$table}` FOR EACH ROW 
                BEGIN
                    IF NOT (OLD.sync_status = 'Pending' AND NEW.sync_status = 'Synced') THEN
                        SET NEW.sync_status = 'Pending';
                    END IF;
                END");

            // =====================================================================
            // MODULE III: RELATIVE HARD DELETION LOGGERS (HARD REPLICATIONS KEY)
            // =====================================================================
            // Determine structural fallback variables values mappings contextually
            $referenceFieldColumn = 'NULL';
            if ($table === 'users') { 
                $referenceFieldColumn = 'OLD.employee_id'; 
            } elseif ($table === 'memos') { 
                $referenceFieldColumn = 'OLD.memo_ref'; 
            } elseif (in_array($table, ['visitors_log', 'appointments', 'documents'])) { 
                $referenceFieldColumn = "CONCAT('ID Reference Link: ', OLD.id)"; 
            }

            // Recreate the hard drop capture tracker trigger mapping into central deletions_log
            $con->exec("CREATE TRIGGER `trg_{$table}_delete_sync` AFTER DELETE ON `{$table}` FOR EACH ROW 
                BEGIN
                    INSERT INTO deletions_log (target_table, original_record_id, record_reference)
                    VALUES ('{$table}', OLD.id, {$referenceFieldColumn});
                END");
        }

        // =========================================================================
        // MODULE IV: ISOLATED RE-ANCHOR FOR DELETIONS_LOG IMMUTABILITY OVERRIDES
        // =========================================================================
        $con->exec("DROP TRIGGER IF EXISTS `trg_deletions_log_bypass_update_lock`");
        $con->exec("DROP TRIGGER IF EXISTS `trg_deletions_log_bypass_delete_lock`");

        $con->exec("CREATE TRIGGER `trg_deletions_log_bypass_update_lock` BEFORE UPDATE ON `deletions_log` FOR EACH ROW
            BEGIN
                IF (@application_auth_context IS NULL OR @application_auth_context != 'SECURE_CAMPUS_DESK_TOKEN_2026') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security Violation: Cloud sync deletion queues are immutable and cannot be altered via phpMyAdmin.';
                END IF;
            END");

        $con->exec("CREATE TRIGGER `trg_deletions_log_bypass_delete_lock` BEFORE DELETE ON `deletions_log` FOR EACH ROW
            BEGIN
                IF (@application_auth_context IS NULL OR @application_auth_context != 'SECURE_CAMPUS_DESK_TOKEN_2026') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Security Violation: Cloud sync deletion queues are immutable and cannot be wiped or deleted via phpMyAdmin.';
                END IF;
            END");


        $con->commit();

        return [
            'status'  => 'success',
            'message' => "Local workspace registry completely overwritten from authoritative Cloud Master database. Processed [{$executedQueriesCount}] transaction schema steps and successfully re-anchored all security triggers."
        ];

    } catch (\Throwable $databaseOverwriteException) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        error_log("❌ Critical Overwrite Overwrite Exception: " . $databaseOverwriteException->getMessage());
        return [
            'status'  => 'error',
            'message' => 'Authoritative Overwrite Interrupted: ' . $databaseOverwriteException->getMessage()
        ];
    }
}


/**
 * Static configuration register holding the official Seventh-day Adventist College 
 * of Education File Index classification numbers mapping array matrix.
 * 
 * @return array Alpha-categorized structural dictionary keys maps
 */
function getInstitutionalFileIndexMap(): array {
    return [
        'A' => [
            1 => 'ACADEMIC BOARD',
            2 => 'ACCREDITATION',
            3 => 'ACCEPTANCE LETTERS',
            4 => 'ADMISSIONS PROTOCOL',
            5 => 'APPLICATIONS',
            6 => 'APPOINTMENTS AND PROMOTION',
            7 => 'APPRECIATION',
            8 => 'ANNUAL/ CASUAL LEAVE',
            9 => 'AUDIT'
        ],
        'B' => [
            10 => 'BUDGET',
            11 => 'BUSINESS REGISTRATION'
        ],
        'C' => [
            12 => 'CASUAL STAFF',
            13 => 'CETAG',
            14 => 'CIRCULAR',
            15 => 'COLLEGE WELFARE',
            16 => 'COLLEGE POLYCLINIC',
            17 => 'CONFIDENTIAL'
        ],
        'E' => [
            18 => 'EXAMINATION/TIMETABLE',
            61 => 'EGA/SPORTS'
        ],
        'F' => [
            19 => 'FDA',
            20 => 'FINANCIAL REQUESTS',
            21 => 'FINANCE OFFICE',
            22 => 'FOREIGN TRAVELS'
        ],
        'G' => [
            23 => 'GTEC FINANCE',
            24 => 'GTEC',
            25 => 'GETFUND',
            26 => 'GRADUATION'
        ],
        'H' => [
            27 => 'HANDING OVER',
            28 => 'HALLS'
        ],
        'I' => [
            29 => 'ITECPD/GRADUATE SCH. UEW',
            30 => 'INTERNAL REPORTS 1',
            31 => 'INTERNAL REPORTS 2',
            32 => 'INTERNET SERVICE',
            33 => 'INVITATIONS AND NOTICES',
            34 => 'INTERNSHIP, ATTACHMENTS & NSS',
            35 => 'INSURANCE/ CERTIFICATE'
        ],
        'L' => [
            36 => 'LETTER OF INTRODUCTION',
            63 => 'LEGAL'
        ],
        'M' => [
            37 => 'MATRICULATION',
            38 => 'MINUTES'
        ],
        'N' => [
            39 => 'NATIONAL TEACHING COUNCIL'
        ],
        'O' => [
            40 => 'OVERTIME ALLOWANCE'
        ],
        'P' => [
            41 => 'PAYMENT VOUCHERS',
            42 => 'PROCUREMENT OFFICE',
            43 => 'PRINCOF'
        ],
        'Q' => [
            44 => 'QUERY'
        ],
        'R' => [
            45 => 'REQUEST FOR BUS',
            46 => 'RESPONSE TO REQUEST',
            47 => 'REPORTS AND PROPOSALS',
            48 => 'REQUESTS AND PERMISSIONS',
            49 => 'RESIDENCE AND HOUSING COMMITTEE',
            50 => 'RETIREMENT'
        ],
        'S' => [
            51 => 'STS',
            52 => 'SPONSORSHIP (V.R.A)',
            53 => 'STUDY LEAVE',
            54 => 'SCHOLARSHIP',
            55 => 'S.R.C',
            56 => 'SECURITY',
            57 => 'STUDENTS',
            58 => 'STAFF MIGRATION'
        ],
        'T' => [
            62 => 'TENANCY AGREEMENT',
            64 => 'TRANSPORT'
        ],
        'W' => [
            59 => 'WORKS AND DEVELOPMENT',
            60 => 'WORKSHOP'
        ]
    ];
}

/**
 * Static configuration register holding the official department branches.
 * 
 * @return array Key-value pairs mapping storage values to human-readable labels
 */
function getCollegeDepartmentsMap(): array {
    return [
        'Administration'         => '🏢 Administration & Central Desk',
        'Academic Affairs'       => '🎓 Academic Affairs Directorate',
        'Finance Office'         => '💰 Finance & Treasury Accounts',
        'Human Resources'        => '👥 Human Resources (HR) Division',
        'Procurement Logistics'  => '📦 Procurement & Stores Logistics',
        'Information Technology' => '💻 Information Technology (IT) Support',
        'Works Development'      => '🏗️ Works & Estates Development'
    ];
}


/**
 * Global Core Engine: Snapshot logs any table record row state to an immutable 
 * JSON cold backup archive, executes the hard drop, and handles sync trails.
 * 
 * @param PDO $con Active system database connection reference
 * @param string $tableName Explicit system table name to target (e.g., 'documents', 'appointments')
 * @param int $recordId Primary database key ID of the target record row
 * @param int $sessionUserId Active context identifier tracking the executing user
 * @return array Standardized array response contract reflecting execution success or explicit failure
 */
function executeGlobalRecordHardDeletion(PDO $con, string $tableName, int $recordId, int $sessionUserId): array {
    try {
        if ($recordId <= 0) {
            throw new Exception('Data Validation Fault: Invalid database primary row index identifier.');
        }

        // 1. SECURITY WHITELIST GUARD: Prevents SQL Injection vectors on the structural table variable
        $allowedSystemTables = ['documents', 'appointments', 'visitors_log', 'users', 'memos', 'activity_logs'];
        if (!in_array($tableName, $allowedSystemTables)) {
            throw new Exception("Security Violation: Destructive clearance denied on unauthorized table space: [{$tableName}].");
        }

        // Initialize safe isolated database transaction window if not already established
        $startedTransaction = false;
        if (!$con->inTransaction()) {
            $con->beginTransaction();
            $startedTransaction = true;
        }

        // 2. EXTRACT COMPLETE ROW DATA VIA DYNAMIC INTENT SELECT WRITE ROW-LOCK (SELECT * FOR COLD ARCHIVE SNAPSHOT)
        // Clean table name tokens via alphanumeric stripping for extra security boundaries layout
        $cleanTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $lockStr = "SELECT * FROM `$cleanTableName` WHERE `id` = :id LIMIT 1 FOR UPDATE";
        $lockStmt = $con->prepare($lockStr);
        $lockStmt->execute([':id' => $recordId]);
        $targetRow = $lockStmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetRow) {
            throw new Exception("Identity Context Error: Target record entry in table [{$cleanTableName}] already erased or absent.");
        }

        // 3. DYNAMIC REFERENCE COLUMNS DISCOVERY MAPPING
        // Resolves the best column option to present inside your text search indexing archives entries
        $recordReferenceString = "ID_REF_{$recordId}";
        $referencePriorityColumns = ['document_number', 'visitor_name', 'employee_id', 'memo_ref', 'name', 'title'];
        
        foreach ($referencePriorityColumns as $priorityCol) {
            if (isset($targetRow[$priorityCol]) && !empty(trim((string)$targetRow[$priorityCol]))) {
                $recordReferenceString = trim((string)$targetRow[$priorityCol]);
                break;
            }
        }

        // 4. CONVERT ENTIRE DATA SNAPSHOT MATRIX INTO COMPRESSED JSON STRING PACKET
        $stringifiedJsonPayload = json_encode($targetRow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($stringifiedJsonPayload === false) {
            throw new Exception('Serialization Fault: Failed to compile row metrics state matrix array to JSON.');
        }

        // 5. BYPASS ANTI-TAMPERING phpMyAdmin SAFEGUARD TRIGGERS NATIVELY ON THIS ACTIVE THREAD
        $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

        // 6. INJECT SNAPSHOT PAYLOAD DIRECTLY INTO HISTORICAL COLD STORAGE GENERAL COLLATION ARCHIVES
        $archiveQuery = "INSERT INTO `deleted_records_archive` (
                            `target_table`, `original_record_id`, `record_reference`, `json_payload`, `deleted_by`
                        ) VALUES (
                            :tbl, :orig_id, :rec_ref, :json_data, :user_id
                        )";
        $archiveStmt = $con->prepare($archiveQuery);
        $archiveStmt->execute([
            ':tbl'      => $cleanTableName,
            ':orig_id'  => $recordId,
            ':rec_ref'  => $recordReferenceString,
            ':json_data'=> $stringifiedJsonPayload,
            ':user_id'  => $sessionUserId
        ]);

        // 7. EXECUTE THE ABSOLUTE STRUCTURAL HARD DROP FROM ACTIVE DIRECTORY TABLE
        // This natively fires the table's AFTER DELETE sync triggers to append records to deletions_log
        $deleteQuery = "DELETE FROM `$cleanTableName` WHERE `id` = :id";
        $deleteStmt = $con->prepare($deleteQuery);
        $deleteStmt->execute([':id' => $recordId]);

        // 8. RECORD UNIVERSAL COMPLIANT SECURITY AUDIT NARRATIVE FOOTPRINT LOG LINES
        $logMsg = "[User ID: {$sessionUserId}] Executed hard deletion on ledger table [{$cleanTableName}] record reference: [{$recordReferenceString}]. Complete data snapshot archived safely to JSON vault logs.";
        recordSystemActivityLog($con, ucfirst($cleanTableName), 'HARD_DELETE', $logMsg);

         if (isset($con) && $con->inTransaction()) $con->commit();
        

        return [
            'status'  => 'success',
            'message' => "Record dropped locally from the " . htmlspecialchars($cleanTableName) . " register, logged to your cold snapshot archive table, and added to the cloud sync delete queue successfully."
        ];

    } catch (\Throwable $deletionFault) {
        if (isset($con) && $con->inTransaction()) {
            $con->rollBack();
        }
        error_log("❌ Error inside executeGlobalRecordHardDeletion helper engine: " . $deletionFault->getMessage());
        return [
            'status'  => 'error',
            'message' => 'Universal Destructive Engine Clearance Denied: ' . $deletionFault->getMessage()
        ];
    }
}

 
 
 
 
 
 /**
 * Resolves an index code selection to generate a strict chronological sequence number 
 * for a registry document reference marker code, returning a standardized status payload array.
 * 
 * @param PDO $con Active system database connection reference
 * @param int $indexCodeSelected The numerical file index chosen from the Choices.js box
 * @return array Standardized status package containing the tracking ID string or an explicit error matrix
 */
function generateInstitutionalTrackingNumber(PDO $con, int $indexCodeSelected): array {
    try {
        $indexDescriptionLabel = 'DOC';
        $masterIndexLookup = getInstitutionalFileIndexMap();
        
        // 1. Isolate the alpha label classification word from the index map matrix
        foreach ($masterIndexLookup as $group => $items) {
            if (isset($items[$indexCodeSelected])) {
                $rawWords = explode(' ', str_replace(['/', '-', '.'], ' ', $items[$indexCodeSelected]));
                $indexDescriptionLabel = !empty($rawWords[0]) ? strtoupper($rawWords[0]) : 'DOC';
                break;
            }
        }

        // Format the index code choice parameter with leading zeros (e.g., code 9 becomes '09')
        $formattedPaddedIndex = str_pad((string)$indexCodeSelected, 2, '0', STR_PAD_LEFT);
        $currentYear = date('Y');

        // Initialize variables for the retry mechanism
        $maxAttempts = 10;
        $attemptCount = 0;
        $compiledTrackingNumber = '';

        do {
            $attemptCount++;
            
            // Start a clean transaction checkpoint for this specific serial generation attempt
            $con->beginTransaction();

            // 2. QUERY FOR THE MAXIMUM CURRENT SEQUENCE NUMBER REGISTERED UNDER THIS CATEGORY IN THE ACTIVE YEAR
            $likePattern = "SDACOE-" . $indexDescriptionLabel . "-" . $formattedPaddedIndex . "-" . $currentYear . "-%";
            
            $query = "SELECT `document_number` 
                      FROM `documents` 
                      WHERE `document_number` LIKE :pattern 
                      ORDER BY `id` DESC LIMIT 1 FOR UPDATE";
                      
            $stmt = $con->prepare($query);
            $stmt->execute([':pattern' => $likePattern]);
            $lastRecordedDocument = $stmt->fetch(PDO::FETCH_ASSOC);

            $nextSequenceNumber = 1; // Default fallback to initialization counter row if it's the first file

            if ($lastRecordedDocument) {
                $lastTrackingCodeString = $lastRecordedDocument['document_number'];
                
                // Explode the tracking ID token by splitting at the hyphen dashes separators
                // Format layout index parts: 0:SDACOE, 1:LABEL, 2:INDEX, 3:YEAR, 4:SEQUENCE
                $codeSegmentsArray = explode('-', $lastTrackingCodeString);
                
                if (isset($codeSegmentsArray[4])) {
                    // Offset by the current loop attempt to dynamically clear collisions 
                    $lastSequenceInteger = (int)$codeSegmentsArray[4];
                    $nextSequenceNumber = $lastSequenceInteger + $attemptCount; 
                }
            } else {
                // If no record exists yet, increment sequence incrementally if initial attempts hit ghost collisions
                $nextSequenceNumber = $attemptCount;
            }

            // Pad your sequential tracker number with triple digit leading zeros (e.g., sequence 1 becomes '001')
            $formattedPaddedSequence = str_pad((string)$nextSequenceNumber, 3, '0', STR_PAD_LEFT);

            // Compile definitive structural layout sequence string 
            $compiledTrackingNumber = 'SEDACoE-' . $indexDescriptionLabel . '-' . $formattedPaddedIndex . '-' . $currentYear . '-' . $formattedPaddedSequence;

            // 3. VERIFY IMMEDIATELY IF LUCK HAS GENERATED A DUPLICATE ENTRY BEFORE PASSING TO INSERT
            $checkQuery = "SELECT COUNT(*) FROM `documents` WHERE `document_number` = :trackingNumber";
            $checkStmt = $con->prepare($checkQuery);
            $checkStmt->execute([':trackingNumber' => $compiledTrackingNumber]);
            $isDuplicate = (int)$checkStmt->fetchColumn() > 0;

            if (!$isDuplicate) {
                // Commit transaction lock—safe to provision tracking key sequence
                $con->commit();
                break; 
            }

            // If it is a duplicate, roll back the transaction and let the do-while loop iterate again
            $con->rollBack();

        } while ($attemptCount < $maxAttempts);

        if ($attemptCount >= $maxAttempts) {
            throw new \Exception("Could not generate a unique tracking number after {$maxAttempts} sequential attempts.");
        }

        return [
            'status'  => 'success',
            'data'    => $compiledTrackingNumber,
            'message' => 'Chronological tracking sequence ID generated successfully.'
        ];

    } catch (\Throwable $sequenceFault) {
        // Ensure any uncommitted transaction from a sudden failure rolls back cleanly
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        
        error_log("❌ Error in generateInstitutionalTrackingNumber: " . $sequenceFault->getMessage());
        return [
            'status'  => 'error',
            'data'    => '',
            'message' => 'Tracking Number Generator Error: ' . $sequenceFault->getMessage()
        ];
    }
}