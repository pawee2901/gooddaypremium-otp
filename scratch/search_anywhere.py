import json

print("Searching database dump...")
try:
    with open("scratch/firebase_emails_dump.json", "r", encoding="utf-8") as f:
        content = f.read()
        
    print(f"File character length: {len(content)}")
    
    # Simple substring searches
    targets = ["bentler", "maclin", "elisabeth", "deannahd"]
    for t in targets:
        pos = content.lower().find(t)
        print(f"  Substring '{t}' found: {pos != -1} (position: {pos})")
        if pos != -1:
            # Print a snippet of context
            print(f"  Context around '{t}': {content[max(0, pos-150):min(len(content), pos+250)]}")
            
except Exception as e:
    print(f"Error: {e}")
