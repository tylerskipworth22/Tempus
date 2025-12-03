<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once __DIR__ . '/vendor/autoload.php';
use Kreait\Firebase\Factory;

if (!isset($_SESSION['user_id'])) {
    //try to restore from Firebase token cookie
    if (!isset($_COOKIE['firebase_token'])) {
        die("Not logged in.");
    }

    $idToken = $_COOKIE['firebase_token'];

    try {
        $factory = (new Factory)->withServiceAccount(__DIR__.'/firebase-admin.json');
        $auth = $factory->createAuth();
        $verifiedToken = $auth->verifyIdToken($idToken);
        $firebaseUid = $verifiedToken->claims()->get('sub');

        //lookup user in MySQL
        $stmt = $conn->prepare("SELECT user_id, username FROM Users WHERE firebase_uid = ?");
        $stmt->execute([$firebaseUid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) die("User not found in database.");

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

    } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
        die("Invalid token.");
    } catch (Exception $e) {
        die("Firebase session restore failed: " . $e->getMessage());
    }
}

// Set page variables
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
