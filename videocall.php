<?php
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Call</title>
    <style>
        video { width: 45%; margin: 10px; border: 1px solid #ccc; }
        #videos { display: flex; justify-content: center; }
    </style>
        <link rel="stylesheet" href="videostyle.css">

</head>
<body>
<h1>Video Call</h1>
    <div class="videos">
        <div class="video-container">
            <video id="localVideo" autoplay muted></video>
            <div class="username">
                <?php echo htmlspecialchars($_SESSION['username']); ?> <!-- Yerli istifadəçi adı -->
            </div>
        </div>
        
        <div class="video-container">
            <video id="remoteVideo" autoplay></video>
            <div class="username" id="remote-username">
                Remote User
            </div>
        </div>
    </div>
    <script>
        const username = "<?php echo $_SESSION['username']; ?>";
        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const remoteUsernameDiv = document.getElementById('remote-username');

        const ws = new WebSocket("ws://localhost:8080");
        let localStream;
        let peerConnection;
        const config = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

        ws.onmessage = (message) => {
            const data = JSON.parse(message.data);

            switch (data.type) {
                case 'offer':
                    handleOffer(data.offer, data.username);
                    break;
                case 'answer':
                    handleAnswer(data.answer);
                    break;
                case 'candidate':
                    handleCandidate(data.candidate);
                    break;
            }
        };

        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => {
                localStream = stream;
                localVideo.srcObject = stream;
            })
            .catch(error => console.error('Media Error:', error));

        function createPeerConnection() {
            peerConnection = new RTCPeerConnection(config);

            localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));

            peerConnection.onicecandidate = ({ candidate }) => {
                if (candidate) {
                    ws.send(JSON.stringify({ type: 'candidate', candidate, username: username }));
                }
            };

            peerConnection.ontrack = (event) => {
                remoteVideo.srcObject = event.streams[0];
            };
        }

        function makeCall() {
            createPeerConnection();

            peerConnection.createOffer()
                .then(offer => {
                    peerConnection.setLocalDescription(offer);
                    ws.send(JSON.stringify({ type: 'offer', offer, username: username }));
                });
        }

        function handleOffer(offer, remoteUsername) {
            if (peerConnection.signalingState !== "stable") {
                console.warn("Connection is not stable, ignoring new offer.");
                return;
            }

            createPeerConnection();

            peerConnection.setRemoteDescription(new RTCSessionDescription(offer))
                .then(() => {
                    console.log("Remote offer set successfully.");
                    return peerConnection.createAnswer();
                })
                .then(answer => {
                    peerConnection.setLocalDescription(answer);
                    ws.send(JSON.stringify({ type: 'answer', answer, username: username }));
                    console.log("Local answer sent.");
                })
                .catch(error => {
                    console.error("Error handling offer:", error);
                });

            remoteUsernameDiv.textContent = remoteUsername;
        }


        function handleAnswer(answer) {
            peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
        }

        function handleCandidate(candidate) {
            peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
        }

        setTimeout(makeCall, 1000);
    </script>
</body>
</html>

