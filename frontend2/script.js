// Clean up WebRTC connections when leaving the page
window.addEventListener('beforeunload', function() {
    Object.keys(rtcConnections).forEach(pcId => {
        stopStream(pcId);
    });
});// Import server configuration
import { SERVER, APP_CONFIG } from './config.js';

// Track unique PC entries and WebRTC connections
const pcEntries = {};
const rtcConnections = {};

// Initialize WebSocket connection
const ws = new WebSocket(`ws://${SERVER.IP}:${SERVER.PORT}${SERVER.WS_PATH}`);

// WebSocket event handlers
ws.onopen = function() {
    console.log("Connected to WebSocket");
};

ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log("Received update:", data);

    // Check if data has logs property and it's an array
    if (data.logs && Array.isArray(data.logs)) {
        data.logs.forEach(log => {
            updatePCEntry(log);
        });
    } else if (data && typeof data === 'object') {
        // Handle case where data might be a single log object
        updatePCEntry(data);
    } else {
        console.error("Unexpected data format received:", data);
    }
};

ws.onclose = function() {
    console.log("WebSocket closed. Attempting to reconnect...");
    setTimeout(() => location.reload(), APP_CONFIG.RECONNECT_TIMEOUT);
};

// Update or create PC entry
function updatePCEntry(log) {
    // Check if log is valid
    console.log(log.status); // Check the value of log.status

    console.log(log.screenshot_url);
    if (!log || typeof log !== 'object') {
        console.error("Invalid log object:", log);
        return;
    }

    // Extract PC ID, with fallback
    const pcId = log.pc_id || 'unknown';

    let entry = pcEntries[pcId];

    if (!entry) {
        // Create a new entry for this PC
        entry = document.createElement("li");
        entry.id = `pc-${pcId}`;
        entry.className = "pc-entry";
        entry.innerHTML = createPCEntryHTML(log);
        document.getElementById("log-list").appendChild(entry);
        pcEntries[pcId] = entry;
    } else {
        // Update existing entry
        const windowElement = document.getElementById(`window-${pcId}`);
        if (windowElement && log.active_window !== undefined) {
            windowElement.textContent = log.active_window;
        }

        const statusElement = document.getElementById(`status-${pcId}`);
        if (statusElement && log.status !== undefined) {
            statusElement.textContent = log.status;
            statusElement.className = (typeof log.status === 'string') ? log.status.toLowerCase() : '';
        }
    }

    // Update Screenshot Button (if available)
    updateScreenshotButton(log);

    // Update grid square color based on status
    const gridBox = document.getElementById(pcId);
    if (gridBox) {
        if (log.status && typeof log.status === 'string') {
            gridBox.style.backgroundColor = log.status.toLowerCase() === 'active' ? '#00c853' : '#ff1744'; // green or red
        } else {
            gridBox.style.backgroundColor = '#616161'; // default gray
        }
        gridBox.style.color = '#fff';
    }
}

// Create HTML for PC entry
function createPCEntryHTML(log) {
    // Set default values for missing properties
    const pcId = log.pc_id || 'unknown';
    const activeWindow = log.active_window || 'N/A';
    const status = log.status || 'Unknown';
    const statusClass = (typeof status === 'string') ? status.toLowerCase() : 'unknown';
    
    return `
        <div>
            <strong>PC:</strong> ${pcId} |
            <strong>Window:</strong> <span id="window-${pcId}">${activeWindow}</span> |
            <strong>Status:</strong> <span id="status-${pcId}" class="${statusClass}">${status}</span>
        </div>
        <div class="controls">
            <button id="capture-${pcId}" onclick="window.requestScreenshot('${pcId}')">Capture Screenshot</button>
            <button id="screenshot-${pcId}" onclick="window.viewScreenshot('${log.screenshot_url || ''}')">View Screenshot</button>
            <button id="stream-${pcId}" onclick="window.toggleStream('${pcId}')">Start Live Stream</button>
        </div>
        <div id="video-container-${pcId}" class="video-container">
            <canvas id="video-${pcId}" class="video-feed" width="${APP_CONFIG.CANVAS_WIDTH}" height="${APP_CONFIG.CANVAS_HEIGHT}"></canvas>
        </div>
    `;
}

// Update screenshot button visibility and functionality
function updateScreenshotButton(log) {
    // Check if log is valid and has pc_id
    if (!log || !log.pc_id) {
        return;
    }
    
    const screenshotButton = document.getElementById(`screenshot-${log.pc_id}`);
    if (!screenshotButton) {
        return;
    }
    
    if (log.screenshot_url) {
        const correctedUrl = `${SERVER.SCREENSHOT_PATH}${log.screenshot_url/*.split('/').pop()*/}`;
        //screenshotButton.style.display = "inline-block";
        screenshotButton.setAttribute("onclick", `window.viewScreenshot('${correctedUrl}')`);
    } else {
        screenshotButton.style.display = "inline-block"; 
    }
}

// Request a screenshot from a specific PC
function requestScreenshot(pc_id) {
    if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ action: "capture_screenshot", pc_id: pc_id }));
        console.log(`📸 Screenshot request sent for PC: ${pc_id}`);
    } else {
        alert("WebSocket is not connected.");
    }
}

// View a screenshot in a new tab
function viewScreenshot(url) {
    if (url) {
        console.log(url);
        const parts = url.split('/');
        const correctedUrl = `${SERVER.SCREENSHOT_PATH}${parts.slice(-2).join('/')}`;
        window.open(correctedUrl, "_blank");
    } else {
        alert("No screenshot available.");
    }
}

// Toggle live streaming for a PC
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

// Start WebRTC streaming for a PC
function startStream(pcId) {
    // Create WebSocket connection for WebRTC
    const rtcWs = new WebSocket(`ws://${SERVER.IP}:${SERVER.PORT}${SERVER.WEBRTC_PATH}`);
    
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

// Stop WebRTC streaming for a PC
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

// Display a video frame on the canvas
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

// Expose functions to global scope for inline event handlers
window.requestScreenshot = requestScreenshot;
window.viewScreenshot = viewScreenshot;
window.toggleStream = toggleStream;