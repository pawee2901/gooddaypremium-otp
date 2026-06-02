import requests

js_url = "https://maily.space/_next/static/chunks/05mpv205cj3mk.js"
print(f"Fetching {js_url}...")
try:
    res = requests.get(js_url, timeout=10)
    print(f"Length: {len(res.text)}")
    
    # Find all occurrences of api.maily.space
    idx = 0
    while True:
        pos = res.text.find("api.maily.space", idx)
        if pos == -1:
            break
        print(f"\n--- Occurrence at index {pos} ---")
        start = max(0, pos - 400)
        end = min(len(res.text), pos + 1000)
        print(res.text[start:end])
        idx = pos + 1
        
except Exception as e:
    print(f"Error: {e}")
