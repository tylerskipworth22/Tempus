<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    die("Access denied. Only moderators can perform this action.");
}

if (!isset($_POST['capsule_id'], $_POST['action'])) {
    die("Invalid request.");
}

$capsule_id = intval($_POST['capsule_id']);
$action = $_POST['action'];
$moderator_id = $_SESSION['user_id'];

try {
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE Capsule SET status='approved', isReviewed=1 WHERE capsule_id=?");
        $stmt->execute([$capsule_id]);
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE Capsule SET status='rejected', isReviewed=1 WHERE capsule_id=?");
        $stmt->execute([$capsule_id]);
    } elseif ($action === 'warn') {
        if (empty($_POST['message'])) {
            die("Warning message cannot be empty.");
        }
        $message = trim($_POST['message']);
        // Send warning to all owners of the capsule
        $ownersStmt = $conn->prepare("SELECT user_id FROM User_Capsules WHERE capsule_id=? AND role='owner'");
        $ownersStmt->execute([$capsule_id]);
        $owner_ids = $ownersStmt->fetchAll(PDO::FETCH_COLUMN);

        $warnStmt = $conn->prepare("INSERT INTO ModerationWarnings (capsule_id, moderator_id, user_id, message) VALUES (?, ?, ?, ?)");
        foreach ($owner_ids as $user_id) {
            $warnStmt->execute([$capsule_id, $moderator_id, $user_id, $message]);
        }
    } else {
        die("Unknown action.");
    }

    // Redirect back to moderator view for the capsule
    header("Location: modView.php?capsule_id=$capsule_id");
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
