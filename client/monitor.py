import time
import psutil
import pygetwindow as gw
import requests
from pynput import mouse, keyboard
from datetime import datetime
import socket  # For PC ID

API_URL = "http://127.0.0.1:8000/log_activity/"
IDLE_THRESHOLD = 300  # 5 minutes (300 seconds)
PC_ID = socket.gethostname()  # Get PC name

last_activity_time = time.time()

def on_activity(event):
    global last_activity_time
    last_activity_time = time.time()

mouse_listener = mouse.Listener(on_move=on_activity, on_click=on_activity, on_scroll=on_activity)
keyboard_listener = keyboard.Listener(on_press=on_activity)
mouse_listener.start()
keyboard_listener.start()

def get_active_window():
    window = gw.getActiveWindow()
    return window.title if window else "Unknown"

def get_active_process():
    try:
        for proc in psutil.process_iter(attrs=['pid', 'name']):
            if proc.pid == gw.getActiveWindow().pid:
                return proc.info['name']
    except:
        return "Unknown"

def send_activity():
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
            "timestamp": timestamp
        }]
    }

    try:
        response = requests.post(API_URL, json=data)
        print(f"Sent: {data} | Response: {response.status_code} | Response Body: {response.text}")
    except requests.exceptions.RequestException as e:
        print(f"Error sending data: {e}")


def monitor_activity():
    while True:
        send_activity()
        time.sleep(5)

if __name__ == "__main__":
    monitor_activity()
