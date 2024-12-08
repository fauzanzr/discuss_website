<?php
session_start();
include 'db_connect.php';

// Set the timezone to WIB (UTC+7)
date_default_timezone_set('Asia/Jakarta');

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['tingkat'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Function to ban account
if (isset($_POST['ban'])) {
    $no_number = $_POST['no_number'];
    $duration = $_POST['duration'];
    
    // Convert duration to date with WIB timezone
    $durationMap = [
        '1hour' => '+1 hour',
        '1day' => '+1 day',
        '1month' => '+1 month',
        'permanent' => NULL,  // Permanent ban, will set ban_until to NULL
    ];

    // Check if the ban duration is permanent
    if ($duration !== 'permanent') {
        // Calculate the ban until date with WIB timezone
        $ban_until = date("Y-m-d H:i:s", strtotime($durationMap[$duration]));
    } else {
        $ban_until = NULL; // Permanent ban
    }

    // Update user status to banned
    $sql = "UPDATE users SET is_banned = 1, ban_until = ? WHERE no_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $ban_until, $no_number);
    $stmt->execute();

    // Hide discussions by this user
    $sql = "UPDATE diskusi SET is_visible = 0 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $no_number);
    $stmt->execute();

    // Delete comments by this user
    $sql = "DELETE FROM comments WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $no_number);
    $stmt->execute();

    header("Location: manageaccount.php");
    exit;
}

// Function to unban account
if (isset($_GET['unban'])) {
    $no_number = $_GET['unban'];

    // Unban the user
    $sql = "UPDATE users SET is_banned = 0, ban_until = NULL WHERE no_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $no_number);
    $stmt->execute();

    // Show discussions by this user
    $sql = "UPDATE diskusi SET is_visible = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $no_number);
    $stmt->execute();

    header("Location: manageaccount.php");
    exit;
}

// Fetch all users
$sql = "SELECT no_number, tingkat, email, is_banned, ban_until FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts</title>
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
            <span class="nav-title">Manage Accounts</span>
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
                        <a href="manageaccount.php"><i class="fas fa-user"></i>Account Manage</a>
                        <a href="view_reports.php"><i class="fas fa-file"></i>Report List</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2>Manage Accounts</h2>
        <table class="account-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tingkat</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['no_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['tingkat']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php
                                if ($row['is_banned']) {
                                    echo "Banned (until " . htmlspecialchars($row['ban_until']) . ")";
                                } else {
                                    echo "Active";
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (!$row['is_banned']): ?>
                                    <form method="post" action="">
                                        <input type="hidden" name="no_number" value="<?php echo $row['no_number']; ?>">
                                        <select name="duration" required>
                                            <option value="1hour">1 Hour</option>
                                            <option value="1day">1 Day</option>
                                            <option value="1month">1 Month</option>
                                            <option value="permanent">Permanent</option>
                                        </select>
                                        <button type="submit" name="ban">Ban</button>
                                    </form>
                                <?php else: ?>
                                    <a href="?unban=<?php echo $row['no_number']; ?>" class="unban-btn">Unban</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No accounts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="script.js"></script>
</body>
</html>
