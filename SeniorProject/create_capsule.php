<?php
require __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

$user_id = $_SESSION['user_id'];

//allowed types & max sizes (MB)
$file_categories = [
    'image' => ['ext' => ['jpg','jpeg','png'], 'max' => 25],
    'video' => ['ext' => ['mp4','mov','avi'], 'max' => 500],
    'audio' => ['ext' => ['mp3','wav','aac'], 'max' => 50],
    'document' => ['ext' => ['pdf','docx','txt'], 'max' => 50]
];

// Ensure PHP can accept the largest file
ini_set('upload_max_filesize', '600M'); 
ini_set('post_max_size', '600M');       
ini_set('max_file_uploads', 20);       
ini_set('memory_limit', '1024M');      
ini_set('max_execution_time', 600);    
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Form not submitted properly.");
}

//input values
$title = trim($_POST['capTitle'] ?? '');
$description = trim($_POST['capDescription'] ?? '');
$file_descriptions = $_POST['fileDescriptions'] ?? [];
$errors = [];

// Validate required fields
if ($title === '') $errors[] = "Title is required.";
if ($description === '') $errors[] = "Description is required.";
if (empty($_FILES['uploadedFiles']['name'][0])) $errors[] = "At least one file is required.";

if (!empty($errors)) {
    echo "<h3>The following errors occurred:</h3><ul>";
    foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
    echo "</ul><a href='create.php'>Go back</a>";
    exit;
}

try {
    //insert capsule
    $stmt = $conn->prepare("INSERT INTO Capsule (title, description, state) VALUES (?, ?, 'draft')");
    $stmt->execute([$title, $description]);
    $capsule_id = $conn->lastInsertId();

    //link user as owner
    $stmt = $conn->prepare("INSERT INTO User_Capsules (user_id, capsule_id, role) VALUES (?, ?, 'owner')");
    $stmt->execute([$user_id, $capsule_id]);

    $baseDir = __DIR__ . "/uploads/";
    foreach (array_keys($file_categories) as $dir) {
        $path = $baseDir . $dir . "/";
        if (!is_dir($path)) mkdir($path, 0777, true);
    }

    //process files
    foreach ($_FILES['uploadedFiles']['name'] as $key => $name) {
        if (empty($name)) continue;

        $tmp_name = $_FILES['uploadedFiles']['tmp_name'][$key];
        $error = $_FILES['uploadedFiles']['error'][$key];
        $size = $_FILES['uploadedFiles']['size'][$key];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading $name (code $error)";
            continue;
        }

        //determine category
        $category = null;
        foreach ($file_categories as $type => $info) {
            if (in_array($ext, $info['ext'])) {
                $category = $type;

                //check size against category max
                $maxBytes = $info['max'] * 1024 * 1024;
                if ($size > $maxBytes) {
                    $errors[] = "File too big: $name ({$size} bytes). Max allowed for $type: {$info['max']} MB.";
                    continue 2; // skip this file
                }
                break;
            }
        }

        if (!$category) {
            $errors[] = "File type not accepted: $name";
            continue;
        }

        $safe_name = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($name));
        $fileName = $capsule_id . "_" . time() . "_" . $safe_name;
        $targetPath = $baseDir . $category . "/" . $fileName;

        if (!move_uploaded_file($tmp_name, $targetPath)) {
            $errors[] = "Failed to move uploaded file: $name";
            continue;
        }

        //insert into Media
        $stmtType = $conn->prepare("SELECT media_type_id FROM MediaType WHERE name = ?");
        $stmtType->execute([$category]);
        $media_type_id = $stmtType->fetchColumn();

        $file_description = htmlspecialchars($file_descriptions[$key] ?? "Uploaded file: $safe_name");

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

    if (!empty($errors)) {
        echo "<h3>Some files could not be uploaded:</h3><ul>";
        foreach ($errors as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
        echo "</ul><a href='create.php'>Go back</a>";
        exit;
    }

    header("Location: account.php");
    exit;

} catch (PDOException $e) {
    die("<p>Database error: " . htmlspecialchars($e->getMessage()) . "</p>");
}
