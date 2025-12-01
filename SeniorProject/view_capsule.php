<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

$user_id = 5; // Admin ID
$capsule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Confirm DB connection
    if (!isset($conn)) {
        die("Database connection not established.");
    }

    // Get user's role
    $stmtRole = $conn->prepare("SELECT role FROM Users WHERE user_id = ?");
    $stmtRole->execute([$user_id]);
    $user = $stmtRole->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }

    // Admin or moderator can access all capsules
    if ($user['role'] === 'admin' || $user['role'] === 'moderator') {
        $stmt = $conn->prepare("SELECT * FROM Capsule WHERE capsule_id = ?");
        $stmt->execute([$capsule_id]);
    } else {
        // Normal user — must be assigned
        $stmt = $conn->prepare("
            SELECT c.*, uc.role
            FROM Capsule c
            JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id
            WHERE c.capsule_id = ? AND uc.user_id = ?
        ");
        $stmt->execute([$capsule_id, $user_id]);
    }

    $capsule = $stmt->fetch(PDO::FETCH_ASSOC);

    // Diagnostic messages
    if (!$capsule) {
        echo "<h2>Debug Info</h2>";
        echo "<p>User role: " . htmlspecialchars($user['role']) . "</p>";
        echo "<p>Capsule ID: " . htmlspecialchars($capsule_id) . "</p>";

        // Show what capsules exist
        $test = $conn->query("SELECT capsule_id, title FROM Capsule LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>Existing Capsules:\n" . print_r($test, true) . "</pre>";
        die("<p style='color:red;'>Capsule not found or you do not have access.</p>");
    }

    if ($capsule['state'] === 'locked') {
        $now = new DateTime();
        $release = new DateTime($capsule['release_date']);

        if ($now < $release) {
            die("<h2 style='color:red;'>This capsule is locked until " . 
                htmlspecialchars($capsule['release_date']) . ".</h2>");
        }
    }

    // Get media
    $stmtMedia = $conn->prepare("
        SELECT 
            m.*,
            mt.name AS media_type,
            u.username AS uploader_name
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
?>
