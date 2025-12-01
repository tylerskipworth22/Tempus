<?php
session_start();
require __DIR__ . '/vendor/autoload.php';  // Composer autoload
require __DIR__ . '/db.php';               // MySQL connection

use Kreait\Firebase\Factory;

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$idToken = $data['idToken'] ?? '';

if (!$idToken) {
    echo json_encode(['success' => false, 'message' => 'Missing ID token']);
    exit;
}

try {
    $factory = (new Factory)->withServiceAccount(__DIR__.'/firebase-admin.json');
    $auth = $factory->createAuth();

    $verifiedToken = $auth->verifyIdToken($idToken);
    $firebaseUid = $verifiedToken->claims()->get('sub');

    // Optional: fetch user from MySQL if needed
    $stmt = $conn->prepare("SELECT user_id, username, role FROM Users WHERE firebase_uid = :uid LIMIT 1");
    $stmt->execute(['uid' => $firebaseUid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found in database']);
        exit;
    }

    $_SESSION['uid'] = $firebaseUid;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    echo json_encode(['success' => true]);

} catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
