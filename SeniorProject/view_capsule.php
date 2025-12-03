<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this capsule.");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$capsule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    //admin or moderator can access all capsules
    if ($user_role === 'admin' || $user_role === 'moderator') {
        $stmt = $conn->prepare("SELECT * FROM Capsule WHERE capsule_id = ?");
        $stmt->execute([$capsule_id]);
    } else {
        //normal user must be assigned
        $stmt = $conn->prepare("
            SELECT c.*, uc.role
            FROM Capsule c
            JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id
            WHERE c.capsule_id = ? AND uc.user_id = ?
        ");
        $stmt->execute([$capsule_id, $user_id]);
    }

    $capsule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$capsule) die("Capsule not found or access denied.");

    // Locked capsule check
    if ($capsule['state'] === 'locked') {
        $now = new DateTime();
        $release = new DateTime($capsule['release_date']);
        if ($now < $release) {
            die("This capsule is locked until " . htmlspecialchars($capsule['release_date']));
        }
    }

    //get media files
    $stmtMedia = $conn->prepare("
        SELECT m.*, mt.name AS media_type, u.username AS uploader_name
        FROM Media m
        JOIN MediaType mt ON m.media_type_id = mt.media_type_id
        JOIN Users u ON m.uploader_id = u.user_id
        WHERE m.capsule_id = ?
        ORDER BY m.upload_date ASC
    ");
    $stmtMedia->execute([$capsule_id]);
    $media_files = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

    include __DIR__ . '/view_capsule_template.php';

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
