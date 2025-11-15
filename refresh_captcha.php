<?php
session_start();
require_once 'C:\xampp\htdocs\veterinary_portal\includes\functions.php';

$captcha_type = $_GET['type'] ?? 'default'; // 'doctor' or undefined
$session_key = ($captcha_type === 'doctor') ? 'doctor_captcha' : 'captcha';

$_SESSION['captcha'] = generateRandomString(6);

header('Content-Type: application/json');
echo json_encode(['captcha' => $_SESSION['captcha']]);