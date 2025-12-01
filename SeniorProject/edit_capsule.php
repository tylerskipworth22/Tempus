<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
use Kreait\Firebase\Factory;

// ---- Session & Auth ----
require_once 'auth_check.php'; // ensures $user_id and $username

$capsule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch capsule and media data
try {
    $stmt = $conn->prepare("
        SELECT * FROM Capsule 
        WHERE capsule_id = ? 
        AND capsule_id IN (SELECT capsule_id FROM User_Capsules WHERE user_id = ?)
    ");
    $stmt->execute([$capsule_id, $user_id]);
    $capsule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$capsule) die("Capsule not found or access denied.");

    $media_stmt = $conn->prepare("
        SELECT 
            M.media_id,
            M.filename,
            M.file_path,
            M.description,
            MT.name AS media_type,
            U.username AS uploader_name
        FROM Media M
        JOIN MediaType MT ON M.media_type_id = MT.media_type_id
        JOIN Users U ON M.uploader_id = U.user_id
        WHERE M.capsule_id = ?
    ");
    $media_stmt->execute([$capsule_id]);
    $existingFiles = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// Handle POST for updating capsule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $filesToRemove = $_POST['filesToRemove'] ?? '';

    // Update capsule title & description
    $updateStmt = $conn->prepare("UPDATE Capsule SET title = ?, description = ? WHERE capsule_id = ?");
    $updateStmt->execute([$title, $description, $capsule_id]);

    // Remove selected files
    if ($filesToRemove) {
        $ids = explode(',', $filesToRemove);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $deleteStmt = $conn->prepare("DELETE FROM Media WHERE media_id IN ($placeholders)");
        $deleteStmt->execute($ids);
    }

    // Update descriptions for existing files
    if (!empty($_POST['fileDescriptions'])) {
        foreach ($_POST['fileDescriptions'] as $media_id => $desc) {
            $stmt = $conn->prepare("UPDATE Media SET description = ? WHERE media_id = ? AND capsule_id = ?");
            $stmt->execute([$desc, $media_id, $capsule_id]);
        }
    }

    // Handle new uploads
    if (!empty($_FILES['uploadedFiles']['name'][0])) {
        $uploadDir = __DIR__ . "/uploads/";
        $webPathBase = "uploads/"; // relative path for browser
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['uploadedFiles']['tmp_name'] as $index => $tmp_name) {
            $filename = basename($_FILES['uploadedFiles']['name'][$index]);
            $targetPath = $uploadDir . $filename;
            $webPath = $webPathBase . $filename;

            if (move_uploaded_file($tmp_name, $targetPath)) {
                $mediaType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $typeStmt = $conn->prepare("SELECT media_type_id FROM MediaType WHERE file_type = ?");
                $typeStmt->execute([$mediaType]);
                $typeId = $typeStmt->fetchColumn() ?: 1; // fallback

                // Use the fileDescriptions array by index for new files
                $desc = $_POST['fileDescriptions'][$index] ?? "";

                $insertStmt = $conn->prepare("
                    INSERT INTO Media (capsule_id, uploader_id, media_type_id, filename, file_path, file_size, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    $capsule_id,
                    $user_id,
                    $typeId,
                    $filename,
                    $webPath, // store web-accessible path
                    filesize($targetPath),
                    $desc
                ]);
            }
        }
    }

    header("Location: account.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Capsule - <?= htmlspecialchars($capsule['title']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>
<body>
<header class="header">
    <div class="logo-container">
        <a href="account.php" class="logo-link">
            <img src="img/time-capsule.png" alt="Tempus Logo" class="logo">
            <h1>Tempus</h1>
        </a>
    </div>
</header>

<main class="create-form">
    <h1>Edit Capsule</h1>
    <form id="editCapsuleForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="capsule_id" value="<?= htmlspecialchars($capsule['capsule_id']) ?>">

        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($capsule['title']) ?>" required>

        <label>Description</label>
        <textarea name="description" rows="5"><?= htmlspecialchars($capsule['description']) ?></textarea>

        <label>Upload Files</label>
        <input type="file" id="uploadedFiles" name="uploadedFiles[]" multiple>
        <div id="fileList" class="file-list"></div>

        <h3>Existing Files</h3>
        <div class="file-list" id="existingFiles">
            <?php foreach ($existingFiles as $file): ?>
                <div class="file-item" data-id="<?= $file['media_id'] ?>">
                    <?php if($file['media_type'] === 'image'): ?>
                        <img src="<?= htmlspecialchars($file['file_path']) ?>" class="small-preview">
                    <?php elseif($file['media_type'] === 'video'): ?>
                        <video src="<?= htmlspecialchars($file['file_path']) ?>" class="small-preview" controls></video>
                    <?php elseif($file['media_type'] === 'audio'): ?>
                        <audio src="<?= htmlspecialchars($file['file_path']) ?>" controls></audio>
                    <?php else: ?>
                        <p><?= htmlspecialchars($file['filename']) ?></p>
                    <?php endif; ?>

                    <p class="uploader-tag">
                        Uploaded by: <?= htmlspecialchars($file['uploader_name']) ?>
                    </p>
                    
                    <input type="text" name="fileDescriptions[<?= $file['media_id'] ?>]" 
                    value="<?= htmlspecialchars($file['description'] ?? '') ?>" 
                    placeholder="File description">
                    <button type="button" class="remove-existing-btn">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="filesToRemove" id="filesToRemove" value="">

        <input type="submit" value="Save Changes" class="submit-btn">
        <button type="button" onclick="window.location.href='account.php'">Cancel</button>
    </form>
</main>

<footer class="footer">
    <p>2025 Tyler Skipworth</p>
</footer>

<script src="edit_preview.js"></script>
</body>
</html>
