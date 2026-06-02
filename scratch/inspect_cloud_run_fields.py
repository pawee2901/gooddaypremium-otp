import requests
import re

js_url = "https://phatstore.tomoru.fun/static/js/main.fea63888.js"
print("Scanning main JS for getEmails response handling...")
try:
    res = requests.get(js_url, timeout=10)
    
    # Let's search for "getEmails" or the response assignment
    # e.g., finding where .html or .createdAt is used under the email array loop
    pos = res.text.find("senderEmail=")
    if pos != -1:
        print("\n--- Context around getEmails call ---")
        print(res.text[max(0, pos-200):min(len(res.text), pos+1500)])
        
except Exception as e:
    print(f"Error: {e}")
