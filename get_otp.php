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
$config_path = __DIR__ . '/config.json';
$config_data = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];

$maily_api_url = "https://api.maily.space/mail/public/mails";
$maily_tokens = [];
if (isset($config_data['api_providers']) && is_array($config_data['api_providers'])) {
    foreach ($config_data['api_providers'] as $p) {
        if (!empty($p['token']) && ($p['type'] ?? 'maily') === 'maily') {
            $maily_tokens[] = trim($p['token']);
        }
    }
}
if (empty($maily_tokens)) {
    $token_1 = $config_data['api_key_1'] ?? "sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1";
    $token_2 = $config_data['api_key_2'] ?? "otp_key_live_c376c68baadf1927b8459663e1511cbabdd71e46442025d944c87afae2f741b5";
    $maily_tokens = array_values(array_filter([$token_1, $token_2]));
}
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

if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
    foreach ($config_data['imap_emails'] as $item) {
        if (strtolower($item['email'] ?? '') === strtolower($email)) {
            if (isset($item['active']) && $item['active'] === false) {
                echo json_encode([
                    'success' => false,
                    'message' => 'อีเมลนี้ถูกปิดล็อกการใช้งานชั่วคราวจากผู้ดูแลระบบ (กรุณาติดต่อแอดมิน)'
                ]);
                exit;
            }
        }
    }
}

// =========================================================================
// ตรวจสอบ Forwarding Map: ถ้าอีเมลของลูกค้า (Hotmail) มีการตั้งค่า Forward
// → เปลี่ยนไปค้นหาใน Gmail กลางที่รับ Forward แทน
// =========================================================================
$forwarding_target = null;
$forwarding_imap_creds = null;
if (isset($config_data['forwarding_map']) && is_array($config_data['forwarding_map'])) {
    foreach ($config_data['forwarding_map'] as $fwd) {
        if (strtolower($fwd['source_email'] ?? '') === strtolower($email)) {
            $forwarding_target = strtolower($fwd['target_email']);
            // ค้นหา credentials ของ Gmail ปลายทางใน imap_emails
            if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
                foreach ($config_data['imap_emails'] as $imap_item) {
                    if (strtolower($imap_item['email'] ?? '') === $forwarding_target && !empty($imap_item['password'])) {
                        $forwarding_imap_creds = $imap_item;
                        break;
                    }
                }
            }
            break;
        }
    }
}

// ถ้ามี forwarding map และมี credentials → ดึง OTP จาก Gmail IMAP โดยตรง
if ($forwarding_target !== null) {
    if ($forwarding_imap_creds === null) {
        echo json_encode([
            'success' => false,
            'message' => "อีเมลนี้ถูกตั้งค่า Forwarding ไปยัง $forwarding_target แต่ยังไม่ได้เพิ่ม App Password ในระบบ (กรุณาเพิ่มในหน้าแอดมิน)"
        ]);
        exit;
    }

    // ดึงอีเมลผ่าน IMAP Gmail
    $imap_host = $forwarding_imap_creds['host'] ?? 'imap.gmail.com';
    $imap_port = intval($forwarding_imap_creds['port'] ?? 993);
    $imap_user = $forwarding_imap_creds['email'];
    $imap_pass = $forwarding_imap_creds['password'];

    $mailbox_str = "{" . $imap_host . ":" . $imap_port . "/imap/ssl/novalidate-cert}INBOX";
    $imap_conn = @imap_open($mailbox_str, $imap_user, $imap_pass, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);

    if (!$imap_conn) {
        $imap_err = imap_last_error();
        echo json_encode([
            'success' => false,
            'message' => "เชื่อมต่อ Gmail ($forwarding_target) ไม่ได้: ตรวจสอบ App Password อีกครั้ง"
        ]);
        exit;
    }

    // กำหนด keyword ค้นหาตามชื่อบริการ
    $lower_app = strtolower($app_name);
    if (strpos($lower_app, 'netflix') !== false) $search_kw = 'Netflix';
    elseif (strpos($lower_app, 'disney') !== false) $search_kw = 'Disney';
    elseif (strpos($lower_app, 'true') !== false) $search_kw = 'TrueID';
    elseif (strpos($lower_app, 'prime') !== false || strpos($lower_app, 'amazon') !== false) $search_kw = 'Amazon';
    else $search_kw = 'openai';

    $search_results = @imap_search($imap_conn, 'TEXT "' . addslashes($search_kw) . '"', SE_UID);
    if (!$search_results) {
        // ลองค้นหาแบบ ALL แล้วกรองเอง
        $search_results = @imap_search($imap_conn, 'ALL', SE_UID);
    }

    $matching_mails = [];
    if ($search_results) {
        rsort($search_results); // เรียงล่าสุดก่อน
        $limit = min(5, count($search_results));
        for ($i = 0; $i < $limit; $i++) {
            $uid = $search_results[$i];
            $header = @imap_rfc822_parse_headers(@imap_fetchheader($imap_conn, $uid, FT_UID));
            $subject = isset($header->subject) ? @imap_utf8($header->subject) : '';
            $from_obj = isset($header->from[0]) ? $header->from[0] : null;
            $from_email = $from_obj ? ($from_obj->mailbox . '@' . $from_obj->host) : '';

            // กรองตาม app keyword
            if (!matches_app($from_email, $subject, $app_name)) continue;

            // ดึง body
            $body = @imap_fetchbody($imap_conn, $uid, '1', FT_UID);
            if (empty($body)) $body = @imap_fetchbody($imap_conn, $uid, '1.1', FT_UID);

            // Decode encoding
            $struct = @imap_fetchstructure($imap_conn, $uid, FT_UID);
            $encoding = isset($struct->parts[0]->encoding) ? $struct->parts[0]->encoding : ($struct->encoding ?? 0);
            if ($encoding == 3) $body = base64_decode($body);
            elseif ($encoding == 4) $body = quoted_printable_decode($body);

            $date_header = isset($header->date) ? $header->date : '';
            $date_ts = strtotime($date_header);
            $thai_time = $date_ts ? date('d/m/', $date_ts) . (date('Y', $date_ts) + 543) . ' ' . date('H:i', $date_ts) . ' น.' : $date_header;

            $otp_code = extract_otp_code($body) ?? '';
            $ref_code  = extract_ref_code($body) ?? '';

            $matching_mails[] = [
                'subject'   => $subject ?: 'ไม่มีหัวข้อ',
                'from'      => $from_email,
                'time'      => $thai_time,
                'otp'       => $otp_code,
                'ref'       => $ref_code,
                'html_body' => $body
            ];

            if (count($matching_mails) >= 1) break; // แสดงล่าสุดฉบับเดียว
        }
    }
    @imap_close($imap_conn);

    if (empty($matching_mails)) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่พบอีเมลยืนยันของ $app_name ใน Gmail ($forwarding_target) กรุณารอสักครู่แล้วลองใหม่ หรือตรวจสอบว่า Outlook ส่งต่อมายัง Gmail แล้ว"
        ]);
        exit;
    }

    echo json_encode([
        'success'  => true,
        'app_name' => $app_name,
        'email'    => $email,
        'emails'   => $matching_mails
    ]);
    exit;
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
    
    $response = false;
    $http_code = 0;
    $working_token = "";
    
    foreach ($maily_tokens as $token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$maily_api_url?$query_params");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($code === 200 && $res) {
            $test_data = json_decode($res, true);
            if (isset($test_data['data']['mails'])) {
                $response = $res;
                $http_code = $code;
                $working_token = $token;
                break;
            }
        }
    }

    if ($http_code !== 200 || !$response) {
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
                    "Authorization: Bearer " . ($working_token ?: $maily_tokens[0]),
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
