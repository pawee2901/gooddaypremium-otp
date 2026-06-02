import requests
import re
from urllib.parse import urljoin

url = "https://maily.space/mailbox"
print(f"Fetching {url}...")
try:
    res = requests.get(url, timeout=10)
    scripts = re.findall(r'<script[^>]+src=["\']([^"\']+)["\']', res.text)
    
    for s in scripts:
        js_url = urljoin(url, s)
        try:
            js_res = requests.get(js_url, timeout=5)
            content = js_res.text
            
            # Search for occurrences of keywords
            keywords = ["inbox", "emails", "messages", "mailbox", "domains", "get-otp", "v1", "v2"]
            found_keywords = [k for k in keywords if k in content]
            
            if found_keywords:
                print(f"\n==================================================")
                print(f"File: {s} - Found keywords: {found_keywords}")
                print(f"==================================================")
                
                # Search for all strings starting with / or templated endpoints
                # We want to extract paths like /v1/mailbox/... or api.maily.space paths
                # Let's search for patterns like: /api/something or /v1/something
                paths = re.findall(r'/[a-zA-Z0-9_\-\{\}/]{3,}', content)
                filtered_paths = [p for p in set(paths) if 'inbox' in p or 'email' in p or 'mailbox' in p or 'msg' in p or 'domain' in p or 'auth' in p or 'v1' in p or 'v2' in p]
                if filtered_paths:
                    print(f"  Matching paths: {sorted(list(set(filtered_paths))[:15])}")
                
                # Print occurrences of 'inbox' or 'emails' or 'v1' with context
                for kw in found_keywords:
                    if kw in ["inbox", "emails", "messages", "mailbox", "v1"]:
                        pos = 0
                        count = 0
                        while True:
                            pos = content.find(kw, pos)
                            if pos == -1 or count >= 5:
                                break
                            start = max(0, pos - 150)
                            end = min(len(content), pos + 250)
                            print(f"\n  [Context for '{kw}' at {pos}]:\n  {content[start:end]}")
                            pos += len(kw)
                            count += 1
                            
        except Exception as e:
            print(f"Failed to fetch/scan {js_url}: {e}")

except Exception as e:
    print(f"Failed: {e}")
