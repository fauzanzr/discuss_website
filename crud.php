<?php
session_start();
include 'db_connect.php';

// Check if user is logged in (by checking if 'user' session variable exists)
if (!isset($_SESSION['user'])) {
    echo "You need to be logged in to create a discussion.";
    exit; // Prevent further execution if not logged in
}

// Fetch user_id based on the logged-in user's email
$email = $_SESSION['user']['email']; // Get email from session
$sql = "SELECT no_number, tingkat FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Get user_id and role (tingkat)
$user_id = $row['no_number'];
$user_role = $row['tingkat']; // 'tingkat' could be 'admin' or 'user'
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Page</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Navbar -->
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
        <h2>Add a New Discussion</h2>

        <!-- Add Discussion Form -->
        <form method="post" action="">
            <label for="judul_diskusi">Discussion Title:</label>
            <input type="text" id="judul_diskusi" name="judul_diskusi" required><br><br>
            <label for="isi_diskusi">Discussion Content:</label>
            <textarea id="isi_diskusi" name="isi_diskusi" required></textarea><br><br>
            <label for="pembuat">Creator (Your Name):</label>
            <input type="text" id="pembuat" name="pembuat" required><br><br>
            <button type="submit" name="create">Add Discussion</button>
        </form>
        <hr>

        <?php
        // Add Discussion
        if (isset($_POST['create'])) {
            $judul_diskusi = $_POST['judul_diskusi'];
            $isi_diskusi = $_POST['isi_diskusi'];
            $pembuat = $_POST['pembuat'];

            // Insert discussion with dynamic user_id
            $sql = "INSERT INTO diskusi (judul_diskusi, isi_diskusi, pembuat, user_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $judul_diskusi, $isi_diskusi, $pembuat, $user_id);

            if ($stmt->execute()) {
                // Add notification for the creator
                $notification_message = "Diskusi baru berjudul '$judul_diskusi' telah berhasil dibuat.";
                $sql_notification = "INSERT INTO notifications (user_id, message, created_at) VALUES (?, ?, NOW())";
                $stmt_notification = $conn->prepare($sql_notification);
                $stmt_notification->bind_param("is", $user_id, $notification_message);
                $stmt_notification->execute();
                $stmt_notification->close();

                echo "Discussion added successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }

        // Read Discussions: Filter based on user role
        if ($user_role === 'admin') {
            $sql = "SELECT * FROM diskusi";
        } else {
            $sql = "SELECT * FROM diskusi WHERE user_id = ?";
        }

        $stmt = $conn->prepare($sql);
        if ($user_role !== 'admin') {
            $stmt->bind_param("i", $user_id); // Bind user_id for non-admin
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<h3>Discussion List:</h3><ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>";
                echo "<strong>" . htmlspecialchars($row['judul_diskusi']) . "</strong><br>";
                echo htmlspecialchars($row['isi_diskusi']) . "<br>";
                echo "<em>Created by: " . htmlspecialchars($row['pembuat']) . "</em><br>"; // Show the creator's name
                echo "<a href='?edit=" . $row['no_diskusi'] . "'>Edit</a> | ";
                echo "<a href='?delete=" . $row['no_diskusi'] . "'>Delete</a>";
                echo "</li><br>";
            }
            echo "</ul>";
        } else {
            echo "No discussions yet.";
        }

        // Delete Discussion
        if (isset($_GET['delete'])) {
            $no_diskusi = $_GET['delete'];
        
            // Delete related comments first
            $sql_comments = "DELETE FROM comments WHERE no_diskusi = ?";
            $stmt_comments = $conn->prepare($sql_comments);
            $stmt_comments->bind_param("i", $no_diskusi);
            $stmt_comments->execute();
            $stmt_comments->close();
        
            // Now delete the discussion
            $sql = "DELETE FROM diskusi WHERE no_diskusi = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $no_diskusi);
        
            if ($stmt->execute()) {
                // Redirect without output
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        

        // Edit Discussion
        if (isset($_GET['edit'])) {
            $no_diskusi = $_GET['edit'];

            $sql = "SELECT * FROM diskusi WHERE no_diskusi = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $no_diskusi);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
        ?>
                <hr>
                <h3>Edit Discussion</h3>
                <form method="post" action="">
                    <label for="judul_diskusi">Discussion Title:</label>
                    <input type="text" id="judul_diskusi" name="judul_diskusi" value="<?php echo htmlspecialchars($row['judul_diskusi']); ?>" required><br><br>
                    <label for="isi_diskusi">Discussion Content:</label>
                    <textarea id="isi_diskusi" name="isi_diskusi" required><?php echo htmlspecialchars($row['isi_diskusi']); ?></textarea><br><br>
                    <label for="pembuat">Creator:</label>
                    <input type="text" id="pembuat" name="pembuat" value="<?php echo htmlspecialchars($row['pembuat']); ?>" required><br><br>
                    <button type="submit" name="update">Update Discussion</button>
                    <input type="hidden" name="no_diskusi" value="<?php echo $row['no_diskusi']; ?>">
                </form>
        <?php
            }
            $stmt->close();
        }

        // Update Discussion
        if (isset($_POST['update'])) {
            $no_diskusi = $_POST['no_diskusi'];
            $judul_diskusi = $_POST['judul_diskusi'];
            $isi_diskusi = $_POST['isi_diskusi'];
            $pembuat = $_POST['pembuat']; // Get the manually entered creator name

            $sql = "UPDATE diskusi SET judul_diskusi = ?, isi_diskusi = ?, pembuat = ? WHERE no_diskusi = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $judul_diskusi, $isi_diskusi, $pembuat, $no_diskusi);

            if ($stmt->execute()) {
                // Redirect without output
                header("Location: " . $_SERVER['PHP_SELF']);
                exit; // Exit to prevent further script execution after header
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }

        $conn->close();
        ?>
    </div>

    <script>
        // Toggle the profile dropdown when the profile picture is clicked
        document.querySelector('.profile-pic').addEventListener('click', function() {
            const dropdown = document.querySelector('.profile-dropdown');
            dropdown.classList.toggle('show'); // Toggle 'show' class to show/hide the dropdown
        });

        // Close the dropdown if the user clicks anywhere outside the profile section
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.profile-dropdown');
            const profilePic = document.querySelector('.profile-pic');

            // Close the dropdown if the click is outside the profile picture and dropdown
            if (!profilePic.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>

</body>

</html>
