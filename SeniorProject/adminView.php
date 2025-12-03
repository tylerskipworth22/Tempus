<?php
session_start();
require __DIR__ . '/db.php';

//ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if (!isset($_GET['capsule_id'])) die("No capsule ID provided.");
$capsule_id = intval($_GET['capsule_id']);

//get capsule info
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

//get media list
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

        <?php if (empty($media_list)): ?>
            <p>No media uploaded yet.</p>
        <?php else: ?>
            <?php 
                //group media by type
                $grouped = [];
                foreach ($media_list as $file) {
                    $grouped[$file['media_type']][] = $file;
                }
            ?>

            <?php foreach ($grouped as $type => $files): ?>
                <h3><?= ucfirst($type) ?> Files</h3>

                <div class="file-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-card">
                            <?php 
                                $url = htmlspecialchars($file['file_path']);
                                $filename = htmlspecialchars($file['filename']);

                                //detect document types
                                $doc_types = ['pdf', 'doc', 'docx', 'txt'];
                                $file_ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                                $is_doc = in_array($file_ext, $doc_types);
                            ?>

                            <?php if ($type === 'image'): ?>
                                <img src="<?= $url ?>" class="file-preview" alt="Image preview">

                            <?php elseif ($type === 'video'): ?>
                                <video class="file-preview" controls>
                                    <source src="<?= $url ?>" type="video/mp4">
                                </video>

                            <?php elseif ($type === 'audio'): ?>
                                <div class="audio-preview">
                                    <audio controls preload="metadata">
                                        <source src="<?= $url ?>" type="audio/mpeg">
                                    </audio>
                                    <p class="filename"><?= $filename ?></p>
                                </div>

                            <?php elseif ($is_doc): ?>
                                <div class="doc-preview">
                                    <a class="file-link" href="<?= $url ?>" target="_blank"><?= $filename ?></a>
                                </div>

                            <?php else: ?>
                                <a class="file-link" href="<?= $url ?>" target="_blank"><?= $filename ?></a>
                            <?php endif; ?>

                            <p class="uploader-tag">Uploaded by: <?= htmlspecialchars($file['username']) ?></p>

                            <?php if (!empty($file['description'])): ?>
                                <p class="file-desc"><?= htmlspecialchars($file['description']) ?></p>
                            <?php endif; ?>

                            <!--admin only delete action -->
                            <form method="POST" action="adminAction.php">
                                <input type="hidden" name="media_id" value="<?= $file['media_id']; ?>">
                                <input type="hidden" name="capsule_id" value="<?= $capsule['capsule_id']; ?>">
                                <button type="submit" name="action" value="delete_media" class="cancel-btn">Delete Media</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="back-btn-container">
            <a href="adminDash.php" class="back-btn">Back to Dashboard</a>
        </div>
    </section>
</main>

<footer class="footer">
    <p>© 2025 Tyler Skipworth - Tempus Admin Panel</p>
</footer>
</body>
</html>
