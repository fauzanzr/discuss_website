<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

include 'db_connect.php';

// Fetch visible discussions only
$sql = "
    SELECT diskusi.* 
    FROM diskusi 
    LEFT JOIN users ON diskusi.user_id = users.no_number 
    WHERE diskusi.is_visible = 1 AND (users.is_banned = 0 OR users.is_banned IS NULL)
    ORDER BY diskusi.no_diskusi DESC
";

$result = $conn->query($sql);

// Fetch notifications for the logged-in user
$sql_notifications = "
    SELECT message, created_at 
    FROM notifications 
    ORDER BY created_at DESC
";

$result_notifications = $conn->query($sql_notifications);

if ($result_notifications) {
    $notifications = $result_notifications->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Error: " . $conn->error;
    $notifications = [];
}

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
    <nav class="navbar">
        <div class="nav-left">
            <button class="back-btn" title="Go back">
            </button>
            <span class="nav-title">Discussion</span>
        </div>
        <div class="nav-right">
            <div class="notification">
                <i class="fas fa-bell"></i>
                <?php if (count($notifications) > 0): ?>
                    <span class="notification-badge"><?php echo count($notifications); ?></span>
                <?php endif; ?>
                <div class="notification-dropdown">
                    <ul>
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <li data-id="<?php echo $notification['id']; ?>"> <!-- Tambahkan data-id -->
                                    <span><?php echo htmlspecialchars($notification['message']); ?></span>
                                    <small><?php echo date('d M Y, H:i', strtotime($notification['created_at'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No new notifications</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="profile">
                <img src="https://api.dicebear.com/6.x/avataaars/svg?seed=<?php echo urlencode($user['email']); ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-dropdown">
                    <div class="profile-info">
                        <img src="https://api.dicebear.com/6.x/avataaars/svg?seed=<?php echo urlencode($user['email']); ?>" alt="Profile Picture">
                        <div>
                            <h4><?php echo htmlspecialchars($user['email']); ?></h4>
                            <p><?php echo htmlspecialchars($user['tingkat']); ?></p>
                        </div>
                    </div>
                    <div class="dropdown-items">
                        <a href="crud.php"><i class="fas fa-plus"></i>Add Discussion</a>
                        <!-- Menyembunyikan opsi jika bukan admin -->
                        <?php if ($user['tingkat'] == 'admin'): ?>
                            <a href="manageaccount.php"><i class="fas fa-user"></i>Account Manage</a>
                            <a href="view_reports.php"><i class="fas fa-file"></i>Report List</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>


    <div class="container">
    <div class="discussion-list">
        <?php
        if ($result->num_rows > 0) {
            echo "<h3>Daftar Diskusi:</h3>";
            while ($row = $result->fetch_assoc()) {
                $isi_diskusi = implode(' ', array_slice(explode(' ', $row['isi_diskusi']), 0, 50)) . '...';
                echo "<div class='discussion-post'>";
                echo "<div class='post-header'>";
                echo "<div class='post-info'>";
                echo "<h3 class='discussion-title'><a href='view_discussion.php?no_diskusi=" . $row['no_diskusi'] . "'>" . htmlspecialchars($row['judul_diskusi']) . "</a></h3>";
                echo "<p class='post-metadata'><em>Created By: " . htmlspecialchars($row['pembuat']) . "</em></p>";
                echo "</div>";
                echo "<div class='post-actions'>";
                echo "<div class='votes'>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                echo "<div class='post-content'>";
                echo "<p class='post-text'>" . htmlspecialchars($isi_diskusi) . "</p>";
                echo "</div>";
                echo "<div class='post-footer'></div>";
                echo "<hr class='post-divider'>";
                echo "</div>";
            }
        } else {
            echo "<p>Belum ada diskusi.</p>";
        }
        ?>
    </div>
</div>



    <style>
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 40px;
            right: 0;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
            width: 300px;
            padding: 10px;
            border-radius: 5px;
        }

        .notification-dropdown ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .notification-dropdown ul li {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        .notification-dropdown ul li:last-child {
            border-bottom: none;
        }

        .notification-dropdown.show {
            display: block;
        }
    </style>

    <script>
        document.querySelector('.notification').addEventListener('click', function(event) {
            event.stopPropagation();
            const dropdown = document.querySelector('.notification-dropdown');
            dropdown.classList.toggle('show');
        });



        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.notification-dropdown');
            if (!event.target.closest('.notification')) {
                dropdown.classList.remove('show');
            }
        });

        document.querySelector('.profile-pic').addEventListener('click', function() {
            const dropdown = document.querySelector('.profile-dropdown');
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.profile-dropdown');
            const profilePic = document.querySelector('.profile-pic');

            if (!profilePic.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        document.querySelectorAll('.notification-dropdown li').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id'); // Ambil ID notifikasi
                if (notificationId) {
                    fetch(`mark_notification_read.php?id=${notificationId}`)
                        .then(response => response.text())
                        .then(data => {
                            console.log(data); // Debug: Tampilkan respons di konsol
                            this.remove(); // Hapus notifikasi dari dropdown
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
    </script>
</body>

</html>