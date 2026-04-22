<?php
/**
 * Database connection configuration.
 * Copy this file to db_conn.php and fill in real credentials.
 * db_conn.php is listed in .gitignore and will never be committed.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'Mediasystem');

function createDbConnection() {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection->connect_error) {
        http_response_code(500);
        die('Database connection failed. Check your db_conn.php configuration.');
    }
    return $connection;
}
