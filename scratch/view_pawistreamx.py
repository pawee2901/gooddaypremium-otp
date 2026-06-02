import json

with open("scratch/firebase_shops_dump.json", "r", encoding="utf-8") as f:
    data = json.load(f)

for key, val in data.items():
    shop_name = val.get("shopName", "")
    if "pawistreamx" in shop_name.lower():
        print(json.dumps(val, indent=2, ensure_ascii=False))
