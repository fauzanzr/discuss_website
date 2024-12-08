<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']);

    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $notification_id);

    if ($stmt->execute()) {
        echo "Notification marked as read.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
