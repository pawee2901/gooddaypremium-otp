import requests
import json

base_url = "https://backend-email-e48b6-default-rtdb.asia-southeast1.firebasedatabase.app"

paths = [
    "/",
    "/emails",
    "/users",
    "/mappings",
    "/accounts",
    "/mailboxes",
    "/shops",
    "/settings"
]

print("Probing Firebase Realtime Database...")

for p in paths:
    url = f"{base_url}{p}.json"
    try:
        print(f"Testing Firebase Path: {url}")
        res = requests.get(url, timeout=5)
        print(f"  Status: {res.status_code}")
        if res.status_code == 200:
            text = res.text
            print(f"  Response (first 400 chars): {text[:400]}")
            if text != "null" and len(text) > 4:
                print(f"  ⭐ SUCCESS! Found data on path: {p}")
                with open(f"scratch/firebase_{p.replace('/', '')}.json", "w", encoding="utf-8") as f:
                    f.write(text)
    except Exception as e:
        print(f"  Failed: {e}")
