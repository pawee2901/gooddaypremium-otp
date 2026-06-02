import requests
import json
import re

url = "https://api.maily.space/mail/public/mails"
token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"
master_email = "phatstore-a4lrns@lico.moe"
target_user_email = "BentlerMaclin96@hotmail.com"

account_name, domain = master_email.split("@")
domain_id = domain.replace(".", "")

params = {
    "size": 50,
    "page": 1,
    "accountName": account_name,
    "domainId": domain_id
}

headers = {"Authorization": f"Bearer {token}"}

try:
    res = requests.get(url, params=params, headers=headers, timeout=5)
    if res.status_code == 200:
        data = res.json()
        mails = data.get("data", {}).get("mails", [])
        
        # Clean target format
        clean_target = target_user_email.lower().strip()
        username_target = clean_target.split("@")[0] # "bentlermaclin96"
        
        print(f"Total emails: {len(mails)}")
        print(f"Searching for sub-string: '{username_target}' in 'from' headers...")
        
        found = False
        for mail in mails:
            mail_from = mail.get("from", "")
            mail_subject = mail.get("subject", "")
            
            # Check if username is in the sender address or subject
            if username_target in mail_from.lower() or username_target in mail_subject.lower():
                print("\nMATCH FOUND!")
                print(f"  From: {mail_from}")
                print(f"  Subject: {mail_subject}")
                print(f"  CreatedAt: {mail.get('createdAt')}")
                found = True
                
        if not found:
            print("No matching email found in the master inbox.")
            print("Listing unique sender addresses in master inbox:")
            senders = set(m.get("from") for m in mails)
            for s in sorted(list(senders)):
                print(f"  - {s}")
                
except Exception as e:
    print(f"Error: {e}")
