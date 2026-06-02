import json

print("Searching database dump...")
try:
    with open("scratch/firebase_emails_dump.json", "r", encoding="utf-8") as f:
        data = json.load(f)
        
    print(f"Total entries: {len(data)}")
    
    for key, val in data.items():
        email = val.get("email", "").lower()
        if "elisabeth" in email or "bentler" in email or "delilah" in email or "deannahd" in email or "maclin" in email:
            print(f"\nFound match under key: {key}")
            print(json.dumps(val, indent=2, ensure_ascii=False))
            
except Exception as e:
    print(f"Error: {e}")
