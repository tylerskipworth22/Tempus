<?php
session_start();
require __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid warning ID']);
    exit;
}

$warning_id = intval($_POST['id']);

//delete the warning for this user only
$stmt = $conn->prepare("DELETE FROM ModerationWarnings WHERE warning_id = ? AND user_id = ?");
$success = $stmt->execute([$warning_id, $_SESSION['user_id']]);

echo json_encode(['success' => $success]);
