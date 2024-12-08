<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['tingkat'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all reports for discussions
$sql_reports = "SELECT reports.*, diskusi.judul_diskusi, users.email AS reporter_email 
                FROM reports
                JOIN diskusi ON reports.no_diskusi = diskusi.no_diskusi
                JOIN users ON reports.user_id = users.no_number
                ORDER BY reports.created_at DESC";
$result_reports = $conn->query($sql_reports);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <button class="back-btn" title="Go back" onclick="href='dashboard.php';">
                <i class="fas fa-arrow-left"></i>
            </button>
            <span class="nav-title">View Reports</span>
        </div>
        <div class="nav-right">
            <div class="profile">
                <img src="https://api.dicebear.com/6.x/avataaars/svg?seed=<?php echo urlencode($_SESSION['user']['email']); ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-dropdown">
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($_SESSION['user']['email']); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['user']['tingkat']); ?></p>
                    </div>
                    <div class="dropdown-items">
                        <a href="crud.php"><i class="fas fa-plus"></i>Add Discussion</a>
                        <a href="manageaccount.php"><i class="fas fa-cog"></i>Account Manage</a>
                        <a href="view_reports.php"><i class="fas fa-cog"></i>Report List</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Reports for Discussions</h1>

        <?php if ($result_reports->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Discussion Title</th>
                        <th>Reported By</th>
                        <th>Reason</th>
                        <th>Reported On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($report = $result_reports->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['judul_diskusi']); ?></td>
                            <td><?php echo htmlspecialchars($report['reporter_email']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($report['report_reason'])); ?></td>
                            <td><?php echo $report['created_at']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No reports available.</p>
        <?php endif; ?>
    </div>

    <script src="script.js"></script>
</body>
</html>

<?php
$conn->close();
?>
