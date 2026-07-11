<?php
declare(strict_types=1); 
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '1024M'); // Matches your 1G preference in a standard unit
gc_enable(); 
$ALLOW_ORIGIN = 'https://focusmedia.me';                   // frontend origin (change for prod)
$HMAC_SECRET   = getenv('HMAC_SECRET') ?: 'your-super-secure-shared-hmac-secret';
$CSRF_TTL      = 10 * 60; // 10 minutes default token lifetime
date_default_timezone_set('UTC');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $ALLOW_ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS'); 
header('Access-Control-Allow-Credentials: true'); 
header('Cache-Control: no-cache, must-revalidate');        // Optional: prevent caching  
session_set_cookie_params([
    'lifetime' => 0,        // expire on browser close
    'path'     => '/',
    // 'domain' => 'yourdomain.com', // set if needed
    'secure'   => true,     // HTTPS only
    'httponly' => true,     // not accessible to JS
    'samesite' => 'Strict'  // CSRF protection
]);
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('X-CSRF-Token: ' . $_SESSION['csrf_token']);
    // http_response_code(200);
    exit;
}elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed.']);
    exit;
}

require_once '../../assets/backend/system_dir_helper.php'; // needed 
error_log("POST Data:\n" . print_r($_POST, true));  
  
require_once '../../conn.php'; // your DB connection (adjust path)
require_once '../../assets/backend/helper_functions.php'; // if needed
// require_once '../../assets/backend/cron.php'; // if needed
$con->exec('SET SQL_BIG_SELECTS=1, MAX_JOIN_SIZE=9100000000'); 
// UNLOCK HOOK: Injected into the active DB thread context immediately to authorize code execution
$con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

try { 
   

if (!isset($con)) {
    echo json_encode(['status' => 'error', 'message' => 'System link connection dead.']);
    exit;
}

    if (!$con) {
        throw new Exception('Database link reference dropped or unassigned.');
    }

    // Access metrics extraction
    $sessionUserId = $_SESSION['user_id'] ?? 0;
    $sessionRole   = $_SESSION['role'] ?? '';

     // =========================================================================
    // AUTHORIZATION CONTEXT BOUNDARY GUARD & TRANSPARENT PAYLOAD TRACE
    // ========================================================================= 
    $isLoginAttempt = (isset($_POST['Systemlogin']) && ($_POST['Systemlogin'] === 'true' || $_POST['Systemlogin'] == true));

    if (!$isLoginAttempt) {
        // Enforce strict authorization session validations across all standard admin pages
        if (empty($sessionUserId) || empty($sessionRole)) {
            $dumpedPayloadString = print_r($_POST, true);
            throw new Exception("Operation Blocked: Active security authorization context or clearance tokens not found on host node directory registry. Data Stream Context Dump: \n" . $dumpedPayloadString);
        }
    }



    // =========================================================================
    // BRANCH U: TRANSMIT LOCAL DATA CHUNKS & BASE64 URL BINARY FILES TO CLOUD (`execute_database_cloud_sync=true`)
    // =========================================================================
    if (isset($_POST['execute_database_cloud_sync']) && $_POST['execute_database_cloud_sync'] === 'true') {
        try {
            // RBAC Access Guard: Only Principal and Secretary accounts can fire sync routines
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Security profile lacks execution clearance metrics.');
            }

            // 1. Define your remote cloud production target REST API landing destination
            $cloudSyncApiUrl = 'https://sedacoe.edu.gh/asset/backend/api/principal_secretary.php';
            $securedHandshakeToken = 'SECURE_KTU_SDACOE_BRIDGE_TOKEN_2026_XYZ'; 

            // Explicit list tracking all operational text data and ledger tables
            $tablesToSync = ['users', 'visitors_log', 'memos', 'appointments', 'activity_logs', 'documents', 'deletions_log'];

            // 2. EXTRACT DATA ROWS BY UNWRAPPING THE COMPILATION PACKET CONTRACT
            $dbChangesResponse = compilePendingDatabaseChanges($con, $tablesToSync);
            if ($dbChangesResponse['status'] === 'error') {
                throw new Exception("Database Engine Fault: " . $dbChangesResponse['message']);
            }
            
            $payloadBundle = $dbChangesResponse['data'];

            // Compute structural total quantity of records found inside row bundles
            $recordsCount = 0;
            foreach ($payloadBundle as $tableDataRows) {
                $recordsCount += count($tableDataRows);
            }

            // 3. CALL NEW HTTP URL COMPILER TO FETCH AND BASE64-ENCODE OUTSTANDING PHYSICAL FILES
            $filesResponse = compilePendingBinaryFiles($con);
            if ($filesResponse['status'] === 'error') {
                throw new Exception("File URL Storage Engine Fault: " . $filesResponse['message']);
            }
            
            $binaryFilesPayload = $filesResponse['data'];

            // Terminate processing early if both data rows and file queues are completely clear
            if ($recordsCount === 0 && empty($binaryFilesPayload)) {
                echo json_encode([
                    'status'  => 'success', 
                    'message' => 'Synchronization complete: No pending data rows or files waiting in local queues.'
                ]);
                exit;
            }

            // Assemble payload structure carrying rows, deletions, and Base64 media files
            $syncRequestPacket = [
                'handshake_token' => $securedHandshakeToken,
                'timestamp'       => date('Y-m-d H:i:s'),
                'origin_node'     => 'Local_Laragon_Campus_Desk',
                'dataset'         => $payloadBundle,
                'binary_files'    => $binaryFilesPayload 
            ];

            $jsonPayloadString = json_encode($syncRequestPacket);
            error_log(" syncRequestPacket " .print_r($syncRequestPacket, true));
            

            // 4. DISPATCH SECURED PAYLOAD TO CLOUD DESTINATION VIA CURL
            $curlHandle = curl_init($cloudSyncApiUrl);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $jsonPayloadString);
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 60); // Extended timeout limit (60s) for streaming binary file payloads
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true); // Enforce strict SSL tunnel integrity validations
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonPayloadString)
            ]);

            $apiResultResponse = curl_exec($curlHandle);
            $curlErrorNo       = curl_errno($curlHandle);
            $curlErrorMessage  = curl_error($curlHandle);
            $httpStatusCode    = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            curl_close($curlHandle);

            if ($curlErrorNo) {
                throw new Exception("Network Tunnel Interruption: Unable to reach online gateway. Details: {$curlErrorMessage}");
            }
            if ($httpStatusCode !== 200) {
                throw new Exception("Cloud Application Fault: Remote web server returned unhealthy status code: [{$httpStatusCode}].");
            }

            $decodedApiResponse = json_decode($apiResultResponse, true);
            if (!$decodedApiResponse || !isset($decodedApiResponse['status']) || $decodedApiResponse['status'] !== 'success') {
                $errorDetails = $decodedApiResponse['message'] ?? 'Unknown serialization formatting violation.';
                throw new Exception("Cloud Synchronization Denied: Remote destination rejected payload format. Reason: {$errorDetails}");
            }

            // 5. ON SUCCESS: BULK UPDATE ALL STANDARD DATA FLAGS AND FILE ATTACHMENT STATUSES LOCALLY
            $con->beginTransaction();
            
            // Re-inject the authorization key context first to unlock row mutations on tables (phpMyAdmin bypass triggers)
            $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

            // Update standard table text row sync status indicators
            foreach ($payloadBundle as $table => $rows) {
                $updateStmt = $con->prepare("UPDATE `$table` SET `sync_status` = 'Synced', `last_synced_at` = CURRENT_TIMESTAMP WHERE `id` = :id");
                foreach ($rows as $r) {
                    $updateStmt->execute([':id' => $r['id']]);
                }
            }
            
            // Update individual table rows that had associated physical file changes
            foreach ($binaryFilesPayload as $fileMeta) {
                $targetTable = $fileMeta['source_table'];
                $recordId    = $fileMeta['record_id'];
                
                $fileUpdateStmt = $con->prepare("UPDATE `$targetTable` SET `file_sync_status` = 'Synced' WHERE `id` = :id");
                $fileUpdateStmt->execute([':id' => $recordId]);
            }
            
            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
            $logMsg = "Dispatched data sync packet via HTTP URL extractor. Data rows mirrored: [{$recordsCount}], Binary files transferred: [" . count($binaryFilesPayload) . "]. All column flags flushed.";
            recordSystemActivityLog($con, 'SyncEngine', 'CLOUD_SYNC', $logMsg);

            $con->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => "Successfully mirrored {$recordsCount} database logs and " . count($binaryFilesPayload) . " media files up to your cloud server. Vault pipelines synced cleanly."
            ]);
            exit;

        } catch (\Throwable $syncEngineException) {
            if ($con->inTransaction()) { 
                $con->rollBack(); 
            }
            error_log("❌ Failure inside Cloud Handshake Sync Engine Branch (U): " . $syncEngineException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => 'Sync Failure: ' . $syncEngineException->getMessage()
            ]);
            exit;
        }
    }

 
    if (isset($_POST['update_profile_avatar']) && $_POST['update_profile_avatar'] === 'true') {
        try {
            // Validate identity context boundaries
            if (!$sessionUserId) {
                throw new Exception('Security Context Error: Active user identifier could not be determined.');
            }

            // Extract only the Base64 cropped text stream payload
            $base64ImageToken = isset($_POST['avatar_base64']) ? trim((string)$_POST['avatar_base64']) : '';

            if (empty($base64ImageToken)) {
                throw new Exception('Data Validation Fault: No cropped image signature payload stream was submitted.');
            }

            $uploadedFileNameSave = null;

            // 1. EVALUATE AND UNPACK BASE64 GRAPHIC DATA URL STRING CHUNKS
            if (preg_match('/^data:image\/(\w+);base64,/', $base64ImageToken, $mimeTypeMatches)) {
                $imageExtensionType = strtolower($mimeTypeMatches[1]); // Extract extension string (jpeg, png, etc.)
                $imageExtensionClean = ($imageExtensionType === 'jpeg') ? 'jpg' : $imageExtensionType;

                // Strip metadata data headers prefix lines off the raw string layout
                $pureBase64EncodedString = substr($base64ImageToken, strpos($base64ImageToken, ',') + 1);
                $decodedBinaryFileData = base64_decode($pureBase64EncodedString);

                if ($decodedBinaryFileData === false) {
                    throw new Exception('Media Compilation Error: Transmission byte decoding routine verification dropped.');
                }

                // Generate a highly secure, non-clashing unique file name hash matrix
                $uploadedFileNameSave = 'avatar_' . $sessionUserId . '_' . bin2hex(random_bytes(4)) . '.' . $imageExtensionClean;
                
                // Enforce your specific project global upload subdirectory tracking mapping
                $avatarStorageDirectory = rtrim($uploadDir, '/') . '/staff_images/';

                if (!is_dir($avatarStorageDirectory)) {
                    mkdir($avatarStorageDirectory, 0755, true);
                }

                // Execute the raw bytes dump onto the server storage volume disc
                $absoluteTargetDestination = $avatarStorageDirectory . $uploadedFileNameSave;
                if (file_put_contents($absoluteTargetDestination, $decodedBinaryFileData) === false) {
                    throw new Exception('Server Storage Fault: System failed to write raw media bytes streams onto storage discs.');
                }
            } else {
                throw new Exception('Media Constraint Error: Invalid image encoding token profile signature submitted.');
            }
                $uploadUrl = rtrim($uploadUrl, '/') . '/staff_images/'.$uploadedFileNameSave;

            // 2. ISOLATED DATABASE WRITE MUTATION (UPDATES USER_PIC CELL ONLY)
            // Implicitly wrapped column within backticks to guarantee structural engine compliance
            $updateQuery = "UPDATE `users` SET `user_pic` = :pic WHERE `id` = :id";
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([
                ':pic' => $uploadUrl,
                ':id'  => $sessionUserId
            ]);

            // Update active session state layout reference parameters so navigation headers repaint instantly
            $_SESSION['user_pic'] = $uploadedFileNameSave;

            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
            $logMsg = "index Successfully uploaded and updated cropped personnel profile avatar photo name: [{$uploadedFileNameSave}].";
            recordSystemActivityLog($con, 'Profile', 'UPDATE_AVATAR', $logMsg);

            echo json_encode([
                'status'  => 'success', 
                'message' => 'Personnel profile avatar photo synchronized and updated successfully.'
            ]);
            exit;

        } catch (\Throwable $branchRException) {
            error_log("❌ Failure inside Profile Base64 Cropper Branch (R): " . $branchRException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchRException->getMessage()
            ]);
            exit;
        }
    }


   // =========================================================================
    // BRANCH T: DYNAMIC SERVER SIDE AUDIT LOGBOOK SEARCH FILTER (`fetch_filtered_logs=true`)
    // =========================================================================
    if (isset($_POST['fetch_filtered_logs']) && $_POST['fetch_filtered_logs'] === 'true') {
        try {
            // Core Security Guard Enforcement Block
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Violation: Security clearance profiles lack log ledger extraction access.');
            }

            // Extract values and clean text field inputs cleanly
            $keywordFilter = isset($_POST['search_keyword']) ? trim((string)$_POST['search_keyword']) : '';
            $moduleFilter  = isset($_POST['filter_module']) ? trim((string)$_POST['filter_module']) : '';
            $dateFrom      = isset($_POST['filter_from_date']) ? trim((string)$_POST['filter_from_date']) : '';
            $dateTo        = isset($_POST['filter_to_date']) ? trim((string)$_POST['filter_to_date']) : '';

            // Master baseline SQL layout targeting the joined user operator records mapping
            $sqlQuery = "SELECT l.*, u.name as operator_name, u.employee_id 
                         FROM activity_logs l 
                         LEFT JOIN users u ON l.user_id = u.id 
                         WHERE 1=1";
            
            $bindParams = [];

            // 1. Alphanumeric search boundaries criteria matching keyword
            if (!empty($keywordFilter)) {
                $sqlQuery .= " AND (l.narrative LIKE :keyword OR l.action_type LIKE :keyword OR u.name LIKE :keyword OR u.employee_id LIKE :keyword)";
                $bindParams[':keyword'] = "%{$keywordFilter}%";
            }

            // 2. Filter exact match components profile modules
            if (!empty($moduleFilter)) {
                $sqlQuery .= " AND l.module = :module";
                $bindParams[':module'] = $moduleFilter;
            }

            // 3. Chronological date limit boundary sweeps 
            if (!empty($dateFrom)) {
                $sqlQuery .= " AND DATE(l.created_at) >= :date_from";
                $bindParams[':date_from'] = $dateFrom;
            }
            if (!empty($dateTo)) {
                $sqlQuery .= " AND DATE(l.created_at) <= :date_to";
                $bindParams[':date_to'] = $dateTo;
            }
            
            // Default Date Fallback Boundary to Today if form range metrics are left empty
            if (empty($dateFrom) && empty($dateTo)) {
                $sqlQuery .= " AND DATE(l.created_at) = CURDATE()";
            }

            // Order chronological listings by descending timestamp parameters mapping
            $sqlQuery .= " ORDER BY l.created_at DESC";

            $stmt = $con->prepare($sqlQuery);
            $stmt->execute($bindParams);
            $filteredAuditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Dispatch success package payload straight back to client re-rendering table
            echo json_encode([
                'status' => 'success',
                'data'   => $filteredAuditLogs
            ]);
            exit;

        } catch (\Throwable $branchTException) {
            error_log("❌ Failure inside Activity Logbook Fetch Branch (T): " . $branchTException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchTException->getMessage(),
                'data'    => []
            ]);
            exit;
        }
    }
    
    
        // =========================================================================
    // BRANCH W2: ALLOCATE PRINCIPAL TIMELINE & CONFIRM BOOKING (`approve_appointment_request=true`)
    // =========================================================================
    if (isset($_POST['approve_appointment_request']) && $_POST['approve_appointment_request'] === 'true') {
        try {
            // RBAC Access Guard Clearance Validation Gate Checks
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Security profiles do not hold administrative calendar sign-off clearance.');
            }

            // Extract core scheduling parameters package variables arrays elements
            $recordId      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $confirmedDate = isset($_POST['appointment_date']) ? trim((string)$_POST['appointment_date']) : '';
            $startTime     = isset($_POST['start_time']) ? trim((string)$_POST['start_time']) : '00:00:00';
            $endTime       = isset($_POST['end_time']) ? trim((string)$_POST['end_time']) : '00:00:00';

            if ($recordId <= 0) {
                throw new Exception('Data Validation Fault: Missing target database primary row reference pointer.');
            }
            if (empty($confirmedDate)) {
                throw new Exception('Data Validation Fault: You must select a clear calendar date the Principal is free to meet.');
            }

            // Initialize isolated safe transaction write lock intent
            $con->beginTransaction();

            $lockStmt = $con->prepare("SELECT `id`, `visitor_name` FROM `appointments` WHERE `id` = :id LIMIT 1 FOR UPDATE");
            $lockStmt->execute([':id' => $recordId]);
            $appointmentRow = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$appointmentRow) {
                throw new Exception('Identity Context Error: Target appointment booking slot not discovered or already dropped.');
            }

            $visitorNameText = $appointmentRow['visitor_name'];

            // Bypass anti-tampering phpMyAdmin triggers natively on this thread context session
            $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

            // Update database row fields, shifting status to Approved and stamping time indices
            // Explicitly resets sync_status to Pending to notify outbound sync engine
            $updateQuery = "UPDATE `appointments` 
                            SET `status`           = 'Approved', 
                                `appointment_date` = :appt_date, 
                                `start_time`       = :start_t, 
                                `end_time`         = :end_t,
                                `sync_status`      = 'Pending' 
                            WHERE `id` = :id";
            
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([
                ':appt_date' => $confirmedDate,
                ':start_t'   => $startTime,
                ':end_t'     => $endTime,
                ':id'        => $recordId
            ]);

            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing operator session context ID
            $logMsg = "Authorized and approved appointment booking for guest [{$visitorNameText}]. Meeting scheduled on [{$confirmedDate}] timeline interval window: [{$startTime} - {$endTime}].";
            recordSystemActivityLog($con, 'Appointments', 'APPROVE_SCHEDULE', $logMsg);

            if (isset($con) && $con->inTransaction()) $con->commit();

            // Re-fetch freshly updated role-based records collection dataset package array configuration signatures
            $freshDataset = getAppointmentsDashboardDataset($con);
            echo json_encode([
                'status'  => 'success',
                'data'    => $freshDataset['appointments_list'], // Automatically flows back to unwrap list layout updates
                'message' => 'Filing committed smoothly.'
            ]);
            exit;

        } catch (\Throwable $branchW2Exception) {
            if (isset($con) && $con->inTransaction()) {
                $con->rollBack();
            }
            error_log("❌ Failure inside Appointment Approval Allocation Branch (W2): " . $branchW2Exception->getMessage());
            echo json_encode([
                'status'  => 'error',
                'message' => 'Clearance Interrupted: ' . $branchW2Exception->getMessage()
            ]);
            exit;
        }
    }


    // =========================================================================
    // BRANCH R: UPDATE ACTIVE PERSONNEL PROFILE BIOMETRICS (`update_profile_info=true`)
    // =========================================================================
    if (isset($_POST['update_profile_info']) && $_POST['update_profile_info'] === 'true') {
        try {
            if (!$sessionUserId) {
                throw new Exception('Security Context Error: Active user identifier could not be determined.');
            }

            // Extract string values safely from post fields matrix
            $updateName  = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
            $updateEmail = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
            $updatePhone = isset($_POST['phone_number']) ? trim((string)$_POST['phone_number']) : '';

            // Strict data presence validation checks
            if (empty($updateName) || empty($updateEmail) || empty($updatePhone)) {
                throw new Exception('Data Validation Fault: Core profile contact information parameters cannot be left empty.');
            }

            if (!filter_var($updateEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Data Validation Fault: The email address provided follows an invalid structural layout formatting rule.');
            }

            // Secure target record row with an explicit write row lock check block
            $checkQuery = $con->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1 FOR UPDATE");
            $checkQuery->execute([':email' => $updateEmail, ':id' => $sessionUserId]);
            if ($checkQuery->fetch()) {
                throw new Exception('Data Collision Fault: The chosen email address is already locked by another active personnel directory account.');
            }

            // Commit profile information parameters modifications natively
            $updateQuery = "UPDATE `users` SET `name` = :name, `email` = :email, `phone_number` = :phone WHERE `id` = :id";
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([
                ':name'  => $updateName,
                ':email' => $updateEmail,
                ':phone' => $updatePhone,
                ':id'    => $sessionUserId
            ]);

            // Re-sync active runtime session parameters to reflect mutations on header components instantly
            $_SESSION['user_name'] = $updateName;

            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
            $logMsg = " Updated core contact information parameters inside account directory (New Email: [{$updateEmail}] Phone: [{$updatePhone}]).";
            recordSystemActivityLog($con, 'Profile', 'UPDATE_INFO', $logMsg);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Personnel identity details altered successfully.'
            ]);
            exit;

        } catch (\Throwable $branchRException) {
            error_log("❌ Failure inside Profile Info Save Branch (R): " . $branchRException->getMessage());
            echo json_encode([
                'status'  => 'error',
                'message' => $branchRException->getMessage()
            ]);
            exit;
        }
    }

    // =========================================================================
    // BRANCH S: CRYPTOGRAPHIC PASSWORD KEY KEY OVERRIDE (`update_profile_security=true`)
    // =========================================================================
    if (isset($_POST['update_profile_security']) && $_POST['update_profile_security'] === 'true') {
        try {
            if (!$sessionUserId) {
                throw new Exception('Security Context Error: Active user identifier could not be determined.');
            }

            // Extract alphanumeric raw strings metrics parameters safely
            $currentPasswordInput = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
            $newPasswordInput     = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';

            if (empty($currentPasswordInput) || empty($newPasswordInput)) {
                throw new Exception('Data Validation Fault: Current and target new password values cannot be left empty.');
            }

            if (strlen($newPasswordInput) < 6) {
                throw new Exception('Data Validation Fault: New security password key values must contain at least 6 characters.');
            }

            // Isolate active password hash string metrics with an update row lock constraint
            $query = $con->prepare("SELECT `password` FROM `users` WHERE `id` = :id LIMIT 1 FOR UPDATE");
            $query->execute([':id' => $sessionUserId]);
            $userRow = $query->fetch(PDO::FETCH_ASSOC);

            if (!$userRow) {
                throw new Exception('Identity Context Fault: Target database system user node not found.');
            }

            // Natively cross-verify existing current cryptographic hash signature parameters matching variables
            if (!password_verify($currentPasswordInput, $userRow['password'])) {
                throw new Exception('Verification Denied: The current active password key you entered is incorrect.');
            }

            // Generate a secure, production-ready, non-reversible cryptographic key hash [27-Jun-2026]
            $securedPasswordHashString = password_hash($newPasswordInput, PASSWORD_BCRYPT, ['cost' => 12]);

            // Execute the master record key parameters write mutation override statement
            $updateQuery = "UPDATE `users` SET `password` = :new_hash WHERE `id` = :id";
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([
                ':new_hash' => $securedPasswordHashString,
                ':id'       => $sessionUserId
            ]);

            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
            $logMsg = " Dispatched secure password cryptographic key override signature. Access tokens revoked and altered successfully.";
            recordSystemActivityLog($con, 'Profile', 'OVERRIDE_PASSWORD', $logMsg);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Cryptographic security key password modified successfully.'
            ]);
            exit;

        } catch (\Throwable $branchSException) {
            error_log("❌ Failure inside Profile Security Reset Branch (S): " . $branchSException->getMessage());
            echo json_encode([
                'status'  => 'error',
                'message' => $branchSException->getMessage()
            ]);
            exit;
        }
    }

 


        // =========================================================================
    // BRANCH L: BROADCAST / PUBLISH UNIFIED CORRESPONDENCE (`save_memo=true`)
    // =========================================================================
    if (isset($_POST['save_memo']) && $_POST['save_memo'] === 'true') {
        try {
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Security profiles do not hold sufficient authorization clearance.');
            }

            // Extract metadata metrics and text payloads safely
            $recordId       = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
            $docTitle       = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
            $officeName     = isset($_POST['office_name']) ? trim((string)$_POST['office_name']) : 'Office of the Principal';
            $docClass       = isset($_POST['document_classification']) ? trim((string)$_POST['document_classification']) : 'Memo';
            $urgencyPriority = isset($_POST['priority']) ? trim((string)$_POST['priority']) : 'Normal';
            $officialDate   = isset($_POST['memo_date']) ? trim((string)$_POST['memo_date']) : date('Y-m-d');
            $richAddressHtml = isset($_POST['recipient_address']) ? trim((string)$_POST['recipient_address']) : '';
            $richContentHtml = isset($_POST['content']) ? trim((string)$_POST['content']) : '';
            $richSubscriptionHtml = isset($_POST['subscription']) ? trim((string)$_POST['subscription']) : '';

            // Strip pure text tags to validate fields data parameters
            $strippedAddressText = trim(strip_tags($richAddressHtml));
            $strippedContentText = trim(strip_tags($richContentHtml));
            $strippedSubText      = trim(strip_tags($richSubscriptionHtml));

            // Granular data validation parameters checkpoints
            if (empty($docTitle)) { throw new Exception('Data Validation Fault: The "Document Subject" heading cannot be left blank.'); }
            if (empty($officeName)) { throw new Exception('Data Validation Fault: The originating "Office Name" field cannot be left blank.'); }
            if (empty($officialDate)) { throw new Exception('Data Validation Fault: The "Official Document Date" parameters cannot be empty.'); }
            if (empty($strippedAddressText)) { throw new Exception('Data Validation Fault: Recipient designation data context cannot be left empty.'); }
            if (empty($strippedContentText)) { throw new Exception('Data Validation Fault: Main document content text body cannot be left empty.'); }
            if (empty($strippedSubText)) { throw new Exception('Data Validation Fault: Closing subscription valedictions text lines cannot be left empty.'); }
            if (!$sessionUserId) { throw new Exception('Security Context Error: Unable to determine active user session identifier.'); }

            if (empty($recordId)) {
                // =============================================================
                // MODE: INSERT NEW CORRESPONDENCE / DISPATCH BROADCAST
                // =============================================================
                $prefixMap = ['Memo' => 'MEMO', 'Official Letter' => 'LTR', 'Circular' => 'CIR'];
                $prefixCode = $prefixMap[$docClass] ?? 'DOC';
                $generatedReferenceCode = $prefixCode . '-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)));

                $insertQuery = "INSERT INTO `memos` (
                                    `memo_ref`, `document_classification`, `office_name`, `title`, 
                                    `content`, `subscription`, `priority`, `sender_id`, 
                                    `recipient_address`, `memo_date`, `read_by_users`
                                ) VALUES (
                                    :ref, :class, :office, :title, 
                                    :content, :sub, :priority, :sender, 
                                    :address, :memo_date, ''
                                )";
                
                $stmt = $con->prepare($insertQuery);
                $stmt->execute([
                    ':ref'        => $generatedReferenceCode,
                    ':class'      => $docClass,
                    ':office'     => $officeName,
                    ':title'      => $docTitle,
                    ':content'    => $richContentHtml,
                    ':sub'        => $richSubscriptionHtml,
                    ':priority'   => $urgencyPriority,
                    ':sender'     => $sessionUserId,
                    ':address'    => $richAddressHtml,
                    ':memo_date'  => $officialDate
                ]);

                $logMsg = " Broadcasted new official document Type: [{$docClass}] Origin: [{$officeName}] Ref: [#{$generatedReferenceCode}].";
                recordSystemActivityLog($con, 'Communications', 'INSERT_CORRESPONDENCE', $logMsg);

                $freshDataset = getMemosDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['memos_list'], 'message' => "Office document broadcasted globally successfully."]);
                exit;

            } else {
                // =============================================================
                // MODE: AMEND CORRESPONDENCE METADATA
                // =============================================================
                $lockStmt = $con->prepare("SELECT `id`, `sender_id` FROM `memos` WHERE `id` = :id LIMIT 1 FOR UPDATE");
                $lockStmt->execute([':id' => $recordId]);
                $existingMemoRow = $lockStmt->fetch();

                if (!$existingMemoRow) { throw new Exception('Target document registry entry profile not found.'); }
                if ($sessionRole === 'Staff' && (int)$existingMemoRow['sender_id'] !== (int)$sessionUserId) {
                    throw new Exception('Access Denied: Unprivileged modification transaction rejected.');
                }

                $updateQuery = "UPDATE `memos` 
                                SET `document_classification` = :class, 
                                    `office_name` = :office,
                                    `title` = :title, 
                                    `content` = :content, 
                                    `priority` = :priority, 
                                    `recipient_address` = :address, 
                                    `memo_date` = :memo_date, 
                                    `subscription` = :subscription 
                                WHERE `id` = :id";
                
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([
                    ':class'        => $docClass,
                    ':office'       => $officeName,
                    ':title'        => $docTitle,
                    ':content'      => $richContentHtml,
                    ':priority'     => $urgencyPriority,
                    ':address'      => $richAddressHtml,
                    ':memo_date'    => $officialDate,
                    ':subscription' => $richSubscriptionHtml,
                    ':id'           => $recordId
                ]);

                $logMsg = " Amended published correspondence properties row index ID [{$recordId}] (Origin: {$officeName}).";
                recordSystemActivityLog($con, 'Communications', 'UPDATE_CORRESPONDENCE', $logMsg);

                $freshDataset = getMemosDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['memos_list'], 'message' => "Correspondence tracking metadata altered successfully."]);
                exit;
            }
        } catch (\Throwable $branchLException) {
            error_log("❌ Failure inside Correspondence Save Branch (L): " . $branchLException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchLException->getMessage(), 'data' => []]);
            exit;
        }
    }


    // =========================================================================
    // BRANCH M: DYNAMIC READ-RECEIPT STRING OVERRIDES (`mark_memo_read=true`)
    // =========================================================================
    if (isset($_POST['mark_memo_read']) && $_POST['mark_memo_read'] === 'true') {
        try {
            $targetMemoId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$targetMemoId) {
                throw new Exception('Invalid tracking target matrix supplied.');
            }

            // Lock row during read receipt update operations to prevent processing collisions
            $statusStmt = $con->prepare("SELECT id, read_by_users FROM memos WHERE id = :id LIMIT 1 FOR UPDATE");
            $statusStmt->execute([':id' => $targetMemoId]);
            $memoRow = $statusStmt->fetch();

            if (!$memoRow) {
                throw new Exception('Target correspondence record does not exist.');
            }

            // Construct standard parsing string markers to evaluate read trace arrays boundaries
            $formattedUserIdMarker = ",{$sessionUserId},";
            $currentReadString     = (string)$memoRow['read_by_users'];

            if (!str_contains($currentReadString, $formattedUserIdMarker)) {
                // If it is a fresh unread instance for the user, initialize or append the string code format
                if (empty($currentReadString)) {
                    $updatedReadString = ",{$sessionUserId},";
                } else {
                    $updatedReadString = $currentReadString . "{$sessionUserId},";
                }

                $updateQuery = "UPDATE memos SET read_by_users = :read_string WHERE id = :id";
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([':read_string' => $updatedReadString, ':id' => $targetMemoId]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
                $logMsg = " Dispatched opened status read-receipt signature validation on correspondence ID [{$targetMemoId}].";
                recordSystemActivityLog($con, 'Communications', 'READ_RECEIPT', $logMsg);
            }

            $freshDataset = getMemosDashboardDataset($con);
            echo json_encode(['status' => 'success', 'data' => $freshDataset['memos_list']]);
            exit;

        } catch (\Throwable $branchMException) {
            error_log("❌ Failure inside Correspondence Receipt Branch (M): " . $branchMException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchMException->getMessage(), 'data' => []]);
            exit;
        }
    }

        // =========================================================================
    // BRANCH O: DYNAMIC SERVER SIDE SEARCH FILTER ENGINE (`fetch_filtered_memos=true`)
    // =========================================================================
    if (isset($_POST['fetch_filtered_memos']) && $_POST['fetch_filtered_memos'] === 'true') {
        try {
            // Extract raw parameters and sanitize text input data fields safely
            $keywordFilter = isset($_POST['search_keyword']) ? trim((string)$_POST['search_keyword']) : '';
            $classFilter   = isset($_POST['filter_classification']) ? trim((string)$_POST['filter_classification']) : '';
            $priorityVal   = isset($_POST['filter_priority']) ? trim((string)$_POST['filter_priority']) : '';

            // Master baseline SQL layout pulling matching joined text blocks across all criteria layers
            $sqlQuery = "SELECT m.*, u.name as sender_name 
                         FROM memos m 
                         JOIN users u ON m.sender_id = u.id 
                         WHERE 1=1";
            
            $bindParams = [];

            // 1. Apply Alphanumeric keyword matches across headings, classification names, refs, addresses, or content bodies
            if (!empty($keywordFilter)) {
                $sqlQuery .= " AND (m.title LIKE :keyword OR m.content LIKE :keyword OR m.memo_ref LIKE :keyword OR m.document_classification LIKE :keyword OR m.recipient_address LIKE :keyword OR u.name LIKE :keyword)";
                $bindParams[':keyword'] = "%{$keywordFilter}%";
            }

            // 2. NEW: Append Document Classification filter restriction mapping parameters
            if (!empty($classFilter)) {
                $sqlQuery .= " AND m.document_classification = :classification";
                $bindParams[':classification'] = $classFilter;
            }

            // 3. Append urgency parameters
            if (!empty($priorityVal)) {
                $sqlQuery .= " AND m.priority = :priority";
                $bindParams[':priority'] = $priorityVal;
            }

            // Sort by inverse chronological order placement alignments
            $sqlQuery .= " ORDER BY m.created_at DESC";

            $stmt = $con->prepare($sqlQuery);
            $stmt->execute($bindParams);
            $filteredMemosList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Dispatch success package payload straight back to client data template re-renderer
            echo json_encode(['status' => 'success', 'data' => $filteredMemosList]);
            exit;

        } catch (\Throwable $branchOException) {
            error_log("❌ Failure inside Correspondence Fetch Filtering Branch (O): " . $branchOException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchOException->getMessage(), 'data' => []]);
            exit;
        }
    }




    // =========================================================================
    // BRANCH I: SAVE / AMEND VISITOR ENTRY LOGS (`save_visitor=true`)
    // =========================================================================
    if (isset($_POST['save_visitor']) && $_POST['save_visitor'] === 'true') {
        try {
            // RBAC Access Control Guard Enforcement Block
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Security profiles do not hold sufficient authorization clearance.');
            }

            // Extract parameters and sanitize input strings cleanly
            $recordId     = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
            $guestName    = isset($_POST['guest_name']) ? trim((string)$_POST['guest_name']) : '';
            $phoneNum     = isset($_POST['phone_number']) ? trim((string)$_POST['phone_number']) : '';
            $organization = isset($_POST['organization']) ? trim((string)$_POST['organization']) : '';
            $purposeText  = isset($_POST['purpose']) ? trim((string)$_POST['purpose']) : '';

            // Validation boundary checkpoints
            if (empty($guestName) || empty($phoneNum) || empty($purposeText)) {
                throw new Exception('Data Validation Fault: Compulsory visitor metric parameters cannot be left blank.');
            }

            if (!$sessionUserId) {
                throw new Exception('Security Context Error: Unable to determine tracking officer login identity.');
            }

            if (empty($recordId)) {
                // =============================================================
                // MODE: INSERT NEW ACTIVE GUEST TIMELINE RECORD
                // =============================================================
                $currentTime = date('H:i:s');
                
                $insertQuery = "INSERT INTO visitors_log (guest_name, organization, phone_number, purpose, entry_time, exit_time, status, logged_by, log_date) 
                                VALUES (:name, :org, :phone, :purpose, :entry, NULL, 'Inside Office', :user_id, CURDATE())";
                
                $stmt = $con->prepare($insertQuery);
                $stmt->execute([
                    ':name'    => $guestName,
                    ':org'     => $organization,
                    ':phone'   => $phoneNum,
                    ':purpose' => $purposeText,
                    ':entry'   => $currentTime,
                    ':user_id' => $sessionUserId
                ]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
                $logMsg = " Initialized entrance gate logs for external guest [{$guestName}] from [{$organization}] at clock time {$currentTime}.";
                recordSystemActivityLog($con, 'Logbook', 'INSERT_VISITOR', $logMsg);

                // Fetch refreshed daily dataset collection bundle array
                $dataset = getVisitorsDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $dataset['visitors_list'], 'message' => "Visitor entry successfully registered inside logbook."]);
                exit;

            } else {
                // =============================================================
                // MODE: AMEND METADATA PARAMETERS ON AN EXISTING LOG ROW
                // =============================================================
                $lockStmt = $con->prepare("SELECT id FROM visitors_log WHERE id = :id LIMIT 1 FOR UPDATE");
                $lockStmt->execute([':id' => $recordId]);
                if (!$lockStmt->fetch()) {
                    throw new Exception('Target registry entry profile not found.');
                }

                $updateQuery = "UPDATE visitors_log 
                                SET guest_name = :name, organization = :org, phone_number = :phone, purpose = :purpose 
                                WHERE id = :id";
                
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([
                    ':name'    => $guestName,
                    ':org'     => $organization,
                    ':phone'   => $phoneNum,
                    ':purpose' => $purposeText,
                    ':id'      => $recordId
                ]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
                $logMsg = " Amended operational parameter properties on visitor reference index key identifier [{$recordId}].";
                recordSystemActivityLog($con, 'Logbook', 'UPDATE_VISITOR', $logMsg);

                $dataset = getVisitorsDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $dataset['visitors_list'], 'message' => "Visitor log updated successfully."]);
                exit;
            }
        } catch (\Throwable $branchIException) {
            error_log("❌ Failure inside Visitor Save Branch (I): " . $branchIException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchIException->getMessage(), 'data' => []]);
            exit;
        }
    }

    // =========================================================================
    // BRANCH J: EMIT DEPARTURE TIMESTAMPS OVERRIDES (`checkout_visitor=true`)
    // =========================================================================
    if (isset($_POST['checkout_visitor']) && $_POST['checkout_visitor'] === 'true') {
        try {
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Security profiles do not hold sufficient authorization clearance.');
            }

            $targetId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if (!$targetId) {
                throw new Exception('Invalid record target pointer submitted.');
            }

            // Extract row state with write lock constraint
            $statusStmt = $con->prepare("SELECT guest_name, status FROM visitors_log WHERE id = :id LIMIT 1 FOR UPDATE");
            $statusStmt->execute([':id' => $targetId]);
            $visitorRow = $statusStmt->fetch();

            if (!$visitorRow) {
                throw new Exception('The designated visitor log reference row does not exist.');
            }
            if ($visitorRow['status'] !== 'Inside Office') {
                throw new Exception('Operation Rejected: This entity has already exited the facility layout.');
            }

            $currentTime = date('H:i:s');
            
            $updateQuery = "UPDATE visitors_log SET status = 'Completed', exit_time = :exit_time WHERE id = :id";
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([':exit_time' => $currentTime, ':id' => $targetId]);

            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
            $logMsg = " Emitted departure check-out timestamp code for visitor [{$visitorRow['guest_name']}] at {$currentTime}.";
            recordSystemActivityLog($con, 'Logbook', 'CHECKOUT_VISITOR', $logMsg);

            $dataset = getVisitorsDashboardDataset($con);
            echo json_encode(['status' => 'success', 'data' => $dataset['visitors_list'], 'message' => "Departure window timestamp logged successfully."]);
            exit;

        } catch (\Throwable $branchJException) {
            error_log("❌ Failure inside Visitor Checkout Branch (J): " . $branchJException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchJException->getMessage(), 'data' => []]);
            exit;
        }
    }

    // =========================================================
        // =========================================================================
    // BRANCH K: DYNAMIC SERVER SIDE VISITOR LOGBOOK SEARCH FILTER (`fetch_filtered_visitors=true`)
    // =========================================================================
    if (isset($_POST['fetch_filtered_visitors']) && $_POST['fetch_filtered_visitors'] === 'true') {
        try {
            // Core Security Authorization Enforcement Checks
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Data stream lookup operation denied.');
            }

            // Extract string values safely from post data fields matrix
            $keywordFilter = isset($_POST['search_keyword']) ? trim((string)$_POST['search_keyword']) : '';
            $dateFrom      = isset($_POST['filter_from_date']) ? trim((string)$_POST['filter_from_date']) : '';
            $dateTo        = isset($_POST['filter_to_date']) ? trim((string)$_POST['filter_to_date']) : '';

            // Master baseline SQL layout targeting the optimized visitors columns mapping
            $sqlQuery = "SELECT v.*, u.name as recorder_name 
                         FROM visitors_log v 
                         JOIN users u ON v.logged_by = u.id 
                         WHERE 1=1";
            
            $bindParams = [];

            // 1. Alphanumeric search boundaries criteria matching
            if (!empty($keywordFilter)) {
                $sqlQuery .= " AND (v.guest_name LIKE :keyword OR v.organization LIKE :keyword OR v.purpose LIKE :keyword OR v.phone_number LIKE :keyword)";
                $bindParams[':keyword'] = "%{$keywordFilter}%";
            }

            // 2. Comparative chronological date range filters variables
            if (!empty($dateFrom)) {
                $sqlQuery .= " AND v.log_date >= :date_from";
                $bindParams[':date_from'] = $dateFrom;
            }
            if (!empty($dateTo)) {
                $sqlQuery .= " AND v.log_date <= :date_to";
                $bindParams[':date_to'] = $dateTo;
            }
            
            // Default Date Fallback Boundary if form range metrics are left empty
            if (empty($dateFrom) && empty($dateTo)) {
                // Safeguard: Automatically fall back to today's visitor schedule logs natively
                $sqlQuery .= " AND v.log_date = CURDATE()";
            }

            // Order chronological listings tracking position alignments
            $sqlQuery .= " ORDER BY v.log_date DESC, v.entry_time DESC";

            $stmt = $con->prepare($sqlQuery);
            $stmt->execute($bindParams);
            $filteredVisitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Dispatch success package payload straight back to client re-rendering matrix
            echo json_encode([
                'status' => 'success', 
                'data'   => $filteredVisitors
            ]);
            exit;

        } catch (\Throwable $branchKException) {
            error_log("❌ Failure inside Visitor Logbook Fetch Branch (K): " . $branchKException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchKException->getMessage(),
                'data'    => []
            ]);
            exit;
        }
    }
 



    // =========================================================================
    // BRANCH F: SAVE / AMEND FACULTY & STAFF PROFILES (`save_staff=true`)
    // =========================================================================
    if (isset($_POST['save_staff']) && $_POST['save_staff'] === 'true') {
        try {
            // COMPLIANT PRIVILEGE GUARD: Using your defined $sessionRole parameter natively
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Violation: Unauthorized workforce directory manipulation rejected.');
            }

            // Extract text metrics safely
            $recordId    = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
            $fullName    = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
            $emailAddr   = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
            $passwordKey = isset($_POST['password']) ? (string)$_POST['password'] : '';
            $employeeId  = isset($_POST['employee_id']) ? trim((string)$_POST['employee_id']) : '';
            $phoneNumber = isset($_POST['phone_number']) ? trim((string)$_POST['phone_number']) : '';
            $deptName    = isset($_POST['department']) ? trim((string)$_POST['department']) : '';
            $designation = isset($_POST['designation']) ? trim((string)$_POST['designation']) : '';
            $targetRoleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

            // Baseline data validation barriers
            if (empty($fullName) || empty($emailAddr) || empty($employeeId) || empty($deptName) || empty($designation) || !$targetRoleId) {
                throw new Exception('Validation Error: Compulsory identity metrics cannot be left blank.');
            }

            // Unique field duplicate valuation check loop
            $dupCheckStmt = $con->prepare("SELECT id FROM users WHERE (email = :email OR employee_id = :emp) AND id != :id LIMIT 1");
            $dupCheckStmt->execute([':email' => $emailAddr, ':emp' => $employeeId, ':id' => (empty($recordId) ? 0 : $recordId)]);
            if ($dupCheckStmt->fetch()) {
                throw new Exception('Conflict Error: A user with this identical Email Address or Employee ID already exists.');
            }

            if (empty($recordId)) {
                // =============================================================
                // MODE: INSERT NEW PROFILE METRIC LINE
                // =============================================================
                if (strlen($passwordKey) < 6) {
                    throw new Exception('Security Restriction: New system account passwords must be at least 6 characters long.');
                }

                $hashedPass = password_hash($passwordKey, PASSWORD_BCRYPT);

                $insertQuery = "INSERT INTO users (name, email, password, role_id, employee_id, department, designation, phone_number, status) 
                                VALUES (:name, :email, :pass, :role, :emp, :dept, :desig, :phone, 'Active')";
                
                $stmt = $con->prepare($insertQuery);
                $stmt->execute([
                    ':name'     => $fullName,
                    ':email'    => $emailAddr,
                    ':pass'     => $hashedPass,
                    ':role'     => $targetRoleId,
                    ':emp'      => $employeeId,
                    ':dept'     => $deptName,
                    ':desig'    => $designation,
                    ':phone'    => $phoneNumber
                ]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly logs the executing $sessionUserId
                $logMsg = " Registered new active system personnel identity profile Name: [{$fullName}] Assigned Role ID: [{$targetRoleId}].";
                recordSystemActivityLog($con, 'HR', 'INSERT_USER', $logMsg);

                $freshDataset = getStaffDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['staff_list'], 'message' => "Personnel identity profile created successfully."]);
                exit;

            } else {
                // =============================================================
                // MODE: AMEND AN EXISTING RECORD VIEW ROW
                // =============================================================
                $lockStmt = $con->prepare("SELECT password FROM users WHERE id = :id LIMIT 1 FOR UPDATE");
                $lockStmt->execute([':id' => $recordId]);
                $userRow = $lockStmt->fetch();

                if (!$userRow) {
                    throw new Exception('Target profile row index not discovered.');
                }

                // Conditionally apply password string hash updates if field was submitted populated
                if ($passwordKey !== '') {
                    if (strlen($passwordKey) < 6) {
                        throw new Exception('Security Restriction: Password update keys must contain 6 or more characters.');
                    }
                    $finalHashedPass = password_hash($passwordKey, PASSWORD_BCRYPT);
                } else {
                    $finalHashedPass = $userRow['password']; // Retain baseline existing key
                }

                $updateQuery = "UPDATE users 
                                SET name = :name, email = :email, password = :pass, role_id = :role, employee_id = :emp, department = :dept, designation = :desig, phone_number = :phone 
                                WHERE id = :id";
                
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([
                    ':name'     => $fullName,
                    ':email'    => $emailAddr,
                    ':pass'     => $finalHashedPass,
                    ':role'     => $targetRoleId,
                    ':emp'      => $employeeId,
                    ':dept'     => $deptName,
                    ':desig'    => $designation,
                    ':phone'    => $phoneNumber,
                    ':id'       => $recordId
                ]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly logs the executing $sessionUserId
                $logMsg = " Amended credential profile parameters for user reference row index ID: [{$recordId}] Name: [{$fullName}].";
                recordSystemActivityLog($con, 'HR', 'UPDATE_USER', $logMsg);

                $freshDataset = getStaffDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['staff_list'], 'message' => "Personnel credential metrics updated successfully."]);
                exit;
            }
        } catch (\Throwable $branchFException) {
            error_log("❌ Failure inside Staff Save Branch (F): " . $branchFException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchFException->getMessage(), 'data' => []]);
            exit;
        }
    }


    // =========================================================================
    // BRANCH F: SAVE / AMEND FACULTY & STAFF PROFILES (`save_staff=true`)
    // =========================================================================
    if (isset($_POST['save_staff']) && $_POST['save_staff'] === 'true') {
        try {
            // RBAC Core Guard: Only Principal and Secretary are allowed to mutate user directory
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Violation: Unauthorized workforce directory manipulation rejected.');
            }

            // Extract text metrics safely
            $recordId    = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
            $fullName    = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
            $emailAddr   = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
            $passwordKey = isset($_POST['password']) ? (string)$_POST['password'] : '';
            $employeeId  = isset($_POST['employee_id']) ? trim((string)$_POST['employee_id']) : '';
            $phoneNumber = isset($_POST['phone_number']) ? trim((string)$_POST['phone_number']) : '';
            $deptName    = isset($_POST['department']) ? trim((string)$_POST['department']) : '';
            $designation = isset($_POST['designation']) ? trim((string)$_POST['designation']) : '';
            $targetRoleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

            // Baseline data validation barriers
            if (empty($fullName) || empty($emailAddr) || empty($employeeId) || empty($deptName) || empty($designation) || !$targetRoleId) {
                throw new Exception('Validation Error: Compulsory identity metrics cannot be left blank.');
            }

            // Unique field duplicate valuation check loop
            $dupCheckStmt = $con->prepare("SELECT id FROM users WHERE (email = :email OR employee_id = :emp) AND id != :id LIMIT 1");
            $dupCheckStmt->execute([':email' => $emailAddr, ':emp' => $employeeId, ':id' => (empty($recordId) ? 0 : $recordId)]);
            if ($dupCheckStmt->fetch()) {
                throw new Exception('Conflict Error: A user with this identical Email Address or Employee ID already exists.');
            }

            if (empty($recordId)) {
                // =============================================================
                // MODE: INSERT NEW PROFILE METRIC LINE
                // =============================================================
                if (strlen($passwordKey) < 6) {
                    throw new Exception('Security Restriction: New system account passwords must be at least 6 characters long.');
                }

                $hashedPass = password_hash($passwordKey, PASSWORD_BCRYPT);

                $insertQuery = "INSERT INTO users (name, email, password, role_id, employee_id, department, designation, phone_number, status) 
                                VALUES (:name, :email, :pass, :role, :emp, :dept, :desig, :phone, 'Active')";
                
                $stmt = $con->prepare($insertQuery);
                $stmt->execute([
                    ':name'     => $fullName,
                    ':email'    => $emailAddr,
                    ':pass'     => $hashedPass,
                    ':role'     => $targetRoleId,
                    ':emp'      => $employeeId,
                    ':dept'     => $deptName,
                    ':desig'    => $designation,
                    ':phone'    => $phoneNumber
                ]);

                $logMsg = "Registered new active system personnel identity profile Name: [{$fullName}] Assigned Role Identifier: [{$targetRoleId}].";
                recordSystemActivityLog($con, 'HR', 'INSERT_USER', $logMsg);

                $freshDataset = getStaffDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['staff_list'], 'message' => "Personnel identity profile created successfully."]);
                exit;

            } else {
                // =============================================================
                // MODE: AMEND AN EXISTING RECORD VIEW ROW
                // =============================================================
                $lockStmt = $con->prepare("SELECT password FROM users WHERE id = :id LIMIT 1 FOR UPDATE");
                $lockStmt->execute([':id' => $recordId]);
                $userRow = $lockStmt->fetch();

                if (!$userRow) {
                    throw new Exception('Target profile row index not discovered.');
                }

                // Conditionally apply password string hash updates if field was submitted populated
                if ($passwordKey !== '') {
                    if (strlen($passwordKey) < 6) {
                        throw new Exception('Security Restriction: Password update keys must contain 6 or more characters.');
                    }
                    $finalHashedPass = password_hash($passwordKey, PASSWORD_BCRYPT);
                } else {
                    $finalHashedPass = $userRow['password']; // Retain baseline existing key
                }

                $updateQuery = "UPDATE users 
                                SET name = :name, email = :email, password = :pass, role_id = :role, employee_id = :emp, department = :dept, designation = :desig, phone_number = :phone 
                                WHERE id = :id";
                
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([
                    ':name'     => $fullName,
                    ':email'    => $emailAddr,
                    ':pass'     => $finalHashedPass,
                    ':role'     => $targetRoleId,
                    ':emp'      => $employeeId,
                    ':dept'     => $deptName,
                    ':desig'    => $designation,
                    ':phone'    => $phoneNumber,
                    ':id'       => $recordId
                ]);

                $logMsg = "Amended credential profile parameters for user index identification ID: [{$recordId}] Name: [{$fullName}].";
                recordSystemActivityLog($con, 'HR', 'UPDATE_USER', $logMsg);

                $freshDataset = getStaffDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['staff_list'], 'message' => "Personnel credential metrics updated successfully."]);
                exit;
            }
        } catch (\Throwable $branchFException) {
            error_log("❌ Failure inside Staff Save Branch (F): " . $branchFException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchFException->getMessage(), 'data' => []]);
            exit;
        }
    }

    // =========================================================================
    // BRANCH G: LOCK / SUSPEND ACCOUNT PRIVILEGES (`update_staff_status=true`)
    // =========================================================================
    if (isset($_POST['update_staff_status']) && $_POST['update_staff_status'] === 'true') {
        try {
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Violation: Identity state manipulation rejected.');
            }

            $targetStaffId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $nextState     = isset($_POST['status']) ? trim((string)$_POST['status']) : '';

            if (!$targetStaffId || !in_array($nextState, ['Active', 'Inactive'])) {
                throw new Exception('Invalid workspace state alteration configuration params supplied.');
            }

            // Protect against self-suspension accidents
            if ($targetStaffId === $sessionUserId) {
                throw new Exception('Operation Blocked: You cannot deactivate your own active computer terminal profile access.');
            }

            $statusStmt = $con->prepare("SELECT name FROM users WHERE id = :id LIMIT 1 FOR UPDATE");
            $statusStmt->execute([':id' => $targetStaffId]);
            $targetUser = $statusStmt->fetch();

            if (!$targetUser) {
                throw new Exception('Target identity profile row index doesn\'t exist.');
            }

            $updateQuery = "UPDATE users SET status = :state WHERE id = :id";
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([':state' => $nextState, ':id' => $targetStaffId]);

            $logMsg = "Shifted system workspace login permissions on staff profile [{$targetUser['name']}] to status state [{$nextState}].";
            recordSystemActivityLog($con, 'HR', 'STATE_MUTATION', $logMsg);

            $freshDataset = getStaffDashboardDataset($con);
            echo json_encode(['status' => 'success', 'data' => $freshDataset['staff_list'], 'message' => "Account access permission shifted to {$nextState}."]);
            exit;

        } catch (\Throwable $branchGException) {
            error_log("❌ Failure inside Staff Status Branch (G): " . $branchGException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchGException->getMessage(), 'data' => []]);
            exit;
        }
    }

   

    // =========================================================================
    // BRANCH E: DYNAMIC SERVER SIDE DOCUMENT DISPATCH (`fetch_filtered_documents=true`)
    // =========================================================================
    if (isset($_POST['fetch_filtered_documents']) && $_POST['fetch_filtered_documents'] === 'true') {
        try {
            // Extract query payload elements safely from post data parameters
            $searchKeyword = isset($_POST['search_keyword']) ? trim((string)$_POST['search_keyword']) : '';
            $dateFrom      = isset($_POST['filter_from_date']) ? trim((string)$_POST['filter_from_date']) : '';
            $dateTo        = isset($_POST['filter_to_date']) ? trim((string)$_POST['filter_to_date']) : '';
            $statusFilter  = isset($_POST['filter_status']) ? trim((string)$_POST['filter_status']) : '';

            // Master baseline SQL layout targeting our optimized documents columns mapping
            $sqlQuery = "SELECT d.*, u.name AS recorder_name 
                         FROM documents d 
                         LEFT JOIN users u ON d.logged_by = u.id 
                         WHERE 1=1";
            
            $bindParams = [];

            // 1. RBAC Safety Perimeter: Staff can only query rows they added natively
            if ($sessionRole === 'Staff') {
                $sqlQuery .= " AND d.logged_by = :active_user_id";
                $bindParams[':active_user_id'] = $sessionUserId;
            }

            // 2. Append string keyword search bounds across index parameters 
            if (!empty($searchKeyword)) {
                $sqlQuery .= " AND (d.title LIKE :keyword OR d.tracking_number LIKE :keyword OR d.sender_receiver LIKE :keyword OR d.doc_type LIKE :keyword)";
                $bindParams[':keyword'] = "%{$searchKeyword}%";
            }

            // 3. Append comparative conditional date tracking variables
            if (!empty($dateFrom)) {
                $sqlQuery .= " AND d.received_dispatched_date >= :date_from";
                $bindParams[':date_from'] = $dateFrom;
            }
            if (!empty($dateTo)) {
                $sqlQuery .= " AND d.received_dispatched_date <= :date_to";
                $bindParams[':date_to'] = $dateTo;
            }

            // 4. Append operational categorization profile criteria
            if (!empty($statusFilter)) {
                $sqlQuery .= " AND d.status = :status_profile";
                $bindParams[':status_profile'] = $statusFilter;
            }

            // Chronological list formatting layout assignment
            $sqlQuery .= " ORDER BY d.received_dispatched_date DESC, d.created_at DESC";

            $stmt = $con->prepare($sqlQuery);
            $stmt->execute($bindParams);
            $filteredDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Package raw structural rows array straight back to client data template re-renderer
            echo json_encode([
                'status' => 'success', 
                'data'   => $filteredDocuments
            ]);
            exit;

        } catch (\Throwable $branchEException) {
            error_log("❌ Failure inside Document Server-Side Fetching Branch (E): " . $branchEException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchEException->getMessage(),
                'data'    => []
            ]);
            exit;
        }
    }
 
 
 
 
     // =========================================================================
    // BRANCH W: AUTHORIZE WORKFLOW SIGN-OFF & CHRONOLOGICAL FILE INDEXING
    // =========================================================================
    if (isset($_POST['update_document_status']) && $_POST['update_document_status'] === 'true') {
        try {
            // 1. RBAC SECURITY VALIDATION GUARD GATE
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Violation: Security profile lacks clearance metrics to authorize signature sign-offs.');
            }

            // 2. PARSE AND SANITIZE INCOMING PAYLOAD VARIABLES
            $recordId          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $nextTargetStatus  = isset($_POST['status']) ? trim((string)$_POST['status']) : 'Signed / Approved';
            $receiverNameInput = isset($_POST['receiver_name']) ? trim((string)$_POST['receiver_name']) : '';
            $receivedDateInput = isset($_POST['received_date']) ? trim((string)$_POST['received_date']) : date('Y-m-d');
            $indexCodeSelected = isset($_POST['tracking_number']) ? (int)$_POST['tracking_number'] : 0;

            // 3. MANDATORY CRITERIA DATA BOUNDARY VALIDATIONS
            if ($recordId <= 0) {
                throw new Exception('Data Validation Fault: Missing target database primary row reference pointer.');
            }
            if (empty($receiverNameInput)) {
                throw new Exception('Data Validation Fault: The "Receiver Name / Target Desk Recipient" field parameters cannot be left blank.');
            }
            if ($indexCodeSelected <= 0) {
                throw new Exception('Workflow Barrier: You must assign an official Institutional File Index Category to archive this returned document.');
            }

            // 4. INITIALIZE SAFE ISOLATED DATABASE CONCURRENCY TRANSACTION TRANSACTION WINDOW
            $con->beginTransaction();

            // Verify the target record context row still exists and capture lock for update intent
            $lockStmt = $con->prepare("SELECT `id`, `title` FROM `documents` WHERE `id` = :id LIMIT 1 FOR UPDATE");
            $lockStmt->execute([':id' => $recordId]);
            $documentRow = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$documentRow) {
                throw new Exception('Identity Context Error: Target document record could not be discovered or was deleted by another node.');
            }

            // 5. CALL THE WRAPPED REUSABLE SEQUENTIAL REFERENCE TRACKER GENERATOR
            $seqNumberResponse = generateInstitutionalTrackingNumber($con, $indexCodeSelected);
            if ($seqNumberResponse['status'] === 'error') {
                throw new Exception("Sequence Compilation Interruption: " . $seqNumberResponse['message']);
            }
            
            $institutionalGeneratedTrackingNumber = $seqNumberResponse['data']; // Safe unwrapped tracking ID string

            // 6. BYPASS ANTI-TAMPERING phpMyAdmin PRODUCTION TRIGGERS NATIVELY ON THIS ACTIVE THREAD
            $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

            // Update database row fields, storing both routing parameters and the new unique chronological tracking ID
            // Implicitly flags sync_status to 'Pending' so the dashboard toolbar synchronizer catches it instantly
            $updateQuery = "UPDATE `documents` 
                            SET `document_number` = :doc_num,
                                `status`          = :status, 
                                `receiver_name`   = :receiver, 
                                `received_date`   = :r_date,
                                `sync_status`     = 'Pending' 
                            WHERE `id` = :id";
            
            $stmt = $con->prepare($updateQuery);
            $stmt->execute([
                ':doc_num'  => $institutionalGeneratedTrackingNumber,
                ':status'   => $nextTargetStatus,
                ':receiver' => $receiverNameInput,
                ':r_date'   => $receivedDateInput,
                ':id'       => $recordId
            ]);

            // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
            $logMsg = "Cleared and archived file record ID: [{$recordId}]. Strict Chronological ID Stamp Generated: [#{$institutionalGeneratedTrackingNumber}] assigned to recipient desk: [{$receiverNameInput}].";
            recordSystemActivityLog($con, 'Documents', 'STATUS_SIGNOFF', $logMsg);

            // Commit all outstanding operations onto the server disc volume safely
            if (isset($con) && $con->inTransaction()) $con->commit();

            // 7. DISPATCH REFRESHED ROLE-BASED LIST DATA GENERATIONS STRAIGHT BACK TO FRONTEND AJAX PROMISE
            $freshDataset = getDocumentsDashboardDataset($con);
            
            echo json_encode([
                'status'  => 'success',
                'data'    => $freshDataset['documents_list'],
                'message' => "Document successfully signed off, classified, and filed chronologically under tracking verification index code: {$institutionalGeneratedTrackingNumber}."
            ]);
            exit;

        } catch (\Throwable $branchWException) {
            // Cancel outstanding operations and rollback transaction lines to prevent database data corruption splits
            if (isset($con) && $con->inTransaction()) {
                $con->rollBack();
            }
            
            error_log("❌ Failure inside Document Status Sign-Off Branch (W): " . $branchWException->getMessage());
            
            echo json_encode([
                'status'  => 'error',
                'message' =>  $branchWException->getMessage()
            ]);
            exit;
        }
    }





       // =========================================================================
    // BRANCH D: SAVE / AMEND DOCUMENT LEDGER ENTRIES (`save_document=true`)
    // =========================================================================
    if (isset($_POST['save_document']) && $_POST['save_document'] === 'true') { 
        try {
            // 1. EXTRACT METADATA STRINGS & DECOUPLED CONTACT INPUT VALUES
            $recordId         = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
            $docTitle         = isset($_POST['title']) ? trim((string)$_POST['title']) : ''; 
            $senderNameInput  = isset($_POST['sender_name']) ? trim((string)$_POST['sender_name']) : '';
            $senderPhoneInput = isset($_POST['sender_phone']) ? trim((string)$_POST['sender_phone']) : '';
            $actionDate       = isset($_POST['received_dispatched_date']) ? trim((string)$_POST['received_dispatched_date']) : date('Y-m-d');
            
            // Extract optional client-side processed base64 components
            $rawBase64        = isset($_POST['file_base64']) ? trim((string)$_POST['file_base64']) : '';
            $clientName       = isset($_POST['file_name']) ? trim((string)$_POST['file_name']) : '';
            // Add this mapping to your input data extractions block inside Branch D:
            $docType = isset($_POST['doc_type']) ? trim((string)$_POST['doc_type']) : 'Incoming Letter'; 


            // =========================================================================
            // 2. ESTABLISH CORE MANDATORY DATA VALIDATION MARGINS CHECKPOINTS
            // =========================================================================
            if (empty($docTitle)) {
                throw new Exception('Data Validation Fault: The "Document Title / Subject Matter" field cannot be left empty.');
            } 
            if (empty($senderNameInput)) {
                throw new Exception('Data Validation Fault: The "Sender Name / Originating Agency" field cannot be left empty.');
            } 
            if (empty($sessionUserId)) {
                throw new Exception('Security Access Fault: Logging operator identity context lost.');
            }

            // =========================================================================
            // 3. PROCESS BASE64 FILE STORAGE PIPELINE (IF PRESENT)
            // =========================================================================
            $finalServerFilePath = null; 

            if (!empty($rawBase64)) {
                // Parse out file meta details from data URL stream (e.g., data:application/pdf;base64,...)
                if (preg_match('/^data:([^;]+);base64,(.+)$/', $rawBase64, $fileMatches)) {
                    $mimeTypeIdentification = $fileMatches[1];
                    $pureBase64StringData   = $fileMatches[2];

                    // Validate MIME profile boundaries
                    $allowedMimeTypes = [
                        'application/pdf' => 'pdf',
                        'image/jpeg'      => 'jpg',
                        'image/jpg'       => 'jpg',
                        'image/png'       => 'png'
                    ];

                    if (!array_key_exists($mimeTypeIdentification, $allowedMimeTypes)) {
                        throw new Exception('File Rejected: Scanned attachments must strictly format as PDF, JPG, or PNG.');
                    }

                    $targetExtension = $allowedMimeTypes[$mimeTypeIdentification];
                    
                    // Generate safe cryptographic name identifier string to block directory traversals
                    $generatedFileName = 'DOC_' . bin2hex(random_bytes(16)) . '_' . time() . '.' . $targetExtension;
                    
                    // Dynamic directory validation check & generation block
                    $targetDiskFolder = rtrim($uploadDir, '/') . '/documents';
                    if (!is_dir($targetDiskFolder)) {
                        if (!mkdir($targetDiskFolder, 0755, true) && !is_dir($targetDiskFolder)) {
                            throw new Exception("FileSystem Fault: Failed to initialize structural directory block path on host storage path.");
                        }
                    }

                    // Map the dynamic absolute write path
                    $absoluteSavePath = $targetDiskFolder . '/' . $generatedFileName;

                    // Decode data stream and save file to the storage volume
                    $decodedBinaryBytes = base64_decode($pureBase64StringData);
                    if ($decodedBinaryBytes === false) {
                        throw new Exception('Cryptographic Handshake Failure: Corrupted or unreadable Base64 file stream.');
                    }

                    if (file_put_contents($absoluteSavePath, $decodedBinaryBytes) === false) {
                        throw new Exception('FileSystem Write Error: Failed to write data bytes to dynamic file storage location.');
                    }

                    // Synchronize corresponding database database tracking path value relative to project public folder
                    $finalServerFilePath = 'uploads/documents/' . $generatedFileName;
                }
            }

            // =========================================================================
            // 4. DISPATCH SQL OPERATIONS BRANCHES (BACKTICK-ALIGNED & SYNC-SUPPORTED)
            // =========================================================================
            // Re-inject the authorization key context first to unlock row mutations on tables (phpMyAdmin bypass triggers)
            $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'"); 
            
                        if (empty($recordId)) {
                // =====================================================================
                // MODE: FAST INSERT (CREATES BASELINE LOG RECORD AWAITING REVIEW)
                // =====================================================================
                $fileSyncStatusFlag = ($finalServerFilePath !== null) ? 'Pending' : 'Synced'; 

                // FIXED: Aligned VALUES parameters to use type-safe colon placeholders (:doc_type) 
                // and adjusted column tracking mappings to write directly into sender_receiver
                $insertQuery = "INSERT INTO `documents` (
                                    `title`, 
                                    `document_number`, 
                                    `doc_type`, 
                                    `sender_name`, 
                                    `sender_phone`, 
                                    `file_path`, 
                                    `logged_by`, 
                                    `received_dispatched_date`, 
                                    `status`, 
                                    `sync_status`, 
                                    `file_sync_status`
                                ) VALUES (
                                    :title, 
                                    NULL, 
                                    :doc_type, 
                                    :sender_recv, 
                                    :s_phone, 
                                    :path, 
                                    :user_id, 
                                    :action_date, 
                                    'Pending Review', 
                                    'Pending', 
                                    :file_sync
                                )";
                
                $stmt = $con->prepare($insertQuery);
                
                // Execute prepared parameter values array metrics safely
                $stmt->execute([
                    ':title'       => $docTitle,
                    ':doc_type'    => $docType, // NEW INJECTION FIXED
                    ':sender_recv' => $senderNameInput,
                    ':s_phone'     => $senderPhoneInput,
                    ':path'        => $finalServerFilePath,
                    ':user_id'     => $sessionUserId,
                    ':action_date' => $actionDate,
                    ':file_sync'   => $fileSyncStatusFlag
                ]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing $sessionUserId
                $logMsg = "Fast-logged inward registry document Subject: [{$docTitle}] Classification Type: [{$docType}]. Dispatched to Professor office awaiting return clearance.";
                recordSystemActivityLog($con, 'Documents', 'INSERT_LEDGER', $logMsg);
 
                $freshDataset = getDocumentsDashboardDataset($con);
                echo json_encode(['status' => 'success', 'data' => $freshDataset['documents_list'], 'message' => "Document reference fast-logged into registry successfully. Awaiting return sign-off."]);
                exit; 

            } else {
                // =====================================================================
                // MODE: AMEND METADATA ON EXISTING REGISTRY RECORD FILE ROW
                // =====================================================================
                $lockStmt = $con->prepare("SELECT `logged_by`, `file_path` FROM `documents` WHERE `id` = :id LIMIT 1 FOR UPDATE");
                $lockStmt->execute([':id' => $recordId]);
                $documentRow = $lockStmt->fetch();

                if (!$documentRow) {
                    throw new Exception('Target ledger file entry profile not found.');
                }
                
                // Enforce field ownership validation parameter checks
                if ($sessionRole === 'Staff' && (int)$documentRow['logged_by'] !== (int)$sessionUserId) {
                    throw new Exception('Access Denied: Unprivileged modification transaction rejected.');
                }

                $fileSyncStatusFlag = 'Synced'; // Default stay unchanged if no new file comes in

                // If a new digital file softcopy scan was uploaded, update path and purge legacy file from disk
                if ($finalServerFilePath !== null) {
                    if (!empty($documentRow['file_path'])) {
                        $oldFileName = basename((string)$documentRow['file_path']);
                        $oldDiskPath = rtrim($uploadDir, '/') . '/documents/' . $oldFileName;
                        
                        if (file_exists($oldDiskPath) && !is_dir($oldDiskPath)) {
                            @unlink($oldDiskPath);
                        }
                    }
                    $updatePathValue = $finalServerFilePath;
                    $fileSyncStatusFlag = 'Pending'; // Flag engine to queue new file upload
                } else {
                    $updatePathValue = $documentRow['file_path'];
                }

                               // =====================================================================
                // MODE: AMEND METADATA ON EXISTING REGISTRY RECORD FILE ROW
                // =====================================================================
                // FIXED: Adjusted to target your exact schema column parameter name (`sender_receiver`)
                // and synchronized variable references to match your top extraction assignments
                $updateQuery = "UPDATE `documents` 
                                SET `title`                    = :title,  
                                    `file_path`                = :path, 
                                    `sender_name`          = :sender_recv, 
                                    `sender_phone`             = :s_phone, 
                                    `received_dispatched_date` = :action_date,
                                    `doc_type`                 = :doc_type,
                                    `sync_status`              = 'Pending',
                                    `file_sync_status`         = :file_sync
                                WHERE `id` = :id";
                
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([
                    ':title'       => $docTitle, 
                    ':path'        => $updatePathValue,
                    ':sender_recv' => $senderNameInput,
                    ':s_phone'     => $senderPhoneInput,
                    ':action_date' => $actionDate,
                    ':doc_type'    => $docType, // FIXED: Changed from $doc_type to match top assignments variable
                    ':file_sync'   => $fileSyncStatusFlag,
                    ':id'          => $recordId
                ]);

                // COMPLIANT SECURITY AUDIT NARRATIVE: Explicitly tracks executing operator user ID
                $logMsg = "[User ID: {$sessionUserId}] Amended classification parameters [{$docType}] and tracking properties on document registry row index key pointer: [{$recordId}]. Custom status flags reset to Pending.";
                recordSystemActivityLog($con, 'Documents', 'UPDATE_LEDGER', $logMsg);

                // Re-fetch freshly updated data array to repaint client dashboard instantly
                $freshDataset = getDocumentsDashboardDataset($con);
                echo json_encode([
                    'status'  => 'success', 
                    'data'    => $freshDataset['documents_list'], 
                    'message' => "Document registry tracking metadata altered successfully."
                ]);
                exit;

            }

 

        } catch (\Throwable $branchDException) {
            error_log("❌ Failure inside Document Ledger Save Branch (D): " . $branchDException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchDException->getMessage()
            ]);
            exit;
        }
    }
    
    
    // =========================================================================
    // BRANCH C: DYNAMIC SERVER SIDE SCHEDULING DISPATCH (`fetch_filtered_appointments=true`)
    // ========================================================================= 
    if (isset($_POST['fetch_filtered_appointments']) && $_POST['fetch_filtered_appointments'] === 'true') {
        try {
            // Extract raw parameters and sanitize inputs safely
            $keyword   = isset($_POST['search_keyword']) ? trim((string)$_POST['search_keyword']) : '';
            $fromDate  = isset($_POST['filter_from_date']) ? trim((string)$_POST['filter_from_date']) : '';
            $toDate    = isset($_POST['filter_to_date']) ? trim((string)$_POST['filter_to_date']) : '';
            $statusVal = isset($_POST['filter_status']) ? trim((string)$_POST['filter_status']) : '';

            // Base query statement layout linking user profile and appointment types data mappings
            $rawQuery = "SELECT a.*, u.name AS scheduler_name, t.type_name AS appointment_type_label 
                         FROM appointments a 
                         LEFT JOIN users u ON a.scheduled_by = u.id 
                         LEFT JOIN appointment_types t ON a.appointment_type_id = t.id
                         WHERE 1=1";
            
            $sqlParams = [];

            // 1. Enforce RBAC visibility boundaries: Staff can only query their own input rows
            if ($sessionRole === 'Staff') {
                $rawQuery .= " AND a.scheduled_by = :session_user_id";
                $sqlParams[':session_user_id'] = $sessionUserId;
            }

            // 2. Append text filter variables matching text inputs
            if (!empty($keyword)) {
                $rawQuery .= " AND (a.visitor_name LIKE :keyword OR a.purpose LIKE :keyword OR u.name LIKE :keyword OR t.type_name LIKE :keyword)";
                $sqlParams[':keyword'] = "%{$keyword}%";
            }

            // 3. Append relational date range configurations
            if (!empty($fromDate)) {
                $rawQuery .= " AND a.appointment_date >= :from_date";
                $sqlParams[':from_date'] = $fromDate;
            }
            if (!empty($toDate)) {
                $rawQuery .= " AND a.appointment_date <= :to_date";
                $sqlParams[':to_date'] = $toDate;
            }

            // 4. Append exact status choices
            if (!empty($statusVal)) {
                $rawQuery .= " AND a.status = :status";
                $sqlParams[':status'] = $statusVal;
            }

            // Chronological order layout alignment
            $rawQuery .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

            $stmt = $con->prepare($rawQuery);
            $stmt->execute($sqlParams);
            $filteredRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. Instantly dispatch raw data matrix back to frontend jQuery renderer loop
            echo json_encode([
                'status' => 'success', 
                'data'   => $filteredRecords
            ]);
            exit;

        } catch (\Throwable $branchCException) {
            error_log("❌ Failure inside Appointment Server-Side Fetching Branch (C): " . $branchCException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchCException->getMessage(),
                'data'    => []
            ]);
            exit;
        }
    } 
    
        // =========================================================================
    // BRANCH A: SAVE / AMEND APPOINTMENT RECORDS (`save_appointment=true`)
    // =========================================================================
    if (isset($_POST['save_appointment']) && $_POST['save_appointment'] === 'true') {
        try {
            // 1. EXTRACT DATA STRINGS & SEPARATED ENTRANCE WORKFLOW VALUES
            $recordId      = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
            $visitorName   = isset($_POST['visitor_name']) ? trim((string)$_POST['visitor_name']) : '';
            $visitorPhone  = isset($_POST['visitor_phone']) ? trim((string)$_POST['visitor_phone']) : '';
            $visitorType   = isset($_POST['visitor_type']) ? trim((string)$_POST['visitor_type']) : '';
            $apptTypeId    = isset($_POST['appointment_type_id']) ? trim((string)$_POST['appointment_type_id']) : '';
            $department    = isset($_POST['department']) ? trim((string)$_POST['department']) : '';
            $bookingDate   = isset($_POST['appointment_date']) ? trim((string)$_POST['appointment_date']) : '';
            $purposeText   = isset($_POST['purpose']) ? trim((string)$_POST['purpose']) : '';

            // 2. STABLISH MANDATORY CORE DATA VALIDATION MARGIN CHANNELS
            if (empty($visitorName)) {
                throw new Exception('Data Validation Fault: The "Visitor Full Name" field cannot be left blank.');
            }
            if (empty($visitorPhone)) {
                throw new Exception('Data Validation Fault: The "Visitor Telephone Number" field cannot be left blank.');
            }
            if (empty($visitorType)) {
                throw new Exception('Data Validation Fault: Please choose a valid "Visitor Operational Profile" option.');
            }
            if (empty($apptTypeId)) {
                throw new Exception('Data Validation Fault: Please choose an option for the "Appointment Session Type" field.');
            }
            if (empty($bookingDate)) {
                throw new Exception('Data Validation Fault: Please select a valid calendar date inside the "Target Booking Date" picker.');
            }
            if (empty($purposeText)) {
                throw new Exception('Data Validation Fault: Detailed Target Agenda / Purpose statement must be provided.');
            }

            // Enforce strict authorization session validations across active background metrics
            if (empty($sessionUserId) || empty($sessionRole)) {
                throw new Exception('Security Access Fault: Logging operator identity context lost.');
            }

            // =========================================================================
            // CORE WORKFLOW COMPLIANCE: FORCE NULL DEPARTMENTS FOR PARENTS & OUTSIDERS
            // =========================================================================
            if ($visitorType === 'Parent' || $visitorType === 'Outsider') {
                $finalDepartmentValue = null; // Overwrites any submitted text parameter to comply with workflow rules
            } else {
                if (empty($department)) {
                    throw new Exception('Workflow Enforcement Guard: This profile type demands a target host department assignment.');
                }
                $finalDepartmentValue = $department;
            }

            // Re-inject the authorization key context first to unlock row mutations on tables (phpMyAdmin bypass triggers)
            $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

            if (empty($recordId)) {
                // =====================================================================
                // MODE: INSERT NEW LOG ENTRY INTO WAITING QUEUE
                // =====================================================================
                // Explicitly tags sync_status to 'Pending' so the dashboard toolbar synchronizer catches it instantly
                $insertQuery = "INSERT INTO `appointments` (
                                    `visitor_name`, `visitor_phone`, `visitor_type`, `department`, 
                                    `appointment_type_id`, `purpose`, `appointment_date`, 
                                    `start_time`, `end_time`, `status`, `scheduled_by`, `sync_status`
                                ) VALUES (
                                    :name, :phone, :v_type, :dept, 
                                    :appt_type_id, :purpose, :date, 
                                    '00:00:00', '00:00:00', 'Pending', :user_id, 'Pending'
                                )";
                
                $stmt = $con->prepare($insertQuery);
                $stmt->execute([
                    ':name'         => $visitorName,
                    ':phone'        => $visitorPhone,
                    ':v_type'       => $visitorType,
                    ':dept'         => $finalDepartmentValue,
                    ':appt_type_id' => $apptTypeId,
                    ':purpose'      => $purposeText,
                    ':date'         => $bookingDate,
                    ':user_id'      => $sessionUserId
                ]);

                // Commit action to tracking ledger history
                $logMessage = "Logged waiting entry slot for visitor [{$visitorName}] contact line [{$visitorPhone}] profile [{$visitorType}] host dept [{$finalDepartmentValue}] targeting timeline [{$bookingDate}].";
                recordSystemActivityLog($con, 'Appointments', 'INSERT_QUEUE', $logMessage);

                // Fetch updated dataset array state
                $dataset = getAppointmentsDashboardDataset($con);
                if ($dataset['status'] === 'error') {
                    throw new Exception($dataset['message']);
                }

                echo json_encode([
                    'status'  => 'success', 
                    'data'    => $dataset['appointments_list'], 
                    'message' => "Visitor {$visitorName} added to the office appointment registry successfully."
                ]);
                exit;

            } else {
                // =====================================================================
                // MODE: AMEND PARAMETERS ON AN EXISTING LOG RECORD
                // =====================================================================
                // Row mutation protection check block using row-locking FOR UPDATE
                $checkStmt = $con->prepare("SELECT `scheduled_by` FROM `appointments` WHERE `id` = :id LIMIT 1 FOR UPDATE");
                $checkStmt->execute([':id' => $recordId]);
                $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$existingRow) {
                    throw new Exception('Target appointment registry entry profile not found.');
                }
                
                // Enforce RBAC ownership validation parameters checks
                if ($sessionRole === 'Staff' && (int)$existingRow['scheduled_by'] !== (int)$sessionUserId) {
                    throw new Exception('Access Denied: You do not hold sufficient ownership credentials to amend this record.');
                }

                // Explicitly resets sync_status back to 'Pending' to queue outbound sync transmission
                $updateQuery = "UPDATE `appointments` 
                                SET `visitor_name` = :name, 
                                    `visitor_phone` = :phone,
                                    `visitor_type` = :v_type, 
                                    `department` = :dept,
                                    `appointment_type_id` = :appt_type_id, 
                                    `purpose` = :purpose, 
                                    `appointment_date` = :date,
                                    `sync_status` = 'Pending' 
                                WHERE `id` = :id";
                
                $stmt = $con->prepare($updateQuery);
                $stmt->execute([
                    ':name'         => $visitorName,
                    ':phone'        => $visitorPhone,
                    ':v_type'       => $visitorType,
                    ':dept'         => $finalDepartmentValue,
                    ':appt_type_id' => $apptTypeId,
                    ':purpose'      => $purposeText,
                    ':date'         => $bookingDate,
                    ':id'           => $recordId
                ]);

                // Record system modification footprint metrics inside live audit trail registers
                $logMessage = "Amended properties definitions for appointment log index key pointer: [{$recordId}] matching visitor name [{$visitorName}].";
                recordSystemActivityLog($con, 'Appointments', 'UPDATE_PARAMS', $logMessage);

                // Refresh application data cache bundle array
                $dataset = getAppointmentsDashboardDataset($con);
                if ($dataset['status'] === 'error') {
                    throw new Exception($dataset['message']);
                }

                echo json_encode([
                    'status'  => 'success', 
                    'data'    => $dataset['appointments_list'], 
                    'message' => "Appointment data properties matching reference index log updated successfully."
                ]);
                exit;
            }
        } catch (\Throwable $branchAException) {
            error_log("❌ Failure inside Appointment Save Branch (A): " . $branchAException->getMessage());
            echo json_encode([
                'status'  => 'error', 
                'message' => $branchAException->getMessage()
            ]);
            exit;
        }
    }



    // =========================================================================
    // BRANCH B: OVERRIDE STATUS VALUES (`update_appointment_status=true`)
    // =========================================================================
    if (isset($_POST['update_appointment_status']) && $_POST['update_appointment_status'] === 'true') {
        try {
            // RBAC Enforcement Layer: Staff profiles cannot modify status flags
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Fault: Only the Principal or Secretary can modify entry states.');
            }

            $targetId     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $targetStatus = isset($_POST['status']) ? trim((string)$_POST['status']) : '';

            if (!$targetId || !in_array($targetStatus, ['Pending', 'Approved', 'Rejected', 'Completed'])) {
                throw new Exception('Invalid status parameter modification values submitted.');
            }

            // Fetch row with mutation structural lock
            $statusStmt = $con->prepare("SELECT * FROM appointments WHERE id = :id LIMIT 1 FOR UPDATE");
            $statusStmt->execute([':id' => $targetId]);
            $appointmentRow = $statusStmt->fetch();

            if (!$appointmentRow) {
                throw new Exception('The designated appointment record index does not exist.');
            }

            // Dynamically capture execution timestamps when status updates occur
            $currentTime = date('H:i:s');
            
            if ($targetStatus === 'Approved') {
                // "Called into Office": Record the exact start time
                $updateStatusQuery = "UPDATE appointments SET status = :status, start_time = :time_stamp WHERE id = :id";
                $narrativeAction   = "authorized entry and called visitor [{$appointmentRow['visitor_name']}] into the inner office at {$currentTime}.";
            } elseif ($targetStatus === 'Completed') {
                // "Concluded & Dismissed": Record the exact completion/end time
                $updateStatusQuery = "UPDATE appointments SET status = :status, end_time = :time_stamp WHERE id = :id";
                $narrativeAction   = "concluded the meeting and dismissed visitor [{$appointmentRow['visitor_name']}] at {$currentTime}.";
            } else {
                // Default changes (Rejected/Pending blocks)
                $updateStatusQuery = "UPDATE appointments SET status = :status WHERE id = :id";
                $narrativeAction   = "changed status profile on appointment ID [{$targetId}] to value state [{$targetStatus}].";
            }

            $stmt = $con->prepare($updateStatusQuery);
            $bindParams = [':status' => $targetStatus, ':id' => $targetId];
            
            if (in_array($targetStatus, ['Approved', 'Completed'])) {
                $bindParams[':time_stamp'] = $currentTime;
            }
            
            $stmt->execute($bindParams);

            // Commit execution footprints to tracking history logs
            $compiledName = $_SESSION['user_name'] ?? 'Authorized Terminal';
            $friendlyDescription = "Office User [{$compiledName}] " . $narrativeAction;
            recordSystemActivityLog($con, 'Appointments', 'STATE_MUTATION', $friendlyDescription);

            echo json_encode(['status' => 'success', 'message' => "Registry timeline status updated to {$targetStatus} successfully."]);
            exit;
        } catch (\Throwable $branchBException) {
            error_log("❌ Failure inside Appointment Status Branch (B): " . $branchBException->getMessage());
            echo json_encode(['status' => 'error', 'message' => $branchBException->getMessage()]);
            exit;
        }
    }



    // =========================================================================
    // SYSTEM GATEWAY: SECURE COLLEGE ADMINISTRATIVE DASHBOARD ACCREDITATION
    // =========================================================================
    if (isset($_POST['Systemlogin'], $_POST['enc_payload']) && $_POST['Systemlogin'] === 'true' && !empty($_POST['enc_payload'])) {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $encPayload = $_POST['enc_payload'];

            // 1. EXECUTE CRYPTOGRAPHIC DECRYPTION HOOK
            $decrypted = decodeLogin($encPayload, $login_encript);
            
            if ($decrypted === false || !is_array($decrypted)) { 
                throw new \Exception('Cryptographic Handshake Failure: Invalid or corrupted transaction payload token received.');
            } 
            error_log('decode: '. print_r($decrypted, true)); 
        
            // Extract, sanitize, and bind credential metrics safely from decrypted payload
            // Maps incoming user identifier to 'email' based on college_admin system requirements
            $usernameField = isset($decrypted['user_id']) ? trim((string)$decrypted['user_id']) : '';
            $passwordField = isset($decrypted['password']) ? (string)$decrypted['password'] : '';
            
            // 2. High-Frequency Boundary Inputs Validations
            if ($usernameField === '' || $passwordField === '') throw new Exception('Access Denied: Compulsory identification credentials cannot be blank.');
            
            // 3. RETRIEVE RECORD WITH WRITE MUTATION ROW LOCK (FOR UPDATE)
            // COMPLIANT SETUP: Joins roles table to pull the verified role_name string based on schema keys
            $authStmt = $con->prepare("
                SELECT u.id, u.name, u.email, u.password, u.employee_id, u.status, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email LIMIT 1 FOR UPDATE
            ");
            $authStmt->execute([':email' => $usernameField]);
            $collegeUserRow = $authStmt->fetch(PDO::FETCH_ASSOC);
            
            // ANTI-BRUTE FORCE WALL GUARD: Keep mismatch errors vague
            if (!$collegeUserRow) throw new Exception('Invalid username or password. Please try again.');
            
            // 🧠 UPDATED PRIVILEGE COMPLIANCE BARRIER: Lock access using college_admin schema 'status' values
            if ($collegeUserRow['status'] !== 'Active') throw new Exception("Access Revoked: Your account is currently suspended. Please contact the administrator.");
            
            // 4. VERIFY CRYPTOGRAPHIC SECURE PASS HASHES (NATIVE PASSWORD VERIFY LINK)
            if (!password_verify($passwordField, $collegeUserRow['password'])) throw new Exception('Invalid username or password. Please try again.');
            
            // 5. SECURE STATE INITIALIZATION: WIPE AND GENERATE NEW SESSION IDs
            if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
            
            // Initialize global session memory matching your active validation parameters
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = (int)$collegeUserRow['id'];
            $_SESSION['user_name']      = (string)$collegeUserRow['name'];
            $_SESSION['email']          = (string)$collegeUserRow['email'];
            $_SESSION['employee_id']    = (string)$collegeUserRow['employee_id'];
            $_SESSION['role']           = (string)$collegeUserRow['role_name']; // Maps to role_name string
            $_SESSION['last_activity']  = time();
            
            $compiledFullName = $collegeUserRow['name'];
            $successNarrativeText = "User accreditation cleared successfully. Personnel [{$compiledFullName}] logged into system under role profile authority [{$collegeUserRow['role_name']}].";
            
            // 6. ✅ LOG AUTH ACTIVITY FOOTPRINT
            error_log("[SECURITY AUDIT - LOGIN] " . json_encode([
                'user_id'     => $collegeUserRow['id'],
                'employee_id' => $collegeUserRow['employee_id'],
                'role'        => $collegeUserRow['role_name'],
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255),
                'description' => $successNarrativeText
            ]));

            // CALL IMMUTABLE DATABASE ACTIVITY LOG HOOK
            $friendly_login_msg = $compiledFullName . " typed their secret password keys on the login screen and opened their computer dashboard to work as a " . $collegeUserRow['role_name'] . ".";
            recordSystemActivityLog($con, 'Auth', 'LOGIN', $friendly_login_msg);

            // Dynamic Role-Based Routing Architecture based on your Sidebar Layout configuration
            // Renders standard landing views relative to Sneat workspace paths
            $redirect = '../staff/index.php';
            
            // 7. Dispatch success JSON array package back to frontend
            echo json_encode([
                'redirect' => $redirect,
                'status'   => 'success', 
                'message'  => "Welcome back, {$compiledFullName}! Authentication credentials successfully verified.", 
                'role'     => $collegeUserRow['role_name']
            ]);
            exit;

        } catch (\Throwable $innerException) {
            // UNIFIED RECOVERY CATCH GATEWAY
            $logMsg = " ❌ Security Accreditation Login Route Exception Failure: {$innerException->getMessage()} in {$innerException->getFile()} on line {$innerException->getLine()}";
            error_log($logMsg);
            
            echo json_encode([
                'status'  => 'error', 
                'message' => $innerException->getMessage()
            ]);
            exit;
        }
    }


    // =========================================================================
    // BRANCH N: MEMORANDUM / CORRESPONDENCE RETRACTION OBLITERATOR (`delete_memo=true`)
    // =========================================================================
    if (isset($_POST['delete_memo']) && $_POST['delete_memo'] === 'true') {
        try {
            // 1. RBAC SECURITY VALIDATION ACCESS ENFORCEMENT CHECK
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) {
                throw new Exception('Privilege Violation: Notice retraction operation rejected. Profiles lack authority metrics.');
            }

            // 2. PARSE AND SANITIZE TARGET RECORD PRIMARY ID KEY
            $targetMemoId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($targetMemoId <= 0) {
                throw new Exception('Data Validation Fault: Invalid record index pointer dispatched.');
            }

            // 3. INVOKE UNIVERSAL MODULAR DYNAMIC HARD DELETION HELPER ENGINE FUNCTION 
            $deletionResponse = executeGlobalRecordHardDeletion($con, 'memos', $targetMemoId, (int)$sessionUserId);
            
            if ($deletionResponse['status'] === 'error') {
                throw new Exception($deletionResponse['message']);
            }

            // 4. RETRIEVE REFRESHED DATASET PACKETS FROM DYNAMIC DIRECTORY
            $freshDataset = getMemosDashboardDataset($con);
            if (!isset($freshDataset['status']) || $freshDataset['status'] === 'error') {
                throw new Exception($freshDataset['message'] ?? 'Unable to compile refreshed memorandum listings.');
            }

            // 5. PACK SUCCESS RESPONSE ENVELOPES CONTRACT FOR ASYNCHRONOUS FRONTEND DISPATCH
            echo json_encode([
                'status'  => 'success',
                'data'    => $freshDataset['memos_list'], // Returns newly updated rows array to refresh UI instantly
                'message' => 'The official memorandum document has been retracted locally, archived as a JSON snapshot string, and scheduled for cloud sync erasure.'
            ]);
            exit;

        } catch (\Throwable $branchNException) {
            error_log("❌ Failure inside Correspondence Delete Retraction Branch (N): " . $branchNException->getMessage());
            echo json_encode([
                'status'  => 'error',
                'message' => 'Retraction Aborted: ' . $branchNException->getMessage(),
                'data'    => []
            ]);
            exit;
        }
    }


    // =========================================================================
    // BRANCH V: HARD ERASE DOCUMENT LEDGER LOGS REGISTRY RECORD (`delete_document=true`)
    // =========================================================================
    if (isset($_POST['delete_document']) && $_POST['delete_document'] === 'true') {
        try {
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) { throw new Exception('Privilege Violation: Security clearance denied.'); }
            $recordId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            // Call the global helper engine: Targets 'documents' table explicitly
            $deletionResponse = executeGlobalRecordHardDeletion($con, 'documents', $recordId, (int)$sessionUserId);
            if ($deletionResponse['status'] === 'error') { throw new Exception($deletionResponse['message']); }

            // Also clean up any physical files on disk since the database row is gone
            // (You can pass this routine right into the controller as a minor post-delete cleanup item)
            // ... (Your standard disk file unlink routines live here) ...

            $freshDataset = getDocumentsDashboardDataset($con);
            echo json_encode(['status' => 'success', 'data' => $freshDataset['documents_list'], 'message' => $deletionResponse['message']]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit;
        }
    }

    // =========================================================================
    // BRANCH Y: HARD ERASE APPOINTMENT LOG RECORDS (`delete_appointment=true`)
    // =========================================================================
    if (isset($_POST['delete_appointment']) && $_POST['delete_appointment'] === 'true') {
        try {
            if (!in_array($sessionRole, ['Principal', 'Secretary'])) { throw new Exception('Privilege Violation: Security clearance denied.'); }
            $recordId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            // Call the exact same global helper engine: Targets 'appointments' table explicitly!
            $deletionResponse = executeGlobalRecordHardDeletion($con, 'appointments', $recordId, (int)$sessionUserId);
            if ($deletionResponse['status'] === 'error') { throw new Exception($deletionResponse['message']); }

            $dataset = getAppointmentsDashboardDataset($con);
            echo json_encode(['status' => 'success', 'data' => $dataset['appointments_list'], 'message' => $deletionResponse['message']]);
            exit;
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit;
        }
    }

 
  } catch (PDOException $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level PDOException: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[DB02] Database error occurred. ']);
    exit;
} catch (TypeError $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level TypeError: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[TE02] Type mismatch error occurred.']);
    exit;
} catch (Error $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level Error: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[E02]System error occurred.' . $msg]);
    exit;
} catch (Exception $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level Exception: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    if (isset($con) && $con->inTransaction()) $con->rollBack();
    $msg = " ❌ Top-Level Throwable: {$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}";
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);
    echo json_encode(['status' => 'error', 'message' => '[TH02]Fatal system error occurred.']);
    exit;
} finally {
    // ✅ This code runs regardless of success or exception
    // For example, log the script execution
    // $msg = " ✅ Script execution completed at " . date('Y-m-d H:i:s');
    // error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $phpErrorLog);
    // error_log("[$_SERVER[REMOTE_ADDR]] $msg\n", 3, $securityLog);

    // Optional: clean up resources
    if (isset($con)) {
        $con = null; // Close PDO connection
    }
}
 
  
 // If no known action matched:
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action or missing flag.']);
exit;