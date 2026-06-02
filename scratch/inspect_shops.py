import requests
import json

url = "https://backend-email-e48b6-default-rtdb.asia-southeast1.firebasedatabase.app/shops.json"
print("Fetching Firebase shops node...")
try:
    res = requests.get(url, timeout=10)
    if res.status_code == 200:
        data = res.json()
        print(f"Shops in database: {list(data.keys())}")
        
        # Write to local file
        with open("scratch/firebase_shops_dump.json", "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("Wrote shops dump to scratch/firebase_shops_dump.json")
        
        # Search for bentler or maclin in the shops dump
        content = json.dumps(data)
        targets = ["bentler", "maclin", "elisabeth", "deannahd"]
        for t in targets:
            pos = content.lower().find(t)
            print(f"  Substring '{t}' found: {pos != -1} (position: {pos})")
            if pos != -1:
                print(f"  Context around '{t}': {content[max(0, pos-150):min(len(content), pos+250)]}")
    else:
        print(f"Failed: {res.status_code}")
except Exception as e:
    print(f"Error: {e}")
