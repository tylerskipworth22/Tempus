<?php
session_start();
$_SESSION['user_id'] = 5;
$_SESSION['role'] = 'admin';
require __DIR__ . '/db.php';

if (!isset($_GET['capsule_id'])) die("No capsule ID provided.");
$capsule_id = intval($_GET['capsule_id']);

// Fetch capsule info
$stmt = $conn->prepare("
    SELECT c.*, GROUP_CONCAT(u.username SEPARATOR ', ') AS contributors
    FROM Capsule c
    LEFT JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id
    LEFT JOIN Users u ON uc.user_id = u.user_id
    WHERE c.capsule_id = ?
    GROUP BY c.capsule_id
");
$stmt->execute([$capsule_id]);
$capsule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$capsule) {
    die("Capsule not found.");
}

// Fetch media list
$stmt = $conn->prepare("
    SELECT m.*, u.username, mt.name AS media_type
    FROM Media m
    JOIN Users u ON m.uploader_id = u.user_id
    JOIN MediaType mt ON m.media_type_id = mt.media_type_id
    WHERE m.capsule_id = ?
");
$stmt->execute([$capsule_id]);
$media_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin View | <?= htmlspecialchars($capsule['title']); ?></title>
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>

<body>
<!-- ======= Navbar ======= -->
<header class="header">
    <div class="logo-container">
        <a href="adminDash.php" class="logo-link">
            <img src="img/time-capsule.png" alt="Logo" class="logo">
            <h1 class="site-title">Tempus Admin</h1>
        </a>
    </div>
    <nav class="navbar">
        <ul class="nav-links">
            <li><a href="adminDash.php">Dashboard</a></li>
            <li><a href="logout.php">Log Out</a></li>
        </ul>
    </nav>
</header>

<main class="admin-view-container">
    <section class="admin-view">
        <h1><?= htmlspecialchars($capsule['title']); ?></h1>

        <div class="capsule-details">
            <p><strong>Description:</strong> <?= htmlspecialchars($capsule['description']); ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($capsule['status']); ?></p>
            <p><strong>State:</strong> <?= htmlspecialchars($capsule['state']); ?></p>
            <p><strong>Contributors:</strong> <?= htmlspecialchars($capsule['contributors']); ?></p>
            <p><strong>Release Date:</strong> <?= htmlspecialchars($capsule['release_date']); ?></p>
        </div>

        <h2>Media Files</h2>

        <?php if (count($media_list) > 0): ?>
            <div class="file-grid">
                <?php foreach ($media_list as $m): ?>
                    <div class="file-card">
                        <p><strong><?= htmlspecialchars($m['filename']); ?></strong></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars($m['media_type']); ?></p>
                        <p><strong>Uploader:</strong> <?= htmlspecialchars($m['username']); ?></p>

                        <?php
                            $fp = htmlspecialchars($m['file_path']);
                            switch ($m['media_type']) {
                                case 'image': echo "<img class='file-preview' src='$fp' alt='Image preview'>"; break;
                                case 'video': echo "<video class='file-preview' controls src='$fp'></video>"; break;
                                case 'audio': echo "<audio class='file-preview' controls src='$fp'></audio>"; break;
                                case 'document': echo "<a class='file-link' href='$fp' target='_blank'>View / Download</a>"; break;
                                default: echo "<a class='file-link' href='$fp' target='_blank'>View File</a>";
                            }
                        ?>

                        <form method="POST" action="adminAction.php">
                            <input type="hidden" name="media_id" value="<?= $m['media_id']; ?>">
                            <input type="hidden" name="capsule_id" value="<?= $capsule['capsule_id']; ?>">
                            <button type="submit" name="action" value="delete_media" class="cancel-btn">Delete Media</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No media uploaded yet.</p>
        <?php endif; ?>

        <a href="adminDash.php" class="submit-btn">← Back to Dashboard</a>
    </section>
</main>

<footer class="footer">
    <p>© 2025 Tyler Skipworth - Tempus Admin Panel</p>
</footer>
</body>
</html>
