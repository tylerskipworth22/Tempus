<?php
session_start();

//if the user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
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
                <li><a href="account.php">Account</a></li>
            </ul>
        </nav>
    </header>

<main>
    <section class="create-form">
        <h2>Create a New Capsule</h2>

        <form id="createCapsuleForm" action="create_capsule.php" method="post" enctype="multipart/form-data">
            <label for="capTitle">Title</label>
            <input type="text" id="capTitle" name="capTitle" required>

            <label for="capDescription">Description</label>
            <textarea id="capDescription" name="capDescription" required></textarea>
            
            <label for="uploadedFiles">Upload Media</label>
            <input 
                type="file" 
                id="uploadedFiles" 
                name="uploadedFiles[]" 
                multiple 
                required
                accept=".jpg,.jpeg,.png,.mp4,.mov,.avi,.mp3,.wav,.aac,.pdf,.docx,.txt"
            />

            <div id="fileList"></div>

            <button type="submit">Save Capsule</button>
        </form>    
    </section>
</main>

<footer class="footer">
    <p>2025 Tyler Skipworth</p>
</footer>

<script src="upload_preview.js"></script>

</body>
</html>
