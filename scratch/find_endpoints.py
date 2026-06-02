import requests
import re

js_url = "https://maily.space/_next/static/chunks/05mpv205cj3mk.js"
try:
    res = requests.get(js_url, timeout=10)
    
    # Let's search for all fetch/ajax URLs and paths
    # Finding templates literals or string literals containing slashes
    paths = re.findall(r'[\'"](/[a-zA-Z0-9_\-\.\{\}/]+)[\'"]', res.text)
    
    print("Found unique paths in JS:")
    for p in sorted(list(set(paths))):
        if len(p) > 2 and ('auth' in p or 'mail' in p or 'domain' in p or 'email' in p or 'msg' in p or 'user' in p or 'inbox' in p):
            print(f"  {p}")
            
    print("\nLet's search for function calls or API integrations. Searching for template literals with endpoint...")
    # Finding string literals starting with ENDPOINT or template strings
    literals = re.findall(r'`\$\{.*?\}[\w/-]+`|ENDPOINT\s*\+\s*[\'"][^\'"]+[\'"]', res.text)
    for l in set(literals):
        print(f"  Literal: {l}")
        
except Exception as e:
    print(f"Error: {e}")
