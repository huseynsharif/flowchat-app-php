const socket = new WebSocket('ws://localhost:8081');
const peerConnections = {}; // Peer connections-ı izləyirik
const remoteVideosContainer = document.getElementById('remoteVideos');

// Yerli video axını al
navigator.mediaDevices.getUserMedia({ video: true, audio: true })
    .then(stream => {
        document.getElementById('localVideo').srcObject = stream;
        // Yerli video axını hər bir yeni peer connection-a əlavə edirik
        socket.onopen = function () {
            // Yalnız öz video axınımızla başlaya bilərik
        };
    })
    .catch(error => console.error('Kamera əldə edilə bilmədi:', error));

// Gələn signaling mesajlarını idarə et
socket.onmessage = function(event) {
    const message = JSON.parse(event.data);

    if (message.type === 'offer') {
        const peerConnection = new RTCPeerConnection();
        peerConnections[message.from] = peerConnection;
        peerConnection.setRemoteDescription(new RTCSessionDescription(message.offer));
        peerConnection.createAnswer().then(answer => {
            peerConnection.setLocalDescription(answer);
            socket.send(JSON.stringify({ type: 'answer', answer, to: message.from }));
        });
    } else if (message.type === 'answer') {
        const peerConnection = peerConnections[message.from];
        peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
    } else if (message.type === 'candidate') {
        const peerConnection = peerConnections[message.from];
        peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
    }
};

socket.onmessage = function(event) {
    const message = JSON.parse(event.data);
    console.log('Signaling message received:', message);

    if (message.type === 'offer') {
        handleOffer(message);
    } else if (message.type === 'answer') {
        handleAnswer(message);
    } else if (message.type === 'candidate') {
        handleCandidate(message);
    }
};


// ICE candidate-ləri göndər
navigator.mediaDevices.getUserMedia({ video: true, audio: true })
    .then(stream => {
        // Axını hər yeni bağlantıya əlavə edirik
        for (let client in peerConnections) {
            peerConnections[client].addTrack(stream.getTracks()[0], stream);
        }
    })
    .catch(error => console.error('Kamera əldə edilə bilmədi:', error));

socket.onicecandidate = function(event) {
    if (event.candidate) {
        socket.send(JSON.stringify({ type: 'candidate', candidate: event.candidate }));
    }
};

// Uzaq axınıları göstərin
function addRemoteVideo(peerId, stream) {
    const videoContainer = document.createElement('div');
    videoContainer.classList.add('video-container');
    videoContainer.id = peerId;

    const videoElement = document.createElement('video');
    videoElement.autoplay = true;
    videoElement.playsinline = true;
    videoElement.srcObject = stream;

    const usernameElement = document.createElement('div');
    usernameElement.classList.add('username');
    usernameElement.textContent = peerId;

    videoContainer.appendChild(videoElement);
    videoContainer.appendChild(usernameElement);
    remoteVideosContainer.appendChild(videoContainer);
}

// Uzaq axını qəbul etdikdə
peerConnection.ontrack = function(event) {
    addRemoteVideo(peerId, event.streams[0]);
};

// Zəng başlatmaq
function startCall(remotePeerId) {
    const peerConnection = new RTCPeerConnection();
    peerConnections[remotePeerId] = peerConnection;

    // ICE candidate-ləri əlavə edirik
    peerConnection.onicecandidate = function(event) {
        if (event.candidate) {
            socket.send(JSON.stringify({ type: 'candidate', candidate: event.candidate, to: remotePeerId }));
        }
    };

    // Yeni axın əlavə edirik
    peerConnection.addTrack(localStream.getTracks()[0], localStream);

    peerConnection.createOffer().then(offer => {
        peerConnection.setLocalDescription(offer);
        socket.send(JSON.stringify({ type: 'offer', offer, to: remotePeerId }));
    });
}
