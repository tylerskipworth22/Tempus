<?php
//handle locked capsule and release date
if ($capsule['state'] === 'locked' && !empty($capsule['release_date'])) {
    try {
        $release = new DateTime($capsule['release_date']);
        $now = new DateTime();

        //check if the capsule is still locked
        if ($now < $release) {
            $lockedMessage = "This capsule is locked until " . $release->format("M j, Y \a\t g:i A");
        } else {
            // Capsule can now be released automatically
            $stmt = $conn->prepare("UPDATE Capsule SET state='released' WHERE capsule_id=?");
            $stmt->execute([$capsule['capsule_id']]);
            $capsule['state'] = 'released';
            $lockedMessage = null;
        }
    } catch (Exception $e) {
        $release = null;
        $lockedMessage = "Invalid release date";
    }
} else {
    $release = !empty($capsule['release_date']) ? new DateTime($capsule['release_date']) : null;
    $lockedMessage = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Tempus - <?= htmlspecialchars($capsule['title']) ?></title>
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>
<body>
<header class="header">
    <div class="logo-container">
        <a href="account.php" class="logo-link">
            <img src="img/time-capsule.png" alt="Tempus Logo" class="logo">
            <h1 class="site-title">Tempus - <?= htmlspecialchars($capsule['title']) ?></h1>
        </a>
    </div>

    <nav class="navbar">
        <ul class="nav-links">
            <li><a href="account.php">Back to Account</a></li>
            <li><a href="create.php">Create New Capsule</a></li>
        </ul>
    </nav>
</header>

<main class="capsule-view">
    <section class="capsule-status">
        <h2><?= htmlspecialchars($capsule['title']) ?></h2>
        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($capsule['description'])) ?></p>
        <p><strong>Status:</strong> <?= ucfirst($capsule['state']) ?></p>
        <?php if ($release): ?>
            <p><strong>Release Date:</strong> <?= $release->format("M j, Y \\a\\t g:i A") ?></p>
        <?php endif; ?>
        <?php if (!empty($lockedMessage)): ?>
            <p style="color:red;"><strong><?= $lockedMessage ?></strong></p>
        <?php endif; ?>
    </section>

    <hr>

    <section class="capsule-content">
        <h2>Contents</h2>

        <?php if (empty($media_files)): ?>
            <p>No files uploaded yet.</p>
        <?php else: ?>
            <?php 
            $grouped = [];
            foreach ($media_files as $file) {
                $grouped[$file['media_type']][] = $file;
            }
            ?>

            <?php foreach ($grouped as $type => $files): ?>
                <h3><?= ucfirst($type) ?> Files</h3>
                <div class="file-grid">
                    <?php foreach ($files as $file): ?>
                        <div class="file-card">
                            <?php $url = htmlspecialchars($file['file_path']); ?>
                            
                            <?php if ($type === 'image'): ?>
                                <img src="<?= $url ?>" alt="" class="file-preview">
                            <?php elseif ($type === 'video'): ?>
                                <video class="file-preview" controls>
                                    <source src="<?= $url ?>" type="video/mp4">
                                </video>
                            <?php elseif ($type === 'audio'): ?>
                                <div class="audio-preview">
                                    <audio controls preload="metadata">
                                        <source src="<?= $url ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                    <p class="filename"><?= htmlspecialchars($file['filename']) ?></p>
                                </div>
                            <?php else: ?>
                                <div class="doc-preview">
                                    <a class="file-link" href="<?= $url ?>" target="_blank"><?= htmlspecialchars($file['filename']) ?></a>
                                </div>
                            <?php endif; ?>

                            <p class="uploader-tag">Added by: <?= htmlspecialchars($file['uploader_name']) ?></p>
                            <?php if (!empty($file['description'])): ?>
                                <p class="file-desc"><?= htmlspecialchars($file['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <div class="back-btn-container" style="text-align:center; margin-top:20px;">
        <a class="back-btn" href="account.php">← Back to Account</a>
    </div>
</main>

<footer class="footer">
    <p>© 2025 Tyler Skipworth</p>
</footer>
</body>
</html>
