<?php

$uploadOk = 1;
$originalFileName = basename($_FILES["fileToUpload"]["name"]);
$fileType = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

// Determine target directory based on file type
if (in_array($fileType, array("jpg", "jpeg", "png", "gif"))) {
    $target_dir = __DIR__ . "/../app-media/images/";
    $tableName = "images";
    $fileCol   = "images_file_location";
} elseif (in_array($fileType, array("mp4", "avi", "mov"))) {
    $target_dir = __DIR__ . "/../app-media/videos/";
    $tableName = "Video";
    $fileCol   = "video_file_location";
} elseif (in_array($fileType, array("mp3", "wav"))) {
    $target_dir = __DIR__ . "/../app-media/music/";
    $tableName = "Music";
    $fileCol   = "music_file_location";
} elseif (in_array($fileType, array("pdf", "doc", "docx"))) {
    $target_dir = __DIR__ . "/../app-media/documents/";
    $tableName = "Document";
    $fileCol   = "document_file_location";
} else {
    echo "Invalid file type.";
    $uploadOk = 0;
}

// Check if file was uploaded without errors
if ($_FILES["fileToUpload"]["error"] != 0) {
    echo "Error uploading file.";
    $uploadOk = 0;
}

// Check if file already exists
$target_file = $target_dir . $originalFileName;
if (file_exists($target_file)) {
    echo "File already exists.";
    $uploadOk = 0;
}

// Check file size (example: 5GB)
if ($_FILES["fileToUpload"]["size"] > 5000000000) { // 5GB in bytes
    echo "File is too large. Maximum file size is 5GB.";
    $uploadOk = 0;
}

// If there is an error or invalid file type, delay the redirect by 3 seconds
if ($uploadOk == 0) {
    echo "<br>Redirecting back to the upload form in 3 seconds...";
    header("Refresh: 3; URL=/");
    exit();
}

// If everything is ok, try to upload the file and insert details into the database
if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    echo "The file " . htmlspecialchars($originalFileName) . " has been uploaded successfully.";

    // Insert file details into the appropriate table
    require_once __DIR__ . '/../app-database-configuration/db_conn.php';
    $conn = createDbConnection();

    $username = "fakeuser";
    $stmt = $conn->prepare("INSERT INTO `$tableName` (user, title, `$fileCol`) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $originalFileName, $target_file);

    if ($stmt->execute()) {
        echo " File details inserted into the database successfully.";
    } else {
        echo " Error inserting file details into the database: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    $conn->close();
} else {
    echo " Sorry, there was an error uploading your file.";
}

echo "<br>Redirecting back in 3 seconds...";
header("Refresh: 3; URL=/");
exit();

?>
