import requests
import re
from datetime import datetime, timezone, timedelta

CLOUD_RUN_URL = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails"
MAILY_SPACE_URL = "https://api.maily.space/mail/public/mails"
API_TOKEN = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"

def get_app_code(app_name):
    lower_name = app_name.lower()
    if "netflix" in lower_name:
        return "NF"
    elif "disney" in lower_name:
        return "DN"
    elif "true" in lower_name:
        return "TM"
    elif "chat" in lower_name or "openai" in lower_name or "gpt" in lower_name:
        return "GPT"
    elif "prime" in lower_name or "amazon" in lower_name:
        return "PR"
    return "GPT"

def parse_utc_timestamp_to_thai(ts_str):
    try:
        match = re.match(r'(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})', ts_str)
        if match:
            year, month, day, hour, minute, second = map(int, match.groups())
            dt_utc = datetime(year, month, day, hour, minute, second, tzinfo=timezone.utc)
            tz_thai = timezone(timedelta(hours=7))
            dt_thai = dt_utc.astimezone(tz_thai)
            thai_months = {
                1: 'มกราคม', 2: 'กุมภาพันธ์', 3: 'มีนาคม', 4: 'เมษายน',
                5: 'พฤษภาคม', 6: 'มิถุนายน', 7: 'กรกฎาคม', 8: 'สิงหาคม',
                9: 'กันยายน', 10: 'ตุลาคม', 11: 'พฤศจิกายน', 12: 'ธันวาคม'
            }
            return f"{dt_thai.day} {thai_months[dt_thai.month]} เวลา {dt_thai.strftime('%H:%M')} น. (ตามเวลาประเทศไทย)"
    except Exception:
        pass
    return datetime.now().strftime('%d/%m/%Y %H:%M น.')

def parse_cloud_run_date_to_thai(date_str):
    try:
        # Format returned by Cloud Run is e.g. "02/06/2026 06:17"
        dt = datetime.strptime(date_str, "%d/%m/%Y %H:%M")
        # Add 7 hours to convert to local Thai time
        dt_thai = dt + timedelta(hours=7)
        thai_months = {
            1: 'มกราคม', 2: 'กุมภาพันธ์', 3: 'มีนาคม', 4: 'เมษายน',
            5: 'พฤษภาคม', 6: 'มิถุนายน', 7: 'กรกฎาคม', 8: 'สิงหาคม',
            9: 'กันยายน', 10: 'ตุลาคม', 11: 'พฤศจิกายน', 12: 'ธันวาคม'
        }
        return f"{dt_thai.day} {thai_months[dt_thai.month]} เวลา {dt_thai.strftime('%H:%M')} น. (ตามเวลาประเทศไทย)"
    except Exception as e:
        print(f"Error parsing date {date_str}: {e}")
    return datetime.now().strftime('%d/%m/%Y %H:%M น.')

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

def fetch_otp_hybrid(email_input, app_name):
    # Determine if this is a direct Maily Space query
    is_maily_domain = False
    lower_email = email_input.lower()
    maily_domains = ["@lico.moe", "@rdcw.plus"] # common Maily Space domains
    
    for d in maily_domains:
        if d in lower_email:
            is_maily_domain = True
            break
            
    if is_maily_domain:
        print(f"\n--- Method A: Direct Maily Space query for '{email_input}' ---")
        try:
            account_name, domain = email_input.split("@", 1)
            domain_id = domain.lower().replace(".", "")
            headers = {"Authorization": f"Bearer {API_TOKEN}"}
            params = {
                "size": 15,
                "page": 1,
                "accountName": account_name.lower().strip(),
                "domainId": domain_id.strip()
            }
            res = requests.get(MAILY_SPACE_URL, params=params, headers=headers, timeout=10)
            if res.status_code == 200:
                data = res.json()
                mails = data.get("data", {}).get("mails", [])
                for mail in mails:
                    if matches_app(mail.get("from", ""), mail.get("subject", ""), app_name):
                        html_body = mail.get("html", "")
                        otp = extract_otp_code(html_body)
                        formatted_time = parse_utc_timestamp_to_thai(mail.get("createdAt", ""))
                        return {"success": True, "otp": otp, "time": formatted_time, "html": len(html_body), "method": "MailySpaceDirect"}
            return {"success": False, "message": "No matching email found directly."}
        except Exception as e:
            return {"success": False, "message": f"Direct fetch error: {e}"}
            
    else:
        print(f"\n--- Method B: Cloud Run API query for '{email_input}' ---")
        app_code = get_app_code(app_name)
        params = {
            "senderEmail": email_input.strip(),
            "appCode": app_code
        }
        try:
            res = requests.get(CLOUD_RUN_URL, params=params, timeout=10)
            print(f"  Cloud Run status: {res.status_code}")
            if res.status_code == 200:
                data = res.json()
                emails = data.get("emails", [])
                print(f"  Cloud Run found {len(emails)} emails.")
                if emails:
                    mail = emails[0]
                    html_body = mail.get("html", "")
                    otp = extract_otp_code(html_body)
                    formatted_time = parse_cloud_run_date_to_thai(mail.get("date", ""))
                    return {"success": True, "otp": otp, "time": formatted_time, "html": len(html_body), "method": "CloudRun"}
            return {"success": False, "message": "No emails found via Cloud Run."}
        except Exception as e:
            return {"success": False, "message": f"Cloud Run error: {e}"}

# Test 1: ElisabethwDeannahd@hotmail.com (Cloud Run path)
res_1 = fetch_otp_hybrid("ElisabethwDeannahd@hotmail.com", "ChatGPT")
print(f"Test 1 result: {res_1}")

# Test 2: phatstore-a4lrns@lico.moe (Maily Space Direct path)
res_2 = fetch_otp_hybrid("phatstore-a4lrns@lico.moe", "ChatGPT")
print(f"Test 2 result: {res_2}")

# Test 3: BentlerMaclin96@hotmail.com (Cloud Run path - should return empty safely without crash)
res_3 = fetch_otp_hybrid("BentlerMaclin96@hotmail.com", "ChatGPT")
print(f"Test 3 result: {res_3}")
