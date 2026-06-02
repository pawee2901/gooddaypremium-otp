import requests
import json

url = "https://api.maily.space/mail/public/mails"
token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"
email_addr = "dis-u1376o@lico.moe"

account_name, domain = email_addr.split("@")
domain_id = domain.replace(".", "")

params = {
    "size": 30,
    "page": 1,
    "accountName": account_name,
    "domainId": domain_id
}

headers = {"Authorization": f"Bearer {token}"}

print(f"Fetching emails for {email_addr}...")
try:
    res = requests.get(url, params=params, headers=headers, timeout=10)
    print(f"Status Code: {res.status_code}")
    if res.status_code == 200:
        data = res.json()
        mails = data.get("data", {}).get("mails", [])
        print(f"Found {len(mails)} total emails in inbox.")
        for m in mails:
            print(f"  - From: {m.get('from')} | Subject: {m.get('subject')} | CreatedAt: {m.get('createdAt')}")
    else:
        print(f"Error response: {res.text}")
except Exception as e:
    print(f"Error: {e}")
