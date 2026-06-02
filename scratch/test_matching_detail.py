import requests
import re
import sys
sys.path.append("D:/otp")
from app import matches_app

url = "https://api.maily.space/mail/public/mails"
token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"
email_addr = "dis-u1376o@lico.moe"
app_name = "Disney+"

account_name, domain = email_addr.split("@")
domain_id = domain.replace(".", "")

params = {
    "size": 30,
    "page": 1,
    "accountName": account_name,
    "domainId": domain_id
}

headers = {"Authorization": f"Bearer {token}"}

print(f"Fetching emails to debug matching...")
res = requests.get(url, params=params, headers=headers, timeout=10)
if res.status_code == 200:
    data = res.json()
    mails = data.get("data", {}).get("mails", [])
    print(f"Found {len(mails)} total emails.")
    for idx, mail in enumerate(mails):
        mail_from = mail.get("from", "")
        mail_subject = mail.get("subject", "")
        matched = matches_app(mail_from, mail_subject, app_name)
        print(f"\nEmail [{idx}]:")
        print(f"  From: '{mail_from}'")
        print(f"  Subject: '{mail_subject}'")
        print(f"  App Name: '{app_name}'")
        print(f"  Matched? {matched}")
        
        # Trace matches_app
        lower_from = mail_from.lower() if mail_from else ""
        lower_sub = mail_subject.lower() if mail_subject else ""
        lower_app = app_name.lower()
        print(f"  Tracing:")
        print(f"    lower_from: '{lower_from}'")
        print(f"    lower_sub: '{lower_sub}'")
        print(f"    lower_app: '{lower_app}'")
        print(f"    'netflix' in '{lower_app}': {'netflix' in lower_app}")
        print(f"    'disney' in '{lower_app}': {'disney' in lower_app}")
        if 'disney' in lower_app:
            print(f"      'disney' in lower_from: {'disney' in lower_from}")
            print(f"      'disney' in lower_sub: {'disney' in lower_sub}")
