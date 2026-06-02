import requests
import json

url = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails"
email = "ElisabethwDeannahd@hotmail.com"
app_code = "GPT"

print(f"Querying Cloud Run function with email: {email} and appCode: {app_code}...")

params = {
    "senderEmail": email,
    "appCode": app_code
}

try:
    res = requests.get(url, params=params, timeout=10)
    print(f"Status Code: {res.status_code}")
    
    if res.status_code == 200:
        data = res.json()
        print("SUCCESS! Output JSON:")
        print(json.dumps(data, indent=2, ensure_ascii=False)[:1000])
        
        # Save to file
        with open("scratch/cloud_run_real_response.json", "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("Wrote full response to scratch/cloud_run_real_response.json")
    else:
        print(f"Failed with status: {res.status_code}")
        print(res.text)
        
except Exception as e:
    print(f"Error: {e}")
