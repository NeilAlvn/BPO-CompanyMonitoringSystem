// Server Configuration
const SERVER = {
    IP: "192.168.0.34",
    PORT: 8000,
    WS_PATH: "/ws",
    WEBRTC_PATH: "/webrtc",
    SCREENSHOT_PATH: "/MONITORING SYSTEM/backend/screenshots/"
};

// Application settings
const APP_CONFIG = {
    RECONNECT_TIMEOUT: 5000,  // Time in ms to reconnect after WebSocket closes
    CANVAS_WIDTH: 1600,
    CANVAS_HEIGHT: 900
};

// Export configurations
export { SERVER, APP_CONFIG };