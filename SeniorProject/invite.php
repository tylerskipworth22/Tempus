<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$capsule_id = $_GET['id'] ?? null;

if (!$capsule_id) {
    die("Invalid capsule ID.");
}

// Check if current user is owner of this capsule
$stmt = $conn->prepare("
    SELECT c.title 
    FROM Capsule c
    JOIN User_Capsules uc 
      ON c.capsule_id = uc.capsule_id
    WHERE uc.user_id = :user_id AND uc.capsule_id = :capsule_id AND uc.role = 'owner'
");
$stmt->execute(['user_id' => $user_id, 'capsule_id' => $capsule_id]);
$capsule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$capsule) {
    die("You do not have permission to invite contributors for this capsule.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invite Contributors</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>
<body>
<div class="invite-container">
    <h1>Invite Contributors to "<?= htmlspecialchars($capsule['title']) ?>"</h1>

    <form action="send_invite.php" method="POST">
        <input type="hidden" name="capsule_id" value="<?= htmlspecialchars($capsule_id) ?>">
        <label for="invite_email">Contributor Email:</label>
        <input type="email" name="invite_email" required placeholder="friend@example.com">
        <button type="submit">Send Invite</button>
    </form>

    <p><a href="account.php">Back to Dashboard</a></p>
</div>
</body>
</html>