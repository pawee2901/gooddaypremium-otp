import requests
import json

url = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails"
email = "BentlerMaclin96@hotmail.com"

# We will test multiple potential appCode values:
# 1. chatgpt / openai
# 2. netflix
# 3. disney
# 4. true / trueid
# 5. prime

app_codes = [
    "chatgpt", "openai", "netflix", "disney", "trueid", "true", "prime", "primevideo",
    "ChatGPT", "Netflix", "Disney+", "TrueID", "Prime Video"
]

print("Probing Google Cloud Run function for getEmails...")

for code in app_codes:
    params = {
        "senderEmail": email,
        "appCode": code
    }
    
    try:
        print(f"\nTesting appCode: '{code}' for '{email}'...")
        res = requests.get(url, params=params, timeout=10)
        print(f"  Status Code: {res.status_code}")
        print(f"  Response Body (first 300 chars): {res.text[:300]}")
        
        if res.status_code == 200:
            try:
                data = res.json()
                print("  SUCCESS! Returned valid JSON.")
                with open(f"scratch/cloud_run_response_{code}.json", "w", encoding="utf-8") as f:
                    json.dump(data, f, indent=2, ensure_ascii=False)
                print(f"  Wrote response to scratch/cloud_run_response_{code}.json")
            except Exception as e:
                print(f"  Returned non-JSON response: {e}")
    except Exception as e:
        print(f"  Failed: {e}")
