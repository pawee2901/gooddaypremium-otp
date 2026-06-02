import json

try:
    with open("scratch/firebase_emails_dump.json", "r", encoding="utf-8") as f:
        data = json.load(f)
        
    print(f"Total entries: {len(data)}")
    
    # Check for domain occurrences
    domains = {}
    matches = []
    
    for key, val in data.items():
        email = val.get("email", "").lower()
        domain = email.split("@")[-1] if "@" in email else "no_domain"
        domains[domain] = domains.get(domain, 0) + 1
        
        if "hotmail" in email or "gmail" in email or "yahoo" in email:
            matches.append(val)
            
    print("\nDomain distribution in database:")
    for d, c in sorted(domains.items(), key=lambda x: x[1], reverse=True):
        print(f"  {d}: {c} accounts")
        
    print(f"\nFound {len(matches)} Hotmail/Gmail/Yahoo matches in database:")
    for m in matches[:10]:
        print(json.dumps(m, indent=2, ensure_ascii=False))
        
except Exception as e:
    print(f"Error: {e}")
