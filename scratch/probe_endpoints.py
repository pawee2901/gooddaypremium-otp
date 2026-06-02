import requests
import json

token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"
email = "phatstore-a4lrns@lico.moe"

headers_options = [
    {"Authorization": f"Bearer {token}"},
    {"x-api-key": token},
    {"api-key": token},
    {"Authorization": token}
]

hosts = [
    "https://api.maily.space",
    "https://maily.space/api",
    "https://maily.space",
    "https://suba.rdcw.co.th/api/maily",
    "https://suba.rdcw.co.th/api/v1/maily",
    "https://suba.rdcw.co.th/v1",
    "https://suba.rdcw.co.th/v2",
    "https://api.rdcw.co.th/v1",
    "https://api.rdcw.co.th",
]

paths = [
    "/v1/emails",
    "/v1/messages",
    "/v1/mailbox",
    "/v1/mailboxes",
    "/emails",
    "/messages",
    "/mailbox",
    "/mailboxes",
    "/get-otp",
    "/get_otp",
    "/otp",
    "/inbox",
    "/v1/inbox",
    "/v2/inbox"
]

print("Starting probes...")

for host in hosts:
    for path in paths:
        url = host + path
        # Try both GET and POST with different headers
        for headers in headers_options:
            try:
                # 1. GET request
                params = {"email": email}
                res_get = requests.get(url, params=params, headers=headers, timeout=3)
                if res_get.status_code != 404:
                    print(f"GET {url} (Headers: {list(headers.keys())[0]}) -> Status: {res_get.status_code}, Response: {res_get.text[:200]}")
            except Exception as e:
                pass
            
            try:
                # 2. POST request
                payload = {"email": email}
                res_post = requests.post(url, json=payload, headers=headers, timeout=3)
                if res_post.status_code != 404:
                    print(f"POST {url} (Headers: {list(headers.keys())[0]}) -> Status: {res_post.status_code}, Response: {res_post.text[:200]}")
            except Exception as e:
                pass

print("Probes completed.")
