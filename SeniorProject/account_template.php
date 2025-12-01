<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="Tyler Skipworth">
    <meta name="description" content="senior project">
    <meta name="keywords" content="time capsule">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tempus</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>

<body>
<header class="header">
    <div class="logo-container">
        <a href="index.html" class="logo-link">
            <img src="img/time-capsule.png" alt="Tempus Logo" class="logo">
            <h1 class="site-title">Tempus</h1>
        </a>
    </div>

    <nav class="navbar">
        <ul class="nav-links">
            <li><a href="index.html">Home</a></li>
            <li><a href="create.php">Create Capsule</a></li>
            <li><a href="account.php">Account</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>

<main class="account-container">
    <h1>Welcome, <?= htmlspecialchars($username ?? $_SESSION['username']) ?></h1>
    <p>Here are your time capsules:</p>

<?php if (!empty($warnings)): ?>
<section class="warnings-box">
    <h2 style="color:#c0392b;">⚠️ Moderator Warnings</h2>
    <?php foreach ($warnings as $w): ?>
        <div class="warning-card" id="warning-<?= $w['warning_id'] ?>">
            <p><strong>Capsule:</strong> <?= htmlspecialchars($w['title']) ?></p>
            <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($w['message'])) ?></p>
            <p class="timestamp">
                Sent on <?= date("M j, Y \\a\\t g:i A", strtotime($w['date_sent'])) ?>
            </p>
            <button type="button" class="dismiss-btn" data-id="<?= $w['warning_id'] ?>">Dismiss</button>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

    <?php if (empty($capsules)): ?>
        <p>No capsules yet. <a href="create.php">Create one now</a>.</p>
    <?php else: ?>
        <?php foreach ($capsules as $capsule): ?>
            <?php
                $release_timestamp = !empty($capsule['release_date']) ? strtotime($capsule['release_date']) : null;
                $state = $capsule['state'] ?? 'draft';
                $capsuleRole = $capsule['role'] ?? 'owner';

                // Determine display status
                if ($state === 'draft') {
                    $status_text = "📝 Draft (editable)";
                } elseif ($state === 'locked') {
                    if ($release_timestamp && $release_timestamp > time()) {
                        $status_text = "🔒 Locked until " . date("M j, Y \\a\\t g:i A", $release_timestamp);
                    } else {
                        // Time has passed — mark released
                        $state = 'released';
                        $status_text = "✅ Released";
                    }
                } elseif ($state === 'released') {
                    $status_text = "✅ Released";
                } else {
                    $status_text = "🔒 Locked"; // fallback
                }
            ?>

            <div class="capsule">
                <p><strong><?= htmlspecialchars($capsule['title']) ?></strong> – <?= $status_text ?></p>

                <p>
                    <?php if ($capsuleRole === 'owner'): ?>
                        Owner of Capsule
                    <?php else: ?>
                        Contributor of Capsule
                    <?php endif; ?>
                </p>

                <!-- Buttons -->
                <?php if ($state === 'draft'): ?>
                    <button onclick="window.location.href='edit_capsule.php?id=<?= urlencode($capsule['capsule_id']) ?>'">
                        ✏️ Edit Capsule
                    </button>

                    <br><br>

                    <?php if ($capsuleRole === 'owner'): ?>
                        <button onclick="window.location.href='invite.php?id=<?= urlencode($capsule['capsule_id']) ?>'">
                            👥 Invite Contributors
                        </button>
                        <br><br>
                        <button onclick="window.location.href='lock_capsule.php?id=<?= urlencode($capsule['capsule_id']) ?>'">
                            🔒 Lock Capsule
                        </button>
                    <?php endif; ?>

                <?php elseif ($state === 'locked'): ?>
                    <button disabled>🔒 Locked</button>

                <?php elseif ($state === 'released'): ?>
                    <button onclick="window.location.href='view_capsule.php?id=<?= urlencode($capsule['capsule_id']) ?>'">
                        👁️ View Capsule
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<footer class="footer">
    <p>2025 Tyler Skipworth</p>
</footer>

<script src="modal.js"></script>
<script src="dismiss_warning.js"></script>

</body>
</html>
