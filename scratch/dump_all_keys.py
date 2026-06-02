import json

try:
    with open("scratch/firebase_emails_dump.json", "r", encoding="utf-8") as f:
        data = json.load(f)
        
    print(f"Total entries: {len(data)}")
    print("Listing first 30 entries:")
    
    count = 0
    for key, val in data.items():
        print(f"  Key: {key} | Email: {val.get('email')} | Fields: {list(val.keys())}")
        count += 1
        if count >= 30:
            break
            
except Exception as e:
    print(f"Error: {e}")
