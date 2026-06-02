import json

# We will test all possible JSON keys sorted (which Flask's jsonify does by default)
# and print their UTF-8 lengths to see which one matches 232 bytes!

# 1. Method A - No matching emails
m1 = {"message": "ไม่พบอีเมลสำหรับ Disney+ ในกล่องข้อความนี้ (กรุณากดขอรหัส OTP จากแอปปลายทางอีกครั้ง)", "success": False}
# 2. Method A - No matching emails with TrueID
m2 = {"message": "ไม่พบอีเมลสำหรับ TrueID ในกล่องข้อความนี้ (กรุณากดขอรหัส OTP จากแอปปลายทางอีกครั้ง)", "success": False}
# 3. Method A - Maily Space 404 (inbox not found)
m3 = {"message": "ไม่สามารถเชื่อมต่อ Maily Space ได้ (โค้ดสถานะ: 404)", "success": False}
# 4. Method A - No inbox messages
m4 = {"message": "ไม่พบกล่องข้อความใดๆ สำหรับอีเมล dis-u1376o@lico.moe ในระบบ", "success": False}

print(f"m1 length: {len(json.dumps(m1, ensure_ascii=False).encode('utf-8'))}")
print(f"m2 length: {len(json.dumps(m2, ensure_ascii=False).encode('utf-8'))}")
print(f"m3 length: {len(json.dumps(m3, ensure_ascii=False).encode('utf-8'))}")
print(f"m4 length: {len(json.dumps(m4, ensure_ascii=False).encode('utf-8'))}")
