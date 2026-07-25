import sys
with open(r'D:\งานที่รับ\otp\admin.php', 'r', encoding='utf-8', errors='replace') as f:
    lines = f.readlines()

for i, line in enumerate(lines):
    if 'id="forwardingModal"' in line or 'MODAL: เพิ่ม Forwarding Map' in line:
        print(f'Modal at line {i+1}')
