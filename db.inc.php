<?php
// Connect to the MySQL database
// 'db' is the name of the database container in docker compose
$con = mysqli_connect('db', 'SeanHope2006', 'SeanHope123', 'SeanDB');

// Show an error if the connection fails
if (!$con) {
    echo "<p>Error!</p>";
}
?>