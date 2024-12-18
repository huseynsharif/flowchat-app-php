<?php
session_start();
// Username-i session-dan alırıq
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Call</title>
    <link rel="stylesheet" href="./videostyle.css">
</head>
<body>
    <h1>Video Call</h1>
    
    <!-- Öz Video -->
    <div class="video-container" id="localContainer">
        <video id="localVideo" autoplay playsinline></video>
        <div class="username"><?php echo htmlspecialchars($username); ?></div>
    </div>

    <!-- Remote Video-ya əlavə edəcəyik -->
    <div id="remoteVideos"></div>
    <script src="./webrtc.js"></script>
</body>
</html>
