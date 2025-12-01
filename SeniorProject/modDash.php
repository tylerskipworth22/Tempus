<?php
date_default_timezone_set('America/Chicago');
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'moderator';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    die("Access denied. Only moderators can view this page.");
}

require __DIR__ . '/db.php';

// Handle capsule-level actions
if (isset($_POST['action'], $_POST['capsule_id'])) {
    $capsule_id = intval($_POST['capsule_id']);

    if ($_POST['action'] === 'approve') {
        $conn->prepare("UPDATE Capsule SET status='approved', isReviewed=1 WHERE capsule_id=?")
             ->execute([$capsule_id]);
    } elseif ($_POST['action'] === 'reject') {
        $conn->prepare("UPDATE Capsule SET status='rejected', isReviewed=1 WHERE capsule_id=?")
             ->execute([$capsule_id]);
    } elseif ($_POST['action'] === 'warn' && !empty($_POST['message'])) {
        $message = $_POST['message'];
        $owners = $conn->prepare("SELECT user_id FROM User_Capsules WHERE capsule_id=? AND role='owner'");
        $owners->execute([$capsule_id]);
        foreach ($owners->fetchAll(PDO::FETCH_COLUMN) as $user_id) {
            $conn->prepare("INSERT INTO ModerationWarnings (capsule_id, moderator_id, user_id, message) VALUES (?, ?, ?, ?)")
                 ->execute([$capsule_id, $_SESSION['user_id'], $user_id, $message]);
        }
    }
}

// Fetch capsules pending review
$stmt = $conn->prepare("
    SELECT c.capsule_id, c.title, c.state, c.release_date, c.isReviewed, c.status, GROUP_CONCAT(u.username) AS owners
    FROM Capsule c
    LEFT JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id AND uc.role='owner'
    LEFT JOIN Users u ON uc.user_id = u.user_id
    WHERE c.status='pending' OR c.isReviewed=0
    GROUP BY c.capsule_id
    ORDER BY c.release_date ASC
");
$stmt->execute();
$capsules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Moderator Dashboard</title>
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>
<body>

<header class="header">
    <div class="logo-container">
        <a href="index.html" class="logo-link">
            <img src="img/time-capsule.png" alt="Tempus Logo" class="logo">
            <h1 class="site-title">Tempus Moderator</h1>
        </a>
    </div>
    <nav class="navbar">
        <ul class="nav-links">
            <li><a href="modDash.php">Dashboard</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>

<main class="account-container">
    <h1>Welcome, <?= htmlspecialchars($username ?? $_SESSION['username']) ?></h1>
    <p>Review pending capsules and send warnings to users.</p>

    <?php if(count($capsules) > 0): ?>
        <div class="file-grid">
            <?php foreach($capsules as $cap): ?>
                <div class="file-card">
                    <h2><?= htmlspecialchars($cap['title']); ?></h2>
                    <p><strong>Owners:</strong> <?= htmlspecialchars($cap['owners']); ?></p>
                    <p><strong>State:</strong> <?= htmlspecialchars($cap['state']); ?></p>
                    <p><strong>Release Date:</strong> <?= htmlspecialchars($cap['release_date']); ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($cap['status']); ?></p>
                    <p><strong>Reviewed:</strong> <?= $cap['isReviewed'] ? 'Yes' : 'No'; ?></p>

                    <div class="moderation-buttons">
                        <a class="submit-btn" href="modView.php?capsule_id=<?= $cap['capsule_id']; ?>">View Capsule</a>

                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="capsule_id" value="<?= $cap['capsule_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="submit" class="submit-btn" value="Approve Capsule">
                        </form>

                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="capsule_id" value="<?= $cap['capsule_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="submit" class="cancel-btn" value="Reject Capsule">
                        </form>
                    </div>

                    <form method="POST" style="margin-top:8px;">
                        <input type="hidden" name="capsule_id" value="<?= $cap['capsule_id']; ?>">
                        <input type="hidden" name="action" value="warn">
                        <textarea name="message" class="create-form" placeholder="Enter warning for owner(s)"></textarea>
                        <input type="submit" class="cancel-btn" value="Send Warning">
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="capsule-status">No capsules pending review.</p>
    <?php endif; ?>
</main>

<footer class="footer">
    <p>© 2025 Tyler Skipworth - Tempus Moderator Panel</p>
</footer>

</body>
</html>
