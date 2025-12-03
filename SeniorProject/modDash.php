<?php
date_default_timezone_set('America/Chicago');
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'moderator') {
    die("Access denied. Only moderators can view this page.");
}

require __DIR__ . '/db.php';

//handle capsule level actions
if (isset($_POST['action'], $_POST['capsule_id'])) {
    $capsule_id = intval($_POST['capsule_id']);

    if ($_POST['action'] === 'approve') {
        //approve capsule
        $stmt = $conn->prepare("UPDATE Capsule SET status='approved', isReviewed=1, rejection_reason=NULL WHERE capsule_id=?");
        $stmt->execute([$capsule_id]);

    } elseif ($_POST['action'] === 'reject') {
        //reject capsule
        if (!empty($_POST['reason'])) {
            $reason = trim($_POST['reason']);

            //update capsule status & store rejection reason
            $stmt = $conn->prepare("UPDATE Capsule SET status='rejected', isReviewed=1, rejection_reason=? WHERE capsule_id=?");
            $stmt->execute([$reason, $capsule_id]);

        } else {
            $error = "Please provide a reason for rejection.";
        }

    } elseif ($_POST['action'] === 'warn') {
        //send warning to capsule owner
        if (!empty($_POST['message'])) {
            $message = trim($_POST['message']);

            //get capsule owner
            $ownerStmt = $conn->prepare("
                SELECT user_id 
                FROM User_Capsules 
                WHERE capsule_id = :capsule_id AND role = 'owner' 
                LIMIT 1
            ");
            $ownerStmt->execute(['capsule_id' => $capsule_id]);
            $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

            if ($owner) {
                //insert warning
                $insertWarn = $conn->prepare("
                    INSERT INTO ModerationWarnings (capsule_id, moderator_id, user_id, message)
                    VALUES (:capsule_id, :moderator_id, :user_id, :message)
                ");
                $insertWarn->execute([
                    'capsule_id'    => $capsule_id,
                    'moderator_id'  => $_SESSION['user_id'],
                    'user_id'       => $owner['user_id'],
                    'message'       => $message
                ]);
            }
        }
    }
}

//get capsules pending review
$stmt = $conn->prepare("
    SELECT c.capsule_id, c.title, c.state, c.release_date, c.isReviewed, c.status, c.rejection_reason, GROUP_CONCAT(u.username) AS owners
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
    <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h1>
    <p>Review pending capsules and send warnings to users.</p>

    <?php if(!empty($error)): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

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

                        <!--approve form -->
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="capsule_id" value="<?= $cap['capsule_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="submit" class="submit-btn" value="Approve Capsule">
                        </form>

                        <!--reject form -->
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="capsule_id" value="<?= $cap['capsule_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="text" name="reason" placeholder="Reason for rejection" style="padding:5px; width:180px;">
                            <input type="submit" class="cancel-btn" value="Reject Capsule">
                        </form>
                    </div>

                    <!--send warning -->
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
