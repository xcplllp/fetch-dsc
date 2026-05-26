<?php
/**
 * DSC Reader - System Configuration & Database Loader
 * Automatically detects and parses database credentials from the caakanksha.com .env file
 */

// Global database configuration
define('ENV_PATHS', [
    'D:/work/caakanksha.com/akanksha_shashank_associates/.env',
    '../caakanksha.com/akanksha_shashank_associates/.env',
    __DIR__ . '/.env'
]);

define('DB_FALLBACK', [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'u495408944_portal',
    'username' => 'u495408944_portal',
    'password' => 'Highcake@363'
]);

/**
 * Load and parse .env files
 */
function loadEnv() {
    foreach (ENV_PATHS as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $env = [];
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) continue;
                
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $val = trim($parts[1]);
                    // Strip quotes if present
                    $val = trim($val, '"\'');
                    $env[$key] = $val;
                }
            }
            return $env;
        }
    }
    return [];
}

/**
 * Establish a PDO database connection
 */
function getDBConnection() {
    $env = loadEnv();
    
    $host = $env['DB_HOST'] ?? DB_FALLBACK['host'];
    $port = $env['DB_PORT'] ?? DB_FALLBACK['port'];
    $dbname = $env['DB_DATABASE'] ?? DB_FALLBACK['database'];
    $username = $env['DB_USERNAME'] ?? DB_FALLBACK['username'];
    $password = $env['DB_PASSWORD'] ?? DB_FALLBACK['password'];
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        // Return null on failure; we will catch and display error in the UI
        return null;
    }
}

/**
 * Standard API JSON response helper
 */
function jsonResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
