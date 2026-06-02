import requests
import re

js_url = "https://phatstore.tomoru.fun/static/js/main.fea63888.js"
print("Scanning main JS for getEmails parameter structure and appCode mapping...")
try:
    res = requests.get(js_url, timeout=10)
    
    # 1. Let's find occurrences of "appCode" or "appcode"
    # Find all places where appCode is set or used
    pos = 0
    count = 0
    while True:
        pos = res.text.find("appCode", pos)
        if pos == -1 or count >= 10:
            break
        print(f"\n--- Context for appCode at index {pos} ---")
        print(res.text[max(0, pos-250):min(len(res.text), pos+450)])
        pos += 7
        count += 1
        
except Exception as e:
    print(f"Error: {e}")
