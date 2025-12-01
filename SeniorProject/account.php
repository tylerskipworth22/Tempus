<?php
session_start();
require __DIR__ . '/db.php';

// ----------------------------------------------
// 1. Check Firebase Authentication
// ----------------------------------------------
if (!isset($_SESSION['uid'])) {
    header('Location: login.html');
    exit;
}

$firebaseUid = $_SESSION['uid'];

try {
    // ----------------------------------------------
    // 2. Look up the user in MySQL
    // ----------------------------------------------
    $stmt = $conn->prepare("
        SELECT user_id, username, email, role 
        FROM Users 
        WHERE firebase_uid = :uid 
        LIMIT 1
    ");
    $stmt->execute(['uid' => $firebaseUid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found in database. Please contact admin.");
    }

    // Maintain session values
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    // ----------------------------------------------
    // 3. Redirect Admin/Moderator
    // ----------------------------------------------
    switch ($user['role']) {
        case 'admin':
            header('Location: adminDash.php');
            exit;

        case 'moderator':
            header('Location: modDash.php');
            exit;

        default:
            // Continue to account page for normal users
            break;
    }

    // ----------------------------------------------
    // 4. USER ACCOUNT LOGIC BELOW
    // ----------------------------------------------
    $user_id = $user['user_id'];

    // Auto-unlock expired capsules
    $unlock = $conn->prepare("
        UPDATE Capsule
        SET state = 'released'
        WHERE state = 'locked'
          AND release_date <= NOW()
          AND capsule_id IN (
             SELECT capsule_id FROM User_Capsules WHERE user_id = :uid
          )
    ");
    $unlock->execute(['uid' => $user_id]);

    // ----------------------------------------------
    // 5. Fetch capsules the user owns
    // ----------------------------------------------
    $ownedQuery = $conn->prepare("
        SELECT c.capsule_id, c.title, c.description, c.release_date, 
               c.state, c.status
        FROM Capsule c
        JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id
        WHERE uc.user_id = :uid AND uc.role = 'owner'
        ORDER BY c.capsule_id DESC
    ");
    $ownedQuery->execute(['uid' => $user_id]);
    $ownedCapsules = $ownedQuery->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------
    // 6. Fetch capsules the user contributes to
    // ----------------------------------------------
    $sharedQuery = $conn->prepare("
        SELECT c.capsule_id, c.title, c.description, c.release_date, 
               c.state, c.status,
               u.username AS owner_name
        FROM Capsule c
        JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id
        JOIN User_Capsules uc_owner ON c.capsule_id = uc_owner.capsule_id
        JOIN Users u ON uc_owner.user_id = u.user_id
        WHERE uc.user_id = :uid AND uc.role = 'contributor'
        ORDER BY c.capsule_id DESC
    ");
    $sharedQuery->execute(['uid' => $user_id]);
    $sharedCapsules = $sharedQuery->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------
    // 7. Merge owner + contributor capsules
    // ----------------------------------------------
    $capsules = array_merge(
        array_map(fn($c) => $c + ['role' => 'owner'], $ownedCapsules),
        array_map(fn($c) => $c + ['role' => 'contributor'], $sharedCapsules)
    );

    // ----------------------------------------------
    // 8. Fetch moderation warnings
    // ----------------------------------------------
    $warnStmt = $conn->prepare("
        SELECT w.warning_id, w.message, w.capsule_id, w.date_sent, c.title
        FROM ModerationWarnings w
        JOIN Capsule c ON w.capsule_id = c.capsule_id
        WHERE w.user_id = :uid
        ORDER BY w.date_sent DESC
    ");
    $warnStmt->execute(['uid' => $user_id]);
    $warnings = $warnStmt->fetchAll(PDO::FETCH_ASSOC);

    // ----------------------------------------------
    // 9. Load the account template
    // ----------------------------------------------
    include __DIR__ . '/account_template.php';

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
