



<?php
// chat.php - Chat Page
session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];
    $stmt = $pdo->prepare("INSERT INTO messages (user_id, message, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $message]);
}

$messages = $pdo->query("SELECT m.message, m.created_at, u.username FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat</title>
    <style>
        .chat-box { height: 300px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; }
        .message { margin: 5px 0; }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <div class="chat-box">
        <?php foreach ($messages as $msg): ?>
            <div class="message">
                <strong><?php echo htmlspecialchars($msg['username']); ?>:</strong>
                <?php echo htmlspecialchars($msg['message']); ?>
                <em>(<?php echo $msg['created_at']; ?>)</em>
            </div>
        <?php endforeach; ?>
    </div>
    <form method="POST" action="chat.php">
        <input type="text" name="message" placeholder="Type a message..." required>
        <button type="submit">Send</button>
    </form>
    <a href="logout.php">Logout</a>
</body>
</html>

<?php
// logout.php - User Logout
session_start();
session_destroy();
header('Location: login.php');
exit;
?>
