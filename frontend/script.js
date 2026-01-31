// PC Monitoring System - Gold Edition - Complete JavaScript

// Global variables
let ws = null;
let selectedPC = null;
let pcData = {};
let rtcConnections = {};
let isStreaming = false;
let isVideoMaximized = false;
let currentFrame = 0;
let editMode = false;
let currentEditingSectionId = null;
let currentEditingButtonElement = null;

// PC navigation
let availablePCs = [];
let currentPCIndex = -1;

// Layout structure
let layoutData = {
    pages: [
        {
            id: 0,
            name: "First Floor",
            sections: []
        }
    ]
};

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
const videoOverlayControls = document.getElementById('videoOverlayControls');
const videoPcName = document.getElementById('videoPcName');
const videoCloseBtn = document.getElementById('videoCloseBtn');
const videoCaptureBtn = document.getElementById('videoCaptureBtn');
const videoViewLastBtn = document.getElementById('videoViewLastBtn');
const videoPrevBtn = document.getElementById('videoPrevBtn');
const videoNextBtn = document.getElementById('videoNextBtn');
const editModeToggle = document.getElementById('editModeToggle');
const frameTabs = document.getElementById('frameTabs');
const frameContainers = document.getElementById('frameContainers');

// Modal elements
const sectionModal = document.getElementById('sectionModal');
const sectionModalTitle = document.getElementById('sectionModalTitle');
const sectionNameInput = document.getElementById('sectionNameInput');
const sectionColumnsInput = document.getElementById('sectionColumnsInput');
const sectionRowsInput = document.getElementById('sectionRowsInput');
const sectionModalCancel = document.getElementById('sectionModalCancel');
const sectionModalConfirm = document.getElementById('sectionModalConfirm');

const buttonModal = document.getElementById('buttonModal');
const buttonNameInput = document.getElementById('buttonNameInput');
const buttonModalCancel = document.getElementById('buttonModalCancel');
const buttonModalConfirm = document.getElementById('buttonModalConfirm');

const pageModal = document.getElementById('pageModal');
const pageNameInput = document.getElementById('pageNameInput');
const pageModalCancel = document.getElementById('pageModalCancel');
const pageModalConfirm = document.getElementById('pageModalConfirm');

// Initialize the system
function init() {
    console.log('Initializing PC Monitoring System...');
    loadLayout(); // Auto-load layout from database
    connectWebSocket();
    setupEventListeners();
}

// Render the entire layout
function renderLayout() {
    // Render tabs
    frameTabs.innerHTML = '';
    layoutData.pages.forEach((page, index) => {
        const tab = document.createElement('button');
        tab.className = `frame-tab ${index === currentFrame ? 'active' : ''}`;
        tab.dataset.frame = index;
        tab.textContent = page.name;
        
        if (editMode && layoutData.pages.length > 1) {
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-page-btn';
            deleteBtn.innerHTML = '×';
            deleteBtn.onclick = (e) => {
                e.stopPropagation();
                deletePage(index);
            };
            tab.appendChild(deleteBtn);
        }
        
        tab.onclick = () => switchFrame(index);
        frameTabs.appendChild(tab);
    });
    
    // Add page button
    if (editMode) {
        const addBtn = document.createElement('button');
        addBtn.className = 'add-page-btn';
        addBtn.innerHTML = '➕ Add Page';
        addBtn.onclick = openPageModal;
        frameTabs.appendChild(addBtn);
    }
    
    // Render frame containers
    frameContainers.innerHTML = '';
    layoutData.pages.forEach((page, pageIndex) => {
        const frameContainer = document.createElement('div');
        frameContainer.className = `frame-container ${pageIndex === currentFrame ? 'active' : ''}`;
        frameContainer.id = `frame-${pageIndex}`;
        
        const gridContainer = document.createElement('div');
        gridContainer.className = 'grid-container';
        
        page.sections.forEach((section, sectionIndex) => {
            const sectionEl = createSectionElement(section, pageIndex, sectionIndex);
            gridContainer.appendChild(sectionEl);
        });
        
        frameContainer.appendChild(gridContainer);
        frameContainers.appendChild(frameContainer);
    });
}

// Create a section element
function createSectionElement(section, pageIndex, sectionIndex) {
    const sectionEl = document.createElement('div');
    sectionEl.className = 'grid-section';
    sectionEl.dataset.pageIndex = pageIndex;
    sectionEl.dataset.sectionIndex = sectionIndex;
    
    // Section header
    const header = document.createElement('div');
    header.className = 'section-header';
    
    const titleWrapper = document.createElement('div');
    titleWrapper.className = 'section-title-wrapper';
    
    const title = document.createElement('div');
    title.className = 'section-title';
    title.textContent = section.name;
    title.contentEditable = editMode;
    title.onblur = () => {
        layoutData.pages[pageIndex].sections[sectionIndex].name = title.textContent;
    };
    titleWrapper.appendChild(title);
    
    // Grid controls (columns adjustment)
    const gridControls = document.createElement('div');
    gridControls.className = 'section-grid-controls';
    
    const colLabel = document.createElement('label');
    colLabel.textContent = 'Cols:';
    gridControls.appendChild(colLabel);
    
    const colInput = document.createElement('input');
    colInput.type = 'number';
    colInput.value = section.columns;
    colInput.min = 1;
    colInput.max = 20;
    colInput.onchange = () => {
        const newCols = parseInt(colInput.value);
        if (newCols > 0 && newCols <= 20) {
            layoutData.pages[pageIndex].sections[sectionIndex].columns = newCols;
            renderLayout();
            showStatus(`Section columns updated to ${newCols}`, 'success');
        }
    };
    gridControls.appendChild(colInput);
    
    titleWrapper.appendChild(gridControls);
    header.appendChild(titleWrapper);
    
    const controls = document.createElement('div');
    controls.className = 'section-controls';
    
    const addRowBtn = document.createElement('button');
    addRowBtn.className = 'btn-add-row';
    addRowBtn.innerHTML = '➕ Row';
    addRowBtn.onclick = () => addRow(pageIndex, sectionIndex);
    controls.appendChild(addRowBtn);
    
    const removeRowBtn = document.createElement('button');
    removeRowBtn.className = 'btn-remove-row';
    removeRowBtn.innerHTML = '➖ Row';
    removeRowBtn.onclick = () => removeRow(pageIndex, sectionIndex);
    controls.appendChild(removeRowBtn);
    
    const addButtonBtn = document.createElement('button');
    addButtonBtn.className = 'btn-add-button';
    addButtonBtn.textContent = '➕ Button';
    addButtonBtn.onclick = () => addButton(pageIndex, sectionIndex);
    controls.appendChild(addButtonBtn);
    
    const deleteSectionBtn = document.createElement('button');
    deleteSectionBtn.className = 'btn-delete-section';
    deleteSectionBtn.textContent = '🗑️ Delete';
    deleteSectionBtn.onclick = () => deleteSection(pageIndex, sectionIndex);
    controls.appendChild(deleteSectionBtn);
    
    header.appendChild(controls);
    sectionEl.appendChild(header);
    
    // Section grid
    const grid = document.createElement('div');
    grid.className = 'section-grid';
    grid.style.gridTemplateColumns = `repeat(${section.columns}, minmax(100px, 1fr))`;
    
    section.buttons.forEach((button, buttonIndex) => {
        const buttonEl = createButtonElement(button, pageIndex, sectionIndex, buttonIndex);
        grid.appendChild(buttonEl);
    });
    
    sectionEl.appendChild(grid);
    return sectionEl;
}

// Create a button element
function createButtonElement(button, pageIndex, sectionIndex, buttonIndex) {
    const buttonEl = document.createElement('div');
    
    if (!button || !button.id) {
        // Empty slot
        buttonEl.className = 'pc-button empty-slot';
        buttonEl.innerHTML = '<div style="font-size: 24px; opacity: 0.3;">+</div>';
        
        if (editMode) {
            buttonEl.onclick = () => fillEmptySlot(pageIndex, sectionIndex, buttonIndex);
        }
    } else {
        // Regular PC button
        buttonEl.className = `pc-button ${button.status.toLowerCase()}`;
        buttonEl.id = `pc-${button.id}`;
        buttonEl.dataset.pageIndex = pageIndex;
        buttonEl.dataset.sectionIndex = sectionIndex;
        buttonEl.dataset.buttonIndex = buttonIndex;
        
        // Mark buttons without names as empty
        if (!button.id || button.id.trim() === '') {
            buttonEl.dataset.empty = 'true';
        }
        
        buttonEl.innerHTML = `
            <div>${button.id}</div>
            <div class="pc-status">${button.status}</div>
        `;
        
        if (editMode) {
            const editBtn = document.createElement('button');
            editBtn.className = 'edit-btn';
            editBtn.innerHTML = '✏️';
            editBtn.onclick = (e) => {
                e.stopPropagation();
                editButton(buttonEl);
            };
            buttonEl.appendChild(editBtn);
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.innerHTML = '×';
            deleteBtn.onclick = (e) => {
                e.stopPropagation();
                deleteButton(pageIndex, sectionIndex, buttonIndex);
            };
            buttonEl.appendChild(deleteBtn);
        } else {
            if (button.id && button.id.trim() !== '') {
                buttonEl.onclick = () => selectPC(button.id);
            }
        }
    }
    
    return buttonEl;
}

// Add row to section
function addRow(pageIndex, sectionIndex) {
    const section = layoutData.pages[pageIndex].sections[sectionIndex];
    const cols = section.columns;
    
    // Add empty slots for a new row
    for (let i = 0; i < cols; i++) {
        section.buttons.push(null);
    }
    
    renderLayout();
    showStatus('Row added', 'success');
}

// Remove row from section
function removeRow(pageIndex, sectionIndex) {
    const section = layoutData.pages[pageIndex].sections[sectionIndex];
    const cols = section.columns;
    
    if (section.buttons.length <= cols) {
        showStatus('Cannot remove the last row', 'error');
        return;
    }
    
    // Remove last row
    section.buttons.splice(-cols, cols);
    
    renderLayout();
    showStatus('Row removed', 'success');
}

// Fill empty slot
function fillEmptySlot(pageIndex, sectionIndex, buttonIndex) {
    currentEditingButtonElement = {
        pageIndex,
        sectionIndex,
        buttonIndex,
        isNewButton: true
    };
    
    buttonNameInput.value = '';
    buttonModal.classList.add('show');
}

// Switch frame
function switchFrame(frameIndex) {
    currentFrame = frameIndex;
    renderLayout();
    showStatus(`Switched to ${layoutData.pages[frameIndex].name}`, "info");
}

// Toggle edit mode
function toggleEditMode() {
    editMode = !editMode;
    document.body.classList.toggle('edit-mode', editMode);
    editModeToggle.classList.toggle('active', editMode);
    editModeToggle.textContent = editMode ? '👁️ View Mode' : '✏️ Edit Mode';
    renderLayout();
    showStatus(editMode ? 'Edit mode enabled' : 'Edit mode disabled', 'info');
}

// Open section modal
function openSectionModal(pageIndex, sectionIndex = null) {
    currentEditingSectionId = sectionIndex;
    
    if (sectionIndex !== null) {
        const section = layoutData.pages[pageIndex].sections[sectionIndex];
        sectionModalTitle.textContent = 'Edit Section';
        sectionNameInput.value = section.name;
        sectionColumnsInput.value = section.columns;
        sectionRowsInput.value = Math.ceil(section.buttons.length / section.columns);
    } else {
        sectionModalTitle.textContent = 'Add Section';
        sectionNameInput.value = '';
        sectionColumnsInput.value = 6;
        sectionRowsInput.value = 2;
    }
    
    sectionModal.classList.add('show');
}

// Confirm section modal
function confirmSectionModal() {
    const name = sectionNameInput.value.trim();
    const columns = parseInt(sectionColumnsInput.value);
    const rows = parseInt(sectionRowsInput.value);
    
    if (!name || columns < 1 || rows < 1) {
        showStatus('Please fill in all fields correctly', 'error');
        return;
    }
    
    if (currentEditingSectionId !== null) {
        // Edit existing section
        const section = layoutData.pages[currentFrame].sections[currentEditingSectionId];
        section.name = name;
        section.columns = columns;
        
        // Adjust buttons array
        const targetButtons = columns * rows;
        while (section.buttons.length < targetButtons) {
            section.buttons.push(null);
        }
        while (section.buttons.length > targetButtons) {
            section.buttons.pop();
        }
    } else {
        // Add new section
        const buttons = [];
        for (let i = 0; i < columns * rows; i++) {
            buttons.push(null); // Empty slots
        }
        
        layoutData.pages[currentFrame].sections.push({
            name,
            columns,
            buttons
        });
    }
    
    renderLayout();
    sectionModal.classList.remove('show');
    showStatus('Section saved', 'success');
}

// Delete section
function deleteSection(pageIndex, sectionIndex) {
    if (confirm('Are you sure you want to delete this section?')) {
        layoutData.pages[pageIndex].sections.splice(sectionIndex, 1);
        renderLayout();
        showStatus('Section deleted', 'success');
    }
}

// Add button to end of section
function addButton(pageIndex, sectionIndex) {
    layoutData.pages[pageIndex].sections[sectionIndex].buttons.push({
        id: `PC-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        status: 'Offline'
    });
    renderLayout();
    showStatus('Button added', 'success');
}

// Edit button
function editButton(buttonElement) {
    currentEditingButtonElement = buttonElement;
    const pageIndex = parseInt(buttonElement.dataset.pageIndex);
    const sectionIndex = parseInt(buttonElement.dataset.sectionIndex);
    const buttonIndex = parseInt(buttonElement.dataset.buttonIndex);
    
    const button = layoutData.pages[pageIndex].sections[sectionIndex].buttons[buttonIndex];
    buttonNameInput.value = button.id;
    buttonModal.classList.add('show');
}

// Confirm button modal
function confirmButtonModal() {
    const newId = buttonNameInput.value.trim();
    
    if (!newId) {
        showStatus('Please enter a PC ID', 'error');
        return;
    }
    
    if (currentEditingButtonElement.isNewButton) {
        // Adding to empty slot
        const { pageIndex, sectionIndex, buttonIndex } = currentEditingButtonElement;
        layoutData.pages[pageIndex].sections[sectionIndex].buttons[buttonIndex] = {
            id: newId,
            status: 'Offline'
        };
    } else {
        // Editing existing button
        const pageIndex = parseInt(currentEditingButtonElement.dataset.pageIndex);
        const sectionIndex = parseInt(currentEditingButtonElement.dataset.sectionIndex);
        const buttonIndex = parseInt(currentEditingButtonElement.dataset.buttonIndex);
        
        const oldId = layoutData.pages[pageIndex].sections[sectionIndex].buttons[buttonIndex].id;
        layoutData.pages[pageIndex].sections[sectionIndex].buttons[buttonIndex].id = newId;
        
        // Update pcData if it exists
        if (pcData[oldId]) {
            pcData[newId] = pcData[oldId];
            delete pcData[oldId];
        }
    }
    
    renderLayout();
    buttonModal.classList.remove('show');
    showStatus('Button updated', 'success');
}

// Delete button (convert to empty slot)
function deleteButton(pageIndex, sectionIndex, buttonIndex) {
    if (confirm('Are you sure you want to delete this button?')) {
        layoutData.pages[pageIndex].sections[sectionIndex].buttons[buttonIndex] = null;
        renderLayout();
        showStatus('Button deleted', 'success');
    }
}

// Open page modal
function openPageModal() {
    pageNameInput.value = '';
    pageModal.classList.add('show');
}

// Confirm page modal
function confirmPageModal() {
    const name = pageNameInput.value.trim();
    
    if (!name) {
        showStatus('Please enter a page name', 'error');
        return;
    }
    
    layoutData.pages.push({
        id: layoutData.pages.length,
        name,
        sections: []
    });
    
    renderLayout();
    pageModal.classList.remove('show');
    showStatus('Page added', 'success');
}

// Delete page
function deletePage(pageIndex) {
    if (confirm('Are you sure you want to delete this page?')) {
        layoutData.pages.splice(pageIndex, 1);
        if (currentFrame >= layoutData.pages.length) {
            currentFrame = layoutData.pages.length - 1;
        }
        renderLayout();
        showStatus('Page deleted', 'success');
    }
}

// Save layout to database
async function saveLayout() {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'save_layout',
                layout: layoutData
            })
        });
        
        const result = await response.json();
        if (result.success) {
            showStatus('Layout saved to database successfully', 'success');
        } else {
            showStatus('Failed to save layout: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error saving layout:', error);
        showStatus('Error saving layout to database', 'error');
    }
}

// Load layout from database
async function loadLayout() {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'load_layout'
            })
        });
        
        const result = await response.json();
        if (result.success && result.layout) {
            layoutData = result.layout;
            renderLayout();
            showStatus('Layout loaded from database successfully', 'success');
        } else {
            // No saved layout, render default
            renderLayout();
            console.log('No saved layout found, using default');
        }
    } catch (error) {
        console.error('Error loading layout:', error);
        renderLayout(); // Render default on error
        showStatus('Error loading layout from database', 'error');
    }
}

// WebSocket connection
function connectWebSocket() {
    try {
        ws = new WebSocket("ws://172.16.2.240:8000/ws");
        
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
    
    pcButton.className = pcButton.className.replace(/\b(active|idle|offline|streaming)\b/g, '');
    pcButton.classList.add(log.status.toLowerCase());
    
    const statusEl = pcButton.querySelector('.pc-status');
    if (statusEl) {
        statusEl.textContent = log.status;
    }
    
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
function selectPC(pcId, skipPanelOpen = false) {
    selectedPC = pcId;
    panelTitle.textContent = `${pcId} - Actions`;
    updatePanelInfo(pcId);
    
    if (!skipPanelOpen) {
        openSidePanel();
    }
    
    // Update PC navigation buttons
    updatePCNavigationButtons();
}

// Update panel info
function updatePanelInfo(pcIdentifier) {
    const data = pcData[pcIdentifier];
    
    document.getElementById('pcId').textContent = pcIdentifier;
    
    if (data) {
        document.getElementById('pcStatus').textContent = data.status;
        document.getElementById('pcWindow').textContent = data.active_window || 'Unknown';
        document.getElementById('pcLastUpdate').textContent = data.lastUpdate;
        
        const isOnline = data.status === 'Active' || data.status === 'Idle';
        captureBtn.disabled = !isOnline;
        openPngBtn.disabled = false;
        streamBtn.disabled = !isOnline;
    } else {
        document.getElementById('pcStatus').textContent = 'Offline';
        document.getElementById('pcWindow').textContent = 'N/A';
        document.getElementById('pcLastUpdate').textContent = 'Never';
        
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
    
    // Video overlay control buttons
    videoCloseBtn.addEventListener('click', toggleMaximize);
    videoCaptureBtn.addEventListener('click', () => {
        if (selectedPC) {
            requestScreenshot();
            showStatus('Screenshot captured!', 'success');
        }
    });
    videoViewLastBtn.addEventListener('click', () => {
        if (selectedPC) {
            openLatestPNG();
        }
    });
    videoPrevBtn.addEventListener('click', navigatePreviousPC);
    videoNextBtn.addEventListener('click', navigateNextPC);
    
    editModeToggle.addEventListener('click', toggleEditMode);
    
    document.getElementById('addSectionBtn').addEventListener('click', () => {
        openSectionModal(currentFrame);
    });
    
    document.getElementById('saveLayoutBtn').addEventListener('click', saveLayout);
    document.getElementById('loadLayoutBtn').addEventListener('click', loadLayout);
    
    // Section modal
    sectionModalCancel.addEventListener('click', () => {
        sectionModal.classList.remove('show');
    });
    sectionModalConfirm.addEventListener('click', confirmSectionModal);
    
    // Button modal
    buttonModalCancel.addEventListener('click', () => {
        buttonModal.classList.remove('show');
    });
    buttonModalConfirm.addEventListener('click', confirmButtonModal);
    
    // Page modal
    pageModalCancel.addEventListener('click', () => {
        pageModal.classList.remove('show');
    });
    pageModalConfirm.addEventListener('click', confirmPageModal);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (sectionModal.classList.contains('show')) {
                sectionModal.classList.remove('show');
            } else if (buttonModal.classList.contains('show')) {
                buttonModal.classList.remove('show');
            } else if (pageModal.classList.contains('show')) {
                pageModal.classList.remove('show');
            } else if (isVideoMaximized) {
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
    
    // Handle window resize for maximized video
    window.addEventListener('resize', function() {
        if (isVideoMaximized) {
            // Recalculate dimensions while maintaining aspect ratio
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight - 60; // Account for padding
            const aspectRatio = 16 / 9;
            
            let canvasWidth, canvasHeight;
            const viewportRatio = viewportWidth / viewportHeight;
            
            if (viewportRatio > aspectRatio) {
                canvasHeight = viewportHeight;
                canvasWidth = canvasHeight * aspectRatio;
            } else {
                canvasWidth = viewportWidth;
                canvasHeight = canvasWidth / aspectRatio;
            }
            
            videoFeed.width = Math.floor(canvasWidth);
            videoFeed.height = Math.floor(canvasHeight);
            videoFeed.style.width = Math.floor(canvasWidth) + 'px';
            videoFeed.style.height = Math.floor(canvasHeight) + 'px';
        }
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
            
            const pngFiles = pngLinks.map(link => {
                const filename = link.textContent.trim();
                let date = null;
                
                const match = filename.match(/(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})/);
                if (match) {
                    const [_, year, month, day, hour, minute, second] = match;
                    date = new Date(year, month-1, day, hour, minute, second);
                }
                
                return {
                    filename,
                    href: link.getAttribute('href'),
                    url: new URL(link.getAttribute('href'), window.location.origin + folderPath).href,
                    date: date || new Date(0)
                };
            });
            
            pngFiles.sort((a, b) => b.date - a.date);
            
            const latestPng = pngFiles[0];
            window.open(latestPng.url, "_blank");
            
            showStatus(`Opening latest: ${latestPng.filename}`, "success");
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
function startStream(skipLayoutSetup = false) {
    if (!selectedPC) return;
    
    try {
        const rtcWs = new WebSocket("ws://172.16.2.240:8000/webrtc");
        
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
            
            // Only set display and add class if not skipping layout
            if (!skipLayoutSetup) {
                videoContainer.style.display = "block";
            }
            
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
            if (!skipLayoutSetup) {
                stopStream();
            }
        };
        
        rtcWs.onerror = function(error) {
            console.error("WebRTC error:", error);
            showStatus("Failed to start stream", "error");
            if (!skipLayoutSetup) {
                stopStream();
            }
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
    
    // Don't close maximized view, just stop streaming
    // if (isVideoMaximized) {
    //     toggleMaximize();
    // }
    
    streamBtn.textContent = "📺 Start Live Stream";
    streamBtn.classList.remove('active');
    
    // Only hide video container if not maximized
    if (!isVideoMaximized) {
        videoContainer.style.display = "none";
    }
    
    if (selectedPC) {
        const pcButton = document.getElementById(`pc-${selectedPC}`);
        if (pcButton) {
            pcButton.classList.remove('streaming');
        }
    }
}

// Navigate to previous PC
function navigatePreviousPC() {
    // Get all available PCs that are active or idle (not offline)
    const allPCs = [];
    layoutData.pages.forEach(page => {
        page.sections.forEach(section => {
            section.buttons.forEach(button => {
                if (button && button.id && button.id.trim() !== '') {
                    // Check ACTUAL status from DOM element
                    const pcButton = document.getElementById(`pc-${button.id}`);
                    if (pcButton) {
                        const isOnline = pcButton.classList.contains('active') || pcButton.classList.contains('idle');
                        if (isOnline) {
                            allPCs.push(button.id);
                        }
                    }
                }
            });
        });
    });
    
    if (allPCs.length === 0) {
        showStatus('No active/idle PCs available', 'error');
        return;
    }
    
    let currentIndex = allPCs.indexOf(selectedPC);
    
    // If current PC is offline (not in list), go to last online PC
    if (currentIndex === -1) {
        const prevPC = allPCs[allPCs.length - 1];
        navigateToPC(prevPC);
        return;
    }
    
    if (currentIndex > 0) {
        // Go to previous PC
        const prevPC = allPCs[currentIndex - 1];
        navigateToPC(prevPC);
    } else {
        showStatus('Already at first active/idle PC', 'info');
    }
    
    updatePCNavigationButtons();
}

// Navigate to next PC
function navigateNextPC() {
    // Get all available PCs that are active or idle (not offline)
    const allPCs = [];
    layoutData.pages.forEach(page => {
        page.sections.forEach(section => {
            section.buttons.forEach(button => {
                if (button && button.id && button.id.trim() !== '') {
                    // Check ACTUAL status from DOM element
                    const pcButton = document.getElementById(`pc-${button.id}`);
                    if (pcButton) {
                        const isOnline = pcButton.classList.contains('active') || pcButton.classList.contains('idle');
                        if (isOnline) {
                            allPCs.push(button.id);
                        }
                    }
                }
            });
        });
    });
    
    if (allPCs.length === 0) {
        showStatus('No active/idle PCs available', 'error');
        return;
    }
    
    let currentIndex = allPCs.indexOf(selectedPC);
    
    // If current PC is offline (not in list), go to first online PC
    if (currentIndex === -1) {
        const nextPC = allPCs[0];
        navigateToPC(nextPC);
        return;
    }
    
    if (currentIndex < allPCs.length - 1) {
        // Go to next PC
        const nextPC = allPCs[currentIndex + 1];
        navigateToPC(nextPC);
    } else {
        showStatus('Already at last active/idle PC', 'info');
    }
    
    updatePCNavigationButtons();
}

// Helper function to navigate to a PC
function navigateToPC(targetPC) {
    // If streaming and maximized, switch stream without closing
    if (isStreaming && isVideoMaximized) {
        // Close old stream connection
        if (rtcConnections[selectedPC]) {
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
        
        // Update selected PC
        selectedPC = targetPC;
        
        // Update PC name in video overlay
        if (videoPcName) {
            videoPcName.textContent = targetPC;
        }
        
        // Update panel info
        panelTitle.textContent = `${targetPC} - Actions`;
        updatePanelInfo(targetPC);
        
        // Start new stream with skipLayoutSetup to keep maximized state
        setTimeout(() => {
            startStream(true);
        }, 300);
        
        showStatus(`Switched to ${targetPC}`, "success");
    } else {
        // Not streaming or not maximized, use normal select
        selectPC(targetPC, true);
        
        if (videoPcName) {
            videoPcName.textContent = targetPC;
        }
        
        showStatus(`Switched to ${targetPC}`, "success");
    }
}

// Update PC navigation button states
function updatePCNavigationButtons() {
    if (!videoPrevBtn || !videoNextBtn) {
        console.log('Navigation buttons not found');
        return;
    }
    
    // Get all available PCs that are active or idle (not offline)
    const allPCs = [];
    layoutData.pages.forEach(page => {
        page.sections.forEach(section => {
            section.buttons.forEach(button => {
                if (button && button.id && button.id.trim() !== '') {
                    // Check ACTUAL status from DOM element (updated by WebSocket)
                    const pcButton = document.getElementById(`pc-${button.id}`);
                    if (pcButton) {
                        const isActive = pcButton.classList.contains('active');
                        const isIdle = pcButton.classList.contains('idle');
                        const isOnline = isActive || isIdle;
                        
                        console.log(`PC: ${button.id}, Active: ${isActive}, Idle: ${isIdle}, Online: ${isOnline}`);
                        
                        if (isOnline) {
                            allPCs.push(button.id);
                        }
                    }
                }
            });
        });
    });
    
    console.log('Online PCs:', allPCs);
    console.log('Current PC:', selectedPC);
    console.log('Total online PCs:', allPCs.length);
    
    // Simple logic: enable if 2+ online PCs
    const shouldEnable = allPCs.length >= 2;
    
    videoPrevBtn.disabled = !shouldEnable;
    videoNextBtn.disabled = !shouldEnable;
    
    // Force remove disabled attribute if should be enabled
    if (shouldEnable) {
        videoPrevBtn.removeAttribute('disabled');
        videoNextBtn.removeAttribute('disabled');
        console.log('ENABLED navigation buttons');
    } else {
        console.log('DISABLED navigation buttons - only', allPCs.length, 'online PC(s)');
    }
    
    // Update tooltips
    if (allPCs.length >= 2) {
        const currentIndex = allPCs.indexOf(selectedPC);
        if (currentIndex === -1 || currentIndex === 0) {
            videoPrevBtn.title = `Previous PC`;
            videoNextBtn.title = `Next PC`;
        } else {
            videoPrevBtn.title = `Previous PC (${allPCs[currentIndex - 1]})`;
            videoNextBtn.title = currentIndex < allPCs.length - 1 ? `Next PC (${allPCs[currentIndex + 1]})` : `Next PC`;
        }
    } else {
        videoPrevBtn.title = 'Need 2+ online PCs';
        videoNextBtn.title = 'Need 2+ online PCs';
    }
}

// Display video frame
function displayFrame(frameData) {
    const ctx = videoFeed.getContext('2d');
    const image = new Image();
    
    image.onload = function() {
        // Only redraw if canvas dimensions match the current state
        const targetWidth = videoFeed.width;
        const targetHeight = videoFeed.height;
        
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        
        // Clear canvas first
        ctx.clearRect(0, 0, targetWidth, targetHeight);
        
        // Draw image to fit canvas
        ctx.drawImage(image, 0, 0, targetWidth, targetHeight);
    };
    
    image.src = "data:image/jpeg;base64," + frameData;
}

// Toggle maximize video
function toggleMaximize() {
    if (isVideoMaximized) {
        videoContainer.style.position = '';
        videoContainer.style.top = '';
        videoContainer.style.left = '';
        videoContainer.style.width = '';
        videoContainer.style.height = '';
        videoContainer.style.background = '';
        videoContainer.style.zIndex = '';
        videoContainer.style.display = 'block';
        videoContainer.style.alignItems = '';
        videoContainer.style.justifyContent = '';
        videoContainer.style.padding = '';
        videoContainer.style.margin = '';
        videoContainer.style.borderRadius = '';
        videoContainer.style.boxSizing = '';
        videoContainer.classList.remove('maximized');
        
        // Hide overlay controls
        videoOverlayControls.style.display = 'none';
        
        maximizeBtn.innerHTML = '⛶';
        maximizeBtn.title = 'Maximize';
        isVideoMaximized = false;
        
        videoFeed.width = 390;
        videoFeed.height = 220;
        videoFeed.style.maxWidth = '100%';
        videoFeed.style.width = '100%';
        videoFeed.style.height = 'auto';
        videoFeed.style.objectFit = '';
        videoFeed.style.borderRadius = '';
        videoFeed.style.boxShadow = '';
        
        //showStatus("Video minimized", "info");
    } else {
        videoContainer.style.position = 'fixed';
        videoContainer.style.top = '0';
        videoContainer.style.left = '0';
        videoContainer.style.width = '100vw';
        videoContainer.style.height = '100vh';
        videoContainer.style.background = 'rgba(0,0,0,0.98)';
        videoContainer.style.zIndex = '3000';
        videoContainer.style.display = 'flex';
        videoContainer.style.alignItems = 'center';
        videoContainer.style.justifyContent = 'center';
        videoContainer.style.padding = '30px 0'; // Add vertical padding
        videoContainer.style.margin = '0';
        videoContainer.style.borderRadius = '0';
        videoContainer.style.boxSizing = 'border-box';
        videoContainer.classList.add('maximized');
        
        // Show overlay controls and set PC name
        videoOverlayControls.style.display = 'block';
        videoPcName.textContent = selectedPC || 'PC Monitor';
        
        // Update PC navigation buttons
        updatePCNavigationButtons();
        
        maximizeBtn.innerHTML = '❌';
        maximizeBtn.title = 'Minimize';
        isVideoMaximized = true;
        
        // Calculate dimensions to maintain aspect ratio with padding
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight - 60; // Account for 30px top + 30px bottom padding
        const aspectRatio = 16 / 9; // Standard monitor aspect ratio
        
        let canvasWidth, canvasHeight;
        
        // Fit to viewport while maintaining aspect ratio
        const viewportRatio = viewportWidth / viewportHeight;
        
        if (viewportRatio > aspectRatio) {
            // Viewport is wider - fit to height
            canvasHeight = viewportHeight;
            canvasWidth = canvasHeight * aspectRatio;
        } else {
            // Viewport is taller - fit to width
            canvasWidth = viewportWidth;
            canvasHeight = canvasWidth / aspectRatio;
        }
        
        // Set canvas dimensions
        videoFeed.width = Math.floor(canvasWidth);
        videoFeed.height = Math.floor(canvasHeight);
        videoFeed.style.width = Math.floor(canvasWidth) + 'px';
        videoFeed.style.height = Math.floor(canvasHeight) + 'px';
        videoFeed.style.maxWidth = '100vw';
        videoFeed.style.maxHeight = 'calc(100vh - 60px)';
        videoFeed.style.objectFit = 'contain';
        
        //showStatus("Video maximized - Press ESC to minimize", "info");
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

// Initialize the application when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}