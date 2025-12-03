<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
use Kreait\Firebase\Factory;

require_once 'auth_check.php';

$capsule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

//allowed file types & max sizes (MB)
$file_categories = [
    'image' => ['ext' => ['jpg','jpeg','png','gif','webp'], 'max' => 25],
    'video' => ['ext' => ['mp4','mov','avi','mkv'], 'max' => 500],
    'audio' => ['ext' => ['mp3','wav','m4a'], 'max' => 50],
    'document' => ['ext' => ['pdf','docx','txt'], 'max' => 50],
];

try {
    //get capsule
    $stmt = $conn->prepare("
        SELECT * FROM Capsule 
        WHERE capsule_id = ? 
        AND capsule_id IN (SELECT capsule_id FROM User_Capsules WHERE user_id = ?)
    ");
    $stmt->execute([$capsule_id, $user_id]);
    $capsule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$capsule) die("Capsule not found or access denied.");

    //get existing media
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

//POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $filesToRemove = $_POST['filesToRemove'] ?? '';

    //update capsule
    $updateStmt = $conn->prepare("UPDATE Capsule SET title = ?, description = ? WHERE capsule_id = ?");
    $updateStmt->execute([$title, $description, $capsule_id]);

    //remove selected files
    if ($filesToRemove) {
        $ids = explode(',', $filesToRemove);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $deleteStmt = $conn->prepare("DELETE FROM Media WHERE media_id IN ($placeholders)");
        $deleteStmt->execute($ids);

        //delete files from server
        foreach ($existingFiles as $file) {
            if (in_array($file['media_id'], $ids) && file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
        }
    }

    //update file descriptions
    if (!empty($_POST['fileDescriptions'])) {
        foreach ($_POST['fileDescriptions'] as $media_id => $desc) {
            $stmt = $conn->prepare("UPDATE Media SET description = ? WHERE media_id = ? AND capsule_id = ?");
            $stmt->execute([$desc, $media_id, $capsule_id]);
        }
    }

    //handle new file uploads
    if (!empty($_FILES['uploadedFiles']['name'][0])) {
        $baseDir = __DIR__ . "/uploads/";

        foreach ($_FILES['uploadedFiles']['name'] as $index => $name) {
            if (empty($name)) continue;

            $tmp_name = $_FILES['uploadedFiles']['tmp_name'][$index];
            $error = $_FILES['uploadedFiles']['error'][$index];
            $size = $_FILES['uploadedFiles']['size'][$index];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($error !== UPLOAD_ERR_OK) continue;

            //determine category & validate size
            $category = null;
            foreach ($file_categories as $key => $info) {
                if (in_array($ext, $info['ext'])) {
                    $category = $key;
                    if ($size > ($info['max'] * 1024 * 1024)) {
                        echo "<p>File too big: $name (Max {$info['max']} MB)</p>";
                        continue 2;
                    }
                    break;
                }
            }
            if (!$category) {
                echo "<p>File type not accepted: $name</p>";
                continue;
            }

            //ensure folder exists
            $targetDir = $baseDir . $category . "/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $safe_name = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($name));
            $fileName = $capsule_id . "_" . time() . "_" . $safe_name;
            $targetPath = $targetDir . $fileName;

            if (!move_uploaded_file($tmp_name, $targetPath)) {
                echo "<p>Failed to move uploaded file: $name</p>";
                continue;
            }

            //get media_type_id
            $stmtType = $conn->prepare("SELECT media_type_id FROM MediaType WHERE name = ?");
            $stmtType->execute([$category]);
            $media_type_id = $stmtType->fetchColumn();

            $file_description = htmlspecialchars($_POST['newFileDescriptions'][$index] ?? '');

            //insert into Media
            $stmt = $conn->prepare("
                INSERT INTO Media (
                    capsule_id, uploader_id, media_type_id,
                    filename, file_path, file_size, upload_date, description
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $capsule_id,
                $user_id,
                $media_type_id,
                $fileName,
                "uploads/$category/$fileName",
                $size,
                $file_description
            ]);
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
<style>
.file-preview-img { max-width:150px; max-height:100px; display:block; margin-bottom:5px; }
.file-preview-video { max-width:300px; max-height:200px; display:block; margin-bottom:5px; }
.doc-preview { width:100%; height:400px; border:1px solid #ccc; margin-bottom:10px; }
.doc-preview-text { max-height:400px; overflow:auto; border:1px solid #ccc; padding:5px; }
</style>
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
    <input type="file" id="uploadedFiles" name="uploadedFiles[]" multiple
    accept=".jpg,.jpeg,.png,.mp4,.mov,.avi,.mp3,.wav,.aac,.pdf,.docx,.txt">
    <div id="fileList" class="file-list"></div>

    <h3>Existing Files</h3>
    <div class="file-list" id="existingFiles">
    <?php foreach ($existingFiles as $file):
        $type = strtolower($file['media_type']);
        $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
        $fileUrl = htmlspecialchars($file['file_path']);
    ?>
    <div class="file-item" data-id="<?= $file['media_id'] ?>">

        <?php if ($type === 'image'): ?>
            <img src="<?= $fileUrl ?>" class="file-preview-img">
        <?php elseif ($type === 'video'): ?>
            <video src="<?= $fileUrl ?>" controls class="file-preview-video"></video>
        <?php elseif ($type === 'audio'): ?>
            <audio src="<?= $fileUrl ?>" controls></audio>
        <?php elseif ($ext === 'pdf'): ?>
            <iframe src="<?= $fileUrl ?>" class="doc-preview"></iframe>
        <?php elseif ($ext === 'docx'): ?>
            <p><a href="<?= $fileUrl ?>" target="_blank">📄 <?= htmlspecialchars($file['filename']) ?></a></p>
        <?php elseif ($ext === 'txt'): ?>
            <?php $content = htmlspecialchars(file_get_contents($file['file_path'])); ?>
            <pre class="doc-preview-text"><?= substr($content,0,500) ?></pre>
        <?php else: ?>
            <div>📄 <?= htmlspecialchars($file['filename']) ?></div>
        <?php endif; ?>

        <p class="file-name"><?= htmlspecialchars($file['filename']) ?></p>

        <p class="uploader-tag">Uploaded by: <?= htmlspecialchars($file['uploader_name']) ?></p>

        <input type="text" 
               name="fileDescriptions[<?= $file['media_id'] ?>]" 
               value="<?= htmlspecialchars($file['description'] ?? '') ?>"
               placeholder="File description">

        <button type="button" class="remove-existing-btn">Remove</button>
    </div>
    <?php endforeach; ?>
    </div>

    <input type="hidden" name="filesToRemove" id="filesToRemove" value="">

    <input type="submit" value="Save Changes" class="submit-btn">
    <button type="button" onclick="window.location.href='account.php'" class="cancel-btn">Cancel</button>
</form>
</main>

<footer class="footer">
<p>2025 Tyler Skipworth</p>
</footer>

<script src="edit_preview.js"></script>
</body>
</html>
