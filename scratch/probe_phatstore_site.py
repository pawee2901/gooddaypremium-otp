import requests
import re
from urllib.parse import urljoin

url = "https://phatstore.tomoru.fun"
print(f"Fetching {url}...")
try:
    res = requests.get(url, timeout=10)
    print(f"Status: {res.status_code}")
    
    # 1. Search for script tags
    scripts = re.findall(r'<script[^>]+src=["\']([^"\']+)["\']', res.text)
    print(f"Found scripts: {scripts}")
    
    for s in scripts:
        js_url = urljoin(url, s)
        print(f"Fetching JS: {js_url}...")
        try:
            js_res = requests.get(js_url, timeout=5)
            content = js_res.text
            
            # Find any domains or api paths
            domains = re.findall(r'https?://[a-zA-Z0-9_\-\./]+', content)
            api_paths = re.findall(r'/api/[a-zA-Z0-9_\-\./]+', content)
            
            unique_domains = list(set(domains))
            if unique_domains:
                print(f"    Domains found in {s}: {unique_domains[:10]}")
                
            unique_apis = list(set(api_paths))
            if unique_apis:
                print(f"    API paths found in {s}: {unique_apis[:15]}")
                
            # Search for database, firestore, firebase or similar config
            firebase_matches = re.findall(r'firebase|firestore|databaseURL|apiKey', content, re.IGNORECASE)
            if firebase_matches:
                print(f"    ⭐ Firebase/Firestore keywords found in {s}!")
                # Let's print the surround context of apiKey or databaseURL
                for kw in ["apiKey", "databaseURL", "projectId"]:
                    pos = content.find(kw)
                    if pos != -1:
                        print(f"      Context for '{kw}': {content[max(0, pos-100):min(len(content), pos+300)]}")
                        
        except Exception as e:
            print(f"    Failed to fetch {js_url}: {e}")

except Exception as e:
    print(f"Failed: {e}")
