import requests
import json

app_url = "http://localhost:8080/get_otp"
headers = {"Content-Type": "application/x-www-form-urlencoded"}

print("Triggering /get_otp with BentlerMaclin96@hotmail.com (Cloud Run path)...")
try:
    # Since the server is not running right now in this console, we can't fetch it over localhost directly.
    # Instead, we can import app.py and trigger it using Flask's test client!
    # This is an extremely elegant and professional way to test Flask routes without starting the web server!
    import sys
    sys.path.append("D:/otp")
    from app import app
    
    client = app.test_client()
    
    # Test case 1: BentlerMaclin96@hotmail.com
    print("\n[Test 1: Unregistered / Expired Hotmail]")
    res_1 = client.post("/get_otp", data={"email": "BentlerMaclin96@hotmail.com", "app_name": "ChatGPT"})
    print(f"Status Code: {res_1.status_code}")
    print(f"JSON Response: {res_1.get_json()}")
    
    # Test case 2: phatstore-a4lrns@lico.moe
    print("\n[Test 2: Direct Maily Space Mailbox]")
    res_2 = client.post("/get_otp", data={"email": "phatstore-a4lrns@lico.moe", "app_name": "ChatGPT"})
    print(f"Status Code: {res_2.status_code}")
    # Strip body html before printing to keep console neat
    data_2 = res_2.get_json()
    if data_2 and "html_body" in data_2:
        data_2["html_body"] = f"... {len(data_2['html_body'])} characters ..."
    print(f"JSON Response: {data_2}")

except Exception as e:
    print(f"Error: {e}")
