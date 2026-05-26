<?php
/**
 * DSC Reader API
 * Handles database operations for displaying and editing DSC records
 */
require_once 'config.php';

// Disable error display in production JSON responses, but log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

$db = getDBConnection();
if (!$db) {
    jsonResponse(false, 'Unable to connect to the database. Please check your database settings and .env path.', null, 500);
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        try {
            // Fetch all users with their basic info and DSC details
            $query = "SELECT id, name, email, phone, role, pan_number, gst_number, 
                             dsc_holder_name, dsc_expiry_date, dsc_class, dsc_token_serial 
                      FROM users 
                      ORDER BY name ASC";
            $stmt = $db->query($query);
            $users = $stmt->fetchAll();
            
            // Calculate some useful stats for the client dashboard
            $stats = [
                'total_users' => count($users),
                'with_dsc' => 0,
                'expired_dsc' => 0,
                'expiring_soon' => 0, // Expiring in next 30 days
                'active_dsc' => 0
            ];
            
            $today = new DateTime();
            $thirtyDaysFromNow = (new DateTime())->modify('+30 days');
            
            foreach ($users as &$user) {
                $user['has_dsc'] = !empty($user['dsc_holder_name']) || !empty($user['dsc_expiry_date']);
                
                if ($user['has_dsc']) {
                    $stats['with_dsc']++;
                    
                    if (!empty($user['dsc_expiry_date'])) {
                        $expiry = new DateTime($user['dsc_expiry_date']);
                        if ($expiry < $today) {
                            $user['dsc_status'] = 'expired';
                            $stats['expired_dsc']++;
                        } elseif ($expiry <= $thirtyDaysFromNow) {
                            $user['dsc_status'] = 'expiring_soon';
                            $stats['expiring_soon']++;
                        } else {
                            $user['dsc_status'] = 'active';
                            $stats['active_dsc']++;
                        }
                    } else {
                        $user['dsc_status'] = 'incomplete';
                    }
                } else {
                    $user['dsc_status'] = 'none';
                }
            }
            
            jsonResponse(true, 'DSC records loaded successfully', [
                'users' => $users,
                'stats' => $stats
            ]);
        } catch (PDOException $e) {
            jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
        }
        break;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Invalid request method', null, 405);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            // Fallback to standard POST parameters if JSON parse fails
            $input = $_POST;
        }
        
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $dsc_holder_name = isset($input['dsc_holder_name']) ? trim($input['dsc_holder_name']) : null;
        $dsc_expiry_date = isset($input['dsc_expiry_date']) ? trim($input['dsc_expiry_date']) : null;
        $dsc_class = isset($input['dsc_class']) ? trim($input['dsc_class']) : null;
        $dsc_token_serial = isset($input['dsc_token_serial']) ? trim($input['dsc_token_serial']) : null;
        
        if ($id <= 0) {
            jsonResponse(false, 'Valid User ID is required', null, 400);
        }
        
        try {
            // Verify if the user exists
            $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                jsonResponse(false, 'User not found', null, 404);
            }
            
            // Format empty inputs to null
            $dsc_holder_name = ($dsc_holder_name === '') ? null : $dsc_holder_name;
            $dsc_expiry_date = ($dsc_expiry_date === '') ? null : $dsc_expiry_date;
            $dsc_class = ($dsc_class === '') ? null : $dsc_class;
            $dsc_token_serial = ($dsc_token_serial === '') ? null : $dsc_token_serial;
            
            // Update user DSC details
            $updateQuery = "UPDATE users 
                            SET dsc_holder_name = ?, 
                                dsc_expiry_date = ?, 
                                dsc_class = ?, 
                                dsc_token_serial = ? 
                            WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                $dsc_holder_name,
                $dsc_expiry_date,
                $dsc_class,
                $dsc_token_serial,
                $id
            ]);
            
            jsonResponse(true, 'DSC details updated successfully', [
                'id' => $id,
                'dsc_holder_name' => $dsc_holder_name,
                'dsc_expiry_date' => $dsc_expiry_date,
                'dsc_class' => $dsc_class,
                'dsc_token_serial' => $dsc_token_serial
            ]);
        } catch (PDOException $e) {
            jsonResponse(false, 'Failed to update database: ' . $e->getMessage(), null, 500);
        }
        break;
        
    default:
        jsonResponse(false, 'Action not supported', null, 400);
        break;
}
