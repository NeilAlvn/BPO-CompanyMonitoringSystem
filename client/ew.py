import time
import psutil
import pygetwindow as gw
import requests
import pyautogui
import websockets
import asyncio
from pynput import mouse, keyboard
from datetime import datetime
import socket
import json
import os
import tempfile

# ✅ Server URLs
API_URL = "http://192.168.0.34:8000/log_activity"
SCREENSHOT_URL = "http://192.168.0.34:8000/upload_screenshot"
WS_URL = "ws://192.168.0.34:8000/ws"

# ✅ Constants
IDLE_THRESHOLD = 30  # seconds
PC_ID = socket.gethostname()
take_screenshot = False  # ✅ Changed to boolean for safer handling
screenshot_lock = asyncio.Lock()  # ✅ Prevents race conditions

# ✅ Track last activity time
last_activity_time = time.time()

def on_activity(event):
    """Updates last activity time on user activity."""
    global last_activity_time
    last_activity_time = time.time()

# ✅ Mouse and Keyboard listeners
mouse_listener = mouse.Listener(on_move=on_activity, on_click=on_activity, on_scroll=on_activity)
keyboard_listener = keyboard.Listener(on_press=on_activity)
mouse_listener.start()
keyboard_listener.start()

def get_active_window():
    """Gets the title of the active window."""
    try:
        window = gw.getActiveWindow()
        return window.title if window else "Unknown"
    except:
        return "Unknown"

def get_active_process():
    """Gets the active process name."""
    try:
        window = gw.getActiveWindow()
        if window:
            for proc in psutil.process_iter(attrs=['pid', 'name']):
                if proc.pid == window.pid:
                    return proc.info['name']
    except:
        pass
    return "Unknown"

async def capture_screenshot():
    """Takes a screenshot, uploads it, and resets the flag."""
    global take_screenshot

    async with screenshot_lock:  # ✅ Prevent multiple screenshots at the same time
        print("📸 Taking a screenshot...")

        # ✅ Use a TEMPORARY directory (cross-platform safe)
        with tempfile.NamedTemporaryFile(suffix=".png", delete=False) as temp_file:
            screenshot_path = temp_file.name  # Get temp file path

        # ✅ Take Screenshot
        pyautogui.screenshot(screenshot_path)

        try:
            # ✅ Upload Screenshot
            with open(screenshot_path, "rb") as file:
                response = requests.post(
                    SCREENSHOT_URL,
                    files={"screenshot": file},  
                    data={"pc_id": PC_ID}
                )

            if response.status_code == 200:
                print(f"✅ Screenshot uploaded successfully: {screenshot_path}")
            else:
                print(f"❌ Screenshot upload failed: {response.text}")
        except Exception as e:
            print(f"❌ Error uploading screenshot: {e}")

        # ✅ Delete local temp file after upload
        os.remove(screenshot_path)

        # ✅ Reset screenshot flag
        take_screenshot = False
        print("🔄 Screenshot flag reset to False")

def send_activity_log():
    """Sends activity data to the server immediately."""
    global take_screenshot

    active_window = get_active_window()
    active_process = get_active_process()
    idle_time = time.time() - last_activity_time
    status = "Idle" if idle_time > IDLE_THRESHOLD else "Active"
    timestamp = datetime.now().isoformat()

    data = {
        "logs": [{
            "pc_id": PC_ID,
            "active_window": active_window,
            "active_process": active_process,
            "status": status,
            "timestamp": timestamp,
            "take_screenshot": take_screenshot
        }]
    }

    try:
        response = requests.post(API_URL, json=data)
        print(f"✅ Sent: {data} | Response: {response.status_code} | Response Body: {response.text}")
    except requests.exceptions.RequestException as e:
        print(f"❌ Error sending data: {e}")

async def send_activity():
    """Sends activity data to the server every 5 seconds."""
    while True:
        send_activity_log()
        await asyncio.sleep(5)  # ✅ Regular interval

async def websocket_client():
    """Handles WebSocket communication with the server."""
    global take_screenshot

    while True:
        try:
            async with websockets.connect(WS_URL) as ws:
                await ws.send(json.dumps({"pc_id": PC_ID}))

                while True:
                    message = await ws.recv()
                    data = json.loads(message)

                    if data.get("action") == "capture_screenshot" and data.get("pc_id") == PC_ID:
                        print("📸 Screenshot request received! Triggering capture...")
                        take_screenshot = True
                        send_activity_log()  # ✅ Immediately send logs
                        asyncio.create_task(capture_screenshot())  # ✅ Take screenshot in background
        except Exception as e:
            print(f"❌ WebSocket Error: {e}")
            print("🔄 Reconnecting in 5 seconds...")
            await asyncio.sleep(5)

async def main():
    """Runs both activity monitoring and WebSocket handling in parallel."""
    await asyncio.gather(send_activity(), websocket_client())

if __name__ == "__main__":
    asyncio.run(main())
