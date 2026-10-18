<?php
// Start the session so we can remember who is logged in
session_start();
include("db.inc.php");

// LOGOUT: clear the session and go back to the login page
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

$message = "";

// LOGIN: runs when the login form is submitted
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Step 1: look for the username in the User table
    $sql = "SELECT UID, Uname, Password, FullName
            FROM User
            WHERE Uname = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // Check the typed password against the hashed one
        if (password_verify($password, $user['Password'])) {
            session_regenerate_id(true);      // new session id for security
            $_SESSION['userID'] = $user['UID'];
            $_SESSION['userName'] = $user['FullName'];
            $_SESSION['role'] = 'user';

            header("Location: user.php");     // send user to their menu
            exit;
        }
    }

    // Step 2: if not a user, look in the Support (agent) table
    $sql = "SELECT SID, Username, Password, FullName, SLevel
            FROM Support
            WHERE Username = ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($agent = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $agent['Password'])) {
            session_regenerate_id(true);
            $_SESSION['agentID'] = $agent['SID'];
            $_SESSION['agentName'] = $agent['FullName'];
            $_SESSION['supportLevel'] = $agent['SLevel'];
            $_SESSION['role'] = 'agent';

            header("Location: agent.php");    // send agent to their portal
            exit;
        }
    }

    // Neither login worked
    $message = "Incorrect username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Support - Login</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body class="user">

    <h1>Discord Support</h1>
    <h2>Login</h2>

    <!-- Login form (used by both users and agents) -->
    <form method="POST" action="index.php">
        <label>Username:</label>
        <input type="text" name="username" required>

        <br><br>

        <label>Password:</label>
        <input type="password" name="password" required>

        <br><br>

        <input type="submit" name="login" value="Login">
    </form>

    <!-- Show the error message if there is one -->
    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <p>Don't have an account?
        <a href="registerUser.php">Register here</a>
    </p>

</body>
</html>