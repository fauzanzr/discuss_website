<?php
session_start();

// Fungsi untuk menghasilkan string acak
function generateCaptchaText($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Karakter yang digunakan
    $captchaText = '';
    for ($i = 0; $i < $length; $i++) {
        $captchaText .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captchaText;
}

// Menyimpan teks CAPTCHA di session
$captchaText = generateCaptchaText();
$_SESSION['captcha_text'] = $captchaText;

// Membuat gambar CAPTCHA
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Menentukan warna
$backgroundColor = imagecolorallocate($image, 255, 255, 255); // Putih
$textColor = imagecolorallocate($image, 0, 0, 0); // Hitam

// Mengisi gambar dengan warna latar belakang
imagefill($image, 0, 0, $backgroundColor);

// Menambahkan teks CAPTCHA ke gambar
imagestring($image, 5, 30, 10, $captchaText, $textColor);

// Menampilkan gambar sebagai gambar PNG
header('Content-Type: image/png');
imagepng($image);

// Membersihkan gambar dari memori
imagedestroy($image);
?>
