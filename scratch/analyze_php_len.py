import json

msg = {
    'success': False,
    'message': 'ไม่พบอีเมลยืนยันสำหรับ Disney+ ส่งมายังกล่องจดหมายนี้ (กรุณากดส่งรหัส OTP หรือรอสักครู่แล้วค้นหาอีกครั้ง)'
}

print(f"PHP message length: {len(json.dumps(msg, ensure_ascii=False).encode('utf-8'))}")
