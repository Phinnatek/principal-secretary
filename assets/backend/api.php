<?php


/**
 * =========================================================================
 * AUTHORITATIVE BIDIRECTIONAL CLOUD SYNCHRONIZATION API ENDPOINT
 * Location: portal.sedacoe.edu.gh/api/receive_local_sync.php
 * =========================================================================
 */

// 1. CONFIGURE BASELINE PLATFORM EXECUTION HEADERS
header("Content-Type: application/json; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// Force strict logging outputs onto background error registers
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once '../../assets/backend/system_dir_helper.php'; // needed 
    require_once '../../conn.php'; // your DB connection (adjust path)
    require_once '../../assets/backend/helper_functions.php'; // if needed
    
   
    $con->exec('SET SQL_BIG_SELECTS=1, MAX_JOIN_SIZE=9100000000'); 
    
    if (!isset($con) || !($con instanceof PDO)) {
        throw new Exception("Cloud Vault Connectivity Error: Database reference link could not be initialized.");
    }

 

    // 3. READ INBOUND REST STREAM AND DECODE PAYLOAD OBJECT PACKETS
    $rawInputJsonStream = file_get_contents('php://input');
    if (empty($rawInputJsonStream)) {
        throw new Exception("Access Denied: Empty transaction payload packet submitted.");
    }

    $requestPacket = json_decode($rawInputJsonStream, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Serialization Exception: Malformed JSON character formatting mapping discovered.");
    }
    
    error_log("requestPacket Data:\n" . print_r($requestPacket, true));  

    // 4. HANDSHAKE TOKEN VERIFICATION GATE
    $securedHandshakeToken = 'SECURE_KTU_SDACOE_BRIDGE_TOKEN_2026_XYZ';
    $incomingToken = $requestPacket['handshake_token'] ?? '';

    if (empty($incomingToken) || !hash_equals($securedHandshakeToken, $incomingToken)) {
        http_response_code(403);
        throw new Exception("Authorization Refused: Cryptographic token validation signature mismatch.");
    }

    // Extract core parameters packages elements
    $dataset     = $requestPacket['dataset'] ?? [];
    $binaryFiles = $requestPacket['binary_files'] ?? [];

    $con->beginTransaction();

    // Bypass any internal cloud anti-tampering guards temporarily during transaction windows
    $con->exec("SET @application_auth_context = 'SECURE_CAMPUS_DESK_TOKEN_2026'");

    // =========================================================================
    // STEP 1: PARSE AND EXECUTE REMOTE HARD DELETIONS LOG LINES
    // =========================================================================
    if (!empty($dataset['deletions_log'])) {
        foreach ($dataset['deletions_log'] as $deletionEntry) {
            $targetTable     = preg_replace('/[^a-zA-Z0-9_]/', '', $deletionEntry['target_table']);
            $originalRecordId = (int)$deletionEntry['original_record_id'];

            if (!empty($targetTable) && $originalRecordId > 0) {
                // Execute destructive row delete cleanly inside cloud volumes
                $deleteQuery = "DELETE FROM `$targetTable` WHERE `id` = :id";
                $delStmt = $con->prepare($deleteQuery);
                $delStmt->execute([':id' => $originalRecordId]);
            }
        }
    }

    // =========================================================================
    // STEP 2: PARSE TEXT RECORD OVERRIDES VIA UPSERT (ON DUPLICATE KEY UPDATE)
    // =========================================================================
    $tablesToIgnore = ['deletions_log']; // Handled explicitly above

    foreach ($dataset as $tableName => $rowsCollection) {
        if (in_array($tableName, $tablesToIgnore) || empty($rowsCollection)) {
            continue;
        }

        // Sanitize database structural table string tokens to stop injection vector bugs
        $cleanTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);

        foreach ($rowsCollection as $rowDataPacket) {
            // Build dynamic query fields array maps elements parameters dynamically
            $columnsList = array_keys($rowDataPacket);
            
            $fieldsSqlString = implode(", ", array_map(function($c) { return "`$c`"; }, $columnsList));
            $paramsSqlString = implode(", ", array_map(function($c) { return ":$c"; }, $columnsList));

            // Generate conditional update array strings to overwrite rows on collisions
            $updateSqlPairs = [];
            foreach ($columnsList as $col) {
                if ($col === 'id') continue; // Preserve primary indexes bounds
                $updateSqlPairs[] = "`$col` = VALUES(`$col`)";
            }
            $updateSqlString = implode(", ", $updateSqlPairs);

            $upsertMasterQuery = "INSERT INTO `$cleanTableName` ($fieldsSqlString) 
                                  VALUES ($paramsSqlString) 
                                  ON DUPLICATE KEY UPDATE $updateSqlString";

            $upsertStmt = $con->prepare($upsertMasterQuery);

            // Re-bind exact data row properties mappings cleanly
            $executionBindArray = [];
            foreach ($rowDataPacket as $key => $val) {
                // If local markers show 'Pending', flip them to 'Synced' natively inside cloud storage
                if ($key === 'sync_status' || $key === 'file_sync_status') {
                    $executionBindArray[":$key"] = 'Synced';
                } else {
                    $executionBindArray[":$key"] = $val;
                }
            }

            $upsertStmt->execute($executionBindArray);
        }
    }

    // =========================================================================
    // STEP 3: EXTRACT BASE64 BINARY DATA CHUNKS AND WRITE TO STORAGE DISKS
    // =========================================================================
    // Define public HTML base subfolder targets on the online live server context
    $cloudUploadRoot = $uploadDir; 

    foreach ($binaryFiles as $filePacket) {
        $fileNameClean  = basename($filePacket['file_name']);
        $targetSubFolder = preg_replace('/[^a-zA-Z0-9_\/]/', '', $filePacket['sub_folder']);
        $rawBase64Stream = $filePacket['base64_data'];

        $absoluteCloudFolderDestination = rtrim($cloudUploadRoot, '/') . '/' . $targetSubFolder;
        
        if (!is_dir($absoluteCloudFolderDestination)) {
            mkdir($absoluteCloudFolderDestination, 0755, true);
        }

        $absoluteCloudFileDestination = $absoluteCloudFolderDestination . $fileNameClean;
        $decodedBinaryFileContent = base64_decode($rawBase64Stream);

        if ($decodedBinaryFileContent !== false) {
            file_put_contents($absoluteCloudFileDestination, $decodedBinaryFileContent);
        }
    }

    // =========================================================================
    // STEP 4: GENERATE AN ABSOLUTE CLOUD AUTHORITATIVE SQL BACKUP TEXT DUMP
    // =========================================================================
    $coreTablesArray = ['users', 'visitors_log', 'memos', 'appointments', 'documents', 'activity_logs'];
    $sqlDumpBuilderText = "";

    foreach ($coreTablesArray as $tbl) {
        $sqlDumpBuilderText .= "DROP TABLE IF EXISTS `$tbl`;\n";
        
        // Extract exact Create table string scheme rules
        $showCreateStmt = $con->query("SHOW CREATE TABLE `$tbl`")->fetch(PDO::FETCH_ASSOC);
        $sqlDumpBuilderText .= $showCreateStmt['Create Table'] . ";\n\n";

        // Dump row insert commands
        $rowsQuery = $con->query("SELECT * FROM `$tbl`");
        $allFetchedRows = $rowsQuery->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($allFetchedRows)) {
            foreach ($allFetchedRows as $r) {
                $columnsKeys = array_keys($r);
                $escapedValues = array_map(function($v) use ($con) {
                    if ($v === null) return 'NULL';
                    return $con->quote($v);
                }, array_values($r));

                $fieldsStr = implode(", ", array_map(function($c) { return "`$c`"; }, $columnsKeys));
                $valsStr   = implode(", ", $escapedValues);

                $sqlDumpBuilderText .= "INSERT INTO `$tbl` ($fieldsStr) VALUES ($valsStr);\n";
            }
            $sqlDumpBuilderText .= "\n";
        }
    }

    // Also include a truncated state cleanup row rule for the exclusions logbook
    $sqlDumpBuilderText .= "DROP TABLE IF EXISTS `deletions_log`;\n";
    $showCreateDelStmt = $con->query("SHOW CREATE TABLE `deletions_log`")->fetch(PDO::FETCH_ASSOC);
    $sqlDumpBuilderText .= $showCreateDelStmt['Create Table'] . ";\n\n";

    // Encode compiled plain text string database back into high density Base64
    $compressedMasterSqlBase64String = base64_encode($sqlDumpBuilderText);
 
    // Commit all upsert parameters lines
   if (isset($con) && $con->inTransaction()) $con->commit();

    // 5. PACK SUCCESS ENVELOPES DATA BACK TO CLIENT FOR RE-INJECTION OVERWRITE
    echo json_encode([
        'status'        => 'success',
        'message'       => 'Bidirectional cloud database processing block completed seamlessly.',
        'master_db_sql' => $compressedMasterSqlBase64String // Carries the recovery dump file string
    ]);
    exit;

} catch (\Throwable $cloudApiException) {
    if (isset($con) && $con->inTransaction()) $con->rollBack(); 
    
    error_log("❌ Cloud API Sync Exception Break: " . $cloudApiException->getMessage());
    http_response_code(500);
    echo json_encode(['status'  => 'error','message' => 'Cloud Hub Processing Interruption: ' . $cloudApiException->getMessage(), 'master_db_sql' => ''
    ]);
    exit;
}