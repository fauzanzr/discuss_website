<?php
include 'db_connect.php';

// Mulai sesi jika belum dimulai
session_start();

// Cek jika ada sesi yang aktif dan pengguna sudah login
if (isset($_SESSION['user'])) {
    $redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';
    header('Location: ' . $redirect_url);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Cek email di database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Periksa apakah akun dibanned
        if ($row['is_banned']) {
            $current_time = date("Y-m-d H:i:s");
            if ($row['ban_until'] && $current_time < $row['ban_until']) {
                echo "Akun Anda dibanned hingga " . htmlspecialchars($row['ban_until']) . ". Silakan coba lagi nanti.";
                exit;
            } else {
                // Reset status ban jika waktu ban telah berakhir
                $sql_update = "UPDATE users SET is_banned = 0, ban_until = NULL WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("s", $email);
                $stmt_update->execute();
            }
        }

        // Verifikasi password
        if (password_verify($password, $row['password'])) {
            // Simpan data pengguna ke sesi
            $_SESSION['user'] = $row;

            // Redirect ke halaman yang dituju atau dashboard jika tidak ada parameter redirect
            $redirect_url = isset($_POST['redirect']) && !empty($_POST['redirect']) ? $_POST['redirect'] : 'dashboard.php';
            header('Location: ' . $redirect_url);
            exit;
        } else {
            echo "Password salah.";
        }
    } else {
        echo "Email tidak ditemukan.";
    }
}
?>



<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
</head>

<body>
    <h2>Login</h2>
    <form method="post" action="?<?php echo htmlspecialchars($_SERVER['QUERY_STRING']); ?>">
        <label>Email:</label>
        <input type="email" name="email" required><br><br>
        <label>Password:</label>
        <input type="password" name="password" required><br><br>

        <!-- Hidden input untuk redirect URL -->
        <input type="hidden" name="redirect" value="<?php echo isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : ''; ?>">

        <button type="submit">Login</button>
    </form>
    <br>
    <button onclick="window.location.href='register.php'">Register</button>
</body>

</html>
