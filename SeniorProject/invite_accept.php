<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to accept an invite.");
}

$user_id = $_SESSION['user_id'];
$invite_id = intval($_GET['invite'] ?? 0);

if ($invite_id <= 0) die("Invalid invite.");

$stmt = $conn->prepare("SELECT * FROM Capsule_Invites WHERE invite_id = ?");
$stmt->execute([$invite_id]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invite) die("Invite not found.");

if ($invite['status'] !== 'pending') die("This invite is no longer active.");


// Check if logged-in user's email matches invite email
$userStmt = $conn->prepare("SELECT email FROM Users WHERE user_id = ?");
$userStmt->execute([$user_id]);
$userEmail = $userStmt->fetchColumn();

if ($userEmail !== $invite['invitee_email']) {
    die("This invitation is not for your account.");
}


// Add contributor permission
$insert = $conn->prepare("
    INSERT INTO User_Capsules (user_id, capsule_id, role)
    VALUES (?, ?, 'contributor')
");
$insert->execute([$user_id, $invite['capsule_id']]);

// Mark invite as accepted
$update = $conn->prepare("UPDATE Capsule_Invites SET status = 'accepted' WHERE invite_id = ?");
$update->execute([$invite_id]);

header("Location: account.php");
exit;
