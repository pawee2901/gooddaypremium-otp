import requests
import json

url = "https://api.maily.space/mail/public/mails"
token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"
email_addr = "phatstore-a4lrns@lico.moe"

account_name, domain = email_addr.split("@")
domain_id = domain.replace(".", "")

params = {
    "size": 10,
    "page": 1,
    "accountName": account_name,
    "domainId": domain_id
}

headers = {"Authorization": f"Bearer {token}"}

print("Fetching emails...")
try:
    res = requests.get(url, params=params, headers=headers, timeout=5)
    print(f"Status Code: {res.status_code}")
    
    if res.status_code == 200:
        data = res.json()
        
        # Write to JSON file with UTF-8 encoding
        with open("scratch/test_maily_api_results.json", "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        print("Successfully wrote response to scratch/test_maily_api_results.json")
        
        mails = data.get("data", {}).get("mails", [])
        print(f"Found {len(mails)} emails in response.")
        
        # Print a simple safe summary
        for m in mails:
            mail_id = m.get('id')
            subject = m.get('subject', '').encode('ascii', 'ignore').decode('ascii')
            sender = m.get('from', '').encode('ascii', 'ignore').decode('ascii')
            created = m.get('createdAt')
            print(f"  - ID: {mail_id} | From: {sender} | Subject: {subject} | Created: {created}")
            
            # Try fetching detail for the first one
            detail_url = f"https://api.maily.space/mail/public/mails/{mail_id}"
            print(f"  Fetching detail from {detail_url}")
            res_detail = requests.get(detail_url, headers=headers, timeout=5)
            print(f"  Detail status: {res_detail.status_code}")
            if res_detail.status_code == 200:
                detail_data = res_detail.json()
                with open(f"scratch/detail_{mail_id}.json", "w", encoding="utf-8") as f_det:
                    json.dump(detail_data, f_det, indent=2, ensure_ascii=False)
                print(f"  Wrote detail to scratch/detail_{mail_id}.json")
            break # Just do one
            
except Exception as e:
    print(f"Error: {e}")
