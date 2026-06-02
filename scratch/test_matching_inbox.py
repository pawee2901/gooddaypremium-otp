import sys
sys.path.append("D:/otp")
from app import app
import json

client = app.test_client()

print("--- Testing /get_otp with Disney+ and dis-u1376o@lico.moe ---")
res = client.post("/get_otp", data={"email": "dis-u1376o@lico.moe", "app_name": "Disney+"})
print(f"Status Code: {res.status_code}")
data = res.get_json()
if data and "emails" in data:
    print(f"Success! Found {len(data['emails'])} matching emails.")
    for idx, mail in enumerate(data["emails"][:3]):
        print(f"  [{idx}] From: {mail.get('from')} | Subject: {mail.get('subject')} | OTP: {mail.get('otp')} | Time: {mail.get('time')}")
else:
    print(f"Failed! Response: {data}")
