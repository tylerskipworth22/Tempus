<?php
// adminAction.php
session_start();
require __DIR__ . '/db.php';

// --- Temporary admin session ---
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 5;
$_SESSION['role'] = $_SESSION['role'] ?? 'admin';

// --- Check admin access ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only admins can perform actions.");
}

// --- Validate action ---
if (!isset($_POST['action'])) {
    die("No action specified.");
}

$action = $_POST['action'];

// --- Helper functions ---
function capsuleExists($conn, $capsule_id) {
    $stmt = $conn->prepare("SELECT 1 FROM Capsule WHERE capsule_id=?");
    $stmt->execute([$capsule_id]);
    return $stmt->fetchColumn() !== false;
}

function mediaExists($conn, $media_id) {
    $stmt = $conn->prepare("SELECT 1 FROM Media WHERE media_id=?");
    $stmt->execute([$media_id]);
    return $stmt->fetchColumn() !== false;
}

function userExists($conn, $user_id) {
    $stmt = $conn->prepare("SELECT 1 FROM Users WHERE user_id=?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() !== false;
}

// --- Handle actions ---
switch($action) {

    // --- Delete a capsule and all related data ---
    case 'delete_capsule':
        if (!empty($_POST['capsule_id'])) {
            $capsule_id = intval($_POST['capsule_id']);
            if (!capsuleExists($conn, $capsule_id)) die("Capsule does not exist.");

            // Delete related media
            $conn->prepare("DELETE FROM Media WHERE capsule_id=?")->execute([$capsule_id]);
            // Delete user associations
            $conn->prepare("DELETE FROM User_Capsules WHERE capsule_id=?")->execute([$capsule_id]);
            // Delete capsule
            $conn->prepare("DELETE FROM Capsule WHERE capsule_id=?")->execute([$capsule_id]);
        }
        header("Location: adminDash.php");
        exit;

    // --- Delete a single media file ---
    case 'delete_media':
        if (!empty($_POST['media_id']) && !empty($_POST['capsule_id'])) {
            $media_id = intval($_POST['media_id']);
            $capsule_id = intval($_POST['capsule_id']);
            if (!mediaExists($conn, $media_id)) die("Media does not exist.");

            $conn->prepare("DELETE FROM Media WHERE media_id=?")->execute([$media_id]);
        }
        header("Location: adminView.php?capsule_id=$capsule_id");
        exit;

    // --- Approve a capsule ---
    case 'approve_capsule':
        if (!empty($_POST['capsule_id'])) {
            $capsule_id = intval($_POST['capsule_id']);
            if (!capsuleExists($conn, $capsule_id)) die("Capsule does not exist.");

            $conn->prepare("UPDATE Capsule SET status='approved' WHERE capsule_id=?")->execute([$capsule_id]);
        }
        header("Location: adminDash.php");
        exit;

    // --- Reject a capsule ---
    case 'reject_capsule':
        if (!empty($_POST['capsule_id'])) {
            $capsule_id = intval($_POST['capsule_id']);
            if (!capsuleExists($conn, $capsule_id)) die("Capsule does not exist.");

            $conn->prepare("UPDATE Capsule SET status='rejected' WHERE capsule_id=?")->execute([$capsule_id]);
        }
        header("Location: adminDash.php");
        exit;

    // --- Delete a user ---
    case 'delete_user':
        if (!empty($_POST['user_id'])) {
            $user_id = intval($_POST['user_id']);
            if (!userExists($conn, $user_id)) die("User does not exist.");

            // Delete all user capsule associations
            $conn->prepare("DELETE FROM User_Capsules WHERE user_id=?")->execute([$user_id]);
            // Delete user account
            $conn->prepare("DELETE FROM Users WHERE user_id=?")->execute([$user_id]);
        }
        header("Location: adminDash.php");
        exit;

    default:
        die("Unknown action: " . htmlspecialchars($action));
}
?>
