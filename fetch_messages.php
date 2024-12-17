
<?php
// fetch_messages.php - Fetch messages for real-time updates
session_start();
require 'db.php';

$messages = $pdo->query("SELECT m.message, m.created_at, u.username FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC LIMIT 50")->fetchAll();

foreach ($messages as $msg) {
    echo '<div class="message">';
    echo '<strong>' . htmlspecialchars($msg['username']) . ':</strong> ';
    echo htmlspecialchars($msg['message']);
    echo ' <em>(' . $msg['created_at'] . ')</em>';
    echo '</div>';
}
?>