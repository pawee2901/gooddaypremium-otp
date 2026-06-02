import json

with open("scratch/firebase_shops_dump.json", "r", encoding="utf-8") as f:
    data = json.load(f)

for key, val in data.items():
    print(f"Shop Name: {val.get('shopName')} | url: {val.get('url')} | token: {val.get('token') or val.get('apiToken')}")
