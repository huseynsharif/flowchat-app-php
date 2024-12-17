<?php
// signup.php - User Registration
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: chat.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $password])) {
        header('Location: login.php');
        exit;
    }
}
?>

<!-- signup.html -->
<form method="POST" action="signup.php">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Sign Up</button>
</form>