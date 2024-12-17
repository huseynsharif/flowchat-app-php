<?php
// login.php - User Login
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: chat.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db.php';
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: chat.php');
        exit;
    } else {
        echo "Invalid credentials";
    }
}

?>
<!-- login.html -->
<form method="POST" action="login.php">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
</form>
