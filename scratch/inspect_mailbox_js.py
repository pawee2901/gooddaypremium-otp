import requests
import re

js_url = "https://maily.space/_next/static/chunks/09jwk~3.jjvgc.js"
print(f"Fetching {js_url}...")
try:
    res = requests.get(js_url, timeout=10)
    print(f"Length: {len(res.text)}")
    
    # Search for all strings starting with '/' and see if there are any API endpoints
    # e.g., '/v1/emails', '/emails', '/api/...'
    # Let's search for any strings enclosed in quotes that have '/' followed by letters
    paths = re.findall(r'[\'"](/[a-zA-Z0-9_\-\.\{\}/]+)[\'"]', res.text)
    print("Found paths in mailbox JS:")
    for p in sorted(list(set(paths))):
        print(f"  {p}")
        
    # Let's find occurrences of fetch/get/post methods
    # Search for keywords like "headers", "token", "query", "email" in context
    for kw in ["token", "Authorization", "email", "get", "post", "fetch"]:
        pos = 0
        count = 0
        while True:
            pos = res.text.find(kw, pos)
            if pos == -1 or count >= 10:
                break
            start = max(0, pos - 150)
            end = min(len(res.text), pos + 250)
            print(f"\nContext for '{kw}' at {pos}:\n{res.text[start:end]}")
            pos += len(kw)
            count += 1

except Exception as e:
    print(f"Error: {e}")
