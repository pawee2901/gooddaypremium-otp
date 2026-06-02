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

print(f"Searching master inbox '{master_email}' for '{target_user_email}'...")
try:
    res = requests.get(url, params=params, headers=headers, timeout=5)
    print(f"Status: {res.status_code}")
    
    if res.status_code == 200:
        data = res.json()
        mails = data.get("data", {}).get("mails", [])
        print(f"Found {len(mails)} emails in master inbox.")
        
        results = []
        found = False
        
        for mail in mails:
            mail_from = mail.get("from", "")
            mail_subject = mail.get("subject", "")
            html_body = mail.get("html", "")
            
            clean_target = target_user_email.lower().strip()
            normalized_target = clean_target.replace("@", "=")
            username_target = clean_target.split("@")[0]
            
            is_match = False
            if (clean_target in mail_from.lower() or 
                normalized_target in mail_from.lower() or 
                clean_target in html_body.lower() or 
                clean_target in mail_subject.lower() or
                username_target in mail_from.lower() or
                username_target in html_body.lower()):
                is_match = True
                found = True
                
            results.append({
                "id": mail.get("id"),
                "from": mail_from,
                "subject": mail_subject,
                "createdAt": mail.get("createdAt"),
                "is_match": is_match
            })
            
        with open("scratch/search_results.json", "w", encoding="utf-8") as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
            
        print("Wrote search logs to scratch/search_results.json")
        print(f"Found any match: {found}")
                
except Exception as e:
    print(f"Error: {e}")
