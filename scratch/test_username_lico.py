import requests
import json

url = "https://api.maily.space/mail/public/mails"
token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"

# We will test two possible inbox formats on Maily Space:
# 1. bentlermaclin96@lico.moe (direct username mapping)
# 2. phatstore-bentlermaclin96@lico.moe (prefixed mapping)

inboxes = [
    {"account": "bentlermaclin96", "domain": "licomoe"},
    {"account": "phatstore-bentlermaclin96", "domain": "licomoe"},
    {"account": "elisabethwdeannahd", "domain": "licomoe"}
]

headers = {"Authorization": f"Bearer {token}"}

for inbox in inboxes:
    account = inbox["account"]
    dom = inbox["domain"]
    print(f"\nQuerying inbox: {account}@{dom}...")
    
    params = {
        "size": 10,
        "page": 1,
        "accountName": account,
        "domainId": dom
    }
    
    try:
        res = requests.get(url, params=params, headers=headers, timeout=5)
        print(f"  Status Code: {res.status_code}")
        if res.status_code == 200:
            data = res.json()
            mails = data.get("data", {}).get("mails", [])
            print(f"  Found {len(mails)} emails.")
            for m in mails[:3]:
                print(f"    - From: {m.get('from')} | Subject: {m.get('subject')[:50]}")
    except Exception as e:
        print(f"  Error: {e}")
