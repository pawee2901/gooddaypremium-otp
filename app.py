import os
import random
import time
import re
import imaplib
import email
from email.header import decode_header
import email.utils
from datetime import datetime, timezone, timedelta
from flask import Flask, render_template, request, jsonify, session, redirect, url_for
import requests
import json

app = Flask(__name__)
app.secret_key = 'gooddaypremium-super-secret-key-12345'

CONFIG_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'config.json')

def load_config():
    if not os.path.exists(CONFIG_PATH):
        default_config = {
            "shop_name": "gooddaypremium",
            "logo_path": "/static/logo.jpg",
            "admin_username": "admin",
            "admin_password": "admin1234"
        }
        with open(CONFIG_PATH, 'w', encoding='utf-8') as f:
            json.dump(default_config, f, indent=4, ensure_ascii=False)
        return default_config
    try:
        with open(CONFIG_PATH, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception:
        return {
            "shop_name": "gooddaypremium",
            "logo_path": "/static/logo.jpg",
            "admin_username": "admin",
            "admin_password": "admin1234"
        }

def save_config(config):
    try:
        with open(CONFIG_PATH, 'w', encoding='utf-8') as f:
            json.dump(config, f, indent=4, ensure_ascii=False)
        return True
    except Exception:
        return False

# =========================================================================
# การตั้งค่าดึงรหัสผ่านตรงจากกล่องอีเมลของคุณ (Direct Mailbox Reader via IMAP)
# =========================================================================
USE_IMAP = False                   # 🟢 เปลี่ยนเป็น True หากต้องการปิดระบบสุ่มแล้วดึงข้อมูลตรงจากกล่องอีเมลของลูกค้าทันที!
IMAP_SERVER = "imap-mail.outlook.com" # สำหรับกล่อง Hotmail / Outlook (ถ้าใช้ Gmail ให้ระบุ imap.gmail.com)
IMAP_PORT = 993                     # พอร์ต SSL มาตรฐานของ IMAP

# โหมดการเข้าถึงเมล:
# โหมด A: ใช้บัญชีหลักสำหรับดึงเมล Forwarding (USE_IMAP_DIRECT = False)
# โหมด B: ล็อกอินรายกล่องโดยใช้อีเมลสืบค้นเป็น Username และรหัสผ่านร่วมกัน (USE_IMAP_DIRECT = True)
USE_IMAP_DIRECT = True              # 🟢 ตั้งเป็น True หากทุกอีเมลล็อกอินของคุณใช้รหัสผ่านส่วนกลางร่วมกัน
IMAP_COMMON_PASSWORD = "common-password-for-all-emails" # ระบุรหัสผ่านกลางของเมลลูกค้ายกชุดตรงนี้

# บัญชีดึงเมลสำหรับ โหมด A (ถ้าเปิดใช้งาน)
IMAP_EMAIL = "your-master-mailbox@hotmail.com" 
IMAP_PASSWORD = "your-master-app-password"

# =========================================================================
# การตั้งค่า API ปลายทางผ่านเซิร์ฟเวอร์หลัก (ถ้าต้องการใช้แบบส่งผ่าน API)
# =========================================================================
API_URL = "https://phatstore.tomoru.fun/api/get-otp" # โดเมนเซิร์ฟเวอร์หลักของ PhatStore
API_TOKEN = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"  # คีย์ลับประเมินค่า

# =========================================================================
# การเชื่อมต่อ API จริงกับทาง Maily Space และ Cloud Run API (RDCW Co., Ltd.)
# =========================================================================
API_URL = "https://api.maily.space/mail/public/mails"
API_TOKEN = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"  # คีย์ลับ API Token
CLOUD_RUN_URL = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails"  # Cloud Run mapping API

# ฟังก์ชันแปลงชื่อแอปเป็นรหัสย่อตามระบบของ RDCW
def get_app_code(app_name):
    lower_name = app_name.lower()
    if "netflix" in lower_name:
        return "NF"
    elif "disney" in lower_name:
        return "DN"
    elif "true" in lower_name:
        return "TM"
    elif "chat" in lower_name or "openai" in lower_name or "gpt" in lower_name:
        return "GPT"
    elif "prime" in lower_name or "amazon" in lower_name:
        return "PR"
    return "GPT"

# ฟังก์ชันแปลงรหัสเวลา UTC ของ Maily Space เป็นเวลาไทยพรีเมียม (GMT+07:00)
def parse_utc_timestamp_to_thai(ts_str):
    try:
        # ดึงปี เดือน วัน ชั่วโมง นาที วินาที จากฟอร์แมต ISO (e.g. 2026-06-02T06:17:03.576Z)
        match = re.match(r'(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})', ts_str)
        if match:
            year, month, day, hour, minute, second = map(int, match.groups())
            dt_utc = datetime(year, month, day, hour, minute, second, tzinfo=timezone.utc)
            
            # แปลงเป็นเวลาท้องถิ่นไทย (GMT+07:00)
            tz_thai = timezone(timedelta(hours=7))
            dt_thai = dt_utc.astimezone(tz_thai)
            
            thai_months = {
                1: 'มกราคม', 2: 'กุมภาพันธ์', 3: 'มีนาคม', 4: 'เมษายน',
                5: 'พฤษภาคม', 6: 'มิถุนายน', 7: 'กรกฎาคม', 8: 'สิงหาคม',
                9: 'กันยายน', 10: 'ตุลาคม', 11: 'พฤศจิกายน', 12: 'ธันวาคม'
            }
            day_num = dt_thai.day
            month_name = thai_months[dt_thai.month]
            time_str = dt_thai.strftime('%H:%M')
            
            return f"{day_num} {month_name} เวลา {time_str} น. (ตามเวลาประเทศไทย)"
    except Exception:
        pass
    
    return datetime.now().strftime('%d/%m/%Y %H:%M น.')

# ฟังก์ชันแปลงวันเวลาของ Cloud Run (e.g. "02/06/2026 06:17") เป็นเวลาไทย
def parse_cloud_run_date_to_thai(date_str):
    try:
        dt = datetime.strptime(date_str, "%d/%m/%Y %H:%M")
        dt_thai = dt + timedelta(hours=7)
        thai_months = {
            1: 'มกราคม', 2: 'กุมภาพันธ์', 3: 'มีนาคม', 4: 'เมษายน',
            5: 'พฤษภาคม', 6: 'มิถุนายน', 7: 'กรกฎาคม', 8: 'สิงหาคม',
            9: 'กันยายน', 10: 'ตุลาคม', 11: 'พฤศจิกายน', 12: 'ธันวาคม'
        }
        day_num = dt_thai.day
        month_name = thai_months[dt_thai.month]
        time_str = dt_thai.strftime('%H:%M')
        return f"{day_num} {month_name} เวลา {time_str} น. (ตามเวลาประเทศไทย)"
    except Exception:
        pass
    return datetime.now().strftime('%d/%m/%Y %H:%M น.')

# ฟังก์ชันตรวจสอบคัดกรองหัวจดหมายและผู้ส่งให้ตรงตามระบบแอปพลิเคชันที่เลือก
def matches_app(mail_from, mail_subject, app_name):
    lower_from = mail_from.lower() if mail_from else ""
    lower_sub = mail_subject.lower() if mail_subject else ""
    lower_app = app_name.lower()
    
    if "netflix" in lower_app:
        return "netflix" in lower_from or "netflix" in lower_sub
    elif "disney" in lower_app:
        return "disney" in lower_from or "disney" in lower_sub
    elif "true" in lower_app:
        return "true" in lower_from or "true" in lower_sub
    elif "chat" in lower_app or "openai" in lower_app:
        return "openai" in lower_from or "openai" in lower_sub or "chatgpt" in lower_from or "chatgpt" in lower_sub
    elif "prime" in lower_app or "amazon" in lower_app:
        return "prime" in lower_from or "prime" in lower_sub or "amazon" in lower_from or "amazon" in lower_sub
    return False

# ฟังก์ชันถอดตัวเลข OTP ออกมาอย่างแม่นยำจาก HTML Body ของจดหมายจริง
def extract_otp_code(html_body):
    if not html_body:
        return None
        
    plain_text = re.sub(r'<[^>]+>', ' ', html_body)
    
    # 1. ค้นหาตัวเลข 6 หลักแบบ Contiguous (สอดคล้องกับ OpenAI / Netflix)
    otp_match = re.search(r'\b\d{6}\b', plain_text)
    if otp_match:
        return otp_match.group(0)
        
    # 2. ค้นหาตัวเลข 4-8 หลัก (เผื่อแบรนด์อื่นๆ)
    otp_match = re.search(r'\b\d{4,8}\b', plain_text)
    if otp_match:
        return otp_match.group(0)
        
    return None

# =========================================================================
# Route 1: หน้าแรก แรนเดอร์หน้าเว็บ (Frontend SPA)
# =========================================================================
@app.route('/')
def index():
    config = load_config()
    return render_template('index.html', shop_name=config.get('shop_name', 'gooddaypremium'), logo_path=config.get('logo_path', '/static/logo.jpg'))

# =========================================================================
# Route 2: หลังบ้านประมวลผลค้นหา OTP (POST Asynchronous)
# =========================================================================
@app.route('/get_otp', methods=['POST'])
def get_otp():
    if request.is_json:
        data = request.get_json()
        email_input = data.get('email', '').strip() if data else ''
        app_name = data.get('app_name', '').strip() if data else ''
    else:
        email_input = request.form.get('email', '').strip()
        app_name = request.form.get('app_name', '').strip()

    if not email_input:
        return jsonify({
            'success': False,
            'message': 'กรุณาระบุอีเมลเพื่อค้นหารหัส OTP'
        }), 400

    # ตรวจสอบรูปแบบอีเมลเบื้องต้น
    if '@' not in email_input or '.' not in email_input:
        return jsonify({
            'success': False,
            'message': 'รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'
        }), 400

    lower_email = email_input.lower()
    is_maily_domain = False
    maily_domains = ["@lico.moe", "@rdcw.plus"] # โดเมนหลักของ Maily Space
    
    for d in maily_domains:
        if d in lower_email:
            is_maily_domain = True
            break

    # -------------------------------------------------------------------------
    # ช่องทาง A: การดึงโดยตรงจาก Maily Space (กรณีเป็นอีเมล Maily Space ตรง)
    # -------------------------------------------------------------------------
    if is_maily_domain:
        try:
            account_name, domain = email_input.split("@", 1)
            domain_id = domain.lower().replace(".", "")
            
            headers = {
                "Authorization": f"Bearer {API_TOKEN}",
                "Content-Type": "application/json"
            }
            params = {
                "size": 15,
                "page": 1,
                "accountName": account_name.lower().strip(),
                "domainId": domain_id.strip()
            }
            
            response = requests.get(API_URL, params=params, headers=headers, timeout=10)
            
            if response.status_code != 200:
                return jsonify({
                    'success': False,
                    'message': f'ไม่สามารถเชื่อมต่อ Maily Space ได้ (โค้ดสถานะ: {response.status_code})'
                }), 200
                
            api_data = response.json()
            mails = api_data.get("data", {}).get("mails", [])
            
            if not mails:
                return jsonify({
                    'success': False,
                    'message': f'ไม่พบกล่องข้อความใดๆ สำหรับอีเมล {email_input} ในระบบ'
                }), 200
                
            matching_mails = []
            for mail in mails:
                if matches_app(mail.get("from", ""), mail.get("subject", ""), app_name):
                    html_body = mail.get("html", "")
                    otp_code = extract_otp_code(html_body) or ""
                    formatted_time = parse_utc_timestamp_to_thai(mail.get("createdAt", ""))
                    matching_mails.append({
                        'subject': mail.get("subject", "ไม่มีหัวข้อ"),
                        'from': mail.get("from", ""),
                        'time': formatted_time,
                        'otp': otp_code,
                        'html_body': html_body
                    })
                    
            if not matching_mails:
                return jsonify({
                    'success': False,
                    'message': f'ไม่พบอีเมลสำหรับ {app_name} ในกล่องข้อความนี้ (กรุณากดขอรหัส OTP จากแอปปลายทางอีกครั้ง)'
                }), 200
                
            return jsonify({
                'success': True,
                'app_name': app_name,
                'email': email_input,
                'emails': matching_mails
            })
            
        except requests.exceptions.RequestException as e:
            return jsonify({
                'success': False,
                'message': f'เครือข่ายขัดข้องในการดึงจดหมาย: {str(e)}'
            }), 200
        except Exception as e:
            return jsonify({
                'success': False,
                'message': f'เกิดข้อผิดพลาดในการดึง OTP: {str(e)}'
            }), 200

    # -------------------------------------------------------------------------
    # ช่องทาง B: การดึงข้อมูลผ่านระบบ Cloud Run ของคุณลูกค้า (กรณีเป็น Hotmail/Gmail ฯลฯ)
    # -------------------------------------------------------------------------
    else:
        app_code = get_app_code(app_name)
        params = {
            "senderEmail": email_input.strip(),
            "appCode": app_code
        }
        
        try:
            response = requests.get(CLOUD_RUN_URL, params=params, timeout=10)
            
            if response.status_code != 200:
                return jsonify({
                    'success': False,
                    'message': f'ระบบคลาวด์ตอบกลับขัดข้อง (โค้ดสถานะ: {response.status_code})'
                }), 200
                
            api_data = response.json()
            emails = api_data.get("emails", [])
            
            if not emails:
                return jsonify({
                    'success': False,
                    'message': f'ไม่พบอีเมลยืนยันตัวตนล่าสุดของ {app_name} ส่งมายัง {email_input} (กรุณากดส่งรหัส OTP หรือตรวจสอบว่าคุณลงทะเบียนเมลนี้แล้ว)'
                }), 200
                
            matching_mails = []
            for mail in emails:
                html_body = mail.get("html", "")
                
                # ดึงเฉพาะเนื้อหา Table หลักหากพบเพื่อป้องกัน overflow หน้าเว็บ
                if html_body:
                    table_idx = html_body.find("<table")
                    if table_idx != -1:
                        html_body = html_body[table_idx:]
                        
                otp_code = extract_otp_code(html_body) or ""
                formatted_time = parse_cloud_run_date_to_thai(mail.get("date", ""))
                
                matching_mails.append({
                    'subject': mail.get("subject", "ไม่มีหัวข้อ"),
                    'from': mail.get("sender", f"{app_name} Security"),
                    'time': formatted_time,
                    'otp': otp_code,
                    'html_body': html_body
                })
                
            if not matching_mails:
                return jsonify({
                    'success': False,
                    'message': f'ไม่พบอีเมลยืนยันตัวตนล่าสุดสำหรับ {app_name}'
                }), 200
                
            return jsonify({
                'success': True,
                'app_name': app_name,
                'email': email_input,
                'emails': matching_mails
            })
            
        except requests.exceptions.RequestException as e:
            return jsonify({
                'success': False,
                'message': f'เครือข่ายเชื่อมต่อระบบคลาวด์ขัดข้อง: {str(e)}'
            }), 200
        except Exception as e:
            return jsonify({
                'success': False,
                'message': f'เกิดปัญหาในการประมวลผลข้อความ: {str(e)}'
            }), 200

# =========================================================================
# Route 3: Admin Dashboard (Authentication & Settings Panel)
# =========================================================================
@app.route('/admin')
def admin():
    config = load_config()
    return render_template('admin.html', shop_name=config.get('shop_name', 'gooddaypremium'), logo_path=config.get('logo_path', '/static/logo.jpg'), logged_in=session.get('admin_logged_in', False))

@app.route('/admin/login', methods=['POST'])
def admin_login():
    username = request.form.get('username', '').strip()
    password = request.form.get('password', '').strip()
    
    config = load_config()
    if username == config.get('admin_username') and password == config.get('admin_password'):
        session['admin_logged_in'] = True
        return jsonify({'success': True, 'message': 'เข้าสู่ระบบสำเร็จ'})
    
    return jsonify({'success': False, 'message': 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'}), 400

@app.route('/admin/logout')
def admin_logout():
    session.pop('admin_logged_in', None)
    return redirect(url_for('admin'))

@app.route('/admin/update', methods=['POST'])
def admin_update():
    if not session.get('admin_logged_in'):
        return jsonify({'success': False, 'message': 'ไม่มีสิทธิ์เข้าถึง'}), 403
        
    shop_name = request.form.get('shop_name', '').strip()
    new_username = request.form.get('username', '').strip()
    new_password = request.form.get('password', '').strip()
    
    if not shop_name:
        return jsonify({'success': False, 'message': 'กรุณากรอกชื่อร้าน'}), 400
        
    config = load_config()
    config['shop_name'] = shop_name
    
    if new_username:
        config['admin_username'] = new_username
    if new_password:
        config['admin_password'] = new_password
        
    if save_config(config):
        return jsonify({'success': True, 'message': 'บันทึกข้อมูลเรียบร้อยแล้ว'})
    return jsonify({'success': False, 'message': 'ไม่สามารถบันทึกข้อมูลได้'}), 500

@app.route('/admin/upload_logo', methods=['POST'])
def admin_upload_logo():
    if not session.get('admin_logged_in'):
        return jsonify({'success': False, 'message': 'ไม่มีสิทธิ์เข้าถึง'}), 403
        
    if 'logo' not in request.files:
        return jsonify({'success': False, 'message': 'ไม่พบไฟล์ภาพ'}), 400
        
    file = request.files['logo']
    if file.filename == '':
        return jsonify({'success': False, 'message': 'กรุณาเลือกไฟล์ภาพ'}), 400
        
    allowed_extensions = {'png', 'jpg', 'jpeg', 'gif', 'webp'}
    ext = file.filename.rsplit('.', 1)[1].lower() if '.' in file.filename else ''
    if ext not in allowed_extensions:
        return jsonify({'success': False, 'message': 'ไฟล์ต้องเป็นประเภทรูปภาพเท่านั้น (png, jpg, jpeg, gif, webp)'}), 400
        
    # บันทึกภาพลง static
    filename = f"logo_custom_{int(time.time())}.{ext}"
    static_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'static')
    
    # ลบโลโก้เก่า (ที่ไม่ใช่ logo.jpg) เพื่อลดขยะในระบบ
    config = load_config()
    old_logo_path = config.get('logo_path', '')
    if old_logo_path and 'logo.jpg' not in old_logo_path:
        old_full_path = os.path.join(static_dir, old_logo_path.split('/static/')[-1])
        if os.path.exists(old_full_path):
            try:
                os.remove(old_full_path)
            except Exception:
                pass

    file_path = os.path.join(static_dir, filename)
    file.save(file_path)
    
    new_logo_path = f"/static/{filename}"
    config['logo_path'] = new_logo_path
    save_config(config)
    
    return jsonify({'success': True, 'message': 'อัปโหลดโลโก้สำเร็จ', 'logo_path': new_logo_path})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080, debug=True)
