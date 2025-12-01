<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

$user_id = $_SESSION['user_id'];

// Max file sizes per type (MB)
$file_categories = [
    'image' => ['ext' => ['jpg','jpeg','png','gif','webp'], 'max' => 50],
    'video' => ['ext' => ['mp4','mov','avi','mkv'], 'max' => 200],
    'audio' => ['ext' => ['mp3','wav','m4a'], 'max' => 50],
    'document' => ['ext' => ['pdf','docx','txt'], 'max' => 20]
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Form not submitted properly.");
}

// Input values
$title = trim($_POST['capTitle'] ?? '');
$description = trim($_POST['capDescription'] ?? '');
$release_date = $_POST['date'] ?? '';
$file_descriptions = $_POST['fileDescriptions'] ?? [];

$errors = [];

// Validate required fields
if ($title === '') $errors[] = "Title is required.";
if ($description === '') $errors[] = "Description is required.";
if (empty($_FILES['uploadedFiles']['name'][0])) $errors[] = "At least one file is required.";

// Display errors
if (!empty($errors)) {
    echo "<h3>The following errors occurred:</h3><ul>";
    foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
    echo "</ul><a href='create.php'>Go back</a>";
    exit;
}

try {
    // Insert capsule
    $stmt = $conn->prepare("
        INSERT INTO Capsule (title, description, state)
        VALUES (?, ?, 'draft')
    ");
    $stmt->execute([$title, $description]);
    $capsule_id = $conn->lastInsertId();

    // Link user as owner
    $stmt = $conn->prepare("
        INSERT INTO User_Capsules (user_id, capsule_id, role)
        VALUES (?, ?, 'owner')
    ");
    $stmt->execute([$user_id, $capsule_id]);

    // Upload directories
    $baseDir = __DIR__ . "/uploads/";
    foreach (['image','video','audio','document'] as $dir) {
        $path = $baseDir . $dir . "/";
        if (!is_dir($path)) {
            // Create folder and make writable
            mkdir($path, 0777, true); 
        }
    }

    // Process files
    foreach ($_FILES['uploadedFiles']['name'] as $key => $name) {
        if (empty($name)) continue;

        $tmp_name = $_FILES['uploadedFiles']['tmp_name'][$key];
        $error = $_FILES['uploadedFiles']['error'][$key];
        $size = $_FILES['uploadedFiles']['size'][$key];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($error !== UPLOAD_ERR_OK) {
            echo "<p>Error uploading $name (code $error)</p>";
            continue;
        }

        // Determine category
        $category = null;
        foreach ($file_categories as $type => $info) {
            if (in_array($ext, $info['ext'])) {
                $category = $type;
                if ($size > ($info['max'] * 1024 * 1024)) {
                    echo "<p>File too large: $name. Max allowed: {$info['max']} MB</p>";
                    continue 2;
                }
                break;
            }
        }
        if (!$category) {
            echo "<p>Unsupported file type: $name</p>";
            continue;
        }

        // Unique filename
        $safe_name = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($name));
        $fileName = $capsule_id . "_" . time() . "_" . $safe_name;
        $targetPath = $baseDir . $category . "/" . $fileName;

        if (!move_uploaded_file($tmp_name, $targetPath)) {
            echo "<p>Failed to move uploaded file: $name</p>";
            continue;
        }

        // Get media_type_id
        $stmtType = $conn->prepare("SELECT media_type_id FROM MediaType WHERE name = ?");
        $stmtType->execute([$category]);
        $media_type_id = $stmtType->fetchColumn();

        $file_description = htmlspecialchars($file_descriptions[$key] ?? "Uploaded file: $safe_name");

        // Insert into Media
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

    header("Location: account.php");
    exit;

} catch (PDOException $e) {
    die("<p>Database error: " . htmlspecialchars($e->getMessage()) . "</p>");
}
