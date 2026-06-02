import requests
import re
from urllib.parse import urljoin

url = "https://maily.space/mailbox"
print(f"Fetching {url}...")
try:
    res = requests.get(url, timeout=10)
    print(f"Status: {res.status_code}")
    
    # 1. Look for API endpoints in the HTML
    print("Searching HTML for API endpoints...")
    api_patterns = re.findall(r'[\'"](?:https?://[^\'"]+)?/api/[^\'"]+[\'"]', res.text)
    for p in set(api_patterns):
        print(f"  Found potential API: {p}")
        
    # 2. Look for script tags using regex
    scripts = re.findall(r'<script[^>]+src=["\']([^"\']+)["\']', res.text)
    print(f"Found scripts: {scripts}")
    
    for s in scripts:
        js_url = urljoin(url, s)
        print(f"Fetching JS: {js_url}...")
        try:
            js_res = requests.get(js_url, timeout=5)
            # Find any api paths or domain names
            urls_in_js = re.findall(r'https?://[a-zA-Z0-9_\-\./]+', js_res.text)
            api_in_js = re.findall(r'/api/[a-zA-Z0-9_\-\./]+', js_res.text)
            
            # Print unique ones
            unique_urls = [u for u in set(urls_in_js) if 'maily.space' in u or 'rdcw' in u]
            if unique_urls:
                print(f"    RDCW/Maily URLs found in {s}: {unique_urls}")
            
            unique_apis = list(set(api_in_js))[:20]
            if unique_apis:
                print(f"    API endpoints found in {s}: {unique_apis}")
                
            # Search for specific email endpoints
            email_routes = [r for r in set(api_in_js) if 'email' in r or 'message' in r or 'mailbox' in r or 'otp' in r]
            if email_routes:
                print(f"    ⭐ Email-related routes found in {s}: {email_routes}")
        except Exception as e:
            print(f"    Failed to fetch {js_url}: {e}")

except Exception as e:
    print(f"Failed to fetch {url}: {e}")
