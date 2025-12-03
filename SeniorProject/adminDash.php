<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only admins can view this page.");
}

require __DIR__ . '/db.php';

//get all capsules
$stmt = $conn->prepare("
    SELECT c.capsule_id, c.title, c.state, c.release_date, c.status, GROUP_CONCAT(u.username) AS owners
    FROM Capsule c
    LEFT JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id AND uc.role='owner'
    LEFT JOIN Users u ON uc.user_id = u.user_id
    GROUP BY c.capsule_id
    ORDER BY c.release_date ASC
");
$stmt->execute();
$capsules = $stmt->fetchAll(PDO::FETCH_ASSOC);

//get moderators
$stmt = $conn->prepare("SELECT user_id, username, email FROM Users WHERE role='moderator'");
$stmt->execute();
$moderators = $stmt->fetchAll(PDO::FETCH_ASSOC);

//get all users

$stmt = $conn->prepare("SELECT user_id, username, email, role FROM Users ORDER BY username ASC");
$stmt->execute();
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

//mod queue
$stmt = $conn->prepare("
    SELECT c.capsule_id, c.title, GROUP_CONCAT(u.username) AS owners, c.status, c.state, c.release_date
    FROM Capsule c
    LEFT JOIN User_Capsules uc ON c.capsule_id = uc.capsule_id AND uc.role='owner'
    LEFT JOIN Users u ON uc.user_id = u.user_id
    WHERE c.status = 'pending'
    GROUP BY c.capsule_id
    ORDER BY c.release_date DESC
");
$stmt->execute();
$pendingCapsules = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Tempus</title>
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>
<body>

<header class="header">
    <div class="logo-container">
        <a href="index.html" class="logo-link">
            <img src="img/time-capsule.png" alt="Tempus Logo" class="logo">
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

<main class="account-container">
    <h1>Welcome, <?= htmlspecialchars($username ?? $_SESSION['username']) ?></h1>
    <p>Manage capsules, moderators, users, and pending content here.</p>

    <section class="admin-section">
        <h2>Capsules</h2>
        <?php if (count($capsules) > 0): ?>
            <div class="file-grid">
                <?php foreach ($capsules as $cap): ?>
                    <div class="file-card">
                        <h3><?= htmlspecialchars($cap['title']); ?></h3>
                        <p><strong>Owners:</strong> <?= htmlspecialchars($cap['owners']); ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($cap['status']); ?></p>
                        <p><strong>State:</strong> <?= htmlspecialchars($cap['state']); ?></p>
                        <p><strong>Release Date:</strong> <?= htmlspecialchars($cap['release_date']); ?></p>

                        <a class="submit-btn" href="adminView.php?capsule_id=<?= $cap['capsule_id']; ?>">View Capsule</a>

                        <form method="POST" action="adminAction.php" style="display:inline-block;">
                            <input type="hidden" name="capsule_id" value="<?= $cap['capsule_id']; ?>">
                            <input type="hidden" name="action" value="delete_capsule">
                            <button type="submit" class="cancel-btn"
                                onclick="return confirm('Delete capsule <?= addslashes($cap['title']); ?>?');">
                                Delete Capsule
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No capsules found.</p>
        <?php endif; ?>
    </section>

<section class="admin-section">
    <h2>User Role Management</h2>
    <p>Edit usernames, emails, and roles for any user.</p>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allUsers as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']); ?></td>
                    <td><?= htmlspecialchars($u['email']); ?></td>
                    <td><?= htmlspecialchars($u['role']); ?></td>
                    <td>
                        <a class="btn" href="edit_user.php?id=<?= urlencode($u['user_id']); ?>">
                            Edit User
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

    <section class="admin-section">
        <h2>Moderation Queue</h2>

        <?php if (count($pendingCapsules) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Owners</th>
                        <th>Status</th>
                        <th>Release Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingCapsules as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['title']); ?></td>
                            <td><?= htmlspecialchars($p['owners']); ?></td>
                            <td><?= htmlspecialchars($p['status']); ?></td>
                            <td><?= htmlspecialchars($p['release_date']); ?></td>
                            <td>
                                <a class="btn" href="adminView.php?capsule_id=<?= $p['capsule_id']; ?>">View</a>

                                <form method="POST" action="adminAction.php" style="display:inline;">
                                    <input type="hidden" name="capsule_id" value="<?= $p['capsule_id']; ?>">
                                    <button type="submit" name="action" value="approve_capsule" class="submit-btn">Approve</button>
                                    <button type="submit" name="action" value="reject_capsule" class="cancel-btn">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p>No capsules pending approval.</p>
        <?php endif; ?>
    </section>
</main>

<footer class="footer">
    <p>© 2025 Tyler Skipworth - Tempus Admin Panel</p>
</footer>

</body>
</html>
