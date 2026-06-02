import requests
import json

print("--- Probing Live Render Site ---")

# We will probe both PHP and Flask endpoints to see which one is active and what they return.
url_php = "https://gooddaypremium-otp.onrender.com/get_otp.php"
url_flask = "https://gooddaypremium-otp.onrender.com/get_otp"

payload = {
    "email": "dis-u1376o@lico.moe",
    "app_name": "Disney+"
}

headers = {
    "Content-Type": "application/x-www-form-urlencoded"
}

# 1. Probe PHP
try:
    print(f"\nQuerying PHP endpoint: {url_php}...")
    res_php = requests.post(url_php, data=payload, headers=headers, timeout=10)
    print(f"Status Code: {res_php.status_code}")
    print(f"Response Headers: {dict(res_php.headers)}")
    print(f"Response Body: {res_php.text[:1000]}")
except Exception as e:
    print(f"PHP endpoint error: {e}")

# 2. Probe Flask
try:
    print(f"\nQuerying Flask endpoint: {url_flask}...")
    res_flask = requests.post(url_flask, data=payload, headers=headers, timeout=10)
    print(f"Status Code: {res_flask.status_code}")
    print(f"Response Headers: {dict(res_flask.headers)}")
    print(f"Response Body: {res_flask.text[:1000]}")
except Exception as e:
    print(f"Flask endpoint error: {e}")
