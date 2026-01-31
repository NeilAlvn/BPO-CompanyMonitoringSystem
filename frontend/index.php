<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root"; // Change this to your DB username
$password = ""; // Change this to your DB password
$dbname = "monitoring_system"; // Change this to your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Process incoming JSON data if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type: application/json");
    
    // Get JSON input
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);
    
    // Handle layout saving to database
    if (isset($data['action']) && $data['action'] === 'save_layout') {
        $layout_json = json_encode($data['layout']);
        $user_id = $_SESSION['user']['id'] ?? 1; // Use logged-in user ID
        
        // Check if layout exists for this user
        $check_sql = "SELECT id FROM pc_layouts WHERE user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing layout
            $sql = "UPDATE pc_layouts SET layout_data = ?, updated_at = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $layout_json, $user_id);
        } else {
            // Insert new layout
            $sql = "INSERT INTO pc_layouts (user_id, layout_data, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $layout_json);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Layout saved to database']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save layout']);
        }
        $stmt->close();
        exit();
    }
    
    // Handle layout loading from database
    if (isset($data['action']) && $data['action'] === 'load_layout') {
        $user_id = $_SESSION['user']['id'] ?? 1;
        
        $sql = "SELECT layout_data FROM pc_layouts WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $layout = json_decode($row['layout_data'], true);
            echo json_encode(['success' => true, 'layout' => $layout]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No saved layout found']);
        }
        $stmt->close();
        exit();
    }
    
    // Process data
    $response = array(
        "data" => $data
    );
    
    // Return JSON response
    echo json_encode($response);
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Monitoring System - Teal Edition</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ PC Monitoring System</h1>
            <div id="connectionStatus" class="connection-status disconnected">
                ⚡ Connecting...
            </div>
        </div>

        <!-- Top Controls -->
        <div class="top-controls">
            <button class="edit-mode-toggle" id="editModeToggle">
                ✏️ Edit Mode
            </button>
            <form action="logout.php" method="post" style="margin: 0;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>

        <!-- Frame Navigation Tabs -->
        <div class="frame-tabs" id="frameTabs">
            <!-- Tabs will be generated dynamically -->
        </div>

        <!-- Grid Controls (visible in edit mode) -->
        <div class="grid-controls">
            <h3>📐 Flexible Layout Editor</h3>
            <div class="control-group">
                <button class="btn-add-section" id="addSectionBtn">
                    ➕ Add Section
                </button>
                <button class="btn-save-layout" id="saveLayoutBtn">
                    💾 Save to Database
                </button>
                <button class="btn-load-layout" id="loadLayoutBtn">
                    📂 Load from Database
                </button>
            </div>
            <div style="color: var(--primary-gold-dark); text-align: center; font-size: 14px; margin-top: 15px; opacity: 0.9; font-weight: 500; letter-spacing: 0.5px;">
                💡 Adjust columns dynamically • Add/remove rows • Click empty slots to add PCs
            </div>
        </div>

        <!-- Frame Containers -->
        <div id="frameContainers">
            <!-- Frame containers will be generated dynamically -->
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
            <!-- Maximized Video Overlay Controls -->
            <div class="video-overlay-controls" id="videoOverlayControls" style="display: none;">
                <!-- Top Left: PC Name and Actions -->
                <div class="video-top-left">
                    <div class="video-pc-name" id="videoPcName">PC Name</div>
                    <div class="video-action-buttons">
                        <button class="video-action-btn-small" id="videoCaptureBtn" title="Capture Screenshot">
                            Capture
                        </button>
                        <button class="video-action-btn-small" id="videoViewLastBtn" title="View Last Screenshot">
                            View Last
                        </button>
                    </div>
                </div>
                
                <!-- Top Right: Close Button -->
                <button class="video-close-btn" id="videoCloseBtn" title="Close">✕</button>
                
                <!-- Left Side: Previous Button -->
                <div class="video-nav-left">
                    <button class="video-nav-btn" id="videoPrevBtn" title="Previous PC">◀</button>
                </div>
                
                <!-- Right Side: Next Button -->
                <div class="video-nav-right">
                    <button class="video-nav-btn" id="videoNextBtn" title="Next PC">▶</button>
                </div>
            </div>
            <canvas id="videoFeed" class="video-feed" width="350" height="197"></canvas>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div class="modal" id="sectionModal">
        <div class="modal-content">
            <h3 id="sectionModalTitle">Add Section</h3>
            <label>Section Name:</label>
            <input type="text" id="sectionNameInput" placeholder="Section Name (e.g., Training Room)">
            <label>Columns:</label>
            <input type="number" id="sectionColumnsInput" placeholder="Columns" value="6" min="1" max="20">
            <label>Initial Rows:</label>
            <input type="number" id="sectionRowsInput" placeholder="Rows" value="2" min="1" max="50">
            <div class="modal-buttons">
                <button class="btn-modal-cancel" id="sectionModalCancel">Cancel</button>
                <button class="btn-modal-confirm" id="sectionModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Edit Button Modal -->
    <div class="modal" id="buttonModal">
        <div class="modal-content">
            <h3>Edit PC Button</h3>
            <label>PC ID:</label>
            <input type="text" id="buttonNameInput" placeholder="PC ID (e.g., 1-PC-001)">
            <div class="modal-buttons">
                <button class="btn-modal-cancel" id="buttonModalCancel">Cancel</button>
                <button class="btn-modal-confirm" id="buttonModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Add Page Modal -->
    <div class="modal" id="pageModal">
        <div class="modal-content">
            <h3>Add New Page</h3>
            <label>Page Name:</label>
            <input type="text" id="pageNameInput" placeholder="Page Name (e.g., Fourth Floor)">
            <div class="modal-buttons">
                <button class="btn-modal-cancel" id="pageModalCancel">Cancel</button>
                <button class="btn-modal-confirm" id="pageModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>