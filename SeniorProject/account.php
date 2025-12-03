<?php
session_start();
require __DIR__ . '/db.php';

//check firebase authentication
if (!isset($_SESSION['uid'])) {
    header('Location: login.html');
    exit;
}

$firebaseUid = $_SESSION['uid'];

try {
    //look up user in MySQL
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

    //maintain session values
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    //redirect Admin/Moderator dash
    switch ($user['role']) {
        case 'admin':
            header('Location: adminDash.php');
            exit;

        case 'moderator':
            header('Location: modDash.php');
            exit;

        default:
            //continue to account page for normal users
            break;
    }

    //user account logic
    $user_id = $user['user_id'];

    //auto-unlock expired capsules
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

    //get capsules user owns
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

    //get capsules user contributes to
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

//merge owner + contributor capsules
$capsules = array_merge(
    array_map(fn($c) => $c + ['role' => 'owner'], $ownedCapsules),
    array_map(fn($c) => $c + ['role' => 'contributor'], $sharedCapsules)
);

//deduplicate by capsule_id: owner role takes precedence
$uniqueCapsules = [];
foreach ($capsules as $c) {
    $id = $c['capsule_id'];
    if (!isset($uniqueCapsules[$id]) || $uniqueCapsules[$id]['role'] !== 'owner') {
        $uniqueCapsules[$id] = $c;
    }
}

//remove rejected capsules from main list
$capsules = array_filter($uniqueCapsules, fn($c) => ($c['status'] ?? 'pending') !== 'rejected');

//active/pending capsules should only display

    //get moderation warnings
    $warnStmt = $conn->prepare("
        SELECT w.warning_id, w.message, w.capsule_id, w.date_sent, c.title
        FROM ModerationWarnings w
        JOIN Capsule c ON w.capsule_id = c.capsule_id
        WHERE w.user_id = :uid
        ORDER BY w.date_sent DESC
    ");
    $warnStmt->execute(['uid' => $user_id]);
    $warnings = $warnStmt->fetchAll(PDO::FETCH_ASSOC);

//get rejected capsules
$rejectedStmt = $conn->prepare("
    SELECT capsule_id, title, rejection_reason, release_date AS rejection_date
    FROM Capsule
    WHERE status = 'rejected' AND capsule_id IN (
        SELECT capsule_id FROM User_Capsules WHERE user_id = :uid
    )
    ORDER BY release_date DESC
");
$rejectedStmt->execute(['uid' => $user_id]);
$rejectedCapsules = $rejectedStmt->fetchAll(PDO::FETCH_ASSOC);

    //load account template
    include __DIR__ . '/account_template.php';

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
