<?php
include 'db_connect.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi CAPTCHA
    if ($_POST['captcha'] !== $_SESSION['captcha_text']) {
        echo "CAPTCHA tidak valid. Silakan coba lagi.";
    } else {
        // Validasi email agar hanya email dengan domain @gmail.com yang bisa didaftarkan
        $email = $_POST['email'];
        
        // Check if email ends with "@gmail.com"
        if (substr($email, -10) !== '@gmail.com') {
            echo "Hanya email dengan domain @gmail.com yang diperbolehkan.";
        } else {
            // Jika CAPTCHA valid dan email valid, lanjutkan dengan registrasi
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $tingkat = $_POST['tingkat'];

            // Insert new user into the database
            $sql = "INSERT INTO users (tingkat, email, password) VALUES ('$tingkat', '$email', '$password')";
            if ($conn->query($sql) === TRUE) {
                echo "Registrasi berhasil. <a href='login.php'>Login di sini</a>";
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form method="post" action="">
        <label>Tingkat:</label>
        <select name="tingkat" required>
            <option value="user">User</option>
        </select><br><br>
        <label>Email:</label>
        <input type="email" name="email" required><br><br>
        <label>Password:</label>
        <input type="password" name="password" required><br><br>
        
        <!-- Menampilkan CAPTCHA -->
        <label>Masukkan CAPTCHA:</label><br>
        <img src="captcha.php" alt="CAPTCHA"><br><br>
        <input type="text" name="captcha" required><br><br>

        <button type="submit">Register</button>
    </form>
    <br>
    <button onclick="window.location.href='login.php'">Kembali</button>
</body>
</html>
