<?php
/**
 * Database Connection Status
 * Beautiful centered display for connection status
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'Mediasystem');

function createDbConnection() {
    // Start output buffering
    ob_start();
    
    // Create connection
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($connection->connect_error) {
        $error = [
            'message' => $connection->connect_error,
            'errno' => $connection->connect_errno,
            'host' => DB_HOST,
            'user' => DB_USER
        ];
        
        // Centered error display
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Database Connection Error</title>
            <style>
                body {
                    font-family: "Segoe UI", Roboto, sans-serif;
                    background: #f8f9fa;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .card {
                    background: white;
                    border-radius: 10px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                    width: 500px;
                    padding: 30px;
                    text-align: center;
                    border-top: 5px solid #dc3545;
                }
                h1 {
                    color: #dc3545;
                    margin-top: 0;
                }
                .details {
                    text-align: left;
                    background: #fff5f5;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                    font-family: monospace;
                }
                .emoji {
                    font-size: 3rem;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="emoji">🔴</div>
                <h1>Database Connection Failed</h1>
                <div class="details">
                    <p><strong>Error:</strong> '.htmlspecialchars($error['message']).'</p>
                    <p><strong>Code:</strong> '.$error['errno'].'</p>
                    <p><strong>Host:</strong> '.htmlspecialchars($error['host']).'</p>
                    <p><strong>User:</strong> '.htmlspecialchars($error['user']).'</p>
                </div>
                <p>Please check your database configuration and try again.</p>
            </div>
        </body>
        </html>';
        
        $output = ob_get_clean();
        die($output);
    }
    
    // Success display
    $serverInfo = $connection->server_info;
    $stats = $connection->stat();
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Successful</title>
        <style>
            body {
                font-family: "Segoe UI", Roboto, sans-serif;
                background: #f8f9fa;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .card {
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                width: 500px;
                padding: 30px;
                text-align: center;
                border-top: 5px solid #28a745;
            }
            h1 {
                color: #28a745;
                margin-top: 0;
            }
            .details {
                text-align: left;
                background: #f0fff4;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                font-family: monospace;
            }
            .emoji {
                font-size: 3rem;
                margin-bottom: 20px;
            }
            .stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-top: 20px;
            }
            .stat {
                background: #e6f7ff;
                padding: 10px;
                border-radius: 5px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="emoji">✅</div>
            <h1>Database Connected!</h1>
            <div class="details">
                <p><strong>Database:</strong> '.htmlspecialchars(DB_NAME).'</p>
                <p><strong>Host:</strong> '.htmlspecialchars(DB_HOST).'</p>
                <p><strong>Server Version:</strong> '.htmlspecialchars($serverInfo).'</p>
            </div>
            <div class="stats">
                <div class="stat">
                    <small>Thread ID</small>
                    <div>'.$connection->thread_id.'</div>
                </div>
                <div class="stat">
                    <small>Protocol</small>
                    <div>'.$connection->protocol_version.'</div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    $output = ob_get_clean();
    echo $output;
    
    return $connection;
}

// Test the connection
try {
    $db = createDbConnection();
    
    // Optional: Test a simple query
    if ($result = $db->query("SELECT 1")) {
        $result->free();
    }
    
    $db->close();
    
} catch (Exception $e) {
    die("Fatal error: " . $e->getMessage());
}
?>