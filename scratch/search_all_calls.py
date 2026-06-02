import requests
import re

js_url = "https://maily.space/_next/static/chunks/09jwk~3.jjvgc.js"
try:
    res = requests.get(js_url, timeout=10)
    # Search for all strings starting with /mail/ or any fetchers calls
    calls = re.findall(r'fetchers\.[a-zA-Z]+\([^\)]+\)', res.text)
    print("Found fetchers calls:")
    for c in set(calls):
        print(f"  {c}")
        
    # Search for all paths containing /mail/
    paths = re.findall(r'/[a-zA-Z0-9_\-\.\{\}/]+', res.text)
    mail_paths = [p for p in set(paths) if 'mail' in p]
    print("\nFound mail paths:")
    for p in mail_paths:
        print(f"  {p}")
        
except Exception as e:
    print(f"Error: {e}")
