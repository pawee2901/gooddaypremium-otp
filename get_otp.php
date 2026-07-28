<?php
error_reporting(0);
ini_set('display_errors', '0');
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
function matches_app($from, $subject, $name, $body = '') {
    $lower_from = strtolower($from);
    $lower_sub = strtolower($subject);
    $lower_app = strtolower($name);
    $lower_body = strtolower($body);
    
    if (strpos($lower_app, 'netflix') !== false) {
        return (strpos($lower_from, 'netflix') !== false || strpos($lower_sub, 'netflix') !== false || strpos($lower_body, 'netflix') !== false);
    } elseif (strpos($lower_app, 'disney') !== false) {
        return (strpos($lower_from, 'disney') !== false || strpos($lower_sub, 'disney') !== false || strpos($lower_body, 'disney') !== false);
    } elseif (strpos($lower_app, 'true') !== false) {
        return (strpos($lower_from, 'true') !== false || strpos($lower_sub, 'true') !== false || strpos($lower_body, 'true') !== false);
    } elseif (strpos($lower_app, 'chat') !== false || strpos($lower_app, 'openai') !== false || strpos($lower_app, 'gpt') !== false) {
        return (strpos($lower_from, 'openai') !== false || strpos($lower_sub, 'openai') !== false || strpos($lower_from, 'chatgpt') !== false || strpos($lower_sub, 'chatgpt') !== false || strpos($lower_body, 'openai') !== false || strpos($lower_body, 'chatgpt') !== false);
    } elseif (strpos($lower_app, 'prime') !== false || strpos($lower_app, 'amazon') !== false) {
        return (strpos($lower_from, 'prime') !== false || strpos($lower_sub, 'prime') !== false || strpos($lower_from, 'amazon') !== false || strpos($lower_sub, 'amazon') !== false || strpos($lower_body, 'prime') !== false || strpos($lower_body, 'amazon') !== false);
    }
    return (strpos($lower_from, $lower_app) !== false || strpos($lower_sub, $lower_app) !== false || strpos($lower_body, $lower_app) !== false);
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

// ฟังก์ชันถอดรหัส Content-Transfer-Encoding ของแต่ละส่วน MIME (3=BASE64, 4=QUOTED-PRINTABLE)
function decode_mime_part_data($data, $encoding) {
    switch ((int)$encoding) {
        case 3:
            return base64_decode($data);
        case 4:
            return quoted_printable_decode($data);
        default:
            return $data;
    }
}

// ฟังก์ชันดึงค่า charset จากพารามิเตอร์ของโครงสร้าง MIME part
function get_mime_part_charset($struct_part) {
    if (!empty($struct_part->parameters)) {
        foreach ($struct_part->parameters as $param) {
            if (strtolower($param->attribute) === 'charset') {
                return $param->value;
            }
        }
    }
    return 'UTF-8';
}

// ฟังก์ชันแกะโครงสร้าง MIME จริงของอีเมล (รองรับ multipart) เพื่อดึงเนื้อหา HTML/Plain ที่ถอดรหัสแล้ว
// (แก้ปัญหาเดิมที่ imap_body() คืนค่า MIME source ดิบๆ ที่ยังไม่ได้ decode มาแสดงตรงๆ)
function imap_extract_body($imap_conn, $msgno) {
    $structure = @imap_fetchstructure($imap_conn, $msgno);
    $result = ['html' => '', 'plain' => ''];
    if (!$structure) return $result;

    $collect = function($part, $part_num) use (&$collect, $imap_conn, $msgno, &$result) {
        if (!empty($part->parts) && is_array($part->parts)) {
            foreach ($part->parts as $idx => $sub_part) {
                $sub_num = ($part_num === '') ? (string)($idx + 1) : $part_num . '.' . ($idx + 1);
                $collect($sub_part, $sub_num);
            }
            return;
        }

        // สนใจเฉพาะส่วนที่เป็น TEXT (type 0 ตามมาตรฐาน IMAP)
        if ($part->type != 0) return;

        $raw = ($part_num === '') ? @imap_body($imap_conn, $msgno) : @imap_fetchbody($imap_conn, $msgno, $part_num);
        if ($raw === false || $raw === null || $raw === '') return;

        $raw = decode_mime_part_data($raw, $part->encoding ?? 0);

        $charset = get_mime_part_charset($part);
        if ($charset && strtoupper($charset) !== 'UTF-8') {
            $converted = @iconv($charset, 'UTF-8//IGNORE', $raw);
            if ($converted !== false) $raw = $converted;
        }

        $subtype = strtolower($part->subtype ?? '');
        if ($subtype === 'html') {
            $result['html'] .= $raw;
        } elseif ($subtype === 'plain') {
            $result['plain'] .= $raw;
        }
    };

    $collect($structure, '');
    return $result;
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
// 3. จัดการการดึงข้อมูลตามประเภทของอีเมล (Maily Space API & Central Accounts)
// =========================================================================
$lower_email = strtolower($email);
$maily_domains = ["@lico.moe", "@rdcw.plus", "@gooddaymail.com"];
$is_maily_domain = false;

foreach ($maily_domains as $d) {
    if (strpos($lower_email, $d) !== false) {
        $is_maily_domain = true;
        break;
    }
}

// สร้างรายการบัญชี Maily Space ที่ต้องสืบค้น
$maily_accounts_to_check = [];

if ($is_maily_domain) {
    $maily_accounts_to_check[] = $email;
}

// ดึงรายการ Maily Central Accounts เพิ่มเติมเสมอเพื่อรองรับอีเมลที่ส่งต่อ (Forwarding) มายัง Maily Space
if (isset($config_data['maily_central_accounts']) && is_array($config_data['maily_central_accounts'])) {
    foreach ($config_data['maily_central_accounts'] as $m_acc) {
        if (!in_array(strtolower($m_acc), array_map('strtolower', $maily_accounts_to_check))) {
            $maily_accounts_to_check[] = $m_acc;
        }
    }
}
if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
    foreach ($config_data['imap_emails'] as $item) {
        if (($item['provider'] ?? '') === 'maily' && !empty($item['email'])) {
            if (!in_array(strtolower($item['email']), array_map('strtolower', $maily_accounts_to_check))) {
                $maily_accounts_to_check[] = $item['email'];
            }
        }
    }
}
if (empty($maily_accounts_to_check)) {
    $maily_accounts_to_check[] = "codehotmail99@gooddaymail.com";
}

// ดำเนินการสืบค้นข้อมูลผ่าน Maily Space API
$maily_found_mails = [];

foreach ($maily_accounts_to_check as $target_maily_email) {
    $parts = explode('@', $target_maily_email, 2);
    if (count($parts) < 2) continue;
    
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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

    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        $mails = isset($data['data']['mails']) ? $data['data']['mails'] : [];
        
        foreach ($mails as $mail) {
            $html_body = $mail['html'] ?? '';
            
            // หากเนื้อหา html ว่างเปล่า ให้ดึงรายละเอียดจดหมาย (Detail API) ล่วงหน้า
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
            
            $full_text = strtolower(($mail['from'] ?? '') . ' ' . ($mail['subject'] ?? '') . ' ' . $html_body);
            
            $user_part = explode('@', $lower_email)[0] ?? '';
            $is_target = (strtolower($target_maily_email) === $lower_email);
            if (!$is_target && !empty($lower_email)) {
                if (strpos($full_text, $lower_email) !== false) {
                    $is_target = true;
                } elseif (!empty($user_part) && strlen($user_part) >= 3 && strpos($full_text, $user_part) !== false) {
                    $is_target = true;
                } elseif (in_array(strtolower($target_maily_email), array_map('strtolower', $maily_accounts_to_check))) {
                    $is_target = true; // อนุโลมสำหรับ Maily Central Forwarding ทุกบัญชีที่ตั้งค่าในระบบ
                }
            }
            if (!$is_target) continue;

            if (matches_app($mail['from'] ?? '', $mail['subject'] ?? '', $app_name, $full_text)) {
                $otp_code = extract_otp_code($html_body) ?? '';
                $ref_code = extract_ref_code($html_body) ?? '';
                $time_formatted = parse_utc_timestamp_to_thai($mail['createdAt'] ?? '');
                
                $maily_found_mails[] = [
                    'subject' => $mail['subject'] ?? 'ไม่มีหัวข้อ',
                    'from' => $mail['from'] ?? '',
                    'time' => $time_formatted,
                    'otp' => $otp_code,
                    'ref' => $ref_code,
                    'html_body' => $html_body
                ];
                
                break; // พบอีเมลฉบับล่าสุดเรียบร้อยแล้ว
            }
        }
    }

    if (!empty($maily_found_mails) && !empty($maily_found_mails[0]['otp'])) {
        break; // พบรหัส OTP จาก Maily Space แล้ว ไม่ต้องสืบค้นบัญชีถัดไป
    }
}

// หากพบข้อมูลผ่าน Maily Space ให้ส่งผลลัพธ์กลับทันที
if (!empty($maily_found_mails)) {
    echo json_encode([
        'success' => true,
        'app_name' => $app_name,
        'email' => $email,
        'emails' => $maily_found_mails
    ]);
    exit;
}

// หากผู้ใช้ค้นหาด้วยอีเมลที่เป็นโดเมน Maily Space โดยตรงแล้วไม่พบข้อมูล ให้คืนค่าแจ้งเตือน
if ($is_maily_domain) {
    echo json_encode([
        'success' => false,
        'message' => "ไม่พบอีเมลยืนยันตัวตนสำหรับ $app_name ส่งมายัง $email (กรุณากดส่งรหัส OTP ใหม่อีกครั้ง)"
    ]);
    exit;
}

// -------------------------------------------------------------------------
// ช่องทาง B1: Cloud Run API ของลูกค้า (RDCW) — ลองก่อนเสมอเพราะเร็วกว่าการไล่สแกน IMAP มาก
// -------------------------------------------------------------------------
$cloud_run_found = [];
$app_code = get_app_code($app_name);
$cr_query = http_build_query([
    "senderEmail" => $email,
    "appCode" => $app_code
]);

$ch_cr = curl_init();
curl_setopt($ch_cr, CURLOPT_URL, "$cloud_run_url?$cr_query");
curl_setopt($ch_cr, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_cr, CURLOPT_TIMEOUT, 10);
curl_setopt($ch_cr, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch_cr, CURLOPT_SSL_VERIFYHOST, false);
$cr_response = @curl_exec($ch_cr);
$cr_http_code = curl_getinfo($ch_cr, CURLINFO_HTTP_CODE);
curl_close($ch_cr);

if ($cr_http_code === 200 && $cr_response) {
    $cr_data = json_decode($cr_response, true);
    $cr_emails = (isset($cr_data['emails']) && is_array($cr_data['emails'])) ? $cr_data['emails'] : [];

    foreach ($cr_emails as $mail) {
        // เก็บ HTML ต้นฉบับไว้ใช้ค้นหา OTP เสมอ เพราะบางเทมเพลต (เช่น ChatGPT/OpenAI)
        // วางรหัสไว้ก่อนแท็ก <table> แรก การตัดข้อความก่อนหน้านั้นทิ้งอาจทำให้รหัสที่แท้จริงหายไป
        // และไปหยิบตัวเลขอื่นที่ไม่เกี่ยวข้อง (เช่น ปีลิขสิทธิ์ในท้ายอีเมล) มาแสดงผิดแทน
        $cr_original_html = $mail['html'] ?? '';
        $cr_html_body = $cr_original_html;
        if ($cr_html_body) {
            $table_idx = strpos($cr_html_body, '<table');
            if ($table_idx !== false) {
                $cr_html_body = substr($cr_html_body, $table_idx);
            }
        }

        $otp_code = extract_otp_code($cr_original_html) ?? '';
        $ref_code = extract_ref_code($cr_original_html) ?? '';
        $formatted_time = parse_cloud_run_date_to_thai($mail['date'] ?? '');

        $cloud_run_found[] = [
            'subject'   => $mail['subject'] ?? 'ไม่มีหัวข้อ',
            'from'      => $mail['sender'] ?? ($app_name . ' Security'),
            'time'      => $formatted_time,
            'otp'       => $otp_code,
            'ref'       => $ref_code,
            'html_body' => $cr_html_body
        ];
    }
}

if (!empty($cloud_run_found)) {
    echo json_encode([
        'success'  => true,
        'app_name' => $app_name,
        'email'    => $email,
        'emails'   => $cloud_run_found
    ]);
    exit;
}

// -------------------------------------------------------------------------
// ช่องทาง B2: Centralized Catch-All (ดึงจาก Gmail หลักทั้งหมด) — Fallback หาก Cloud Run ไม่พบ
// -------------------------------------------------------------------------

    $imap_accounts_to_check = [];
    $direct_match_found = false;

    // หาว่าลูกค้ามีเมลตรงกับในระบบไหม (ลอง Direct Match ก่อน)
    if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
        foreach ($config_data['imap_emails'] as $imap_item) {
            if (strtolower($imap_item['email'] ?? '') === $lower_email && !empty($imap_item['password'])) {
                $imap_item['__is_direct'] = true; // กล่องเมลของลูกค้าเอง (ไม่ใช่บัญชีกลาง) ใช้เส้นทางไวได้
                $imap_accounts_to_check[] = $imap_item;
                break;
            }
        }
    }

    // เพิ่ม Gmail Central Accounts ต่อท้ายเสมอ เพื่อเป็น Fallback สำรองหาก Direct Match ล้มเหลวหรือไม่มี OTP
    $gmail_central = [];
    if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
        foreach ($config_data['imap_emails'] as $imap_item) {
            if (strpos(strtolower($imap_item['host'] ?? ''), 'gmail.com') !== false && !empty($imap_item['password'])) {
                if (empty($imap_accounts_to_check) || strtolower($imap_accounts_to_check[0]['email'] ?? '') !== strtolower($imap_item['email'] ?? '')) {
                    $gmail_central[] = $imap_item;
                }
            }
        }
    }
    if (empty($gmail_central)) {
        $gmail_central = [
            ['email' => 'jj8168902@gmail.com', 'password' => 'wlfeoxoroayxoken', 'host' => 'imap.gmail.com', 'port' => 993],
            ['email' => 'phakhbona@gmail.com', 'password' => 'gtxlslpvzosnztrt', 'host' => 'imap.gmail.com', 'port' => 993]
        ];
    }
    foreach ($gmail_central as $gc) {
        $imap_accounts_to_check[] = $gc;
    }

    if (empty($imap_accounts_to_check)) {
        echo json_encode([
            'success' => false,
            'message' => 'ระบบยังไม่ได้ตั้งค่าบัญชีอีเมลกลางสำหรับดึงรหัสผ่าน (กรุณาให้แอดมินเพิ่มอีเมล IMAP)'
        ]);
        exit;
    }

    $all_found = [];

    // วนลูปเช็คทีละบัญชี
    foreach ($imap_accounts_to_check as $creds) {
        $imap_host = $creds['host'] ?? 'imap.gmail.com';
        $imap_port = intval($creds['port'] ?? 993);
        $imap_user = $creds['email'];
        $imap_pass = $creds['password'];
        $is_direct_account = !empty($creds['__is_direct']);

        $mailbox_str = "{" . $imap_host . ":" . $imap_port . "/imap/ssl/novalidate-cert}INBOX";
        $imap_conn = @imap_open($mailbox_str, $imap_user, $imap_pass, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);

        if (!$imap_conn) {
            continue; // ข้ามไปเช็กเมลต่อไปถ้าต่อไม่ได้
        }

        // ค้นหาจาก 30 อีเมลล่าสุด
        $num_msg = @imap_num_msg($imap_conn);
        if ($num_msg > 0) {
            $start_msg = max(1, $num_msg - 29); // สแกนลึก 30 ฉบับล่าสุดในกล่องกลาง

            // ดึง header ของทุกฉบับในช่วงที่สนใจมาในคำสั่งเดียว (เร็วกว่าการวนขอทีละฉบับแบบเดิมมาก
            // เพราะ imap_headerinfo() แบบเดิมต้องยิงคำสั่งแยกไปเซิร์ฟเวอร์ทีละฉบับ)
            $overviews = @imap_fetch_overview($imap_conn, "$start_msg:$num_msg");
            if ($overviews) {
                usort($overviews, function($a, $b) { return ($b->msgno ?? 0) - ($a->msgno ?? 0); }); // ใหม่สุดก่อน

                foreach ($overviews as $ov) {
                    $msgno = isset($ov->msgno) ? (int)$ov->msgno : 0;
                    if (!$msgno) continue;

                    $from_email = $ov->from ?? '';
                    $to_email = $ov->to ?? '';
                    $subject = isset($ov->subject) ? @imap_utf8($ov->subject) : '';

                    // ตรวจสอบอายุจดหมาย - ถ้าส่งมาเกิน 2 ชั่วโมง ให้ข้ามทันที
                    $udate = isset($ov->udate) ? intval($ov->udate) : (isset($ov->date) ? (strtotime($ov->date) ?: time()) : time());
                    if ((time() - $udate) > 7200) {
                        continue;
                    }

                    $body = null;
                    $full_text = '';

                    if ($is_direct_account) {
                        // กล่องเมลของลูกค้าเอง (ไม่ใช่บัญชีกลาง Forwarding) — ทุกฉบับในนี้เป็นของลูกค้าอยู่แล้ว
                        // เช็คจาก header (from/subject) ก่อนว่าตรงกับแอปที่เลือกไหม เพื่อข้ามการดึง body
                        // ของฉบับที่ไม่เกี่ยวข้องไปเลย (เร็วขึ้นมากเมื่อเทียบกับการดึง body ทุกฉบับแบบเดิม)
                        if (!matches_app($from_email, $subject, $app_name, '')) {
                            continue;
                        }

                        $mail_content = imap_extract_body($imap_conn, $msgno);
                        $body = $mail_content['html'] !== '' ? $mail_content['html'] : $mail_content['plain'];
                        $full_text = strtolower($subject . ' ' . $from_email . ' ' . $to_email . ' ' . $body);
                    } else {
                        // บัญชีกลาง (Gmail Forwarding Central) — อีเมลเป้าหมายของลูกค้าอาจฝังอยู่ในเนื้อหาที่ถูกส่งต่อมา
                        // ไม่ใช่ใน header To: ตรงๆ จึงต้องดึง body มาค้นหาเสมอ (พฤติกรรมเดิม คงไว้เพื่อความถูกต้อง)
                        $mail_content = imap_extract_body($imap_conn, $msgno);
                        $body = $mail_content['html'] !== '' ? $mail_content['html'] : $mail_content['plain'];
                        $full_text = strtolower($subject . ' ' . $from_email . ' ' . $to_email . ' ' . $body);

                        if (strpos($full_text, $lower_email) === false) {
                            continue;
                        }
                        if (!matches_app($from_email, $subject, $app_name, $full_text)) {
                            continue;
                        }
                    }

                    // แปลงเป็นเวลาไทย (Asia/Bangkok) อย่างชัดเจน แทนการพึ่ง timezone เริ่มต้นของเซิร์ฟเวอร์ (มักตั้งเป็น UTC ทำให้เวลาที่แสดงคลาดเคลื่อน 7 ชั่วโมง)
                    $dt_thai = new DateTime('@' . $udate);
                    $dt_thai->setTimezone(new DateTimeZone('Asia/Bangkok'));
                    $thai_time = $dt_thai->format('d/m/') . ((int)$dt_thai->format('Y') + 543) . ' ' . $dt_thai->format('H:i') . ' น.';

                    $otp_code = extract_otp_code($body) ?? '';
                    $ref_code  = extract_ref_code($body) ?? '';

                    $all_found[] = [
                        'subject'   => $subject ?: 'ไม่มีหัวข้อ',
                        'from'      => $from_email,
                        'time'      => $thai_time,
                        'timestamp' => $udate,
                        'otp'       => $otp_code,
                        'ref'       => $ref_code,
                        'html_body' => $body
                    ];

                    // หากพบรหัส OTP แล้ว ให้หยุดอ่านฉบับถัดไปทันที
                    if (!empty($otp_code)) {
                        break;
                    }
                }
            }
        }
        @imap_close($imap_conn);

        // หากพบรหัส OTP ในบัญชีนี้เรียบร้อยแล้ว ไม่จำเป็นต้องค้นหาบัญชีถัดไป
        if (!empty($all_found) && !empty($all_found[0]['otp'])) {
            break;
        }
    }

    // เรียงจดหมายที่พบทั้งหมดตามเวลาล่าสุดจริงๆ (timestamp จากมากไปน้อย)
    usort($all_found, function($a, $b) {
        return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
    });

    $matching_mails = $all_found;

    if (empty($matching_mails)) {
        echo json_encode([
            'success' => false,
            'message' => "ไม่พบอีเมลยืนยันตัวตนล่าสุดของ $app_name ส่งมายัง $email (กรุณากดส่งรหัส OTP ใหม่อีกครั้ง หรือตรวจสอบการตั้งค่า Forwarding)"
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
