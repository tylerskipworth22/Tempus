<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

use Kreait\Firebase\Factory;

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$idToken = $data['idToken'] ?? '';
$email = $data['email'] ?? '';
$username = $data['username'] ?? '';

if (!$idToken || !$email || !$username) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $factory = (new Factory)->withServiceAccount('firebase-admin.json');
    $auth = $factory->createAuth();

    $verifiedToken = $auth->verifyIdToken($idToken);
    $firebaseUid = $verifiedToken->claims()->get('sub');

    //insert user into MySQL
    $stmt = $conn->prepare("INSERT INTO Users (username, email, firebase_uid) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $firebaseUid]);

    echo json_encode(['success' => true]);
} catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid Firebase token']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
