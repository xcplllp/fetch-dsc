<?php
/**
 * DSC Manager Installer Script
 * Creates MySQL tables and saves config parameters.
 */
require_once 'config.php';

// If already configured and users table exists, block installation
$configured = file_exists(CONFIG_FILE);
if ($configured) {
    $db = getDBConnection();
    if ($db) {
        try {
            $stmt = $db->query("SELECT 1 FROM users LIMIT 1");
            if ($stmt) {
                die("DSC Manager is already installed. To reinstall, delete 'db_config.json' and refresh.");
            }
        } catch (PDOException $e) {
            // Table doesn't exist, proceed
        }
    }
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $encryption_key = trim($_POST['encryption_key'] ?? '');
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_pass = trim($_POST['admin_pass'] ?? '');
    $admin_name = trim($_POST['admin_name'] ?? 'Administrator');

    if (empty($db_user) || empty($db_name) || empty($encryption_key) || empty($admin_pass)) {
        $error = 'All fields marked with an asterisk (*) are required.';
    } else {
        try {
            // Test connection first
            $dsn = "mysql:host=$db_host;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Create Users table
            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `role` VARCHAR(20) NOT NULL DEFAULT 'staff',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Create Clients DSC table
            $pdo->exec("CREATE TABLE IF NOT EXISTS `clients_dsc` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `client_name` VARCHAR(150) NOT NULL,
                `holder_name` VARCHAR(150) NOT NULL,
                `entity_type` VARCHAR(50) NOT NULL,
                `pan` VARCHAR(10) NULL,
                `tan` VARCHAR(10) NULL,
                `gstin` VARCHAR(15) NULL,
                `dsc_class` VARCHAR(20) NOT NULL DEFAULT 'Class 3',
                `authority` VARCHAR(50) NOT NULL,
                `expiry_date` DATE NOT NULL,
                `encrypted_pin` VARCHAR(255) NULL,
                `token_status` VARCHAR(50) NOT NULL DEFAULT 'In Office',
                `location` VARCHAR(100) NULL,
                `email` VARCHAR(100) NULL,
                `phone` VARCHAR(15) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`expiry_date`),
                INDEX (`client_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Create Possession logs table
            $pdo->exec("CREATE TABLE IF NOT EXISTS `possession_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `client_id` INT NOT NULL,
                `logged_by` VARCHAR(100) NOT NULL,
                `action_type` VARCHAR(50) NOT NULL, -- e.g., 'Token Checked Out', 'Token Deposited', 'Sent to Client'
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`client_id`) REFERENCES `clients_dsc`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Insert initial admin account
            $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO `users` (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$admin_user, $hashed_pass, $admin_name]);

            // Save database configuration file
            $config_data = [
                'db_host' => $db_host,
                'db_user' => $db_user,
                'db_pass' => $db_pass,
                'db_name' => $db_name,
                'encryption_key' => $encryption_key
            ];
            
            file_put_contents(CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT));
            $success = true;
            
            // Try to auto-delete installation file or direct the user to do so
            @unlink(__FILE__);
            
        } catch (PDOException $e) {
            $error = 'Database Connection/Query Failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install DSC Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --primary-glow: linear-gradient(135deg, #06b6d4, #3b82f6);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error-color: #ef4444;
            --success-color: #10b981;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top left, #1e1b4b, #0f172a);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
            background: var(--primary-glow);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .card {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #06b6d4;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 8px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.25);
        }

        .btn {
            display: block;
            width: 100%;
            background: var(--primary-glow);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--error-color);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid var(--success-color);
            color: #a7f3d0;
            text-align: center;
        }

        .success-actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="logo">DSC Manager</div>
        <div class="subtitle">System Installation & Setup Wizard</div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Installation Completed Successfully!</strong><br><br>
            The database tables have been set up and configuration variables written to <code>db_config.json</code>.<br>
            <em>The installation script was deleted automatically for security reasons.</em>
        </div>
        <div class="success-actions">
            <a href="index.php" class="btn-outline">Open Application Dashboard</a>
        </div>
    <?php else: ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="card">
                <div class="card-title">1. Hostinger MySQL Database Details</div>
                <div class="form-group">
                    <label>Database Host *</label>
                    <input type="text" name="db_host" value="localhost" placeholder="e.g. mysql.hostinger.com or localhost" required>
                </div>
                <div class="form-group">
                    <label>Database Username *</label>
                    <input type="text" name="db_user" placeholder="Enter database user" required>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" placeholder="Enter database password">
                </div>
                <div class="form-group">
                    <label>Database Name *</label>
                    <input type="text" name="db_name" placeholder="Enter database name" required>
                </div>
            </div>

            <div class="card">
                <div class="card-title">2. Security Setup</div>
                <div class="form-group">
                    <label>PIN Encryption Security Key (AES-256) *</label>
                    <input type="text" name="encryption_key" value="<?php echo bin2hex(random_bytes(16)); ?>" placeholder="Must be random and private" required>
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 4px;">
                        This key is used to encrypt all client DSC PINs before saving to the database. Keep a backup!
                    </small>
                </div>
            </div>

            <div class="card">
                <div class="card-title">3. Admin Account Setup</div>
                <div class="form-group">
                    <label>Admin Username *</label>
                    <input type="text" name="admin_user" value="admin" required>
                </div>
                <div class="form-group">
                    <label>Admin Full Name *</label>
                    <input type="text" name="admin_name" value="Administrator" required>
                </div>
                <div class="form-group">
                    <label>Admin Password *</label>
                    <input type="password" name="admin_pass" placeholder="Establish admin password" required>
                </div>
            </div>

            <button type="submit" class="btn">Configure & Install</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
