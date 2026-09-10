<?php
// ============================================
// HELPER FUNCTIONS
// ============================================

// Get environment variable
function env($key, $default = null) {
    $envFile = __DIR__ . '/../.env';
    static $env = null;
    
    if ($env === null) {
        $env = [];
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($k, $v) = explode('=', $line, 2);
                    $env[trim($k)] = trim($v);
                }
            }
        }
    }
    
    return $env[$key] ?? $default;
}

// Get config value
function config($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = [
            'app.name' => env('APP_NAME', 'SCI-CALC'),
            'app.url' => env('APP_URL', 'http://localhost/bekast/'),
            'app.env' => env('APP_ENV', 'development'),
            'app.debug' => env('APP_DEBUG', 'true') === 'true',
            'database.host' => env('DB_HOST', 'localhost'),
            'database.user' => env('DB_USER', 'root'),
            'database.password' => env('DB_PASS', ''),
            'database.name' => env('DB_NAME', 'sci_calc'),
            'whatsapp.number' => env('WHATSAPP_NUMBER', '0655472287'),
            'instagram.link' => env('INSTAGRAM_LINK', 'https://instagram.com/becast10'),
        ];
    }
    
    return $config[$key] ?? $default;
}
?>