<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        header('Location: register.php?error=passwords_dont_match');
        exit();
    }

    // Check if username already exists
    $check_sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($result) > 0) {
        header('Location: register.php?error=username_taken');
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users (username, email, password, user_type) VALUES ('$username', '$email', '$hashed_password', '$user_type')";
    
    if (mysqli_query($conn, $sql)) {
        header('Location: index.php?success=1');
    } else {
        header('Location: register.php?error=registration_failed');
    }
    exit();
} 