<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in.");
}

$user_id = $_SESSION['user_id'];
$capsule_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($capsule_id <= 0) die("Invalid capsule ID.");

//get capsule — only allow if draft and owned by user
$stmt = $conn->prepare("
    SELECT * FROM Capsule
    WHERE capsule_id = ? AND state = 'draft' 
      AND capsule_id IN (
          SELECT capsule_id FROM User_Capsules WHERE user_id = ?
      )
");
$stmt->execute([$capsule_id, $user_id]);
$capsule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$capsule) die("Capsule not found or not editable.");

//form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $release_date = $_POST['release_date'] ?? '';
    if (!$release_date) {
        $error = "Please select a release date.";
    } else {
        //convert HTML5 datetime-local to MySQL DATETIME
        $releaseObj = DateTime::createFromFormat('Y-m-d\TH:i', $release_date);
        if ($releaseObj === false) {
            $error = "Invalid release date format.";
        } else {
            //format properly for MySQL
            $normalized = $releaseObj->format("Y-m-d H:i:s");

            //update capsule with normalized release date and lock capsule
            $stmt = $conn->prepare("
                UPDATE Capsule 
                SET release_date = ?, state = 'locked'
                WHERE capsule_id = ?
            ");
            $stmt->execute([$normalized, $capsule_id]);

            header("Location: account.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Lock Capsule - <?= htmlspecialchars($capsule['title']) ?></title>
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
            <li><a href="account.php">Back to Account</a></li>
            <li><a href="create.php">Create Capsule</a></li>
        </ul>
    </nav>
</header>

<main class="create-container">
<h1>Lock Capsule: <?= htmlspecialchars($capsule['title']) ?></h1>

<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form action="" method="POST">
    <label for="release_date">Pick a release date:</label><br>
    <input 
        type="datetime-local" 
        name="release_date" 
        id="release_date" 
        required
        min="<?= date('Y-m-d\TH:i') ?>"
        value="<?= date('Y-m-d\TH:i', strtotime('+1 hour')) ?>">
    <br><br>

    <input type="submit" value="Lock Capsule" class="submit-btn">
    <button type="button" onclick="window.location.href='account.php'" class="cancel-btn">Cancel</button>
</form>
</main>

<footer class="footer">
    <p>2025 Tyler Skipworth</p>
</footer>

</body>
</html>
