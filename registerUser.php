<?php
include("db.inc.php");
$message = "";

// REGISTER: runs when the register form is submitted
if (isset($_POST['register'])) {
    $FullName = trim($_POST['fullName']);
    $Uname = trim($_POST['username']);
    $Password = $_POST['password'];
    $UDOB = $_POST['dob'];

    // Check if the username is already taken
    $checkSQL = "SELECT UID FROM User WHERE Uname = ?";
    $checkStmt = mysqli_prepare($con, $checkSQL);
    mysqli_stmt_bind_param($checkStmt, "s", $Uname);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($checkResult) > 0) {
        $message = "That username is already taken. Please choose another.";
    } else {
        // Hash the password so it is not stored as plain text
        $hashedPassword = password_hash($Password, PASSWORD_DEFAULT);

        // Save the new user in the database
        $sql = "INSERT INTO User (Uname, Password, FullName, UDOB)
                VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $Uname,
            $hashedPassword,
            $FullName,
            $UDOB
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Registration complete! You can now log in.";
        } else {
            $message = "Error registering. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New User - Discord Support</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body class="user">

    <h1>Discord Support</h1>
    <h2>Create New User</h2>

    <!-- Registration form: JavaScript checks it before it is sent -->
    <form method="POST" action="registerUser.php" onsubmit="return validateRegister();">
        <label>Full Name:</label>
        <input type="text" name="fullName" maxlength="100" required>
        <br><br>

        <label>Username:</label>
        <input type="text" name="username" maxlength="100" required>
        <br><br>

        <label>Password:</label>
        <input type="password" name="password" id="password" required>
        <br><br>

        <label>Confirm Password:</label>
        <input type="password" id="confirmPassword" required>
        <br><br>

        <label>Date of Birth:</label>
        <input type="date" name="dob" required>
        <br><br>

        <input type="submit" name="register" value="Register">
    </form>

    <!-- Show the result message if there is one -->
    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <p><a href="index.php">Return to Login</a></p>

    <script>
    // Runs in the browser before the form is sent
    function validateRegister() {
        var pw = document.getElementById('password').value;
        var confirmPw = document.getElementById('confirmPassword').value;

        // Password must be at least 8 characters
        if (pw.length < 8) {
            alert('Password must be at least 8 characters long.');
            return false;   // stops the form being sent
        }

        // Both password boxes must match
        if (pw !== confirmPw) {
            alert('Passwords do not match.');
            return false;
        }

        return true;        // everything is fine, send the form
    }
    </script>

</body>
</html>