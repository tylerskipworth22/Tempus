<?php
    define("USERNAME", "twskipwo");
    define("PASSWORD", "TBone2319");
    define("DBHOST", "localhost");
    define("DBNAME", "twskipwo");

    try {
        $conn = new PDO('mysql:host=' . DBHOST . ';dbname=' . DBNAME, USERNAME, PASSWORD);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //echo "Successful Connection";
    } catch(PDOException $e) {
        die("Could not connect to database: " . $e->getMessage());
    }
?>