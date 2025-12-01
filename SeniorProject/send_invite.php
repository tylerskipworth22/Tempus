<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$capsule_id = $_POST['capsule_id'] ?? null;
$invite_email = trim($_POST['invite_email'] ?? '');

if (!$capsule_id || !$invite_email) {
    die("Missing capsule or email.");
}

// Check if current user is owner
$stmt = $conn->prepare("
    SELECT * FROM User_Capsules 
    WHERE user_id = :user_id AND capsule_id = :capsule_id AND role = 'owner'
");
$stmt->execute(['user_id' => $user_id, 'capsule_id' => $capsule_id]);
if (!$stmt->fetch()) {
    die("You do not have permission to invite contributors for this capsule.");
}

// Check if invited user exists
$stmt = $conn->prepare("SELECT user_id FROM Users WHERE email = :email");
$stmt->execute(['email' => $invite_email]);
$invitedUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invitedUser) {
    die("No user found with that email.");
}

$inviteUserId = $invitedUser['user_id'];

// Check if already a contributor
$stmt = $conn->prepare("
    SELECT * FROM User_Capsules 
    WHERE user_id = :inviteUserId AND capsule_id = :capsule_id
");
$stmt->execute(['inviteUserId' => $inviteUserId, 'capsule_id' => $capsule_id]);
if ($stmt->fetch()) {
    die("User is already a contributor.");
}

// Add user as contributor
$stmt = $conn->prepare("
    INSERT INTO User_Capsules (user_id, capsule_id, role)
    VALUES (:inviteUserId, :capsule_id, 'contributor')
");
$stmt->execute(['inviteUserId' => $inviteUserId, 'capsule_id' => $capsule_id]);

header("Location: account.php");
exit;
