<?php
session_start();
include("db.inc.php");

$message = "";

// Only logged-in agents can see this page
if (!isset($_SESSION['agentID'])) {
    header("Location: index.php");
    exit;
}

// RESOLVE TICKET: runs when an agent resolves a ticket
if (isset($_POST['resolve'])) {
    $ticketID = (int) $_POST['ticketID'];
    $resolution = trim($_POST['resolution']);
    $agentID = $_SESSION['agentID'];

    // The agent must write how it was resolved
    if ($resolution !== "") {
        // Set status to Resolved and save the resolution + agent ID
        $sql = "UPDATE Ticket
                SET Status = 'Resolved',
                    Resolution = ?,
                    SID = ?
                WHERE TID = ?
                AND Status = 'Open'";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param(
            $stmt, "sii", $resolution, $agentID, $ticketID
        );
        mysqli_stmt_execute($stmt);

        // Check if a row was actually changed
        $message = mysqli_stmt_affected_rows($stmt) > 0
            ? "Ticket resolved successfully."
            : "Ticket was not found or is no longer open.";
    } else {
        $message = "Please enter the resolution details.";
    }
}

// ELEVATE TICKET: pass a ticket to a higher-level agent
if (isset($_POST['elevate'])) {
    $ticketID = (int) $_POST['ticketID'];
    $newAgentID = (int) $_POST['newAgentID'];
    $currentLevel = (int) $_SESSION['supportLevel'];

    // Check the chosen agent really has a higher level
    $sql = "SELECT SID
            FROM Support
            WHERE SID = ?
            AND SLevel > ?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param(
        $stmt, "ii", $newAgentID, $currentLevel
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_fetch_assoc($result)) {
        // Assign the ticket to the new agent (it stays Open)
        $sql = "UPDATE Ticket
                SET SID = ?
                WHERE TID = ?
                AND Status = 'Open'";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param(
            $stmt, "ii", $newAgentID, $ticketID
        );
        mysqli_stmt_execute($stmt);

        $message = mysqli_stmt_affected_rows($stmt) > 0
            ? "Ticket elevated successfully."
            : "Ticket was not found or is no longer open.";
    } else {
        $message = "Choose an agent with a higher support level.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Agent - Discord Support</title>
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body class="agent">

<h1>Discord Support</h1>
<h2>Support Agent Portal</h2>

<!-- Greet the agent and show their support level -->
<p>Welcome,
    <?php echo htmlspecialchars($_SESSION['agentName']); ?>!
</p>

<p>Support level:
    <?php echo (int) $_SESSION['supportLevel']; ?>
</p>

<h3>Open Tickets</h3>

<!-- Search form: searches subject and category -->
<form method="GET" action="agent.php">
    <label>Search tickets:</label>
    <input type="text" name="search"
           placeholder="Search subject or category">
    <input type="submit" value="Search">
    <a href="agent.php">Show All</a>
</form>

<?php
// Get the search text (empty if nothing was searched)
$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : "";

if ($search !== "") {
    // Search: open tickets matching the subject or category
    $term = "%" . $search . "%";

    $sql = "SELECT TID, UID, Subject, Category,
                   Description, DateSubm
            FROM Ticket
            WHERE Status = 'Open'
            AND (Subject LIKE ? OR Category LIKE ?)
            ORDER BY DateSubm ASC";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $term, $term);
} else {
    // No search: show all open tickets, oldest first
    $sql = "SELECT TID, UID, Subject, Category,
                   Description, DateSubm
            FROM Ticket
            WHERE Status = 'Open'
            ORDER BY DateSubm ASC";

    $stmt = mysqli_prepare($con, $sql);
}

mysqli_stmt_execute($stmt);
$tickets = mysqli_stmt_get_result($stmt);

// Message if there are no open tickets
if (mysqli_num_rows($tickets) == 0) {
    echo "<p>No open tickets found.</p>";
}

// Show each open ticket one by one
while ($ticket = mysqli_fetch_assoc($tickets)):
?>

    <hr>

    <h3>
        Ticket #<?php echo (int) $ticket['TID']; ?>:
        <?php echo htmlspecialchars($ticket['Subject']); ?>
    </h3>

    <p>Category:
        <?php echo htmlspecialchars($ticket['Category']); ?>
    </p>

    <p>User ID: <?php echo (int) $ticket['UID']; ?></p>

    <p>Submitted:
        <?php echo htmlspecialchars($ticket['DateSubm']); ?>
    </p>

    <p>Description:<br>
        <?php
        echo nl2br(htmlspecialchars($ticket['Description']));
        ?>
    </p>

    <!-- Resolve form -->
    <form method="POST" action="agent.php">
        <input type="hidden" name="ticketID"
               value="<?php echo (int) $ticket['TID']; ?>">

        <label>Resolution details:</label><br>
        <textarea name="resolution" rows="3" cols="40"
                  placeholder="Explain how the issue was resolved"></textarea>
        <br>
        <input type="submit" name="resolve"
               value="Resolve Ticket">
    </form>

    <!-- Elevate form -->
    <form method="POST" action="agent.php">
        <input type="hidden" name="ticketID"
               value="<?php echo (int) $ticket['TID']; ?>">

        <label>Elevate to a higher-level agent:</label>

        <select name="newAgentID" required>
            <option value="">Select Agent</option>

            <?php
            // Get agents with a higher level than this agent
            $currentLevel = (int) $_SESSION['supportLevel'];

            $sqlAgents = "SELECT SID, Username, FullName, SLevel
                          FROM Support
                          WHERE SLevel > ?
                          ORDER BY SLevel ASC";

            $agentStmt = mysqli_prepare($con, $sqlAgents);
            mysqli_stmt_bind_param(
                $agentStmt, "i", $currentLevel
            );
            mysqli_stmt_execute($agentStmt);
            $higherAgents = mysqli_stmt_get_result($agentStmt);

            // Add each higher-level agent to the dropdown
            while ($higher = mysqli_fetch_assoc($higherAgents)) {
                echo "<option value='" . (int) $higher['SID'] . "'>";
                echo htmlspecialchars($higher['FullName']);
                echo " (Level " . (int) $higher['SLevel'] . ")";
                echo "</option>";
            }
            ?>
        </select>

        <input type="submit" name="elevate"
               value="Elevate Ticket">
    </form>

<?php endwhile; ?>

<br>
<a href="index.php?logout=1">Logout</a>

<!-- Show a message after an action -->
<?php
if ($message !== "") {
    echo "<p>" . htmlspecialchars($message) . "</p>";
}
?>

<br>
<a href="index.php">Return to Main Login</a>

</body>
</html>