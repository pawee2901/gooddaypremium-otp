import json

# Let's check the length of different possible JSON responses from app.py
msg_disney_not_found = {
    'success': False,
    'message': 'ไม่พบอีเมลสำหรับ Disney+ ในกล่องข้อความนี้ (กรุณากดขอรหัส OTP จากแอปปลายทางอีกครั้ง)'
}

msg_trueid_not_found = {
    'success': False,
    'message': 'ไม่พบอีเมลสำหรับ TrueID ในกล่องข้อความนี้ (กรุณากดขอรหัส OTP จากแอปปลายทางอีกครั้ง)'
}

msg_maily_disney_not_found = {
    'success': False,
    'message': 'ไม่พบกล่องข้อความใดๆ สำหรับอีเมล dis-u1376o@lico.moe ในระบบ'
}

print(f"Disney+ not found length: {len(json.dumps(msg_disney_not_found, ensure_ascii=False).encode('utf-8'))}")
print(f"TrueID not found length: {len(json.dumps(msg_trueid_not_found, ensure_ascii=False).encode('utf-8'))}")
print(f"Maily inbox not found length: {len(json.dumps(msg_maily_disney_not_found, ensure_ascii=False).encode('utf-8'))}")
