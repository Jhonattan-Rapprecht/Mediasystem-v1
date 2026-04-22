<?php
/**
 * Mediasystem Database Setup
 * With floating connection header and infinite-scroll setup
 */

// Start output buffering to capture connection status
ob_start();
require_once 'db_conn.php';
$conn = createDbConnection();
$connectionStatus = ob_get_clean();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mediasystem Database Setup</title>
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f9f9f9;
            color: #333;
        }
        
        /* Floating connection header */
        .connection-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--dark);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transform: translateY(-100%);
            animation: slideDown 0.8s forwards 0.5s;
        }
        
        @keyframes slideDown {
            to { transform: translateY(0); }
        }
        
        .connection-title {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .connection-details {
            font-family: monospace;
            background: rgba(0,0,0,0.2);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .close-btn {
            background: var(--danger);
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            font-weight: bold;
            margin-left: 15px;
        }
        
        /* Infinite scroll setup area */
        .setup-container {
            margin-top: 70px; /* Space for header */
            padding: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .setup-title {
            color: var(--dark);
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .setup-title::after {
            content: "";
            display: block;
            width: 100px;
            height: 4px;
            background: var(--primary);
            margin: 10px auto;
            border-radius: 2px;
        }
        
        .status-feed {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 0;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .status-message {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .status-icon {
            margin-right: 15px;
            font-size: 1.5em;
        }
        
        .success {
            color: var(--success);
        }
        
        .error {
            color: var(--danger);
        }
        
        .status-text {
            flex-grow: 1;
        }
        
        .timestamp {
            font-size: 0.8em;
            color: #95a5a6;
            margin-left: 15px;
        }
        
        .final-status {
            background: var(--primary);
            color: white;
            padding: 20px;
            text-align: center;
            font-weight: bold;
            border-radius: 0 0 8px 8px;
            margin-top: -1px;
        }
        
        /* Decorative elements */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            opacity: 0.1;
            background-image: radial-gradient(var(--primary) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body>
    <!-- Decorative background -->
    <div class="particles"></div>
    
    <!-- Floating connection header -->
    <div class="connection-header">
        <div class="connection-title">Database Connection Active</div>
        <div class="connection-details">
            <?= strip_tags($connectionStatus, '<strong><em><br>') ?>
        </div>
        <button class="close-btn" onclick="this.parentElement.style.display='none'">×</button>
    </div>
    
    <!-- Infinite scroll setup area -->
    <div class="setup-container">
        <h1 class="setup-title">Mediasystem Database Setup</h1>
        
        <div class="status-feed" id="statusFeed">
            <?php
            function createTable($conn, $tableName, $tableDefinition) {
                $sql = "CREATE TABLE IF NOT EXISTS $tableName ($tableDefinition)";
                $startTime = microtime(true);
                
                if ($conn->query($sql)) {
                    $time = round((microtime(true) - $startTime) * 1000);
                    echo '<div class="status-message">
                            <span class="status-icon success">✓</span>
                            <span class="status-text">Created table <strong>'.htmlspecialchars($tableName).'</strong></span>
                            <span class="timestamp">'.$time.'ms</span>
                          </div>';
                } else {
                    echo '<div class="status-message">
                            <span class="status-icon error">✗</span>
                            <span class="status-text">Error creating table <strong>'.htmlspecialchars($tableName).'</strong>: '.htmlspecialchars($conn->error).'</span>
                          </div>';
                }
                
                // Flush output immediately for streaming effect
                ob_flush();
                flush();
                usleep(200000); // Small delay for visual effect
            }
            
            // Define table structures
            $tables = [
                'users' => 'id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            username VARCHAR(255),
                            password VARCHAR(255),
                            email VARCHAR(128),
                            UNIQUE(username),
                            INDEX(email)',
                
                'messages' => 'id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                              author VARCHAR(255),
                              recipient VARCHAR(255),
                              pm CHAR(1),
                              time INT UNSIGNED,
                              message TEXT,
                              INDEX(author),
                              INDEX(recipient)',
                
                'friends' => 'user VARCHAR(255),
                             friend VARCHAR(255),
                             INDEX(user),
                             INDEX(friend)',
                
                'profiles' => 'user VARCHAR(255),
                              type VARCHAR(128),
                              text TEXT,
                              INDEX(user)',

                'images' => 'user VARCHAR(255),
                             title VARCHAR(255),
                             images_file_location VARCHAR(255),
                             imagefile BLOB,
                             INDEX(user),
                             INDEX(title)',
                
                'Video' => 'user VARCHAR(255),
                           title VARCHAR(255),
                           video_file_location VARCHAR(255),
                           videofile BLOB,
                           INDEX(user),
                           INDEX(title)',
                
                'Music' => 'user VARCHAR(255),
                           title VARCHAR(255),
                           music_file_location VARCHAR(255),
                           music BLOB,
                           INDEX(user),
                           INDEX(title)',
                
                'Document' => 'user VARCHAR(255),
                              title VARCHAR(255),
                              document_file_location VARCHAR(255),
                              document BLOB,
                              INDEX(user),
                              INDEX(title)'
            ];
            
            foreach ($tables as $tableName => $tableDefinition) {
                createTable($conn, $tableName, $tableDefinition);
            }
            
            $conn->close();
            ?>
            
            <div class="final-status">
                Database setup completed successfully!
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll the status feed to bottom
        const feed = document.getElementById('statusFeed');
        function scrollToBottom() {
            feed.scrollTop = feed.scrollHeight;
        }
        
        // Scroll when new messages are added
        const observer = new MutationObserver(scrollToBottom);
        observer.observe(feed, { childList: true });
        
        // Initial scroll
        setTimeout(scrollToBottom, 100);
    </script>
</body>
</html>