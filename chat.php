<?php
// chat.php - Chat Page
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat</title>
    <link rel="stylesheet" href="chat.css">

</head>
<body>
    <div id="root"></div>

    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    
    <form id="message-form">
        <input type="text" id="message" placeholder="Type a message..." required>
        <button type="submit" id="send">Send</button>
    </form>
    <a href="logout.php">Logout</a>

    <script>
        const ws = new WebSocket("ws://localhost:8080");

        ws.onopen = function() {
            console.log("WebSocket bağlantısı quruldu");
        };

        ws.onmessage = function(event) {
            // const messages = document.getElementById("messages");
            // const li = document.createElement("li");
            // li.textContent = "Gələn mesaj: " + event.data;
            // messages.appendChild(li);

            console.log(event.data);
            
        };

        function sendMessage() {
            const input = document.getElementById("message");
            ws.send(input.value);
            input.value = '';
        }

        var btn = document.getElementById("send")
        btn.addEventListener("click", sendMessage);
    </script>
</body>
</html>



