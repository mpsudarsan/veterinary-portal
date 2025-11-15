<?php
session_start();
header('Content-type: image/png');

// Create image
$image = imagecreatetruecolor(150, 50);
$bg_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 0, 0, 0);
$noise_color = imagecolorallocate($image, 100, 120, 180);

// Fill background
imagefilledrectangle($image, 0, 0, 150, 50, $bg_color);

// Add noise
for ($i = 0; $i < 200; $i++) {
    imagesetpixel($image, rand() % 150, rand() % 50, $noise_color);
}

// Add text
imagettftext($image, 20, rand(-10, 10), 20, 35, $text_color, 'arial.ttf', $_SESSION['captcha']);

// Output image
imagepng($image);
imagedestroy($image);
?>