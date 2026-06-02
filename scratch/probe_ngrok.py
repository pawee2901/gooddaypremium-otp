import requests

urls = [
    "https://otp.ngrok-free.dev",
    "https://otp.ngrok-free.app"
]

print("--- Probing user's ngrok URLs ---")
for url in urls:
    try:
        print(f"\nProbing {url}...")
        res = requests.get(url, timeout=5)
        print(f"  Status: {res.status_code}")
        print(f"  Headers: {dict(res.headers)}")
        print(f"  Body (first 200 chars): {res.text[:200]}")
    except Exception as e:
        print(f"  Failed to probe {url}: {e}")
