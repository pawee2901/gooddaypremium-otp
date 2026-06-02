import requests
import json

url = "https://backend-email-e48b6-default-rtdb.asia-southeast1.firebasedatabase.app/emails.json"
print(f"Fetching Firebase emails node...")
try:
    res = requests.get(url, timeout=10)
    if res.status_code == 200:
        data = res.json()
        print(f"Total accounts in database: {len(data)}")
        
        # Write to local JSON file for full examination
        with open("scratch/firebase_emails_dump.json", "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("Wrote database dump to scratch/firebase_emails_dump.json")
        
        # Search for ElisabethwDeannahd@hotmail.com
        for key, val in data.items():
            email_val = val.get("email", "").lower()
            if "elisabeth" in email_val or "bentler" in email_val:
                print(f"\n--- Found record for: {val.get('email')} ---")
                print(json.dumps({key: val}, indent=2, ensure_ascii=False))
                
    else:
        print(f"Failed with status: {res.status_code}")
except Exception as e:
    print(f"Error: {e}")
