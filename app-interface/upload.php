<?php

$uploadOk = 1;
$originalFileName = basename($_FILES["fileToUpload"]["name"]);
$fileType = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

// Determine target directory based on file type
if (in_array($fileType, array("jpg", "jpeg", "png", "gif"))) {
    $target_dir = "../app-media/images/";
    $tableName = "images";
} elseif (in_array($fileType, array("mp4", "avi", "mov"))) {
    $target_dir = "../app-media/videos/";
    $tableName = "Video";
} elseif (in_array($fileType, array("mp3", "wav"))) {
    $target_dir = "../app-media/music/";
    $tableName = "Music";
} elseif (in_array($fileType, array("pdf", "doc", "docx"))) {
    $target_dir = "../app-media/documents/";
    $tableName = "Document";
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
    header("Refresh: 3; URL=index.php"); // Redirect back to index.php after 3 seconds
    exit();
}

// If everything is ok, try to upload the file and insert details into the database
if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    echo "The file " . htmlspecialchars($originalFileName) . " has been uploaded successfully.";

    // Insert file details into the appropriate table
    require_once '../app-database-configuration/db_conn.php'; // Include your database connection script
    $conn = createDbConnection(); // Establish database connection using the function from db_conn.php

    $username = "fakeuser"; // Replace this with the appropriate username
    $sql = "INSERT INTO $tableName (user, title, ${tableName}_file_location) VALUES ('$username', '$originalFileName', '$target_file')";

    if ($conn->query($sql) === TRUE) {
        echo " File details inserted into the database successfully.";
    } else {
        echo " Error inserting file details into the database: " . $conn->error;
    }

    $conn->close(); // Close the database connection
} else {
    echo " Sorry, there was an error uploading your file.";
}

echo "<br>Redirecting back to the upload form in 3 seconds...";
header("Refresh: 3; URL=index.php"); // Redirect back to index.php after 3 seconds
exit();

?>
