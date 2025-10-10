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
        
        /* Frame Navigation Tabs */
        .frame-tabs {
            display: flex;
            justify-content: center;
            gap: 2px;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 5px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        .frame-tab {
            flex: 1;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: bold;
            color: rgba(255,255,255,0.7);
            background: transparent;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .frame-tab:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .frame-tab.active {
            color: white;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        /* Frame Containers */
        .frame-container {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .frame-container.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            transform: none  ;
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
        
        /* Logout Button Container */
        .logout-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 900;
        }

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
            
            .logout-container {
                top: 15px;
                right: 15px;
            }
            
            .logout-btn {
                padding: 10px 20px;
                font-size: 12px;
                border-radius: 20px;
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
            
            .logout-btn {
                padding: 8px 16px;
                font-size: 11px;
            }
        }
        /* Second Floor Separated Layout Styles */
#frame-2 .button-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
    padding: 10px;
    max-width: 1200px;
    box-sizing: border-box;
}

/* Training Room Section */
.training-room-section {
    background: rgba(15, 81, 124, 0.1);
    border: 2px solid rgba(70, 139, 185, 0.3);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(52, 152, 219, 0.2);
    backdrop-filter: blur(10px);
}

.training-room-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 20px 1fr 1fr 20px 1fr 1fr;
    grid-template-rows: repeat(6, 60px);
    gap: 5px;
    place-items: stretch;
}

/* Production Area Section */
.production-area-section {
    background: rgba(46, 204, 113, 0.1);
    border: 2px solid rgba(46, 204, 113, 0.3);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(46, 204, 113, 0.2);
    backdrop-filter: blur(10px);
}

.production-area-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    grid-template-rows: repeat(2, 60px) 30px repeat(2, 60px) 30px repeat(2, 60px) 30px repeat(2, 60px);
    gap: 5px;
    place-items: stretch;
}

/* Section Titles */
.section-title {
    color: white;
    text-align: center;
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    padding: 10px;
    border-radius: 10px;
    background: rgba(255,255,255,0.1);
}

.training-room-section .section-title {
    background: rgba(52, 152, 219, 0.2);
    border: 1px solid rgba(52, 152, 219, 0.4);
}

.production-area-section .section-title {
    background: rgba(46, 204, 113, 0.2);
    border: 1px solid rgba(46, 204, 113, 0.4);
}

/* PC Buttons in Frame 2 */
#frame-2 .pc-button {
    position: relative;
    padding: 12px 8px !important;
    font-size: 14px !important;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    width: 100% !important;
    height: 100% !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#frame-2 .pc-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

#frame-2 .pc-button.active {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    animation: pulse 2s infinite;
}

#frame-2 .pc-button.idle {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

#frame-2 .pc-button.offline {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

#frame-2 .pc-button.streaming {
    background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%);
    box-shadow: 0 0 20px rgba(232, 62, 140, 0.5);
}

/* Third Floor Separated Layout Styles */

#frame-3 .button-grid {
    display: flex;
    flex-direction: column;
    gap: 30px;
    padding: 10px;
    max-width: 1200px;
    box-sizing: border-box;
}

/* Computer Lab Section */
.computer-lab-section {
    background: rgba(155, 89, 182, 0.1);
    border: 2px solid rgba(155, 89, 182, 0.3);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(155, 89, 182, 0.2);
    backdrop-filter: blur(10px);
}

.computer-lab-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 20px 1fr 1fr 20px 1fr 1fr;
    grid-template-rows: repeat(10, 60px);
    gap: 5px;
    place-items: stretch;
}

/* Development Zone Section */
.development-zone-section {
    background: rgba(230, 126, 34, 0.1);
    border: 2px solid rgba(230, 126, 34, 0.3);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(230, 126, 34, 0.2);
    backdrop-filter: blur(10px);
}

.development-zone-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    grid-template-rows: 
        repeat(2, 60px) 30px    /* First group (6 cols): rows 1-2, spacing row 3 */
        repeat(2, 60px) 30px    /* Second group (6 cols): rows 4-5, spacing row 6 */
        repeat(2, 60px) 30px    /* Third group (8 cols): rows 7-8, spacing row 9 */
        repeat(2, 60px) 30px    /* Fourth group (8 cols): rows 10-11, spacing row 12 */
        repeat(2, 60px) 30px    /* Fifth group (8 cols): rows 13-14, spacing row 15 */
        repeat(2, 60px) 30px    /* Sixth group (8 cols): rows 16-17, spacing row 18 */
        repeat(2, 60px) 30px    /* Seventh group (6 cols): rows 19-20, spacing row 21 */
        repeat(2, 60px);        /* Eighth group (8 cols): rows 22-23 */
    gap: 5px;
    place-items: stretch;
}

/* Section Titles */
.section-title {
    color: white;
    text-align: center;
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    padding: 10px;
    border-radius: 10px;
    background: rgba(255,255,255,0.1);
}

.computer-lab-section .section-title {
    background: rgba(155, 89, 182, 0.2);
    border: 1px solid rgba(155, 89, 182, 0.4);
}

.development-zone-section .section-title {
    background: rgba(230, 126, 34, 0.2);
    border: 1px solid rgba(230, 126, 34, 0.4);
}

/* PC Buttons in Frame 3 */
#frame-3 .pc-button {
    position: relative;
    padding: 12px 8px !important;
    font-size: 14px !important;
    font-weight: bold;
    color: white;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    width: 100% !important;
    height: 100% !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#frame-3 .pc-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

#frame-3 .pc-button.active {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    animation: pulse 2s infinite;
}

#frame-3 .pc-button.idle {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

#frame-3 .pc-button.offline {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

#frame-3 .pc-button.streaming {
    background: linear-gradient(135deg, #e83e8c 0%, #d63384 100%);
    box-shadow: 0 0 20px rgba(232, 62, 140, 0.5);
}

/* Pulse animation for active buttons */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
    }
}
    </style>
</head>
<body>
    <!-- Logout Button -->
    <div class="logout-container">
        <form action="logout.php" method="post" style="margin: 0;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="container">
        <div class="header">
            <h1>🖥️ PC Monitoring System</h1>
            <div id="connectionStatus" class="connection-status disconnected">
                ⚡ Connecting...
            </div>
        </div>

        <!-- Frame Navigation Tabs -->
        <div class="frame-tabs">
            <button class="frame-tab active" data-frame="1">
                First Floor
            </button>
            <button class="frame-tab" data-frame="2">
                Second Floor
            </button>
            <button class="frame-tab" data-frame="3">
                Third Floor
            </button>
        </div>

        <!-- Frame 1 -->
        <div class="frame-container active" id="frame-1">
            <div class="button-grid" id="buttonGrid-1">
                <!-- PC buttons 1-96 with zigzag pattern -->
            </div>
        </div>

        <!-- Frame 2 -->
        <div class="frame-container" id="frame-2">
            <div class="button-grid" id="buttonGrid-2">
                <!-- PC buttons 97-192 with zigzag pattern -->
            </div>
        </div>

        <!-- Frame 3 -->
        <div class="frame-container" id="frame-3">
            <div class="button-grid" id="buttonGrid-3">
                <!-- PC buttons 193-288 with zigzag pattern -->
            </div>
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
        let currentFrame = 1;
        
        // DOM elements
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
            console.log('Initializing PC Monitoring System...');
            generateAllFrames();
            connectWebSocket();
            setupEventListeners();
            setupFrameSwitching();
        }

        // Generate all 3 frames with PC buttons using zigzag pattern
        function generateAllFrames() {
            for (let frame = 1; frame <= 3; frame++) {
                generatePCButtonsForFrame(frame);
            }
            console.log('Generated all 3 frames with 288 total PCs using zigzag pattern');
        }
        
        // Generate PC buttons for a specific frame with zigzag pattern (starting from right)
         function generatePCButtonsForFrame(frameNumber) {
            const buttonGrid = document.getElementById(`buttonGrid-${frameNumber}`);
            if (!buttonGrid) {
                console.log("buttonGrid is null!"); // ADD THIS LINE
                return;
            }
            
            buttonGrid.innerHTML = '';
            
            if (frameNumber === 2) {
                // Special layout for Frame 2 (Second Floor)
                generateSecondFloorLayout(buttonGrid);
                return;
            } else if (frameNumber === 3) {
                // Special layout for Frame 3 (Third Floor)
                generateThirdFloorLayout(buttonGrid);
                return;
            }
            
            const pcsPerFrame = 96;
            const columns = 6;
            const rows = Math.ceil(pcsPerFrame / columns);
            const frameOffset = (frameNumber - 1) * 96;
            
            // Create array to store PC positions
            const pcPositions = [];
            
            // Generate zigzag pattern starting from top-right
            for (let row = 0; row < rows; row++) {
                for (let col = 0; col < columns; col++) {
                    const pcIndex = row * columns + col + 1;
                    if (pcIndex > pcsPerFrame) break;
                    
                    let actualCol;
                    // Zigzag pattern: even rows go right-to-left, odd rows go left-to-right
                    if (row % 2 === 0) {
                        // Even row (0, 2, 4...): right to left (start from right)
                        actualCol = columns - 1 - col;
                    } else {
                        // Odd row (1, 3, 5...): left to right
                        actualCol = col;
                    }
                    
                    const globalPCNumber = pcIndex + frameOffset;
                    const pcNumber = globalPCNumber.toString().padStart(3, '0');
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
            
            console.log(`Generated ${pcsPerFrame} buttons for Frame ${frameNumber} with zigzag pattern`);
        }

        // Generate special layout for Second Floor (Frame 2) with separated sections
function generateSecondFloorLayout(buttonGrid) {
    console.log("generateSecondFloorLayout called for frame 2");
    
    // Clear the existing content
    buttonGrid.innerHTML = '';
    
    // Create Training Room section (PCs 1-36)
    const trainingSection = document.createElement('div');
    trainingSection.className = 'training-room-section';
    trainingSection.innerHTML = '<h3 class="section-title">TRAINING ROOM</h3>';
    
    const trainingGrid = document.createElement('div');
    trainingGrid.className = 'training-room-grid';
    
    // Define the Training Room PC layout groups
    const trainingGroups = [
        // Top-left group (TRAINING ROOM - left side)
        {
            startRow: 0, startCol: 0,
            pcs: [
                ['2-PC-001', '2-PC-012'],
                ['2-PC-002', '2-PC-011'], 
                ['2-PC-003', '2-PC-010'],
                ['2-PC-004', '2-PC-009'],
                ['2-PC-005', '2-PC-008'],
                ['2-PC-006', '2-PC-007']
            ]
        },
        // Top-center group (TRAINING ROOM - center)
        {
            startRow: 0, startCol: 3, // Skip column 2 (spacing)
            pcs: [
                ['2-PC-013', '2-PC-024'],
                ['2-PC-014', '2-PC-023'],
                ['2-PC-015', '2-PC-022'],
                ['2-PC-016', '2-PC-021'],
                ['2-PC-017', '2-PC-020'],
                ['2-PC-018', '2-PC-019']
            ]
        },
        // Top-right group (TRAINING ROOM - right side)
        {
            startRow: 0, startCol: 6, // Skip column 5 (spacing)
            pcs: [
                ['2-PC-025', '2-PC-036'],
                ['2-PC-026', '2-PC-035'],
                ['2-PC-027', '2-PC-034'],
                ['2-PC-028', '2-PC-033'],
                ['2-PC-029', '2-PC-032'],
                ['2-PC-030', '2-PC-031']
            ]
        }
    ];
    
    // Create Training Room grid items
    const trainingGridItems = [];
    for (let row = 0; row < 6; row++) {
        for (let col = 0; col < 8; col++) { // Changed to 8 columns to accommodate spacing
            trainingGridItems.push({ row, col, pc: null });
        }
    }
    
    // Place Training Room PC buttons according to groups
    trainingGroups.forEach(group => {
        group.pcs.forEach((pcRow, rowIndex) => {
            pcRow.forEach((pcId, colIndex) => {
                const gridRow = group.startRow + rowIndex;
                const gridCol = group.startCol + colIndex;
                const gridIndex = gridRow * 8 + gridCol; // Changed to 8 columns
                
                if (gridIndex < trainingGridItems.length && gridRow < 6 && gridCol < 8) { // Changed to 8 columns
                    trainingGridItems[gridIndex].pc = pcId;
                }
            });
        });
    });
    
    // Create DOM elements for Training Room buttons
    trainingGridItems.forEach(item => {
        const div = document.createElement('div');
        div.style.gridRow = item.row + 1;
        div.style.gridColumn = item.col + 1;
        
        if (item.pc) {
            const button = document.createElement('button');
            button.className = 'pc-button offline';
            button.id = `pc-${item.pc}`;
            button.innerHTML = `
                <div>${item.pc}</div>
                <div class="pc-status">Offline</div>
            `;
            button.addEventListener('click', () => selectPC(item.pc));
            div.appendChild(button);
        }
        
        trainingGrid.appendChild(div);
    });
    
    trainingSection.appendChild(trainingGrid);
    buttonGrid.appendChild(trainingSection);
    
    // Create Production Area section (PCs 37-84)
    const productionSection = document.createElement('div');
    productionSection.className = 'production-area-section';
    productionSection.innerHTML = '<h3 class="section-title">PRODUCTION AREA</h3>';
    
    const productionGrid = document.createElement('div');
    productionGrid.className = 'production-area-grid';
    
    // Define the Production Area PC layout groups
    const productionGroups = [
        // First production group (6x2 block)
        {
            startRow: 0, startCol: 0,
            pcs: [
                ['2-PC-037', '2-PC-038', '2-PC-039', '2-PC-040', '2-PC-041', '2-PC-042'],
                ['2-PC-048', '2-PC-047', '2-PC-046', '2-PC-045', '2-PC-044', '2-PC-043']
            ]
        },
        // Second production group (6x2 block)
        {
            startRow: 3, startCol: 0,
            pcs: [
                ['2-PC-049', '2-PC-050', '2-PC-051', '2-PC-052', '2-PC-053', '2-PC-054'],
                ['2-PC-060', '2-PC-059', '2-PC-058', '2-PC-057', '2-PC-056', '2-PC-055']
            ]
        },
        // Third production group (6x2 block)
        {
            startRow: 6, startCol: 0,
            pcs: [
                ['2-PC-061', '2-PC-062', '2-PC-063', '2-PC-064', '2-PC-065', '2-PC-066'],
                ['2-PC-072', '2-PC-071', '2-PC-070', '2-PC-069', '2-PC-068', '2-PC-067']
            ]
        },
        // Fourth production group (6x2 block)
        {
            startRow: 9, startCol: 0,
            pcs: [
                ['2-PC-073', '2-PC-074', '2-PC-075', '2-PC-076', '2-PC-077', '2-PC-078'],
                ['2-PC-084', '2-PC-083', '2-PC-082', '2-PC-081', '2-PC-080', '2-PC-079']
            ]
        }
    ];
    
    // Create Production Area grid items
    const productionGridItems = [];
    for (let row = 0; row < 12; row++) {
        for (let col = 0; col < 6; col++) {
            productionGridItems.push({ row, col, pc: null });
        }
    }
    
    // Place Production Area PC buttons according to groups
    productionGroups.forEach(group => {
        group.pcs.forEach((pcRow, rowIndex) => {
            pcRow.forEach((pcId, colIndex) => {
                const gridRow = group.startRow + rowIndex;
                const gridCol = group.startCol + colIndex;
                const gridIndex = gridRow * 6 + gridCol;
                
                if (gridIndex < productionGridItems.length && gridRow < 12 && gridCol < 6) {
                    productionGridItems[gridIndex].pc = pcId;
                }
            });
        });
    });
    
    // Create DOM elements for Production Area buttons
    productionGridItems.forEach(item => {
        const div = document.createElement('div');
        div.style.gridRow = item.row + 1;
        div.style.gridColumn = item.col + 1;
        
        if (item.pc) {
            const button = document.createElement('button');
            button.className = 'pc-button offline';
            button.id = `pc-${item.pc}`;
            button.innerHTML = `
                <div>${item.pc}</div>
                <div class="pc-status">Offline</div>
            `;
            button.addEventListener('click', () => selectPC(item.pc));
            div.appendChild(button);
        }
        
        productionGrid.appendChild(div);
    });
    
    productionSection.appendChild(productionGrid);
    buttonGrid.appendChild(productionSection);
    
    console.log('Generated Second Floor layout with separated Training Room and Production Area sections');
}
// Add this new function after the generateSecondFloorLayout function:

function generateThirdFloorLayout(buttonGrid) {
    console.log("generateThirdFloorLayout called for frame 3");
    
    // Clear the existing content
    buttonGrid.innerHTML = '';
    
    // Create Computer Lab section (PCs 1-36)
    const computerLabSection = document.createElement('div');
    computerLabSection.className = 'computer-lab-section';
    computerLabSection.innerHTML = '<h3 class="section-title">LEFT SIDE PRODUCTION AREA</h3>';
    
    const computerLabGrid = document.createElement('div');
    computerLabGrid.className = 'computer-lab-grid';
    
    // Define the Computer Lab PC layout groups
    const computerLabGroups = [
        // Left group (COMPUTER LAB - left side)
        {
            startRow: 0, startCol: 0,
            pcs: [
                ['3-PC-008', '3-PC-016'],
                ['3-PC-007', '3-PC-015'],
                ['3-PC-006', '3-PC-014'],
                ['3-PC-005', '3-PC-013'],
                ['3-PC-004', '3-PC-012'],
                ['3-PC-003', '3-PC-011'],
                ['3-PC-002', '3-PC-010'],
                ['3-PC-001', '3-PC-009']
            ]
        },
        // Center group (COMPUTER LAB - center)
        {
            startRow: 0, startCol: 3, // Skip column 2 (spacing)
            pcs: [
                ['3-PC-022', '3-PC-028'],
                ['3-PC-021', '3-PC-027'],
                ['3-PC-020', '3-PC-026'],
                ['3-PC-019', '3-PC-025'],
                ['3-PC-018', '3-PC-024'],
                ['3-PC-017', '3-PC-023']
            ]
        },
        // Right group (COMPUTER LAB - right side)
        {
            startRow: 0, startCol: 6, // Skip column 5 (spacing)
            pcs: [
                ['3-PC-038', '3-PC-048'],
                ['3-PC-037', '3-PC-047'],
                ['3-PC-036', '3-PC-046'],
                ['3-PC-035', '3-PC-045'],
                ['3-PC-034', '3-PC-044'],
                ['3-PC-033', '3-PC-043'],
                ['3-PC-032', '3-PC-042'],
                ['3-PC-031', '3-PC-041'],
                ['3-PC-030', '3-PC-040'],
                ['3-PC-029', '3-PC-039']
            ]
        }
    ];
    
    // Create Computer Lab grid items
    const computerLabGridItems = [];
    for (let row = 0; row < 10; row++) {
        for (let col = 0; col < 8; col++) { // Changed to 8 columns to accommodate spacing
            computerLabGridItems.push({ row, col, pc: null });
        }
    }
    
    // Place Computer Lab PC buttons according to groups
    computerLabGroups.forEach(group => {
        group.pcs.forEach((pcRow, rowIndex) => {
            pcRow.forEach((pcId, colIndex) => {
                const gridRow = group.startRow + rowIndex;
                const gridCol = group.startCol + colIndex;
                const gridIndex = gridRow * 8 + gridCol; // Changed to 8 columns
                
                if (gridIndex < computerLabGridItems.length && gridRow < 10 && gridCol < 8) { // Changed to 10 rows
                    computerLabGridItems[gridIndex].pc = pcId;
                }
            });
        });
    });
    
    // Create DOM elements for Computer Lab buttons
    computerLabGridItems.forEach(item => {
        const div = document.createElement('div');
        div.style.gridRow = item.row + 1;
        div.style.gridColumn = item.col + 1;
        
        if (item.pc) {
            const button = document.createElement('button');
            button.className = 'pc-button offline';
            button.id = `pc-${item.pc}`;
            button.innerHTML = `
                <div>${item.pc}</div>
                <div class="pc-status">Offline</div>
            `;
            button.addEventListener('click', () => selectPC(item.pc));
            div.appendChild(button);
        }
        
        computerLabGrid.appendChild(div);
    });
    
    computerLabSection.appendChild(computerLabGrid);
    buttonGrid.appendChild(computerLabSection);
    
    // Create Development Zone section (PCs 49-164)
    const developmentZoneSection = document.createElement('div');
    developmentZoneSection.className = 'development-zone-section';
    developmentZoneSection.innerHTML = '<h3 class="section-title">RIGHT SIDE PRODUCTION AREA</h3>';
    
    const developmentZoneGrid = document.createElement('div');
    developmentZoneGrid.className = 'development-zone-grid';
    
    // Define the Development Zone PC layout groups following your specified pattern
    const developmentZoneGroups = [
        // First group (6x2 block)
        {
            startRow: 0, startCol: 0,
            pcs: [
                ['3-PC-049', '3-PC-050', '3-PC-051', '3-PC-052', '3-PC-053', '3-PC-054'],
                ['3-PC-060', '3-PC-059', '3-PC-058', '3-PC-057', '3-PC-056', '3-PC-055']
            ]
        },
        // Second group (6x2 block)
        {
            startRow: 3, startCol: 0,
            pcs: [
                ['3-PC-061', '3-PC-062', '3-PC-063', '3-PC-064', '3-PC-065', '3-PC-066'],
                ['3-PC-072', '3-PC-071', '3-PC-070', '3-PC-069', '3-PC-068', '3-PC-067']
            ]
        },
        // Third group (8x2 block)
        {
            startRow: 6, startCol: 0,
            pcs: [
                ['3-PC-073', '3-PC-074', '3-PC-075', '3-PC-076', '3-PC-077', '3-PC-078', '3-PC-079', '3-PC-080'],
                ['3-PC-088', '3-PC-087', '3-PC-086', '3-PC-085', '3-PC-084', '3-PC-083', '3-PC-082', '3-PC-081']
            ]
        },
        // Fourth group (8x2 block)
        {
            startRow: 9, startCol: 0,
            pcs: [
                ['3-PC-096', '3-PC-095', '3-PC-094', '3-PC-093', '3-PC-092', '3-PC-091', '3-PC-090', '3-PC-089'],
                ['3-PC-097', '3-PC-098', '3-PC-099', '3-PC-100', '3-PC-101', '3-PC-102', '3-PC-103', '3-PC-104']
            ]
        },
        // Fifth group (8x2 block)
        {
            startRow: 12, startCol: 0,
            pcs: [
                ['3-PC-112', '3-PC-111', '3-PC-110', '3-PC-109', '3-PC-108', '3-PC-107', '3-PC-106', '3-PC-105'],
                ['3-PC-113', '3-PC-114', '3-PC-115', '3-PC-116', '3-PC-117', '3-PC-118', '3-PC-119', '3-PC-120']
            ]
        },
        // Sixth group (8x2 block)
        {
            startRow: 15, startCol: 0,
            pcs: [
                ['3-PC-128', '3-PC-127', '3-PC-126', '3-PC-125', '3-PC-124', '3-PC-123', '3-PC-122', '3-PC-121'],
                ['3-PC-129', '3-PC-130', '3-PC-131', '3-PC-132', '3-PC-133', '3-PC-134', '3-PC-135', '3-PC-136']
            ]
        },
        // Seventh group (6x2 block)
        {
            startRow: 18, startCol: 0,
            pcs: [
                ['3-PC-142', '3-PC-141', '3-PC-140', '3-PC-139', '3-PC-138', '3-PC-137'],
                ['3-PC-143', '3-PC-144', '3-PC-145', '3-PC-146', '3-PC-147', '3-PC-148']
            ]
        },
        // Eighth group (8x2 block)
        {
            startRow: 21, startCol: 0,
            pcs: [
                ['3-PC-156', '3-PC-155', '3-PC-154', '3-PC-153', '3-PC-152', '3-PC-151', '3-PC-150', '3-PC-149'],
                ['3-PC-157', '3-PC-158', '3-PC-159', '3-PC-160', '3-PC-161', '3-PC-162', '3-PC-163', '3-PC-164']
            ]
        }
    ];
    
    // Create Development Zone grid items (expanded to accommodate 8 columns and more rows)
    const developmentZoneGridItems = [];
    for (let row = 0; row < 24; row++) {
        for (let col = 0; col < 8; col++) {
            developmentZoneGridItems.push({ row, col, pc: null });
        }
    }
    
    // Place Development Zone PC buttons according to groups
    developmentZoneGroups.forEach(group => {
        group.pcs.forEach((pcRow, rowIndex) => {
            pcRow.forEach((pcId, colIndex) => {
                const gridRow = group.startRow + rowIndex;
                const gridCol = group.startCol + colIndex;
                const gridIndex = gridRow * 8 + gridCol;
                
                if (gridIndex < developmentZoneGridItems.length && gridRow < 24 && gridCol < 8) {
                    developmentZoneGridItems[gridIndex].pc = pcId;
                }
            });
        });
    });
    
    // Create DOM elements for Development Zone buttons
    developmentZoneGridItems.forEach(item => {
        const div = document.createElement('div');
        div.style.gridRow = item.row + 1;
        div.style.gridColumn = item.col + 1;
        
        if (item.pc) {
            const button = document.createElement('button');
            button.className = 'pc-button offline';
            button.id = `pc-${item.pc}`;
            button.innerHTML = `
                <div>${item.pc}</div>
                <div class="pc-status">Offline</div>
            `;
            button.addEventListener('click', () => selectPC(item.pc));
            div.appendChild(button);
        }
        
        developmentZoneGrid.appendChild(div);
    });
    
    developmentZoneSection.appendChild(developmentZoneGrid);
    buttonGrid.appendChild(developmentZoneSection);
    
    console.log('Generated Third Floor layout with updated Development Zone (PCs 49-164)');
}
        // Setup frame switching functionality
        function setupFrameSwitching() {
            const frameTabs = document.querySelectorAll('.frame-tab');
            const frameContainers = document.querySelectorAll('.frame-container');

            frameTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const frameNumber = parseInt(tab.dataset.frame);
                    
                    // Update active tab
                    frameTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Update active frame
                    frameContainers.forEach(f => f.classList.remove('active'));
                    document.getElementById(`frame-${frameNumber}`).classList.add('active');
                    
                    currentFrame = frameNumber;
                    
                    showStatus(`Switched to Frame ${frameNumber}`, "info");
                });
            });
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
            
            pcData[log.pc_id] = {
                ...log,
                lastUpdate: new Date().toLocaleTimeString()
            };
            
            pcButton.className = `pc-button ${log.status.toLowerCase()}`;
            pcButton.innerHTML = `
                <div>${log.pc_id}</div>
                <div class="pc-status">${log.status}</div>
            `;
            
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
                
                const isOnline = data.status === 'Active' || data.status === 'Idle';
                captureBtn.disabled = !isOnline;
                openPngBtn.disabled = false;
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
            
            if (isStreaming) {
                stopStream();
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            closePanel.addEventListener('click', closeSidePanel);
            overlay.addEventListener('click', closeSidePanel);
            
            captureBtn.addEventListener('click', requestScreenshot);
            openPngBtn.addEventListener('click', openLatestPNG);
            streamBtn.addEventListener('click', toggleStream);
            
            maximizeBtn.addEventListener('click', toggleMaximize);
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (isVideoMaximized) {
                        toggleMaximize();
                    } else {
                        closeSidePanel();
                    }
                }
            });
            
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
                    
                    console.log("Found PNG files:", pngLinks.map(link => link.textContent.trim()));
                    
                    const pngFiles = [];
                    
                    pngLinks.forEach(link => {
                        const filename = link.textContent.trim();
                        let date = null;
                        
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
                            match = filename.match(/(\d{8,14})/);
                            if (match) {
                                const timestamp = match[1];
                                if (timestamp.length >= 8) {
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
                            console.warn(`Could not parse timestamp from: ${filename}`);
                            pngFiles.push({
                                filename,
                                href: link.getAttribute('href'),
                                date: new Date(0),
                                dateString: "No timestamp found"
                            });
                        }
                    });
                    
                    if (pngFiles.length === 0) {
                        throw new Error("No PNG files found");
                    }
                    
                    console.log("Processed PNG files:", pngFiles);
                    
                    pngFiles.sort((a, b) => {
                        const timeA = a.date.getTime();
                        const timeB = b.date.getTime();
                        
                        if (timeA === timeB) {
                            return b.filename.localeCompare(a.filename);
                        }
                        
                        return timeB - timeA;
                    });
                    
                    console.log("Sorted PNG files (newest first):", pngFiles.map(f => ({
                        filename: f.filename,
                        date: f.dateString,
                        timestamp: f.date.getTime()
                    })));
                    
                    const latestPng = pngFiles[0];
                    
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
            
            if (isVideoMaximized) {
                toggleMaximize();
            }
            
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
                videoContainer.classList.remove('video-maximized');
                maximizeBtn.innerHTML = '⛶';
                maximizeBtn.title = 'Maximize';
                isVideoMaximized = false;
                
                videoFeed.width = 350;
                videoFeed.height = 197;
                
                showStatus("Video minimized", "info");
            } else {
                videoContainer.classList.add('video-maximized');
                maximizeBtn.innerHTML = '❌';
                maximizeBtn.title = 'Minimize';
                isVideoMaximized = true;
                
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