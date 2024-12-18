<?php
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Group Video Call</title>
    <style>
        .videos { display: flex; flex-wrap: wrap; gap: 10px; }
        .video-container { text-align: center; }
        video { width: 300px; height: 200px; border: 1px solid #ccc; }
        .username { font-weight: bold; margin-top: 5px; }
    </style>
</head>
<body>
    <h1>Group Video Call</h1>
    <div class="videos" id="videos"></div>

    <script>
        const ws = new WebSocket("ws://localhost:8081");
        const localVideo = document.createElement("video");
        localVideo.autoplay = true;
        localVideo.muted = true;

        const videosContainer = document.getElementById("videos");
        videosContainer.appendChild(createVideoContainer(localVideo, "You"));

        const peers = {};
        let localStream;

        function createVideoContainer(videoElement, username) {
            const container = document.createElement("div");
            container.classList.add("video-container");

            const nameLabel = document.createElement("div");
            nameLabel.classList.add("username");
            nameLabel.textContent = username;

            container.appendChild(videoElement);
            container.appendChild(nameLabel);

            return container;
        }

        navigator.mediaDevices.getUserMedia({ video: true, audio: true })
            .then(stream => {
                localStream = stream;
                localVideo.srcObject = stream;

                ws.onopen = () => console.log("WebSocket bağlantısı quruldu.");

                ws.onmessage = async (message) => {
                    const data = JSON.parse(message.data);

                    switch (data.type) {
                        case "join":
                            if (!peers[data.from]) { // PeerConnection təkrar yaradılmasın
                                await createOffer(data.from);
                            }
                            break;
                        case "offer":
                            if (!peers[data.from]) {
                                await handleOffer(data.offer, data.from);
                            }
                            break;
                        case "answer":
                            await handleAnswer(data.answer, data.from);
                            break;
                        case "candidate":
                            if (peers[data.from]) {
                                await peers[data.from].addIceCandidate(new RTCIceCandidate(data.candidate));
                            }
                            break;
                    }
                };
            });

        function createPeerConnection(id) {
            if (peers[id]) return peers[id]; // Əgər artıq bağlantı varsa geri qaytar

            const peerConnection = new RTCPeerConnection({
                iceServers: [{ urls: "stun:stun.l.google.com:19302" }]
            });

            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    ws.send(JSON.stringify({ type: "candidate", candidate: event.candidate, to: id }));
                }
            };

            peerConnection.ontrack = (event) => {
                if (!document.getElementById(`video-${id}`)) { // Təkrar video yaratmamaq üçün yoxlayırıq
                    const remoteVideo = document.createElement("video");
                    remoteVideo.autoplay = true;
                    remoteVideo.id = `video-${id}`;
                    videosContainer.appendChild(createVideoContainer(remoteVideo, `User ${id}`));
                    remoteVideo.srcObject = event.streams[0];
                }
            };

            localStream.getTracks().forEach(track => {
                peerConnection.addTrack(track, localStream);
            });

            peers[id] = peerConnection; // PeerConnection-u yadda saxlayırıq
            return peerConnection;
        }

        async function createOffer(id) {
            const peerConnection = createPeerConnection(id);
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            ws.send(JSON.stringify({ type: "offer", offer, to: id }));
        }

        async function handleOffer(offer, from) {
            const peerConnection = createPeerConnection(from);
            await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));

            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            ws.send(JSON.stringify({ type: "answer", answer, to: from }));
        }

        async function handleAnswer(answer, from) {
            await peers[from].setRemoteDescription(new RTCSessionDescription(answer));
        }

    </script>
</body>
</html>
