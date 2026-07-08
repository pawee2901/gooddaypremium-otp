<?php
header('Content-Type: application/json; charset=utf-8');

// =========================================================================
// 1. รับค่าอีเมลและชื่อบริการจากหน้าบ้าน (POST Method)
// =========================================================================
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$app_name = isset($_POST['app_name']) ? trim($_POST['app_name']) : '';

if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุอีเมลเพื่อค้นหารหัส OTP'
    ]);
    exit;
}

if (empty($app_name)) {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาระบุชื่อบริการที่เลือกดึงรหัส'
    ]);
    exit;
}

// ตรวจสอบรูปแบบอีเมลเบื้องต้น
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'รูปแบบอีเมลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'
    ]);
    exit;
}

// =========================================================================
// 2. การตั้งค่าการเชื่อมต่อ API จริง (Maily Space & Cloud Run)
// =========================================================================
$maily_api_url = "https://api.maily.space/mail/public/mails";
$maily_token = "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1"; // Production Token
$cloud_run_url = "https://getemails-wfudlrftlq-uc.a.run.app/getEmails";

// ฟังก์ชันแปลงชื่อบริการเป็นรหัสย่อของระบบ RDCW Cloud Run
function get_app_code($name) {
    $lower = strtolower($name);
    if (strpos($lower, 'netflix') !== false) {
        return 'NF';
    } elseif (strpos($lower, 'disney') !== false) {
        return 'DN';
    } elseif (strpos($lower, 'true') !== false) {
        return 'TM';
    } elseif (strpos($lower, 'chat') !== false || strpos($lower, 'openai') !== false || strpos($lower, 'gpt') !== false) {
        return 'GPT';
    } elseif (strpos($lower, 'prime') !== false || strpos($lower, 'amazon') !== false) {
        return 'PR';
    }
    return 'GPT';
}

// ฟังก์ชันกรองหัวข้อผู้ส่งให้ตรงตามบริการหลัก
function matches_app($from, $subject, $name) {
    $lower_from = strtolower($from);
    $lower_sub = strtolower($subject);
    $lower_app = strtolower($name);
    
    if (strpos($lower_app, 'netflix') !== false) {
        return (strpos($lower_from, 'netflix') !== false || strpos($lower_sub, 'netflix') !== false);
    } elseif (strpos($lower_app, 'disney') !== false) {
        return (strpos($lower_from, 'disney') !== false || strpos($lower_sub, 'disney') !== false);
    } elseif (strpos($lower_app, 'true') !== false) {
        return (strpos($lower_from, 'true') !== false || strpos($lower_sub, 'true') !== false);
    } elseif (strpos($lower_app, 'chat') !== false || strpos($lower_app, 'openai') !== false) {
        return (strpos($lower_from, 'openai') !== false || strpos($lower_sub, 'openai') !== false || strpos($lower_from, 'chatgpt') !== false || strpos($lower_sub, 'chatgpt') !== false);
    } elseif (strpos($lower_app, 'prime') !== false || strpos($lower_app, 'amazon') !== false) {
        return (strpos($lower_from, 'prime') !== false || strpos($lower_sub, 'prime') !== false || strpos($lower_from, 'amazon') !== false || strpos($lower_sub, 'amazon') !== false);
    }
    return false;
}

// ฟังก์ชันแปลงรูปแบบเวลา UTC จาก Maily Space เป็นเวลาไทยท้องถิ่น (GMT+07:00)
function parse_utc_timestamp_to_thai($ts_str) {
    try {
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/', $ts_str, $matches)) {
            $year = intval($matches[1]);
            $month = intval($matches[2]);
            $day = intval($matches[3]);
            $hour = intval($matches[4]);
            $minute = intval($matches[5]);
            $second = intval($matches[6]);
            
            $dt = new DateTime("$year-$month-$day $hour:$minute:$second", new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Asia/Bangkok'));
            
            $thai_months = [
                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
            ];
            
            $day_num = intval($dt->format('d'));
            $month_name = $thai_months[intval($dt->format('m'))];
            $time_str = $dt->format('H:i');
            
            return "$day_num $month_name เวลา $time_str น. (ตามเวลาประเทศไทย)";
        }
    } catch (Exception $e) {}
    return date('d/m/Y H:i น.');
}

// ฟังก์ชันแปลงวันเวลาของ Cloud Run (e.g. "02/06/2026 06:17") เป็นเวลาไทย
function parse_cloud_run_date_to_thai($date_str) {
    try {
        $dt = DateTime::createFromFormat('d/m/Y H:i', $date_str, new DateTimeZone('UTC'));
        if ($dt) {
            $dt->setTimezone(new DateTimeZone('Asia/Bangkok'));
            $thai_months = [
                1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
                5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
                9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
            ];
            
            $day_num = intval($dt->format('d'));
            $month_name = $thai_months[intval($dt->format('m'))];
            $time_str = $dt->format('H:i');
            
            return "$day_num $month_name เวลา $time_str น. (ตามเวลาประเทศไทย)";
        }
    } catch (Exception $e) {}
    return date('d/m/Y H:i น.');
}

// ฟังก์ชันแกะตัวเลข OTP อย่างแม่นยำจาก HTML Body
function extract_otp_code($html_body) {
    if (empty($html_body)) return null;
    
    // ลบเนื้อหาภายในแท็ก style และ script ออกทั้งหมดก่อนเพื่อป้องกันการดึงรหัสสี/สไตล์ (เช่น #121212)
    $clean_html = preg_replace('/<(style|script)\b[^>]*>(.*?)<\/\1>/is', '', $html_body);
    $plain_text = strip_tags($clean_html);
    
    // 1. ค้นหารูปแบบข้อความที่มีคีย์เวิร์ดนำหน้าภาษาไทย/อังกฤษเพื่อความแม่นยำสูงสุด (รองรับคำเชื่อม "ของคุณ/ของท่าน")
    if (preg_match('/(?:รหัสยืนยัน|รหัสผ่านชั่วคราว|OTP|code|รหัส|โค้ด)(?:\s*(?:ของคุณ|ของท่าน))?\s*(?:คือ|:|\s)\s*(\d{4,8})/ui', $plain_text, $matches)) {
        return $matches[1];
    }
    
    // 2. ตรวจสอบรหัส 6 หลักติดกัน (เป็นหลักทั่วไปของ OTP)
    if (preg_match('/\b\d{6}\b/', $plain_text, $matches)) {
        return $matches[0];
    }
    
    // 3. ตรวจสอบรหัส 4-8 หลักอื่นๆ
    if (preg_match('/\b\d{4,8}\b/', $plain_text, $matches)) {
        return $matches[0];
    }
    
    return null;
}

// ฟังก์ชันดึงรหัสอ้างอิง (Reference Code) จาก HTML Body
function extract_ref_code($html_body) {
    if (empty($html_body)) return '';
    
    // ลบเนื้อหาภายในแท็ก style และ script ออกทั้งหมดก่อน
    $clean_html = preg_replace('/<(style|script)\b[^>]*>(.*?)<\/\1>/is', '', $html_body);
    $plain_text = strip_tags($clean_html);
    
    if (preg_match('/(?:รหัสอ้างอิง|อ้างอิง|Ref|Reference)\s*(?:คือ|:|\s)\s*([A-Za-z0-9]{4,10})/ui', $plain_text, $matches)) {
        return $matches[1];
    }
    return '';
}

$lower_email = strtolower($email);
$is_maily_domain = false;
$maily_domains = ["@lico.moe", "@rdcw.plus", "@gooddaymail.com"];

foreach ($maily_domains as $d) {
    if (strpos($lower_email, $d) !== false) {
        $is_maily_domain = true;
        break;
    }
}

// -------------------------------------------------------------------------
// ช่องทาง A: ดึงตรงจาก Maily Space API
// -------------------------------------------------------------------------
if ($is_maily_domain) {
    $parts = explode('@', $email, 2);
    $account_name = strtolower(trim($parts[0]));
    $domain_id = str_replace('.', '', strtolower(trim($parts[1])));
    
    $query_params = http_build_query([
        "size" => 15,
        "page" => 1,
        "accountName" => $account_name,
        "domainId" => $domain_id
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$maily_api_url?$query_params");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $maily_token",
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);
    
    if ($curl_err || $http_code !== 200) {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่สามารถเชื่อมต่อระบบ Maily Space ได้ชั่วคราว กรุณาลองใหม่อีกครั้ง'
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    $mails = isset($data['data']['mails']) ? $data['data']['mails'] : [];
    
    if (empty($mails)) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่พบกล่องข้อความใดๆ สำหรับอีเมล $email ในขณะนี้"
        ]);
        exit;
    }
    
    $matching_mails = [];
    foreach ($mails as $mail) {
        if (matches_app($mail['from'] ?? '', $mail['subject'] ?? '', $app_name)) {
            $html_body = $mail['html'] ?? '';
            
            // หากเนื้อหา html ว่างเปล่า ให้ดึงผ่านรายละเอียดจดหมาย (Detail API)
            if (empty($html_body) && !empty($mail['id'])) {
                $mail_id = $mail['id'];
                $detail_params = http_build_query([
                    "accountName" => $account_name,
                    "domainId" => $domain_id
                ]);
                $detail_url = "https://api.maily.space/mail/public/mails/$mail_id?$detail_params";
                
                $ch_detail = curl_init();
                curl_setopt($ch_detail, CURLOPT_URL, $detail_url);
                curl_setopt($ch_detail, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_detail, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch_detail, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch_detail, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch_detail, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer $maily_token",
                    "Content-Type: application/json"
                ]);
                $detail_response = curl_exec($ch_detail);
                curl_close($ch_detail);
                
                if ($detail_response) {
                    $detail_data = json_decode($detail_response, true);
                    if (isset($detail_data['data']['html'])) {
                        $html_body = $detail_data['data']['html'];
                    }
                }
            }
            
            $otp_code = extract_otp_code($html_body) ?? '';
            $ref_code = extract_ref_code($html_body) ?? '';
            $time_formatted = parse_utc_timestamp_to_thai($mail['createdAt'] ?? '');
            
            $matching_mails[] = [
                'subject' => $mail['subject'] ?? 'ไม่มีหัวข้อ',
                'from' => $mail['from'] ?? '',
                'time' => $time_formatted,
                'otp' => $otp_code,
                'ref' => $ref_code,
                'html_body' => $html_body
            ];
            
            // แสดงเฉพาะจดหมายเข้าล่าสุดฉบับเดียวเท่านั้น
            break;
        }
    }
    
    if (empty($matching_mails)) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่พบอีเมลยืนยันสำหรับ $app_name ส่งมายังกล่องจดหมายนี้ (กรุณากดส่งรหัส OTP หรือรอสักครู่แล้วค้นหาอีกครั้ง)"
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'app_name' => $app_name,
        'email' => $email,
        'emails' => $matching_mails
    ]);
    exit;

} else {
    // -------------------------------------------------------------------------
    // ช่องทาง B: ดึงจาก Cloud Run API ( Hotmail, Gmail ฯลฯ )
    // -------------------------------------------------------------------------
    $app_code = get_app_code($app_name);
    $query_params = http_build_query([
        "senderEmail" => trim($email),
        "appCode" => $app_code
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$cloud_run_url?$query_params");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);
    
    if ($curl_err || $http_code !== 200) {
        echo json_encode([
            'success' => false,
            'message' => 'ระบบคลาวด์เชื่อมต่อขัดข้องชั่วคราว กรุณาตรวจสอบความถูกต้องของอีเมลหรือลองใหม่อีกครั้ง'
        ]);
        exit;
    }
    
    $data = json_decode($response, true);
    $emails = isset($data['emails']) ? $data['emails'] : [];
    
    if (empty($emails)) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่พบอีเมลยืนยันตัวตนล่าสุดของ $app_name ส่งมายัง $email (กรุณากดส่งรหัส OTP หรือตรวจสอบว่าได้ลงทะเบียนเมลนี้แล้ว)"
        ]);
        exit;
    }
    
    $matching_mails = [];
    foreach ($emails as $mail) {
        $html_body = $mail['html'] ?? '';
        
        // ดึงเฉพาะเนื้อหา Table หลักหากพบเพื่อป้องกัน overflow หน้าเว็บ
        if (!empty($html_body)) {
            $table_idx = strpos($html_body, "<table");
            if ($table_idx !== false) {
                $html_body = substr($html_body, $table_idx);
            }
        }
        
        $otp_code = extract_otp_code($html_body) ?? '';
        $ref_code = extract_ref_code($html_body) ?? '';
        $time_formatted = parse_cloud_run_date_to_thai($mail['date'] ?? '');
        
        $matching_mails[] = [
            'subject' => $mail['subject'] ?? 'ไม่มีหัวข้อ',
            'from' => $mail['sender'] ?? "$app_name Security",
            'time' => $time_formatted,
            'otp' => $otp_code,
            'ref' => $ref_code,
            'html_body' => $html_body
        ];
    }
    
    if (empty($matching_mails)) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่พบอีเมลยืนยันตัวตนล่าสุดสำหรับ $app_name"
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'app_name' => $app_name,
        'email' => $email,
        'emails' => $matching_mails
    ]);
    exit;
}
