<?php
session_start();
include("db.inc.php");

// Only logged-in users can see this page
if (!isset($_SESSION['userID'])) {
    header("Location: index.php");
    exit;
}

$userID = (int) $_SESSION['userID'];
$message = "";

// CREATE TICKET: runs when the new ticket form is submitted
if (isset($_POST['submitTicket'])) {
    $category = $_POST['category'];
    $subject = trim($_POST['subject']);
    $description = trim($_POST['description']);

    // Subject and description must not be empty
    if ($subject !== "" && $description !== "") {
        // New tickets always start as 'Open'
        $sql = "INSERT INTO Ticket
                (UID, Subject, Description, Category, Status, DateSubm, Resolution)
                VALUES (?, ?, ?, ?, 'Open', NOW(), '')";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param(
            $stmt, "isss", $userID, $subject, $description, $category
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Ticket submitted successfully!";
        } else {
            $message = "There was a problem submitting your ticket.";
        }
    } else {
        $message = "Please complete the subject and description.";
    }
}

// CLOSE TICKET: only the user's own Resolved tickets can be closed
if (isset($_POST['closeTicket'])) {
    $ticketID = (int) $_POST['ticketID'];

    $sql = "UPDATE Ticket
            SET Status = 'Closed'
            WHERE TID = ? AND UID = ? AND Status = 'Resolved'";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $ticketID, $userID);
    mysqli_stmt_execute($stmt);

    // Check if a row was actually changed
    $message = mysqli_stmt_affected_rows($stmt) > 0
        ? "Ticket closed successfully."
        : "Only your resolved tickets can be closed.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Menu - Discord Support</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body class="user">

<h1>Discord Support</h1>

<h2>User Menu</h2>

<!-- Greet the user by name -->
<p>
    Welcome,
    <?php echo htmlspecialchars($_SESSION['userName'] ?? 'User'); ?>!
</p>

<!-- Show a message after an action -->
<?php
if ($message !== "") {
    echo "<p>" . htmlspecialchars($message) . "</p>";
}
?>

<h3>Create a New Ticket</h3>

<!-- New ticket form -->
<form method="POST" action="user.php">
    <label>Category:</label>
    <select name="category" required>
        <option value="">Select Category</option>
        <option value="Account">Account Problem</option>
        <option value="Technical">Technical Problem</option>
        <option value="Payment">Payment / Purchase</option>
        <option value="Game">Game Problem</option>
        <option value="Security">Security Problem</option>
        <option value="Other">Other</option>
    </select>

    <br><br>

    <label>Subject:</label>
    <input type="text" name="subject" maxlength="250" required>

    <br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="6" cols="50"
              maxlength="500" required></textarea>

    <br><br>

    <input type="submit" name="submitTicket" value="Submit Ticket">
</form>

<hr>

<h3>My Tickets</h3>

<?php
// Get all tickets that belong to this user, newest first
$sql = "SELECT TID, Subject, Description, Category,
               Status, DateSubm, Resolution
        FROM Ticket
        WHERE UID = ?
        ORDER BY DateSubm DESC";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);
$tickets = mysqli_stmt_get_result($stmt);

// Message if the user has no tickets yet
if (mysqli_num_rows($tickets) == 0) {
    echo "<p>You have not submitted any tickets yet.</p>";
}

// Show each ticket one by one
while ($ticket = mysqli_fetch_assoc($tickets)):
?>

    <hr>

    <h4>
        Ticket #<?php echo (int) $ticket['TID']; ?>:
        <?php echo htmlspecialchars($ticket['Subject']); ?>
    </h4>

    <p>Category:
        <?php echo htmlspecialchars($ticket['Category']); ?>
    </p>

    <p>Status:
        <?php echo htmlspecialchars($ticket['Status']); ?>
    </p>

    <p>Date submitted:
        <?php echo htmlspecialchars($ticket['DateSubm']); ?>
    </p>

    <p>Description:<br>
        <?php echo nl2br(htmlspecialchars($ticket['Description'])); ?>
    </p>

    <!-- Only show the resolution once an agent has written one -->
    <?php if ($ticket['Resolution'] !== ""): ?>
        <p>Resolution:<br>
            <?php echo nl2br(htmlspecialchars($ticket['Resolution'])); ?>
        </p>
    <?php endif; ?>

    <!-- Only Resolved tickets get a Close button (with a popup to confirm) -->
    <?php if ($ticket['Status'] === 'Resolved'): ?>
        <form method="POST" action="user.php"
              onsubmit="return confirm('Close this ticket? You will not be able to reopen it.');">
            <input type="hidden" name="ticketID"
                   value="<?php echo (int) $ticket['TID']; ?>">
            <input type="submit" name="closeTicket"
                   value="Close Ticket">
        </form>
    <?php endif; ?>

<?php endwhile; ?>

<br>
<a href="index.php?logout=1">Logout</a>

</body>
</html>