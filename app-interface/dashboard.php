<?php
if (!defined('APP_BOOTSTRAPPED')) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../app-database-configuration/db_conn.php';
$conn = createDbConnection();

function mediaUrl(string $storedPath): string {
    $normalizedPath = str_replace('\\', '/', $storedPath);
    $mediaSegment = '/app-media/';
    $mediaPosition = strpos($normalizedPath, $mediaSegment);

    if ($mediaPosition !== false) {
        return app_url(substr($normalizedPath, $mediaPosition + 1));
    }

    return app_url(ltrim($normalizedPath, '/'));
}

function appAbsoluteUrl(string $relativePath): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $relativePath;
}

function formatBytes(int $bytes): string {
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = (int)floor(log($bytes, 1024));
    $power = min($power, count($units) - 1);
    $value = $bytes / (1024 ** $power);
    return number_format($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
}

function isManagedMediaPath(string $path): bool {
    $mediaRoot = realpath(__DIR__ . '/../app-media');
    $target = realpath($path);
    if ($mediaRoot === false || $target === false) {
        return false;
    }
    $mediaRootNorm = str_replace('\\', '/', $mediaRoot);
    $targetNorm = str_replace('\\', '/', $target);
    return strpos($targetNorm, $mediaRootNorm . '/') === 0 || $targetNorm === $mediaRootNorm;
}

function fetchMedia($conn, string $kind, array $config): array {
    $tableEsc = $conn->real_escape_string($config['table']);
    $fileColEsc = $conn->real_escape_string($config['fileCol']);
    $stmt = $conn->prepare("SELECT user, title, `$fileColEsc` FROM `$tableEsc` ORDER BY title ASC");
    if (!$stmt) {
        return [];
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $path = (string)($row[$config['fileCol']] ?? '');
        $exists = $path !== '' && file_exists($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $sizeBytes = $exists ? (int)filesize($path) : 0;
        $modified = $exists ? date('Y-m-d H:i', filemtime($path)) : 'N/A';
        $relativeUrl = mediaUrl($path);

        $previewType = 'none';
        $previewUrl = '';
        if ($ext === 'pdf') {
            $previewType = 'pdf';
            $previewUrl = $relativeUrl;
        } elseif (in_array($ext, ['doc', 'docx'], true)) {
            $previewType = 'office';
            $previewUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode(appAbsoluteUrl($relativeUrl));
        }

        $rows[] = [
            'kind' => $kind,
            'user' => $row['user'] ?? '',
            'title' => $row['title'] ?? '',
            'filePath' => $path,
            'url' => $relativeUrl,
            'ext' => $ext,
            'meta' => [
                'sizeBytes' => $sizeBytes,
                'sizeLabel' => formatBytes($sizeBytes),
                'modified' => $modified,
                'exists' => $exists,
            ],
            'previewType' => $previewType,
            'previewUrl' => $previewUrl,
            'table' => $config['table'],
            'fileCol' => $config['fileCol'],
        ];
    }

    $stmt->close();
    return $rows;
}

$mediaConfig = [
    'images' => ['table' => 'images', 'fileCol' => 'images_file_location', 'label' => 'Images'],
    'videos' => ['table' => 'Video', 'fileCol' => 'video_file_location', 'label' => 'Videos'],
    'music' => ['table' => 'Music', 'fileCol' => 'music_file_location', 'label' => 'Music'],
    'documents' => ['table' => 'Document', 'fileCol' => 'document_file_location', 'label' => 'Documents'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['rename_media', 'delete_media'], true)) {
        if (!validate_csrf($_POST['csrf_token'] ?? null, 'media_manage')) {
            $_SESSION['dashboard_notice'] = 'Security validation failed. Please try again.';
            $_SESSION['dashboard_notice_type'] = 'status-error';
            header('Location: ' . app_url());
            exit();
        }

        $kind = trim($_POST['kind'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $filePath = trim($_POST['file_path'] ?? '');

        if (!isset($mediaConfig[$kind])) {
            $_SESSION['dashboard_notice'] = 'Invalid content type.';
            $_SESSION['dashboard_notice_type'] = 'status-error';
            header('Location: ' . app_url());
            exit();
        }

        if ($filePath === '' || !isManagedMediaPath($filePath)) {
            $_SESSION['dashboard_notice'] = 'Invalid media path.';
            $_SESSION['dashboard_notice_type'] = 'status-error';
            header('Location: ' . app_url());
            exit();
        }

        $cfg = $mediaConfig[$kind];

        if ($action === 'rename_media') {
            $newTitle = trim($_POST['new_title'] ?? '');

            if ($newTitle === '') {
                $_SESSION['dashboard_notice'] = 'Title cannot be empty.';
                $_SESSION['dashboard_notice_type'] = 'status-error';
                header('Location: ' . app_url());
                exit();
            }

            $stmt = $conn->prepare("UPDATE `{$cfg['table']}` SET title = ? WHERE `{$cfg['fileCol']}` = ? AND title = ? LIMIT 1");
            $stmt->bind_param('sss', $newTitle, $filePath, $title);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            $_SESSION['dashboard_notice'] = $affected > 0 ? 'Title updated.' : 'No item updated.';
            $_SESSION['dashboard_notice_type'] = $affected > 0 ? 'status-ok' : 'status-error';
            header('Location: ' . app_url());
            exit();
        }

        if ($action === 'delete_media') {
            $stmt = $conn->prepare("DELETE FROM `{$cfg['table']}` WHERE `{$cfg['fileCol']}` = ? AND title = ? LIMIT 1");
            $stmt->bind_param('ss', $filePath, $title);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0 && file_exists($filePath)) {
                @unlink($filePath);
            }

            $_SESSION['dashboard_notice'] = $affected > 0 ? 'Item deleted.' : 'No item deleted.';
            $_SESSION['dashboard_notice_type'] = $affected > 0 ? 'status-ok' : 'status-error';
            header('Location: ' . app_url());
            exit();
        }
    }
}

$images = fetchMedia($conn, 'images', $mediaConfig['images']);
$videos = fetchMedia($conn, 'videos', $mediaConfig['videos']);
$music = fetchMedia($conn, 'music', $mediaConfig['music']);
$documents = fetchMedia($conn, 'documents', $mediaConfig['documents']);

$allMedia = array_merge($images, $videos, $music, $documents);
$totalSizeBytes = 0;
foreach ($allMedia as $item) {
    $totalSizeBytes += (int)$item['meta']['sizeBytes'];
}

$dashboardNotice = $_SESSION['dashboard_notice'] ?? '';
$dashboardNoticeType = $_SESSION['dashboard_notice_type'] ?? 'status-ok';
unset($_SESSION['dashboard_notice'], $_SESSION['dashboard_notice_type']);

$conn->close();

include __DIR__ . '/../app-shared/header.php';
?>

<div id="dashboard">

    <section class="dash-section">
        <h2>&#128202; Library Overview</h2>
        <div class="stats-grid">
            <div class="stat-card"><span>Total Items</span><strong><?= count($allMedia) ?></strong></div>
            <div class="stat-card"><span>Images</span><strong><?= count($images) ?></strong></div>
            <div class="stat-card"><span>Videos</span><strong><?= count($videos) ?></strong></div>
            <div class="stat-card"><span>Music</span><strong><?= count($music) ?></strong></div>
            <div class="stat-card"><span>Documents</span><strong><?= count($documents) ?></strong></div>
            <div class="stat-card"><span>Total Size</span><strong><?= htmlspecialchars(formatBytes($totalSizeBytes)) ?></strong></div>
        </div>
    </section>

    <?php if ($dashboardNotice !== ''): ?>
        <section class="dash-section">
            <p class="<?= htmlspecialchars($dashboardNoticeType) ?>"><?= htmlspecialchars($dashboardNotice) ?></p>
        </section>
    <?php endif; ?>

    <!-- Upload Section -->
    <section class="dash-section" id="upload-file">
        <h2>&#8679; Upload File</h2>
        <p>Supported formats: JPG, PNG, GIF &bull; MP4, AVI, MOV &bull; MP3, WAV &bull; PDF, DOC, DOCX</p>
        <form action="<?= htmlspecialchars(app_url('?page=upload')) ?>" method="post" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('upload')) ?>">
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
                        <img src="<?= htmlspecialchars($img['url']) ?>" alt="<?= htmlspecialchars($img['title']) ?>" loading="lazy">
                        <div class="media-info">
                            <span class="media-title"><?= htmlspecialchars($img['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($img['user']) ?></span>
                            <span class="media-meta"><?= strtoupper(htmlspecialchars($img['ext'])) ?> &bull; <?= htmlspecialchars($img['meta']['sizeLabel']) ?> &bull; <?= htmlspecialchars($img['meta']['modified']) ?></span>
                        </div>

                        <form method="post" action="<?= htmlspecialchars(app_url('?page=dashboard')) ?>" class="item-actions js-item-actions">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('media_manage')) ?>">
                            <input type="hidden" name="kind" value="images">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($img['title']) ?>">
                            <input type="hidden" name="file_path" value="<?= htmlspecialchars($img['filePath']) ?>">
                            <input type="hidden" name="new_title" class="js-new-title" value="">
                            <input type="hidden" name="action" class="js-action-field" value="">
                            <button type="button" class="action-btn js-rename" data-current-title="<?= htmlspecialchars($img['title']) ?>" title="Rename">✏</button>
                            <a class="action-btn" href="<?= htmlspecialchars($img['url']) ?>" download title="Download">⭳</a>
                            <button type="submit" name="action" value="delete_media" class="action-btn danger" onclick="return confirm('Delete this image?')" title="Delete">🗑</button>
                        </form>
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
                            <source src="<?= htmlspecialchars($vid['url']) ?>">
                            Your browser does not support the video tag.
                        </video>
                        <div class="media-info">
                            <span class="media-title"><?= htmlspecialchars($vid['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($vid['user']) ?></span>
                            <span class="media-meta"><?= strtoupper(htmlspecialchars($vid['ext'])) ?> &bull; <?= htmlspecialchars($vid['meta']['sizeLabel']) ?> &bull; <?= htmlspecialchars($vid['meta']['modified']) ?></span>
                        </div>

                        <form method="post" action="<?= htmlspecialchars(app_url('?page=dashboard')) ?>" class="item-actions js-item-actions">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('media_manage')) ?>">
                            <input type="hidden" name="kind" value="videos">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($vid['title']) ?>">
                            <input type="hidden" name="file_path" value="<?= htmlspecialchars($vid['filePath']) ?>">
                            <input type="hidden" name="new_title" class="js-new-title" value="">
                            <input type="hidden" name="action" class="js-action-field" value="">
                            <button type="button" class="action-btn js-rename" data-current-title="<?= htmlspecialchars($vid['title']) ?>" title="Rename">✏</button>
                            <a class="action-btn" href="<?= htmlspecialchars($vid['url']) ?>" download title="Download">⭳</a>
                            <button type="submit" name="action" value="delete_media" class="action-btn danger" onclick="return confirm('Delete this video?')" title="Delete">🗑</button>
                        </form>
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
                            <span class="media-meta"><?= strtoupper(htmlspecialchars($track['ext'])) ?> &bull; <?= htmlspecialchars($track['meta']['sizeLabel']) ?> &bull; <?= htmlspecialchars($track['meta']['modified']) ?></span>
                        </div>
                        <audio controls preload="metadata">
                            <source src="<?= htmlspecialchars($track['url']) ?>">
                            Your browser does not support the audio tag.
                        </audio>

                        <form method="post" action="<?= htmlspecialchars(app_url('?page=dashboard')) ?>" class="item-actions js-item-actions">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('media_manage')) ?>">
                            <input type="hidden" name="kind" value="music">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($track['title']) ?>">
                            <input type="hidden" name="file_path" value="<?= htmlspecialchars($track['filePath']) ?>">
                            <input type="hidden" name="new_title" class="js-new-title" value="">
                            <input type="hidden" name="action" class="js-action-field" value="">
                            <button type="button" class="action-btn js-rename" data-current-title="<?= htmlspecialchars($track['title']) ?>" title="Rename">✏</button>
                            <a class="action-btn" href="<?= htmlspecialchars($track['url']) ?>" download title="Download">⭳</a>
                            <button type="submit" name="action" value="delete_media" class="action-btn danger" onclick="return confirm('Delete this track?')" title="Delete">🗑</button>
                        </form>
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
                    <div class="doc-card">
                        <span class="doc-icon">&#128196;</span>
                        <div class="media-info">
                            <span class="media-title"><?= htmlspecialchars($doc['title']) ?></span>
                            <span class="media-user"><?= htmlspecialchars($doc['user']) ?></span>
                            <span class="media-meta"><?= strtoupper(htmlspecialchars($doc['ext'])) ?> &bull; <?= htmlspecialchars($doc['meta']['sizeLabel']) ?> &bull; <?= htmlspecialchars($doc['meta']['modified']) ?></span>
                        </div>

                        <form method="post" action="<?= htmlspecialchars(app_url('?page=dashboard')) ?>" class="item-actions js-item-actions">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('media_manage')) ?>">
                            <input type="hidden" name="kind" value="documents">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($doc['title']) ?>">
                            <input type="hidden" name="file_path" value="<?= htmlspecialchars($doc['filePath']) ?>">
                            <input type="hidden" name="new_title" class="js-new-title" value="">
                            <input type="hidden" name="action" class="js-action-field" value="">

                            <?php if ($doc['previewType'] !== 'none'): ?>
                                <button
                                    type="button"
                                    class="action-btn js-preview"
                                    data-preview-url="<?= htmlspecialchars($doc['previewUrl']) ?>"
                                    data-preview-type="<?= htmlspecialchars($doc['previewType']) ?>"
                                    title="Preview"
                                >👁</button>
                            <?php else: ?>
                                <button type="button" class="action-btn disabled" title="Preview not available" disabled>👁</button>
                            <?php endif; ?>

                            <button type="button" class="action-btn js-rename" data-current-title="<?= htmlspecialchars($doc['title']) ?>" title="Rename">✏</button>
                            <a class="action-btn" href="<?= htmlspecialchars($doc['url']) ?>" download title="Download">⭳</a>
                            <button type="submit" name="action" value="delete_media" class="action-btn danger" onclick="return confirm('Delete this document?')" title="Delete">🗑</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<div id="previewModal" class="preview-modal" aria-hidden="true">
    <div class="preview-dialog">
        <button type="button" class="preview-close" id="previewClose" aria-label="Close preview">×</button>
        <iframe id="previewFrame" class="preview-frame" src="about:blank" title="Document preview"></iframe>
        <p id="previewFallback" class="preview-fallback" hidden>
            Preview is not available for this document type in this environment.
        </p>
    </div>
</div>

<script>
    document.getElementById('myFile').addEventListener('change', function () {
        var label = document.getElementById('file-chosen');
        label.textContent = this.files.length ? this.files[0].name : 'No file chosen';
    });

    document.querySelectorAll('.js-rename').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('.js-item-actions');
            var currentTitle = btn.getAttribute('data-current-title') || '';
            var newTitle = window.prompt('Rename item:', currentTitle);

            if (newTitle === null) {
                return;
            }

            newTitle = newTitle.trim();
            if (!newTitle) {
                alert('Title cannot be empty.');
                return;
            }

            form.querySelector('.js-new-title').value = newTitle;
            form.querySelector('.js-action-field').value = 'rename_media';
            form.submit();
        });
    });

    var previewModal = document.getElementById('previewModal');
    var previewFrame = document.getElementById('previewFrame');
    var previewFallback = document.getElementById('previewFallback');
    var previewClose = document.getElementById('previewClose');

    function closePreview() {
        previewModal.classList.remove('open');
        previewModal.setAttribute('aria-hidden', 'true');
        previewFrame.src = 'about:blank';
    }

    document.querySelectorAll('.js-preview').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-preview-url') || '';
            var type = btn.getAttribute('data-preview-type') || 'none';

            if (!url || type === 'none') {
                previewFallback.hidden = false;
                previewFrame.hidden = true;
            } else {
                previewFallback.hidden = true;
                previewFrame.hidden = false;
                previewFrame.src = url;
            }

            previewModal.classList.add('open');
            previewModal.setAttribute('aria-hidden', 'false');
        });
    });

    previewClose.addEventListener('click', closePreview);
    previewModal.addEventListener('click', function (e) {
        if (e.target === previewModal) {
            closePreview();
        }
    });
</script>

<?php include __DIR__ . '/../app-shared/footer.php'; ?>
