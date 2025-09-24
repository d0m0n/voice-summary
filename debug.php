<?php
// デバッグ用ファイル - さくらサーバーにアップロードして実行

echo "<h1>PHP Debug Info</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";

echo "<h2>Extensions:</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'ctype', 'json'];
foreach ($extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "✅" : "❌") . "<br>";
}

echo "<h2>File Permissions:</h2>";
$paths = ['storage', 'bootstrap/cache', '.env'];
foreach ($paths as $path) {
    if (file_exists($path)) {
        echo $path . ": " . substr(sprintf('%o', fileperms($path)), -4) . " ✅<br>";
    } else {
        echo $path . ": File not found ❌<br>";
    }
}

echo "<h2>Environment:</h2>";
if (file_exists('.env')) {
    echo ".env file exists ✅<br>";
    // セキュリティのため、内容は表示しない
} else {
    echo ".env file missing ❌<br>";
}

echo "<h2>Laravel Bootstrap:</h2>";
try {
    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        echo "Composer autoload: ✅<br>";
        
        if (file_exists('bootstrap/app.php')) {
            echo "Bootstrap file exists: ✅<br>";
            
            $app = require_once 'bootstrap/app.php';
            echo "App bootstrap: ✅<br>";
            
            echo "App environment: " . $app->environment() . "<br>";
        } else {
            echo "Bootstrap file missing: ❌<br>";
        }
    } else {
        echo "Vendor autoload missing: ❌<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . " ❌<br>";
}

echo "<h2>Error Logs:</h2>";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLogs = array_slice($lines, -20); // 最新20行
    echo "<pre>" . implode("\n", $recentLogs) . "</pre>";
} else {
    echo "No log file found<br>";
}
?>