<?php
// chat.php - Chat Page
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['videocall'])) {
    header('Location: videocall.php');
    exit;
}
require 'db.php';


try {

    $stmt = $pdo->query("SELECT messages.message, messages.created_at, users.username 
                         FROM messages 
                         JOIN users ON messages.user_id = users.id 
                         ORDER BY messages.created_at ASC");

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // echo json_encode($messages);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat</title>
    <link rel="stylesheet" href="chat.css">
</head>
<body>
    <h1 class="title">FlowChat</h1>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <div id="chat-box" class="chat-box">
         
    </div>
    <form id="message-form">
        <input type="text" id="message" placeholder="Type a message..." required>
        <button type="submit" id="send">Send</button>
    </form>
    <button id="video">VideoCall</button>

    <a href="logout.php">Logout</a>

    <script>
        const username = "<?php echo $_SESSION['username']; ?>";

        const ws = new WebSocket("ws://localhost:8080");

        ws.onopen = function() {
            console.log("WebSocket bağlantısı quruldu");
        };

        ws.onmessage = function(event) {
            console.log(event.data);

            const data = JSON.parse(event.data);
            addMessage(data.username, data.message, data.time);
        };

        function sendMessage() {
            event.preventDefault();
            const messageInput = document.getElementById("message");
            const message = messageInput.value;

            if (message.trim() !== "") {
                const time = new Date().toLocaleTimeString();

                const payload = JSON.stringify({
                    username: username,
                    message: message,
                    time: time
                });

                ws.send(payload); 
                messageInput.value = ''; 
                // addMessage("You", message, time)
            }
        }

        var btn = document.getElementById("send")
        var btnVideo = document.getElementById("video")
        btn.addEventListener("click", sendMessage);
        btnVideo.addEventListener("click", goToVideoCall);

        function goToVideoCall() {
            window.location.href = "videocall.php";
        }
        function addMessage(username2, message, time) {
            const chatBox = document.getElementById("chat-box");

            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message");

            const userNameElem = document.createElement("strong");
            userNameElem.textContent = username2==username ? "You" : username2;

            const messageElem = document.createElement("p");
            messageElem.textContent = message;

            const timeElem = document.createElement("em");
            timeElem.textContent = `(${time})`;

            messageDiv.appendChild(userNameElem);
            messageDiv.appendChild(messageElem);
            messageDiv.appendChild(timeElem);

            chatBox.appendChild(messageDiv);

            chatBox.scrollTop = chatBox.scrollHeight;
        }


        const messages = <?php echo json_encode($messages); ?>;

        function loadMessages() {
            messages.forEach(msg => {
                addMessage(msg.username, msg.message, msg.created_at);
            });
        }

        window.onload = function() {
            loadMessages(); 
        };

    </script>
</body>
</html>



