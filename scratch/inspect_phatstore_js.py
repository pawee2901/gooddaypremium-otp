import requests

js_url = "https://phatstore.tomoru.fun/static/js/main.fea63888.js"
print(f"Fetching {js_url}...")
try:
    res = requests.get(js_url, timeout=10)
    print(f"Length: {len(res.text)}")
    
    # Let's search for "getemails-wfudlrftlq"
    pos = res.text.find("getemails-wfudlrftlq")
    if pos != -1:
        print("\n--- Context for Cloud Run Function ---")
        print(res.text[max(0, pos-400):min(len(res.text), pos+1200)])
        
    # Let's search for "backend-email-e48b6"
    pos_fb = res.text.find("backend-email-e48b6")
    if pos_fb != -1:
        print("\n--- Context for Firebase RTDB ---")
        print(res.text[max(0, pos_fb-400):min(len(res.text), pos_fb+1200)])
        
except Exception as e:
    print(f"Error: {e}")
