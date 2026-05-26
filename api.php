<?php
/**
 * Standalone DSC Registry API
 * Performs complete CRUD operations on the dsc_registry table.
 * Automatically handles schema creation on initialization.
 */
require_once 'config.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

$db = getDBConnection();
if (!$db) {
    jsonResponse(false, 'Unable to connect to the database. Please check your database settings or .env configuration.', null, 500);
}

// ------------------------------------------------------------------
// AUTOMATIC DATABASE SCHEMA SETUP
// ------------------------------------------------------------------
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `dsc_registry` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `holder_name` VARCHAR(150) NOT NULL,
        `serial_number` VARCHAR(150) NOT NULL UNIQUE,
        `dsc_class` VARCHAR(50) NOT NULL DEFAULT 'Class 3',
        `expiry_date` DATE NOT NULL,
        `client_name` VARCHAR(150) NULL,
        `pin` VARCHAR(100) NULL,
        `email` VARCHAR(100) NULL,
        `phone` VARCHAR(30) NULL,
        `token_status` VARCHAR(50) NOT NULL DEFAULT 'In Office',
        `location` VARCHAR(100) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (`expiry_date`),
        INDEX (`holder_name`),
        INDEX (`serial_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    jsonResponse(false, 'Failed to initialize database table dsc_registry: ' . $e->getMessage(), null, 500);
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        try {
            $stmt = $db->query("SELECT * FROM dsc_registry ORDER BY expiry_date ASC");
            $records = $stmt->fetchAll();
            
            // Calculate stats
            $stats = [
                'total' => count($records),
                'active' => 0,
                'expiring_soon' => 0,
                'expired' => 0
            ];
            
            $today = new DateTime();
            $thirtyDaysFromNow = (new DateTime())->modify('+30 days');
            
            foreach ($records as &$row) {
                $expiry = new DateTime($row['expiry_date']);
                
                if ($expiry < $today) {
                    $row['status'] = 'expired';
                    $stats['expired']++;
                } elseif ($expiry <= $thirtyDaysFromNow) {
                    $row['status'] = 'expiring_soon';
                    $stats['expiring_soon']++;
                } else {
                    $row['status'] = 'active';
                    $stats['active']++;
                }
            }
            
            jsonResponse(true, 'DSC records loaded successfully', [
                'records' => $records,
                'stats' => $stats
            ]);
        } catch (PDOException $e) {
            jsonResponse(false, 'Database fetch error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method', null, 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $holder_name = isset($input['holder_name']) ? trim($input['holder_name']) : '';
        $serial_number = isset($input['serial_number']) ? trim($input['serial_number']) : '';
        $expiry_date = isset($input['expiry_date']) ? trim($input['expiry_date']) : '';
        $dsc_class = isset($input['dsc_class']) ? trim($input['dsc_class']) : 'Class 3';
        
        if (empty($holder_name) || empty($serial_number) || empty($expiry_date)) {
            jsonResponse(false, 'Missing hardware DSC details. Please ensure the token is plugged in.', null, 400);
        }
        
        try {
            // ----------------------------------------------------------
            // Auto-fetch email & phone from portal users table
            // Matches by name = holder_name (case-insensitive)
            // ----------------------------------------------------------
            $auto_email = null;
            $auto_phone = null;
            try {
                $userStmt = $db->prepare(
                    "SELECT email, phone FROM users WHERE LOWER(name) = LOWER(?) LIMIT 1"
                );
                $userStmt->execute([$holder_name]);
                $userRow = $userStmt->fetch();
                if ($userRow) {
                    $auto_email = $userRow['email'] ?: null;
                    $auto_phone = $userRow['phone'] ?: null;
                }
            } catch (PDOException $ue) {
                // users table missing or column mismatch — silently continue
            }

            // Check if the DSC token serial number already exists in the registry
            $checkStmt = $db->prepare("SELECT id, email, phone, token_status FROM dsc_registry WHERE serial_number = ?");
            $checkStmt->execute([$serial_number]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Update hardware fields; also fill email/phone if they are still empty
                $updateStmt = $db->prepare(
                    "UPDATE dsc_registry 
                     SET holder_name = ?, expiry_date = ?, dsc_class = ?,
                         email = COALESCE(NULLIF(email, ''), ?),
                         phone = COALESCE(NULLIF(phone, ''), ?)
                     WHERE id = ?"
                );
                $updateStmt->execute([
                    $holder_name, $expiry_date, $dsc_class,
                    $auto_email, $auto_phone,
                    $existing['id']
                ]);
                
                jsonResponse(true, 'Existing DSC record updated successfully', [
                    'id'          => $existing['id'],
                    'holder_name' => $holder_name,
                    'serial_number' => $serial_number,
                    'expiry_date' => $expiry_date,
                    'dsc_class'   => $dsc_class,
                    'email'       => $existing['email'] ?: $auto_email,
                    'phone'       => $existing['phone'] ?: $auto_phone,
                    'is_new'      => false
                ]);
            } else {
                // Insert a brand new row with auto-fetched email & phone
                $insertStmt = $db->prepare(
                    "INSERT INTO dsc_registry (holder_name, serial_number, expiry_date, dsc_class, email, phone) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $insertStmt->execute([
                    $holder_name, $serial_number, $expiry_date, $dsc_class,
                    $auto_email, $auto_phone
                ]);
                $newId = $db->lastInsertId();
                
                jsonResponse(true, 'New DSC token registered successfully', [
                    'id'          => $newId,
                    'holder_name' => $holder_name,
                    'serial_number' => $serial_number,
                    'expiry_date' => $expiry_date,
                    'dsc_class'   => $dsc_class,
                    'email'       => $auto_email,
                    'phone'       => $auto_phone,
                    'is_new'      => true
                ]);
            }
        } catch (PDOException $e) {
            jsonResponse(false, 'Failed to save DSC token: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method', null, 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $client_name = isset($input['client_name']) ? trim($input['client_name']) : null;
        $pin = isset($input['pin']) ? trim($input['pin']) : null;
        $email = isset($input['email']) ? trim($input['email']) : null;
        $phone = isset($input['phone']) ? trim($input['phone']) : null;
        $token_status = isset($input['token_status']) ? trim($input['token_status']) : 'In Office';
        $location = isset($input['location']) ? trim($input['location']) : null;
        
        // Allow updating core hardware fields via edit form as well
        $holder_name = isset($input['holder_name']) ? trim($input['holder_name']) : null;
        $expiry_date = isset($input['expiry_date']) ? trim($input['expiry_date']) : null;
        $dsc_class = isset($input['dsc_class']) ? trim($input['dsc_class']) : null;
        $serial_number = isset($input['serial_number']) ? trim($input['serial_number']) : null;
        
        if ($id <= 0) {
            jsonResponse(false, 'Valid ID is required', null, 400);
        }
        
        try {
            // Check if record exists
            $checkStmt = $db->prepare("SELECT id FROM dsc_registry WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                jsonResponse(false, 'DSC record not found', null, 404);
            }
            
            // Format empty values to null
            $client_name = ($client_name === '') ? null : $client_name;
            $pin = ($pin === '') ? null : $pin;
            $email = ($email === '') ? null : $email;
            $phone = ($phone === '') ? null : $phone;
            $location = ($location === '') ? null : $location;
            
            $updateQuery = "UPDATE dsc_registry 
                            SET client_name = ?, 
                                pin = ?, 
                                email = ?, 
                                phone = ?, 
                                token_status = ?, 
                                location = ?";
            $params = [$client_name, $pin, $email, $phone, $token_status, $location];
            
            if ($holder_name !== null) {
                $updateQuery .= ", holder_name = ?";
                $params[] = $holder_name;
            }
            if ($expiry_date !== null) {
                $updateQuery .= ", expiry_date = ?";
                $params[] = $expiry_date;
            }
            if ($dsc_class !== null) {
                $updateQuery .= ", dsc_class = ?";
                $params[] = $dsc_class;
            }
            if ($serial_number !== null) {
                $updateQuery .= ", serial_number = ?";
                $params[] = $serial_number;
            }
            
            $updateQuery .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $db->prepare($updateQuery);
            $stmt->execute($params);
            
            jsonResponse(true, 'DSC record updated successfully');
        } catch (PDOException $e) {
            jsonResponse(false, 'Failed to update DSC: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            jsonResponse(false, 'Invalid request method', null, 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? $_GET;
        $id = isset($input['id']) ? intval($input['id']) : 0;
        
        if ($id <= 0) {
            jsonResponse(false, 'Valid ID is required for deletion', null, 400);
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM dsc_registry WHERE id = ?");
            $stmt->execute([$id]);
            
            jsonResponse(true, 'DSC record deleted successfully');
        } catch (PDOException $e) {
            jsonResponse(false, 'Failed to delete DSC record: ' . $e->getMessage(), null, 500);
        }
        break;
        
    default:
        jsonResponse(false, 'Action not supported', null, 400);
        break;
}
