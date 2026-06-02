import requests
import re
from datetime import datetime, timezone, timedelta

API_URL = "https://api.maily.space/mail/public/mails"
API_TOKEN = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"

def parse_utc_timestamp_to_thai(ts_str):
    try:
        match = re.match(r'(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})', ts_str)
        if match:
            year, month, day, hour, minute, second = map(int, match.groups())
            dt_utc = datetime(year, month, day, hour, minute, second, tzinfo=timezone.utc)
            tz_thai = timezone(timedelta(hours=7))
            dt_thai = dt_utc.astimezone(tz_thai)
            thai_months = {
                1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr',
                5: 'May', 6: 'Jun', 7: 'Jul', 8: 'Aug',
                9: 'Sep', 10: 'Oct', 11: 'Nov', 12: 'Dec'
            }
            return f"{dt_thai.day} {thai_months[dt_thai.month]} at {dt_thai.strftime('%H:%M')} (GMT+07:00)"
    except Exception as e:
        print(f"Error parsing date: {e}")
    return datetime.now().strftime('%d/%m/%Y %H:%M')

def matches_app(mail_from, mail_subject, app_name):
    lower_from = mail_from.lower() if mail_from else ""
    lower_sub = mail_subject.lower() if mail_subject else ""
    lower_app = app_name.lower()
    if "netflix" in lower_app:
        return "netflix" in lower_from or "netflix" in lower_sub
    elif "disney" in lower_app:
        return "disney" in lower_from or "disney" in lower_sub
    elif "true" in lower_app:
        return "true" in lower_from or "true" in lower_sub
    elif "chat" in lower_app or "openai" in lower_app:
        return "openai" in lower_from or "openai" in lower_sub or "chatgpt" in lower_from or "chatgpt" in lower_sub
    elif "prime" in lower_app or "amazon" in lower_app:
        return "prime" in lower_from or "prime" in lower_sub or "amazon" in lower_from or "amazon" in lower_sub
    return False

def extract_otp_code(html_body):
    if not html_body:
        return None
    plain_text = re.sub(r'<[^>]+>', ' ', html_body)
    otp_match = re.search(r'\b\d{6}\b', plain_text)
    if otp_match:
        return otp_match.group(0)
    otp_match = re.search(r'\b\d{4,8}\b', plain_text)
    if otp_match:
        return otp_match.group(0)
    return None

email_input = "phatstore-a4lrns@lico.moe"
app_name = "ChatGPT"

account_name, domain = email_input.split("@", 1)
domain_id = domain.lower().replace(".", "")

headers = {
    "Authorization": f"Bearer {API_TOKEN}",
    "Content-Type": "application/json"
}
params = {
    "size": 15,
    "page": 1,
    "accountName": account_name.lower().strip(),
    "domainId": domain_id.strip()
}

print(f"Querying Maily Space for {email_input}...")
response = requests.get(API_URL, params=params, headers=headers, timeout=10)
print(f"API status: {response.status_code}")

if response.status_code == 200:
    api_data = response.json()
    mails = api_data.get("data", {}).get("mails", [])
    print(f"Found {len(mails)} total emails in inbox.")
    
    matching_mail = None
    for mail in mails:
        mail_from = mail.get("from", "")
        mail_subject = mail.get("subject", "")
        if matches_app(mail_from, mail_subject, app_name):
            matching_mail = mail
            break
            
    if matching_mail:
        print("\nSUCCESS: Found matching email for ChatGPT!")
        # Clean print non-ascii to avoid crash
        sender = matching_mail.get('from', '').encode('ascii', 'ignore').decode('ascii')
        print(f"From: {sender}")
        
        html_body = matching_mail.get("html", "")
        otp_code = extract_otp_code(html_body)
        print(f"Extracted OTP: {otp_code}")
        
        created_at = matching_mail.get("createdAt", "")
        formatted_time = parse_utc_timestamp_to_thai(created_at)
        print(f"Formatted Time: {formatted_time}")
        print(f"HTML Body Size: {len(html_body)} characters")
    else:
        print(f"ERROR: No email found for {app_name} in inbox.")
else:
    print(f"ERROR: Non-200 API response: {response.status_code}")
