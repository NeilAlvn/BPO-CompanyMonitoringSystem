<?php
/**
 * CSV File Upload Script
 * Allows uploading CSV files from remote computers to a specified directory
 */

// Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_EXTENSIONS', ['csv']);

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Initialize variables
$message = '';
$messageType = '';

// Process file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload failed with error code: ' . $file['error'];
        $messageType = 'error';
    }
    // Check file size
    elseif ($file['size'] > MAX_FILE_SIZE) {
        $message = 'File size exceeds maximum allowed size of ' . (MAX_FILE_SIZE / 1048576) . 'MB';
        $messageType = 'error';
    }
    // Check file extension
    else {
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            $message = 'Only CSV files are allowed';
            $messageType = 'error';
        }
        // Validate CSV content
        elseif (!validateCSV($file['tmp_name'])) {
            $message = 'Invalid CSV file format';
            $messageType = 'error';
        }
        else {
            // Generate unique filename to prevent overwrites
            $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
            $timestamp = date('Y-m-d_H-i-s');
            $newFileName = $originalName . '_' . $timestamp . '.' . $fileExtension;
            $destination = UPLOAD_DIR . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $message = 'File uploaded successfully: ' . htmlspecialchars($newFileName);
                $messageType = 'success';
            } else {
                $message = 'Failed to move uploaded file';
                $messageType = 'error';
            }
        }
    }
}

// Function to validate CSV file
function validateCSV($filePath) {
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return false;
    }
    
    // Try to read first line
    $firstLine = fgetcsv($handle);
    fclose($handle);
    
    return $firstLine !== false;
}

// Get list of uploaded files
function getUploadedFiles() {
    $files = [];
    if (is_dir(UPLOAD_DIR)) {
        $items = scandir(UPLOAD_DIR);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && pathinfo($item, PATHINFO_EXTENSION) === 'csv') {
                $filePath = UPLOAD_DIR . $item;
                $files[] = [
                    'name' => $item,
                    'size' => filesize($filePath),
                    'date' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }
    }
    return $files;
}

$uploadedFiles = getUploadedFiles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV File Upload</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 40px;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }
        
        .upload-area.dragover {
            background: #e8ebff;
            border-color: #764ba2;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .upload-text {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .upload-hint {
            color: #666;
            font-size: 14px;
        }
        
        input[type="file"] {
            display: none;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9ff;
            border-radius: 5px;
            text-align: left;
        }
        
        .file-info p {
            margin: 5px 0;
            color: #333;
        }
        
        .uploaded-files {
            margin-top: 40px;
        }
        
        .uploaded-files h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .file-list {
            background: #f8f9ff;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .file-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .file-item:hover {
            background: #f0f2ff;
        }
        
        .file-details {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .file-meta {
            font-size: 12px;
            color: #666;
        }
        
        .download-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
        }
        
        .download-script-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ebff 100%);
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .download-script-section h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .download-script-section p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .btn-download-script {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-download-script:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .no-files {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 CSV File Upload</h1>
            <p>Upload CSV files from any computer to this server</p>
        </div>
        
        <div class="content">
            <!-- Download Script Section -->
            <div class="download-script-section">
                <h2>📥 Download Required Script</h2>
                <p>Download and run this script on each PC before uploading CSV files</p>
                <a href="Export-ChromeHistory.ps1" download class="btn-download-script">Download Script</a>
            </div>
            
            <hr style="margin: 40px 0; border: none; border-top: 1px solid #e0e0e0;">
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">Click to select or drag and drop your CSV file here</div>
                    <div class="upload-hint">Maximum file size: <?php echo MAX_FILE_SIZE / 1048576; ?>MB</div>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                </div>
                
                <div id="fileInfo" class="file-info" style="display: none;">
                    <p><strong>Selected file:</strong> <span id="fileName"></span></p>
                    <p><strong>Size:</strong> <span id="fileSize"></span></p>
                </div>
                
                <center>
                    <button type="submit" class="btn" id="uploadBtn" disabled>Upload CSV File</button>
                </center>
            </form>
            
            <?php if (count($uploadedFiles) > 0): ?>
                <div class="uploaded-files">
                    <h2>Uploaded Files (<?php echo count($uploadedFiles); ?>)</h2>
                    <div class="file-list">
                        <?php foreach ($uploadedFiles as $file): ?>
                            <div class="file-item">
                                <div class="file-details">
                                    <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                                    <div class="file-meta">
                                        Size: <?php echo number_format($file['size'] / 1024, 2); ?> KB | 
                                        Uploaded: <?php echo htmlspecialchars($file['date']); ?>
                                    </div>
                                </div>
                                <a href="uploads/<?php echo urlencode($file['name']); ?>" download class="download-btn">Download</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="uploaded-files">
                    <h2>Uploaded Files</h2>
                    <div class="no-files">No files uploaded yet</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('csv_file');
        const uploadBtn = document.getElementById('uploadBtn');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        // Click to select file
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // File input change
        fileInput.addEventListener('change', handleFileSelect);
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });
        
        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
                fileInfo.style.display = 'block';
                uploadBtn.disabled = false;
            }
        }
    </script>
</body>
</html>