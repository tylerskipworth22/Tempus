<?php
require_once 'db.php';
session_start();

$user_id = $_GET['id'] ?? null;
if (!$user_id) die("User ID missing.");

$delete = $conn->prepare("DELETE FROM Users WHERE user_id = :id");
$delete->execute(['id' => $user_id]);

header("Location: admin.php");
exit;
?>
