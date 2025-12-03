<?php
require_once 'db.php';
require_once 'auth_check.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only admins can edit users.");
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) die("User ID missing.");

$stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) die("User not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $role = $_POST['role'];

    $update = $conn->prepare("UPDATE Users SET username=:u, role=:r WHERE user_id=:id");
    $update->execute(['u'=>$username, 'r'=>$role, 'id'=>$user_id]);

    header("Location: adminDash.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User - <?= htmlspecialchars($user['username']); ?></title>
<link rel="stylesheet" href="style.css">
<link rel="icon" type="image/x-icon" href="img/time-capsule.png">
</head>
<body>

<header class="header">
    <div class="logo-container">
        <a href="adminDash.php" class="logo-link">
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
    <div class="edit-user-card">
        <h1>Edit User: <?= htmlspecialchars($user['username']); ?></h1>
        <form method="post" class="edit-user-form">
            <label for="username">Username</label>
            <input id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" required>

            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                <option value="moderator" <?= $user['role']=='moderator'?'selected':'' ?>>Moderator</option>
                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
            </select>

            <div class="form-buttons">
                <button type="submit" class="submit-btn">Save Changes</button>
                <a href="adminDash.php" class="cancel-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>

<footer class="footer">
    <p>© 2025 Tyler Skipworth - Tempus Admin Panel</p>
</footer>

</body>
</html>
