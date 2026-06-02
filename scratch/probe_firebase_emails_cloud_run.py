import json
import requests

url = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails"
app_codes = ["DN", "TM", "NF", "GPT", "PR"]

with open("scratch/firebase_emails_dump.json", "r", encoding="utf-8") as f:
    data = json.load(f)

print(f"Total accounts to scan: {len(data)}")

found_count = 0
for key, val in data.items():
    email = val.get("email", "").strip()
    if not email:
        continue
    
    # Let's query only a subset or query until we find something
    for code in app_codes:
        params = {
            "senderEmail": email,
            "appCode": code
        }
        try:
            res = requests.get(url, params=params, timeout=5)
            if res.status_code == 200:
                res_data = res.json()
                emails = res_data.get("emails", [])
                if emails:
                    print(f"⭐ FOUND EMAILS for {email} with appCode {code}!")
                    print(json.dumps(res_data, indent=2, ensure_ascii=False))
                    found_count += 1
                    if found_count >= 5:
                        break
        except Exception as e:
            pass
    if found_count >= 5:
        break

print("Scan complete.")
