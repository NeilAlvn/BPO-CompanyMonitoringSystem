<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Process incoming JSON data if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
    
    // Get JSON input
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);
    
    // Process data
    $response = array(
        "data" => $data
    );
    
    // Return JSON response
    echo json_encode($response);
    exit(); // Stop execution after handling the API request
}

// If not a POST request, continue with regular page content below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Monitoring System</title>
    <!-- Move this to inside the body tag, preferably right after the opening <body> tag -->
    <div class="logout-container">
        <form action="logout.php" method="post" style="margin: 0;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .connection-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .connected {
            background: rgba(40, 167, 69, 0.8);
            color: white;
        }
        
        .disconnected {
            background: rgba(220, 53, 69, 0.8);
            color: white;
        }
        
        .button-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 5px;
            padding: 10px;
            background: rgba(255,255,255,0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            place-items: stretch;
        }
        
        .pc-button {
            position: relative;
            padding: 12px 8px;
            font-size: 14px;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            min-height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .pc-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .pc-button.active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            animation: pulse 2s infinite;
        }
        
        .pc-button.idle {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .pc-button.offline {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .pc-button.streaming {
            background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%);
            box-shadow: 0 0 20px rgba(232, 62, 140, 0.5);
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            50% { box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4), 0 0 20px rgba(40, 167, 69, 0.3); }
            100% { box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        }
        
        .pc-status {
            font-size: 10px;
            margin-top: 3px;
            opacity: 0.9;
        }
        
        /* Spacing between button sections - every 2 rows (12 buttons) */
        .pc-button:nth-child(n+13):nth-child(-n+18),
        .pc-button:nth-child(n+25):nth-child(-n+30),
        .pc-button:nth-child(n+37):nth-child(-n+42),
        .pc-button:nth-child(n+49):nth-child(-n+54),
        .pc-button:nth-child(n+61):nth-child(-n+66),
        .pc-button:nth-child(n+73):nth-child(-n+78),
        .pc-button:nth-child(n+85):nth-child(-n+90) {
            margin-top: 30px;
        }
        
        .side-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: -5px 0 25px rgba(0,0,0,0.4);
            transition: right 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            z-index: 1000;
            padding: 30px 25px;
            box-sizing: border-box;
            overflow-y: auto;
        }
        
        .side-panel.open {
            right: 0;
        }
        
        .side-panel h3 {
            color: white;
            margin-bottom: 25px;
            font-size: 22px;
            text-align: center;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .pc-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
        }
        
        .pc-info div {
            margin-bottom: 8px;
        }
        
        .pc-info strong {
            color: #3498db;
        }
        
        .panel-button {
            display: block;
            width: 100%;
            padding: 18px 25px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .panel-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .panel-button.capture {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .panel-button.open-png {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .panel-button.stream {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }
        
        .panel-button.stream.active {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .close-panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 28px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-panel:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.6);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            backdrop-filter: blur(3px);
        }
        
        .overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .video-container {
            margin-top: 20px;
            display: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        
        .video-feed {
            width: 100%;
            max-width: 350px;
            display: block;
            border-radius: 10px;
        }
        
        .video-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
        }
        
        .video-control-btn {
            background: rgba(0,0,0,0.7);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .video-control-btn:hover {
            background: rgba(0,0,0,0.9);
            transform: scale(1.1);
        }
        
        /* Maximized video styles */
        .video-maximized {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.95);
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            margin: 0;
        }
        
        .video-maximized .video-feed {
            max-width: 90vw;
            max-height: 90vh;
            width: auto;
            height: auto;
            border-radius: 0;
            display: block;
            margin: 50px auto 0 auto;
        }
        
        .video-maximized .video-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 3001;
        }
        
        .status-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: bold;
            z-index: 2000;
            display: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        
        .status-message.success {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }
        
        .status-message.error {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }
        
        .status-message.info {
            background: rgba(23, 162, 184, 0.9);
            color: white;
        }
        
        @media (max-width: 768px) {
            .button-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                padding: 20px;
            }
            
            .pc-button {
                padding: 15px 10px;
                font-size: 14px;
                min-height: 70px;
            }
            
            /* Reset desktop spacing and add tablet spacing */
            .pc-button:nth-child(12n+1):not(:first-child) {
                margin-top: 0;
            }
            
            .pc-button:nth-child(8n+1):not(:first-child) {
                margin-top: 25px;
            }
            
            .side-panel {
                width: 100%;
                right: -100%;
            }
        }
        
        @media (max-width: 480px) {
            .button-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            
            /* Reset tablet spacing and add mobile spacing */
            .pc-button:nth-child(8n+1):not(:first-child) {
                margin-top: 0;
            }
            
            .pc-button:nth-child(6n+1):not(:first-child) {
                margin-top: 20px;
            }
        }
        /* Logout Button Container */
.logout-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 900; /* Lower than side panel (1000) but higher than main content */
}

/* Logout Button Styling */
.logout-btn {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    backdrop-filter: blur(10px);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.logout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
}

.logout-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.logout-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3);
}

/* Add icon before text */
/* Icons removed per request */

/* Alternative version with text icon */
/* Icons removed per request */

/* Mobile responsive */
@media (max-width: 768px) {
    .logout-container {
        top: 15px;
        right: 15px;
    }
    
    .logout-btn {
        padding: 10px 20px;
        font-size: 12px;
        border-radius: 20px;
    }
    
    .logout-btn::before {
        /* Icon removed */
    }
}

@media (max-width: 480px) {
    .logout-btn {
        padding: 8px 16px;
        font-size: 11px;
    }
}

/* Glassmorphism effect variant */
.logout-btn.glass {
    background: rgba(231, 76, 60, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.logout-btn.glass:hover {
    background: rgba(231, 76, 60, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* Minimal variant */
.logout-btn.minimal {
    background: transparent;
    color: rgba(255, 255, 255, 0.9);
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: none;
}

.logout-btn.minimal:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-1px);
}

/* Animation for attention-grabbing effect */
@keyframes logoutPulse {
    0% { 
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        transform: scale(1);
    }
    50% { 
        box-shadow: 0 4px 20px rgba(231, 76, 60, 0.5);
        transform: scale(1.02);
    }
    100% { 
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        transform: scale(1);
    }
}

.logout-btn.pulse {
    animation: logoutPulse 2s infinite;
}

/* Animation for attention-grabbing effect */
@keyframes logoutPulse {
    0% { 
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        transform: scale(1);
    }
    50% { 
        box-shadow: 0 4px 20px rgba(231, 76, 60, 0.5);
        transform: scale(1.02);
    }
    100% { 
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        transform: scale(1);
    }
}

.logout-btn.pulse {
    animation: logoutPulse 2s infinite;
}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖥️ PC Monitoring System</h1>
            <div id="connectionStatus" class="connection-status disconnected">
                ⚡ Connecting...
            </div>
        </div>
        <div class="button-grid" id="buttonGrid">
            <!-- PC buttons will be generated by JavaScript -->
        </div>
    </div>

    <!-- Status Messages -->
    <div class="status-message" id="statusMessage"></div>

    <!-- Side Panel -->
    <div class="overlay" id="overlay"></div>
    <div class="side-panel" id="sidePanel">
        <button class="close-panel" id="closePanel">×</button>
        <h3 id="panelTitle">PC Actions</h3>
        
        <div class="pc-info" id="pcInfo">
            <div><strong>PC ID:</strong> <span id="pcId">-</span></div>
            <div><strong>Status:</strong> <span id="pcStatus">-</span></div>
            <div><strong>Active Window:</strong> <span id="pcWindow">-</span></div>
            <div><strong>Last Update:</strong> <span id="pcLastUpdate">-</span></div>
        </div>
        
        <button class="panel-button capture" id="captureBtn">
            📸 Capture Screenshot
        </button>
        <button class="panel-button open-png" id="openPngBtn">
            🖼️ Open Latest PNG
        </button>
        <button class="panel-button stream" id="streamBtn">
            📺 Start Live Stream
        </button>
        
        <div id="videoContainer" class="video-container">
            <div class="video-controls">
                <button class="video-control-btn" id="maximizeBtn" title="Maximize">
                    ⛶
                </button>
            </div>
            <canvas id="videoFeed" class="video-feed" width="350" height="197"></canvas>
        </div>
    </div>

    <script>
        // Global variables
        let ws = null;
        let selectedPC = null;
        let pcData = {};
        let rtcConnections = {};
        let isStreaming = false;
        let isVideoMaximized = false;
        
        // DOM elements
        const buttonGrid = document.getElementById('buttonGrid');
        const sidePanel = document.getElementById('sidePanel');
        const overlay = document.getElementById('overlay');
        const closePanel = document.getElementById('closePanel');
        const panelTitle = document.getElementById('panelTitle');
        const captureBtn = document.getElementById('captureBtn');
        const openPngBtn = document.getElementById('openPngBtn');
        const streamBtn = document.getElementById('streamBtn');
        const connectionStatus = document.getElementById('connectionStatus');
        const statusMessage = document.getElementById('statusMessage');
        const videoContainer = document.getElementById('videoContainer');
        const videoFeed = document.getElementById('videoFeed');
        const maximizeBtn = document.getElementById('maximizeBtn');
        
        // Info elements
        const pcId = document.getElementById('pcId');
        const pcStatus = document.getElementById('pcStatus');
        const pcWindow = document.getElementById('pcWindow');
        const pcLastUpdate = document.getElementById('pcLastUpdate');
        
        // Initialize the system
        function init() {
            generatePCButtons();
            connectWebSocket();
            setupEventListeners();
        }
        
        // Generate PC buttons with zigzag numbering pattern starting from right
        function generatePCButtons() {
            // Clear existing buttons
            buttonGrid.innerHTML = '';
            
            const totalPCs = 96;
            const columns = 6;
            const rows = Math.ceil(totalPCs / columns);
            
            // Create array to store PC positions
            const pcPositions = [];
            
            // Generate zigzag pattern starting from top-right
            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < columns; col++) {
                    const pcIndex = row * columns + col + 1;
                    if (pcIndex > totalPCs) break;
                    
                    let actualCol;
                    // Zigzag pattern: even rows go right-to-left, odd rows go left-to-right
                    if (row % 2 === 0) {
                        // Even row (0, 2, 4...): right to left (start from right)
                        actualCol = columns - 1 - col;
                    } else {
                        // Odd row (1, 3, 5...): left to right
                        actualCol = col;
                    }
                    
                    const pcNumber = pcIndex.toString().padStart(2, '0');
                    const pcId = `1-PC-${pcNumber}`;
                    
                    pcPositions.push({
                        pcId,
                        row,
                        col: actualCol,
                        index: pcIndex
                    });
                }
            }
            
            // Sort by row and column for proper grid placement
            pcPositions.sort((a, b) => {
                if (a.row !== b.row) return a.row - b.row;
                return a.col - b.col;
            });
            
            // Create buttons in the sorted order
            pcPositions.forEach(pc => {
                const button = document.createElement('button');
                button.className = 'pc-button offline';
                button.id = `pc-${pc.pcId}`;
                button.innerHTML = `
                    <div>${pc.pcId}</div>
                    <div class="pc-status">Offline</div>
                `;
                button.addEventListener('click', () => selectPC(pc.pcId));
                buttonGrid.appendChild(button);
            });
            
            console.log('Generated 96 PC buttons with zigzag pattern starting from top-right');
        }
        
        // WebSocket connection
        function connectWebSocket() {
            try {
                ws = new WebSocket("ws://172.16.1.5:8000/ws");
                
                ws.onopen = function() {
                    console.log("Connected to WebSocket");
                    updateConnectionStatus(true);
                    showStatus("Connected to monitoring server", "success");
                };
                
                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    console.log("Received update:", data);
                    
                    if (data.logs) {
                        data.logs.forEach(log => {
                            updatePCStatus(log);
                        });
                    }
                };
                
                ws.onclose = function() {
                    console.log("WebSocket closed");
                    updateConnectionStatus(false);
                    showStatus("Connection lost. Attempting to reconnect...", "error");
                    
                    // Attempt to reconnect after 5 seconds
                    setTimeout(connectWebSocket, 5000);
                };
                
                ws.onerror = function(error) {
                    console.error("WebSocket error:", error);
                    updateConnectionStatus(false);
                };
                
            } catch (error) {
                console.error("Failed to connect WebSocket:", error);
                updateConnectionStatus(false);
            }
        }
        
        // Update PC status
        function updatePCStatus(log) {
            const pcButton = document.getElementById(`pc-${log.pc_id}`);
            if (!pcButton) return;
            
            // Store PC data
            pcData[log.pc_id] = {
                ...log,
                lastUpdate: new Date().toLocaleTimeString()
            };
            
            // Update button appearance
            pcButton.className = `pc-button ${log.status.toLowerCase()}`;
            pcButton.innerHTML = `
                <div>${log.pc_id}</div>
                <div class="pc-status">${log.status}</div>
            `;
            
            // Update panel if this PC is selected
            if (selectedPC === log.pc_id) {
                updatePanelInfo(log.pc_id);
            }
        }
        
        // Update connection status
        function updateConnectionStatus(connected) {
            if (connected) {
                connectionStatus.className = 'connection-status connected';
                connectionStatus.textContent = '🟢 Connected';
            } else {
                connectionStatus.className = 'connection-status disconnected';
                connectionStatus.textContent = '🔴 Disconnected';
            }
        }
        
        // Select PC
        function selectPC(pcId) {
            selectedPC = pcId;
            panelTitle.textContent = `${pcId} - Actions`;
            updatePanelInfo(pcId);
            openSidePanel();
        }
        
        // Update panel info
        function updatePanelInfo(pcIdentifier) {
            const data = pcData[pcIdentifier];
            
            document.getElementById('pcId').textContent = pcIdentifier;
            
            if (data) {
                pcStatus.textContent = data.status;
                pcWindow.textContent = data.active_window || 'Unknown';
                pcLastUpdate.textContent = data.lastUpdate;
                
                // Enable/disable buttons based on PC status
                const isOnline = data.status === 'Active' || data.status === 'Idle';
                captureBtn.disabled = !isOnline;
                openPngBtn.disabled = false; // Always allow viewing screenshots
                streamBtn.disabled = !isOnline;
            } else {
                pcStatus.textContent = 'Offline';
                pcWindow.textContent = 'N/A';
                pcLastUpdate.textContent = 'Never';
                
                captureBtn.disabled = true;
                openPngBtn.disabled = false;
                streamBtn.disabled = true;
            }
        }
        
        // Side panel functions
        function openSidePanel() {
            sidePanel.classList.add('open');
            overlay.classList.add('show');
        }
        
        function closeSidePanel() {
            sidePanel.classList.remove('open');
            overlay.classList.remove('show');
            
            // Stop streaming if active
            if (isStreaming) {
                stopStream();
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            closePanel.addEventListener('click', closeSidePanel);
            overlay.addEventListener('click', closeSidePanel);
            
            // Panel button actions
            captureBtn.addEventListener('click', requestScreenshot);
            openPngBtn.addEventListener('click', openLatestPNG);
            streamBtn.addEventListener('click', toggleStream);
            
            // Video controls
            maximizeBtn.addEventListener('click', toggleMaximize);
            
            // Close panel with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (isVideoMaximized) {
                        toggleMaximize();
                    } else {
                        closeSidePanel();
                    }
                }
            });
            
            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                Object.keys(rtcConnections).forEach(pcId => {
                    if (rtcConnections[pcId]) {
                        rtcConnections[pcId].close();
                    }
                });
            });
        }
        
        // Request screenshot
        function requestScreenshot() {
            if (!selectedPC) return;
            
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({ 
                    action: "capture_screenshot", 
                    pc_id: selectedPC 
                }));
                showStatus(`Screenshot request sent for PC ${selectedPC}`, "success");
            } else {
                showStatus("WebSocket is not connected", "error");
            }
        }
        
        // Open latest PNG
        function openLatestPNG() {
            if (!selectedPC) return;
            
            // Use the new floor-based PC ID format for folder path (1-PC-01, 1-PC-02, etc.)
            const folderPath = `/MONITORING%20SYSTEM/backend/screenshots/${selectedPC}/`;
            showStatus(`Accessing screenshots for ${selectedPC}...`, "info");
            
            fetch(folderPath)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Failed to access directory (${response.status})`);
                    }
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const pngLinks = Array.from(doc.querySelectorAll('a[href$=".png"]'));
                    
                    if (pngLinks.length === 0) {
                        throw new Error("No screenshots found");
                    }
                    
                    // Debug: Log all found PNG files
                    console.log("Found PNG files:", pngLinks.map(link => link.textContent.trim()));
                    
                    // Find the latest PNG file with multiple timestamp formats
                    const pngFiles = [];
                    
                    pngLinks.forEach(link => {
                        const filename = link.textContent.trim();
                        let date = null;
                        
                        // Try multiple timestamp patterns
                        // Pattern 1: screenshot_20250510_153045.png (YYYYMMdd_HHmmss)
                        let match = filename.match(/(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/);
                        if (match) {
                            const [_, year, month, day, hour, minute, second] = match;
                            date = new Date(year, month-1, day, hour, minute, second);
                        }
                        
                        // Pattern 2: screenshot_2025-05-10_15-30-45.png (YYYY-MM-dd_HH-mm-ss)
                        if (!date) {
                            match = filename.match(/(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})-(\d{2})/);
                            if (match) {
                                const [_, year, month, day, hour, minute, second] = match;
                                date = new Date(year, month-1, day, hour, minute, second);
                            }
                        }
                        
                        // Pattern 3: screenshot_2025-05-10T15:30:45.png (ISO-like format)
                        if (!date) {
                            match = filename.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/);
                            if (match) {
                                const [_, year, month, day, hour, minute, second] = match;
                                date = new Date(year, month-1, day, hour, minute, second);
                            }
                        }
                        
                        // Pattern 4: Any sequence of digits that could be a timestamp
                        if (!date) {
                            match = filename.match(/(\d{8,14})/); // 8-14 consecutive digits
                            if (match) {
                                const timestamp = match[1];
                                if (timestamp.length >= 8) {
                                    // Try to parse as YYYYMMddHHmmss or YYYYMMdd
                                    const year = timestamp.substr(0, 4);
                                    const month = timestamp.substr(4, 2);
                                    const day = timestamp.substr(6, 2);
                                    const hour = timestamp.length > 8 ? timestamp.substr(8, 2) || '00' : '00';
                                    const minute = timestamp.length > 10 ? timestamp.substr(10, 2) || '00' : '00';
                                    const second = timestamp.length > 12 ? timestamp.substr(12, 2) || '00' : '00';
                                    
                                    if (year > 2000 && month <= 12 && day <= 31) {
                                        date = new Date(year, month-1, day, hour, minute, second);
                                    }
                                }
                            }
                        }
                        
                        if (date && !isNaN(date.getTime())) {
                            pngFiles.push({
                                filename,
                                href: link.getAttribute('href'),
                                date: date,
                                dateString: date.toLocaleString()
                            });
                        } else {
                            // If no timestamp found, use current time as fallback
                            console.warn(`Could not parse timestamp from: ${filename}`);
                            pngFiles.push({
                                filename,
                                href: link.getAttribute('href'),
                                date: new Date(0), // Use epoch time as fallback (will be sorted first)
                                dateString: "No timestamp found"
                            });
                        }
                    });
                    
                    if (pngFiles.length === 0) {
                        throw new Error("No PNG files found");
                    }
                    
                    console.log("Processed PNG files:", pngFiles);
                    
                    // Sort by date - newest first (latest timestamps get higher values)
                    pngFiles.sort((a, b) => {
                        // Convert dates to timestamps for reliable comparison
                        const timeA = a.date.getTime();
                        const timeB = b.date.getTime();
                        
                        // If times are equal, sort by filename alphabetically (newer names usually come last)
                        if (timeA === timeB) {
                            return b.filename.localeCompare(a.filename);
                        }
                        
                        // Newest first (larger timestamp first)
                        return timeB - timeA;
                    });
                    
                    console.log("Sorted PNG files (newest first):", pngFiles.map(f => ({
                        filename: f.filename,
                        date: f.dateString,
                        timestamp: f.date.getTime()
                    })));
                    
                    const latestPng = pngFiles[0]; // First item is now the newest
                    
                    const latestPngUrl = new URL(latestPng.href, window.location.origin + folderPath).href;
                    window.open(latestPngUrl, "_blank");
                    
                    showStatus(`Opening latest: ${latestPng.filename} (${latestPng.dateString})`, "success");
                })
                .catch(error => {
                    console.error("Error:", error);
                    showStatus(`Error: ${error.message}`, "error");
                });
        }
        
        // Toggle stream
        function toggleStream() {
            if (!selectedPC) return;
            
            if (isStreaming) {
                stopStream();
            } else {
                startStream();
            }
        }
        
        // Start stream
        function startStream() {
            if (!selectedPC) return;
            
            try {
                const rtcWs = new WebSocket("ws://172.16.1.5:8000/webrtc");
                
                rtcWs.onopen = function() {
                    console.log(`WebRTC connection opened for PC ${selectedPC}`);
                    
                    rtcWs.send(JSON.stringify({
                        type: "register",
                        pc_id: selectedPC,
                        client_type: "viewer"
                    }));
                    
                    rtcConnections[selectedPC] = rtcWs;
                    isStreaming = true;
                    
                    // Update UI
                    streamBtn.textContent = "🛑 Stop Stream";
                    streamBtn.classList.add('active');
                    videoContainer.style.display = "block";
                    
                    const pcButton = document.getElementById(`pc-${selectedPC}`);
                    if (pcButton) {
                        pcButton.classList.add('streaming');
                    }
                    
                    showStatus(`Started live stream for ${selectedPC}`, "success");
                };
                
                rtcWs.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === "video-frame" && data.pc_id === selectedPC) {
                        displayFrame(data.frame);
                    } else if (data.type === "stream-ended" && data.pc_id === selectedPC) {
                        showStatus(`Stream from ${selectedPC} has ended`, "error");
                        stopStream();
                    }
                };
                
                rtcWs.onclose = function() {
                    console.log(`WebRTC connection closed for ${selectedPC}`);
                    stopStream();
                };
                
                rtcWs.onerror = function(error) {
                    console.error("WebRTC error:", error);
                    showStatus("Failed to start stream", "error");
                    stopStream();
                };
                
            } catch (error) {
                console.error("Failed to start stream:", error);
                showStatus("Failed to start stream", "error");
            }
        }
        
        // Stop stream
        function stopStream() {
            if (selectedPC && rtcConnections[selectedPC]) {
                try {
                    rtcConnections[selectedPC].send(JSON.stringify({
                        type: "viewer-command",
                        command: "stop-stream",
                        pc_id: selectedPC
                    }));
                } catch (error) {
                    console.error("Error sending stop command:", error);
                }
                
                rtcConnections[selectedPC].close();
                delete rtcConnections[selectedPC];
            }
            
            isStreaming = false;
            
            // Minimize video if maximized
            if (isVideoMaximized) {
                toggleMaximize();
            }
            
            // Update UI
            streamBtn.textContent = "📺 Start Live Stream";
            streamBtn.classList.remove('active');
            videoContainer.style.display = "none";
            
            if (selectedPC) {
                const pcButton = document.getElementById(`pc-${selectedPC}`);
                if (pcButton) {
                    pcButton.classList.remove('streaming');
                }
            }
        }
        
        // Display video frame
        function displayFrame(frameData) {
            const ctx = videoFeed.getContext('2d');
            const image = new Image();
            
            image.onload = function() {
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(image, 0, 0, videoFeed.width, videoFeed.height);
            };
            
            image.src = "data:image/jpeg;base64," + frameData;
        }
        
        // Toggle maximize video
        function toggleMaximize() {
            if (isVideoMaximized) {
                // Minimize video
                videoContainer.classList.remove('video-maximized');
                maximizeBtn.innerHTML = '⛶';
                maximizeBtn.title = 'Maximize';
                isVideoMaximized = false;
                
                // Restore canvas size
                videoFeed.width = 350;
                videoFeed.height = 197;
                
                showStatus("Video minimized", "info");
            } else {
                // Maximize video
                videoContainer.classList.add('video-maximized');
                maximizeBtn.innerHTML = '❌';
                maximizeBtn.title = 'Minimize';
                isVideoMaximized = true;
                
                // Adjust canvas size for maximized view
                const maxWidth = window.innerWidth * 0.9;
                const maxHeight = window.innerHeight * 0.9;
                const aspectRatio = 350 / 197;
                
                if (maxWidth / maxHeight > aspectRatio) {
                    videoFeed.height = maxHeight;
                    videoFeed.width = maxHeight * aspectRatio;
                } else {
                    videoFeed.width = maxWidth;
                    videoFeed.height = maxWidth / aspectRatio;
                }
                
                showStatus("Video maximized - Press ESC to minimize", "info");
            }
        }
        function showStatus(message, type) {
            statusMessage.textContent = message;
            statusMessage.className = `status-message ${type}`;
            statusMessage.style.display = 'block';
            
            // Auto-hide after 5 seconds for success messages
            if (type === "success") {
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 5000);
            } else if (type === "info") {
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 3000);
            }
        }
        
        // Initialize the application
        init();
    </script>
</body>
</html>