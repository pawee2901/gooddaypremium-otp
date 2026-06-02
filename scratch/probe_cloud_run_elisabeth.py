import requests
import json

url = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails"
email = "ElisabethwDeannahd@hotmail.com"

# We will test multiple potential appCode values:
# The frontend selection has appName. Let's see what appCodes are used.
app_codes = ["chatgpt", "openai", "netflix", "disney", "trueid", "prime"]

print("Probing Cloud Run for ElisabethwDeannahd@hotmail.com...")

for code in app_codes:
    params = {
        "senderEmail": email,
        "appCode": code
    }
    
    try:
        print(f"\nTesting appCode: '{code}'...")
        res = requests.get(url, params=params, timeout=10)
        print(f"  Status Code: {res.status_code}")
        print(f"  Response Body (first 300 chars): {res.text[:300]}")
        
        if res.status_code == 200:
            data = res.json()
            if "emails" in data and data["emails"]:
                print(f"  ⭐ SUCCESS! Found {len(data['emails'])} emails for appCode: '{code}'!")
                with open(f"scratch/cloud_run_success_{code}.json", "w", encoding="utf-8") as f:
                    json.dump(data, f, indent=2, ensure_ascii=False)
                break
    except Exception as e:
        print(f"  Failed: {e}")
