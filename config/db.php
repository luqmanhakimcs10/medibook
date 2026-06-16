<?php
// MediBook - Database Configuration (FIXED VERSION)

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medibook_db');
define('SITE_NAME', 'MediBook');
define('SITE_URL', 'http://localhost/medibook');

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("
    <div style='font-family:sans-serif;padding:30px;background:#fff0f0;
                color:#c00;border:1px solid #f5c2c7;border-radius:8px;margin:20px'>
        <h2>⚠️ Database Connection Failed</h2>
        <p><strong>Error:</strong> " . $conn->connect_error . "</p>

        <hr>

        <p><strong>Checklist:</strong></p>
        <ul>
            <li>✅ XAMPP MySQL is running (green)</li>
            <li>✅ Database exists: <b>medibook_db</b></li>
            <li>✅ You imported your SQL file correctly</li>
            <li>✅ No typo in DB name</li>
        </ul>
    </div>");
}

$conn->set_charset("utf8");
?>