<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Check if no_diskusi parameter is provided
if (!isset($_GET['no_diskusi']) || empty($_GET['no_diskusi'])) {
    echo "Diskusi tidak ditemukan.";
    exit;
}

$no_diskusi = $_GET['no_diskusi'];

// Fetch the discussion details
$sql = "SELECT * FROM diskusi WHERE no_diskusi = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $no_diskusi);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Diskusi tidak ditemukan.";
    exit;
}

$diskusi = $result->fetch_assoc();

// Fetch user details (to check if the user is banned)
$user_id = $_SESSION['user']['no_number'];
$sql_user = "SELECT is_banned FROM users WHERE no_number = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    // Check if user is banned
    if ($user['is_banned'] == 1) {
        // If user is banned, delete their comments
        $sql_delete_comments = "DELETE FROM comments WHERE user_id = ?";
        $stmt_delete_comments = $conn->prepare($sql_delete_comments);
        $stmt_delete_comments->bind_param("i", $user_id);
        $stmt_delete_comments->execute();
        $stmt_delete_comments->close();

        echo "Your account is banned, and your comments have been removed.";
        exit;  // Prevent further script execution
    }
} else {
    echo "User not found.";
    exit;
}

// Fetch comments for the discussion
$sql_comments = "SELECT comments.*, users.email AS commenter_email FROM comments
                 JOIN users ON comments.user_id = users.no_number
                 WHERE comments.no_diskusi = ? ORDER BY comments.created_at ASC";
$stmt_comments = $conn->prepare($sql_comments);
$stmt_comments->bind_param("i", $no_diskusi);
$stmt_comments->execute();
$result_comments = $stmt_comments->get_result();

// Handle comment submission
if (isset($_POST['submit_comment'])) {
    $comment_text = $_POST['comment_text'];
    $user_id = $_SESSION['user']['no_number'];

    // Insert comment into the database
    $sql_insert_comment = "INSERT INTO comments (no_diskusi, user_id, comment_text) VALUES (?, ?, ?)";
    $stmt_insert_comment = $conn->prepare($sql_insert_comment);
    $stmt_insert_comment->bind_param("iis", $no_diskusi, $user_id, $comment_text);

    if ($stmt_insert_comment->execute()) {
        header("Location: view_discussion.php?no_diskusi=" . $no_diskusi);
        exit;
    } else {
        echo "Gagal menambahkan komentar: " . $stmt_insert_comment->error;
    }

    $stmt_insert_comment->close();
}

// Handle report submission
if (isset($_POST['report_discussion'])) {
    $report_reason = $_POST['report_reason'];
    $user_id = $_SESSION['user']['no_number'];

    // Insert report into the database
    $sql_insert_report = "INSERT INTO reports (no_diskusi, user_id, report_reason) VALUES (?, ?, ?)";
    $stmt_insert_report = $conn->prepare($sql_insert_report);
    $stmt_insert_report->bind_param("iis", $no_diskusi, $user_id, $report_reason);

    if ($stmt_insert_report->execute()) {
        echo "Diskusi berhasil dilaporkan.";
    } else {
        echo "Gagal melaporkan diskusi: " . $stmt_insert_report->error;
    }

    $stmt_insert_report->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Detail</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="nav-left">
            <button class="back-btn" title="Go back" onclick="window.location.href='dashboard.php';">
                <i class="fas fa-arrow-left"></i>
            </button>
            <span class="nav-title">Discussion Page</span>
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
                        <?php if ($_SESSION['user']['tingkat'] === 'admin'): ?>
                            <a href="manageaccount.php"><i class="fas fa-cog"></i>Account Manage</a>
                            <a href="view_reports.php"><i class="fas fa-file"></i>Report List</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>


    <div class="container">
        <!-- Share Link Button -->
        <div class="share-link">
            <button id="shareBtn" title="Share this discussion">
                <i class="fas fa-share"></i>
            </button>
        </div>

        <div class="discussion-detail">
            <h2 class="discussion-title"><?php echo htmlspecialchars($diskusi['judul_diskusi']); ?></h2>
            <p class="post-metadata"><em>Created by: <?php echo htmlspecialchars($diskusi['pembuat']); ?></em></p>
            <hr>
            <div class="post-content">
                <p><?php echo nl2br(htmlspecialchars($diskusi['isi_diskusi'])); ?></p>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="comments-section">
            <h3>Comments:</h3>

            <?php if ($result_comments->num_rows > 0): ?>
                <ul>
                    <?php while ($comment = $result_comments->fetch_assoc()): ?>
                        <li class="comment-item">
                            <div class="comment-header">
                                <div class="comment-meta">
                                    <strong><?php echo htmlspecialchars($comment['commenter_email']); ?></strong>
                                    <em>Posted on: <?php echo $comment['created_at']; ?></em>
                                </div>
                            </div>
                            <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No comments yet. Be the first to comment!</p>
            <?php endif; ?>

            <!-- Comment Form -->
            <button class="new-comment-btn">
                <i class="fas fa-plus"></i> New Comment
            </button>

            <div class="new-comment-form hidden">
                <form method="post" action="">
                    <textarea name="comment_text" placeholder="Add a comment..." required></textarea><br><br>
                    <button type="submit" name="submit_comment">Post Comment</button>
                    <button type="button" class="cancel-comment">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Report Section -->
        <div class="report-section">
            <h3>Report this Discussion</h3>
            <form method="post" action="">
                <textarea name="report_reason" placeholder="Why are you reporting this discussion?" required></textarea><br><br>
                <button type="submit" name="report_discussion">Report</button>
            </form>
        </div>

    </div>

    <script src="script.js"></script>

    
</body>

</html>
<script>

document.getElementById('shareBtn').addEventListener('click', function () {
    // Get current page URL
    const pageUrl = window.location.href;

    // Copy the URL to clipboard
    navigator.clipboard.writeText(pageUrl).then(() => {
        // Show notification
        const notification = document.getElementById('shareNotification');
        notification.classList.add('visible');

        // Hide notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('visible');
        }, 3000);
    }).catch(err => {
        console.error('Failed to copy link: ', err);
    });
});

</script>

<?php
$stmt_comments->close();
$stmt->close();
$conn->close();
?>