<?php
require_once '../app-database-configuration/db_conn.php';
ob_start();
$conn = createDbConnection();
ob_end_clean();

function fetchMedia($conn, $table, $fileCol) {
    $tableEsc = $conn->real_escape_string($table);
    $fileColEsc = $conn->real_escape_string($fileCol);
    $stmt = $conn->prepare("SELECT user, title, `$fileColEsc` FROM `$tableEsc` ORDER BY title ASC");
    if (!$stmt) return [];
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

$images    = fetchMedia($conn, 'images',   'images_file_location');
$videos    = fetchMedia($conn, 'Video',    'video_file_location');
$music     = fetchMedia($conn, 'Music',    'music_file_location');
$documents = fetchMedia($conn, 'Document', 'document_file_location');
$conn->close();

include '../app-shared/header.php';
?>

<div id="dashboard">

    <!-- Upload Section -->
    <section class="dash-section" id="upload-file">
        <h2>&#8679; Upload File</h2>
        <p>Supported formats: JPG, PNG, GIF &bull; MP4, AVI, MOV &bull; MP3, WAV &bull; PDF, DOC, DOCX</p>
        <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
            <label class="file-label" for="myFile">Choose File</label>
            <input type="file" id="myFile" name="fileToUpload"
                   accept=".jpg,.jpeg,.png,.gif,.mp4,.avi,.mov,.mp3,.wav,.pdf,.doc,.docx">
            <span id="file-chosen">No file chosen</span>
            <button type="submit" class="btn-upload">Upload</button>
        </form>
    </section>

    <!-- Images Section -->
    <section class="dash-section" id="images-section">
        <h2>&#128444; Images</h2>
        <?php if (empty($images)): ?>
            <p class="empty-msg">No images uploaded yet.</p>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($images as $img): ?>
                    <div class="media-card">
                        <img src="/<?= htmlspecialchars(ltrim(str_replace('\\', '/', $img['images_file_location']), '/')) ?>" alt="<?= htmlspecialchars($img['title']) ?>" loading="lazy">
                        <div class="media-info">
                            <span class="media-title"><?= htmlspecialchars($img['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($img['user']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Videos Section -->
    <section class="dash-section" id="videos-section">
        <h2>&#127909; Videos</h2>
        <?php if (empty($videos)): ?>
            <p class="empty-msg">No videos uploaded yet.</p>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($videos as $vid): ?>
                    <div class="media-card">
                        <video controls preload="metadata">
                            <source src="/<?= htmlspecialchars(ltrim(str_replace('\\', '/', $vid['video_file_location']), '/')) ?>">
                            Your browser does not support the video tag.
                        </video>
                        <div class="media-info">
                            <span class="media-title"><?= htmlspecialchars($vid['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($vid['user']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Music Section -->
    <section class="dash-section" id="music-section">
        <h2>&#127925; Music</h2>
        <?php if (empty($music)): ?>
            <p class="empty-msg">No music uploaded yet.</p>
        <?php else: ?>
            <div class="music-list">
                <?php foreach ($music as $track): ?>
                    <div class="music-card">
                        <div class="music-meta">
                            <span class="media-title"><?= htmlspecialchars($track['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($track['user']) ?></span>
                        </div>
                        <audio controls preload="metadata">
                            <source src="/<?= htmlspecialchars(ltrim(str_replace('\\', '/', $track['music_file_location']), '/')) ?>">
                            Your browser does not support the audio tag.
                        </audio>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Documents Section -->
    <section class="dash-section" id="documents-section">
        <h2>&#128196; Documents</h2>
        <?php if (empty($documents)): ?>
            <p class="empty-msg">No documents uploaded yet.</p>
        <?php else: ?>
            <div class="doc-list">
                <?php foreach ($documents as $doc): ?>
                    <a class="doc-card" href="/<?= htmlspecialchars(ltrim(str_replace('\\', '/', $doc['document_file_location']), '/')) ?>" target="_blank" rel="noopener">
                        <span class="doc-icon">&#128196;</span>
                        <div class="media-info">
                            <span class="media-title"><?= htmlspecialchars($doc['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($doc['user']) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<script>
    // Show selected filename next to file input
    document.getElementById('myFile').addEventListener('change', function () {
        var label = document.getElementById('file-chosen');
        label.textContent = this.files.length ? this.files[0].name : 'No file chosen';
    });
</script>

<?php include '../app-shared/footer.php'; ?>
