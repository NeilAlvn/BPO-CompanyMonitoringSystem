<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Activity Monitor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            color: #333;
        }
        #log-list {
            list-style-type: none;
            padding: 0;
        }
        .pc-entry {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        .controls {
            margin-top: 10px;
        }
        button {
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }
        .video-container {
            margin-top: 10px;
            display: none;
            border: 1px solid #ccc;
            padding: 5px;
        }
        .video-feed {
            width: 100%;
            max-width: 800px;
            /*filter: blur(5px);*/
            display: block;
        }
        .active-stream {
            border: 2px solid #4CAF50;
            background-color: #f8f8f8;
        }
        .active {
            color: green;
            font-weight: bold;
        }
        .idle {
            color: orange;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Live PC Activity Monitoring</h2>
        <div style="text-align: right; margin-bottom: 10px;">
            <form action="logout.php" method="post" style="display:inline;">
                <button type="submit">Logout</button>
            </form>
        </div>
    <ul id="log-list"></ul>

    <script>
        const ws = new WebSocket("ws://192.168.0.34:8000/ws");
        const pcEntries = {}; // Track unique PC entries
        const rtcConnections = {}; // Track WebRTC connections

        ws.onopen = function() {
            console.log("Connected to WebSocket");
        };

        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            console.log("Received update:", data);

            data.logs.forEach(log => {
                let entry = pcEntries[log.pc_id];

                if (!entry) {
                    // ✅ Create a new entry for this PC
                    entry = document.createElement("li");
                    entry.id = `pc-${log.pc_id}`;
                    entry.className = "pc-entry";
                    entry.innerHTML = `
                        <div>
                            <strong>PC:</strong> ${log.pc_id} |
                            <strong>Window:</strong> <span id="window-${log.pc_id}">${log.active_window}</span> |
                            <strong>Status:</strong> <span id="status-${log.pc_id}" class="${log.status.toLowerCase()}">${log.status}</span>
                        </div>
                        <div class="controls">
                            <button id="capture-${log.pc_id}" onclick="requestScreenshot('${log.pc_id}')">Capture Screenshot</button>
                            <button id="screenshot-${log.pc_id}" onclick="viewScreenshot('${log.screenshot_url}')">View Screenshot</button>
                            <button id="stream-${log.pc_id}" onclick="toggleStream('${log.pc_id}')">Start Live Stream</button>
                        </div>
                        <div id="video-container-${log.pc_id}" class="video-container">
                            <canvas id="video-${log.pc_id}" class="video-feed" width="1600" height="900"></canvas>
                        </div>
                    `;
                    document.getElementById("log-list").appendChild(entry);
                    pcEntries[log.pc_id] = entry;
                } else {
                    // ✅ Update existing entry
                    document.getElementById(`window-${log.pc_id}`).textContent = log.active_window;
                    const statusElement = document.getElementById(`status-${log.pc_id}`);
                    statusElement.textContent = log.status;
                    statusElement.className = log.status.toLowerCase();
                }

                // ✅ Update Screenshot Button (if available)
                const screenshotButton = document.getElementById(`screenshot-${log.pc_id}`);
                if (log.screenshot_url) {
                    const correctedUrl = `/MONITORING SYSTEM/backend/screenshots/${log.screenshot_url.split('/').pop()}`; // Fix path
                    screenshotButton.style.display = "inline-block";
                    screenshotButton.setAttribute("onclick", `viewScreenshot('${correctedUrl}')`);
                } else {
                    screenshotButton.style.display = "inline-block"; 
                }
            });
        };
        function requestScreenshot(pc_id) {
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ action: "capture_screenshot", pc_id: pc_id }));
                console.log(`📸 Screenshot request sent for PC: ${pc_id}`);
            } else {
                alert("WebSocket is not connected.");
            }
        }
        function viewScreenshot(url) {
            if (url) {
                const correctedUrl = `/MONITORING SYSTEM/backend/screenshots/${url.split('/').pop()}`; // Fix path
                window.open(correctedUrl, "_blank"); // Open screenshot in a new tab
            } else {
                alert("No screenshot available.");
            }
        }

        // WebRTC Live Stream Functionality
        function toggleStream(pcId) {
            const streamButton = document.getElementById(`stream-${pcId}`);
            const videoContainer = document.getElementById(`video-container-${pcId}`);
            const pcEntry = document.getElementById(`pc-${pcId}`);
            
            if (streamButton.textContent === "Start Live Stream") {
                // Start streaming
                startStream(pcId);
                streamButton.textContent = "Stop Live Stream";
                videoContainer.style.display = "block";
                pcEntry.classList.add("active-stream");
            } else {
                // Stop streaming
                stopStream(pcId);
                streamButton.textContent = "Start Live Stream";
                videoContainer.style.display = "none";
                pcEntry.classList.remove("active-stream");
            }
        }

        function startStream(pcId) {
            // Create WebSocket connection for WebRTC
            const rtcWs = new WebSocket("ws://192.168.0.34:8000/webrtc");
            
            rtcWs.onopen = function() {
                console.log(`WebRTC connection opened for ${pcId}`);
                
                // Register as a viewer for this PC
                rtcWs.send(JSON.stringify({
                    type: "register",
                    pc_id: pcId,
                    client_type: "viewer"
                }));
                
                // Store connection
                rtcConnections[pcId] = rtcWs;
            };
            
            rtcWs.onmessage = function(event) {
                const data = JSON.parse(event.data);
                
                if (data.type === "video-frame" && data.pc_id === pcId) {
                    // Display the video frame
                    displayFrame(pcId, data.frame);
                } else if (data.type === "stream-ended" && data.pc_id === pcId) {
                    // Handle stream end
                    alert(`Stream from ${pcId} has ended.`);
                    stopStream(pcId);
                    const streamButton = document.getElementById(`stream-${pcId}`);
                    streamButton.textContent = "Start Live Stream";
                    document.getElementById(`video-container-${pcId}`).style.display = "none";
                    document.getElementById(`pc-${pcId}`).classList.remove("active-stream");
                }
            };
            
            rtcWs.onclose = function() {
                console.log(`WebRTC connection closed for ${pcId}`);
                
                // Clean up
                if (rtcConnections[pcId]) {
                    delete rtcConnections[pcId];
                }
            };
        }
        
        function stopStream(pcId) {
            // Send command to stop streaming
            if (rtcConnections[pcId]) {
                rtcConnections[pcId].send(JSON.stringify({
                    type: "viewer-command",
                    command: "stop-stream",
                    pc_id: pcId
                }));
                
                // Close connection
                rtcConnections[pcId].close();
                delete rtcConnections[pcId];
            }
        }
        
        function displayFrame(pcId, frameData) {
            const canvas = document.getElementById(`video-${pcId}`);
            const ctx = canvas.getContext('2d');
            
            // Create an image from the base64 data
            const image = new Image();
            image.onload = function() {
                ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
            };
            image.src = "data:image/jpeg;base64," + frameData;
        }

        ws.onclose = function() {
            console.log("WebSocket closed. Attempting to reconnect...");
            setTimeout(() => location.reload(), 5000);
        };

        // Clean up WebRTC connections when leaving the page
        window.addEventListener('beforeunload', function() {
            Object.keys(rtcConnections).forEach(pcId => {
                stopStream(pcId);
            });
        });
    </script>
</body>
</html>