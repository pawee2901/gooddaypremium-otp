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
// 3. จัดการการดึงข้อมูลตามประเภทของอีเมล (Maily vs IMAP Catch-All)
// =========================================================================
$lower_email = strtolower($email);
$is_maily_domain = false;
$maily_domains = ["@lico.moe", "@rdcw.plus", "@gooddaymail.com"];

foreach ($maily_domains as $d) {
    if (strpos($lower_email, $d) !== false) {
        $is_maily_domain = true;
        break;
    }
}

if ($is_maily_domain) {
    // -------------------------------------------------------------------------
    // ช่องทาง A: ดึงตรงจาก Maily Space API
    // -------------------------------------------------------------------------
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
    // ช่องทาง B: Centralized Catch-All (ดึงจาก Gmail หลักทั้งหมด)
    // -------------------------------------------------------------------------
    
    $imap_accounts_to_check = [];
    $direct_match_found = false;

    // หาว่าลูกค้ามีเมลตรงกับในระบบไหม
    if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
        foreach ($config_data['imap_emails'] as $imap_item) {
            if (strtolower($imap_item['email'] ?? '') === $lower_email && !empty($imap_item['password'])) {
                $imap_accounts_to_check[] = $imap_item;
                $direct_match_found = true;
                break;
            }
        }
    }

    // ถ้าไม่มีเมลตรงๆ (เช่นเป็น Hotmail ของลูกค้า) ให้หาใน Gmail กลางทั้งหมด
    if (!$direct_match_found) {
        // 1. หาจาก gmail_central_accounts ใน config.json
        if (isset($config_data['gmail_central_accounts']) && is_array($config_data['gmail_central_accounts'])) {
            foreach ($config_data['gmail_central_accounts'] as $gacc) {
                if (!empty($gacc['user']) && !empty($gacc['pass'])) {
                    $imap_accounts_to_check[] = [
                        'email' => $gacc['user'],
                        'password' => $gacc['pass'],
                        'host' => 'imap.gmail.com',
                        'port' => 993
                    ];
                }
            }
        }

        // 2. หาจาก imap_emails ที่เป็น gmail และมี password
        if (isset($config_data['imap_emails']) && is_array($config_data['imap_emails'])) {
            foreach ($config_data['imap_emails'] as $imap_item) {
                if (strpos(strtolower($imap_item['host'] ?? ''), 'gmail.com') !== false && !empty($imap_item['password'])) {
                    $already_in = false;
                    foreach ($imap_accounts_to_check as $chk) {
                        if (strtolower($chk['email'] ?? '') === strtolower($imap_item['email'] ?? '')) {
                            $already_in = true;
                            break;
                        }
                    }
                    if (!$already_in) {
                        $imap_accounts_to_check[] = $imap_item;
                    }
                }
            }
        }

        // 3. ถ้ายังไม่มี ให้ใช้ค่า default Gmail Central ทั้งหมด
        if (empty($imap_accounts_to_check)) {
            $imap_accounts_to_check = [
                ['email' => 'jj8168902@gmail.com', 'password' => 'wlfeoxoroayxoken', 'host' => 'imap.gmail.com', 'port' => 993],
                ['email' => 'phakhbona@gmail.com', 'password' => 'gtxlslpvzosnztrt', 'host' => 'imap.gmail.com', 'port' => 993]
            ];
        }
    }

    if (empty($imap_accounts_to_check)) {
        echo json_encode([
            'success' => false,
            'message' => 'ระบบยังไม่ได้ตั้งค่าบัญชีอีเมลกลางสำหรับดึงรหัสผ่าน (กรุณาให้แอดมินเพิ่มอีเมล IMAP)'
        ]);
        exit;
    }

    $matching_mails = [];

    // 0. ลองดึงผ่าน Cloud Run API ก่อน (กำหนด timeout ให้เร็ว 2 วินาที)
    $app_code = get_app_code($app_name);
    $cr_url = $cloud_run_url . '?email=' . urlencode($email) . '&app=' . urlencode($app_code);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $cr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $cr_resp = curl_exec($ch);
    $cr_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($cr_http === 200 && !empty($cr_resp)) {
        $cr_json = json_decode($cr_resp, true);
        if (isset($cr_json['emails']) && is_array($cr_json['emails']) && !empty($cr_json['emails'])) {
            foreach ($cr_json['emails'] as $mail) {
                $html_body = $mail['html'] ?? '';
                if (!empty($html_body)) {
                    $table_idx = strpos($html_body, '<table');
                    if ($table_idx !== false) {
                        $html_body = substr($html_body, $table_idx);
                    }
                }
                $otp_code = extract_otp_code($html_body) ?? '';
                $ref_code = extract_ref_code($html_body) ?? '';
                $formatted_time = parse_cloud_run_date_to_thai($mail['date'] ?? '');

                $matching_mails[] = [
                    'subject'   => $mail['subject'] ?? 'ไม่มีหัวข้อ',
                    'from'      => $mail['sender'] ?? "$app_name Security",
                    'time'      => $formatted_time,
                    'otp'       => $otp_code,
                    'ref'       => $ref_code,
                    'html_body' => $html_body
                ];
            }
        }
    }

    // 1. ถ้า Cloud Run ไม่พบบทความ ให้ค้นหาใน IMAP ของ Gmail กลาง (ความเร็วสูง)
    if (empty($matching_mails)) {
        foreach ($imap_accounts_to_check as $creds) {
            $imap_host = $creds['host'] ?? 'imap.gmail.com';
            $imap_port = intval($creds['port'] ?? 993);
            $imap_user = $creds['email'];
            $imap_pass = $creds['password'];

            // ลองเปิด INBOX ก่อน (เร็วกว่า [Gmail]/All Mail มาก)
            $mb_inbox = "{" . $imap_host . ":" . $imap_port . "/imap/ssl/novalidate-cert}INBOX";
            $mb_all   = "{" . $imap_host . ":" . $imap_port . "/imap/ssl/novalidate-cert}[Gmail]/All Mail";
            
            $imap_conn = @imap_open($mb_inbox, $imap_user, $imap_pass, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
            if (!$imap_conn) {
                $imap_conn = @imap_open($mb_all, $imap_user, $imap_pass, 0, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
            }

            if (!$imap_conn) {
                continue;
            }

            // ใช้ Sequence Number ดึง 15 ฉบับล่าสุดแบบ Instant (ไม่ต้องพึ่ง imap_search ที่ช้า)
            $check = @imap_check($imap_conn);
            $nmsgs = $check ? $check->Nmsgs : 0;
            
            if ($nmsgs > 0) {
                $start_msg = max(1, $nmsgs - 15);
                for ($msgno = $nmsgs; $msgno >= $start_msg; $msgno--) {
                    $header = @imap_headerinfo($imap_conn, $msgno);
                    if (!$header) continue;

                    $subject = isset($header->subject) ? @imap_utf8($header->subject) : '';
                    $from_obj = isset($header->from[0]) ? $header->from[0] : null;
                    $from_email = $from_obj ? ($from_obj->mailbox . '@' . $from_obj->host) : '';
                    
                    $to_obj = isset($header->to[0]) ? $header->to[0] : null;
                    $to_email = $to_obj ? ($to_obj->mailbox . '@' . $to_obj->host) : '';

                    // ดึงเฉพาะ body ทั้งฉบับในครั้งเดียว (Fast Path)
                    $b_raw = @imap_body($imap_conn, $msgno);
                    if (empty($b_raw)) {
                        $b_raw = @imap_fetchbody($imap_conn, $msgno, "1");
                    }

                    $body_decoded = $b_raw;
                    if (strpos($b_raw, 'Content-Transfer-Encoding: base64') !== false) {
                        $b64_parts = explode("\r\n\r\n", $b_raw, 2);
                        if (count($b64_parts) > 1) {
                            $body_decoded .= "\n" . base64_decode(preg_replace('/\s+/', '', $b64_parts[1]));
                        }
                    }
                    if (strpos($b_raw, 'quoted-printable') !== false) {
                        $body_decoded .= "\n" . quoted_printable_decode($b_raw);
                    }

                    $full_text = strtolower($subject . ' ' . $from_email . ' ' . $to_email . ' ' . $body_decoded);

                    // ตรวจสอบว่าเกี่ยวข้องกับ target email ของลูกค้าหรือไม่
                    $is_target = (strtolower($to_email) === $lower_email || strpos($full_text, $lower_email) !== false);
                    if (!$is_target) continue;

                    // ตรวจสอบว่าตรงกับแอปที่ลูกค้าเลือกหรือไม่
                    if (!matches_app($from_email, $subject, $app_name, $full_text)) continue;

                    $date_header = isset($header->date) ? $header->date : '';
                    $date_ts = strtotime($date_header);
                    $thai_time = $date_ts ? date('d/m/', $date_ts) . (date('Y', $date_ts) + 543) . ' ' . date('H:i', $date_ts) . ' น.' : $date_header;

                    $otp_code = extract_otp_code($body_decoded) ?? '';
                    $ref_code  = extract_ref_code($body_decoded) ?? '';

                    $matching_mails[] = [
                        'subject'   => $subject ?: 'ไม่มีหัวข้อ',
                        'from'      => $from_email ?: "$app_name Security",
                        'time'      => $thai_time,
                        'otp'       => $otp_code,
                        'ref'       => $ref_code,
                        'html_body' => $body_decoded
                    ];
                    break 2; // เจอแล้ว หยุดหาทันที
                }
            }
            @imap_close($imap_conn);
        }
    }

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
}
