<?php
// =========================================================================
// extras.php — ฟีเจอร์ที่ยกมาจากเว็บเช่า (sysrantotp) แพ็กเกจธรรมดา
//   Maily คีย์ sk_v1 + PIN กล่อง · Netflix 4/6 หลัก · แจ้งเตือน Discord · พื้นหลัง/ข้อความหน้าร้าน
//
// ค่าตั้งใหม่ทั้งหมดเป็น "คีย์เพิ่ม" ใน config.json — ไม่แก้/ไม่ลบคีย์เดิม:
//   shop_page       { bg_type, bg_color, bg_image, texts{} }
//   maily_pins      { "<email>": "123456" }
//   netflix         { default{four,six}, emails{ "<email>": {four,six} } }
//   discord_webhook "https://discord.com/api/webhooks/..."
// =========================================================================

function cfg_path() {
    return __DIR__ . '/config.json';
}

/**
 * แก้ config.json อย่างปลอดภัย: อ่านไฟล์ล่าสุด → แก้เฉพาะส่วนที่ต้องการ → เขียนไฟล์ชั่วคราวแล้วค่อยแทนที่
 * (ล็อกกันเขียนชนกัน · อ่านไฟล์เดิมไม่ออก = ไม่เขียนทับเด็ดขาด) คืน config ใหม่ หรือ false ถ้าไม่สำเร็จ
 */
function cfg_update(callable $fn) {
    $path = cfg_path();
    $lock = @fopen($path . '.lock', 'c');
    if ($lock) flock($lock, LOCK_EX);
    try {
        $config = [];
        if (file_exists($path)) {
            $config = json_decode((string)file_get_contents($path), true);
            if (!is_array($config)) return false;
        }
        $config = $fn($config);
        // ตัวอักษรที่ไม่ใช่ UTF-8 (เช่น วางข้อความจากโปรแกรมอื่น) → แทนที่แทนการบันทึกล้มทั้งไฟล์
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
        if ($json === false) return false;
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $json) !== false && @rename($tmp, $path)) return $config;
        @unlink($tmp);
        return file_put_contents($path, $json) !== false ? $config : false;
    } finally {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

// ---------------------------------------------------------------- หน้าร้าน

// ข้อความบนหน้าร้านที่แอดมินแก้เองได้: key => [ชื่อช่องในหลังบ้าน, ข้อความเดิม] (ช่องว่าง = ใช้ข้อความเดิม)
const SHOP_PAGE_TEXTS = [
    'tagline'      => ['ข้อความใต้ชื่อร้าน', 'กดรับ otp เองได้ ตลอด 24 ชั่วโมง'],
    'apps_title'   => ['หัวข้อหน้าหลัก (เลือกแอป)', 'เลือกแอปที่ต้องการรับรหัสยืนยัน'],
    'search_title' => ['หัวข้อหน้ากรอกอีเมล', 'ค้นหารหัสผ่านทางอีเมล'],
    'search_sub'   => ['คำอธิบายใต้หัวข้อหน้ากรอกอีเมล', 'ใส่ที่อยู่อีเมลของคุณเพื่อดึงรหัสยืนยันตัวล่าสุดได้ทันที'],
    'button'       => ['ข้อความปุ่มค้นหา', 'ค้นหารหัสยืนยัน (OTP)'],
];

function shop_page_options($config) {
    $p = (isset($config['shop_page']) && is_array($config['shop_page'])) ? $config['shop_page'] : [];
    return [
        'bg_type'  => ($p['bg_type'] ?? 'color') === 'image' ? 'image' : 'color',
        'bg_color' => (isset($p['bg_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $p['bg_color'])) ? $p['bg_color'] : '#ffffff',
        'bg_image' => (isset($p['bg_image']) && is_string($p['bg_image'])) ? $p['bg_image'] : '',
        'texts'    => (isset($p['texts']) && is_array($p['texts'])) ? $p['texts'] : [],
    ];
}

function shop_text($page, $key) {
    $v = $page['texts'][$key] ?? '';
    return (is_string($v) && trim($v) !== '') ? $v : SHOP_PAGE_TEXTS[$key][1];
}

// สไตล์พื้นหลังหน้าร้าน — รูปต้องเป็นไฟล์ใน /static/ หรือ https (กันค่าแปลก ๆ หลุดเข้า CSS)
function shop_bg_style($page) {
    if ($page['bg_type'] === 'image' && preg_match('#^(/static/[A-Za-z0-9_.-]+|https://[^\s"\'()\\\\]+)$#', $page['bg_image'])) {
        return "background: #fff url('" . $page['bg_image'] . "') center / cover no-repeat fixed;";
    }
    if ($page['bg_type'] === 'color' && strtolower($page['bg_color']) !== '#ffffff') {
        return 'background: ' . $page['bg_color'] . ';';
    }
    return '';
}

// ---------------------------------------------------------------- Maily

function maily_is_v1_key($token) {
    return (bool)preg_match('/^sk_v\d+_/i', (string)$token);
}

function maily_pin_for($config, $email) {
    $pins = (isset($config['maily_pins']) && is_array($config['maily_pins'])) ? $config['maily_pins'] : [];
    $pin = $pins[strtolower(trim((string)$email))] ?? '';
    return preg_match('/^\d{6}$/', (string)$pin) ? (string)$pin : '';
}

function maily_http($url, $headers, $post_body = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($post_body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$res, $code];
}

/**
 * ดึงรายการเมลของกล่อง Maily — คืน [response JSON รูปแบบเดิม {data:{mails:[]}}, http code, token ที่ใช้ได้]
 * - คีย์แบบใหม่ sk_v1_... (จาก maily.space/dashboard/api) ใช้ API v1: POST /v1/mails ส่ง apiKey + email ใน body
 * - Bearer token แบบเดิมใช้ /mail/public/mails · กล่องที่มี PIN ส่ง header X-Mailbox-Pin (อ่านด้วย PIN อย่างเดียวได้ ไม่ต้องมี token)
 */
function maily_fetch_list($api_url, $query_params, $email, $tokens, $pin) {
    $attempts = $pin !== '' ? array_merge([''], $tokens) : $tokens;
    foreach ($attempts as $token) {
        if (maily_is_v1_key($token)) {
            list($res, $code) = maily_http('https://api.maily.space/v1/mails', ['Content-Type: application/json'],
                json_encode(['apiKey' => $token, 'email' => strtolower($email), 'size' => 15, 'page' => 1]));
            $j = $res ? json_decode($res, true) : null;
            $mails = $j['mails'] ?? ($j['data']['mails'] ?? null);
            if ($code === 200 && is_array($mails)) {
                return [json_encode(['data' => ['mails' => $mails]]), 200, $token];
            }
            continue;
        }
        $headers = ['Content-Type: application/json'];
        if ($token !== '') $headers[] = "Authorization: Bearer $token";
        if ($pin !== '') $headers[] = "X-Mailbox-Pin: $pin";
        list($res, $code) = maily_http("$api_url?$query_params", $headers);
        if ($code === 200 && $res) {
            $j = json_decode($res, true);
            if (isset($j['data']['mails'])) return [$res, 200, $token];
        }
    }
    return [false, 0, ''];
}

// ---------------------------------------------------------------- Netflix 4 / 6 หลัก

// four = หารหัส 4 หลักก่อน (ยืนยันทีวี/ครัวเรือน) · six = ให้ลูกค้าเห็นรหัส 6 หลัก — ยังไม่เคยตั้ง: 4 ปิด · 6 เปิด
function nf_settings($config, $email) {
    $nf = (isset($config['netflix']) && is_array($config['netflix'])) ? $config['netflix'] : [];
    $def = (isset($nf['default']) && is_array($nf['default'])) ? $nf['default'] : [];
    $one = $nf['emails'][strtolower(trim((string)$email))] ?? [];
    if (!is_array($one)) $one = [];
    return [
        'four' => (bool)($one['four'] ?? ($def['four'] ?? false)),
        'six'  => (bool)($one['six'] ?? ($def['six'] ?? true)),
    ];
}

function otp_plain_text($html) {
    $html = preg_replace('#<(style|script)\b.*?</\1>#is', ' ', (string)$html);
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')));
}

/** ปรับผลลัพธ์ก่อนส่งให้ลูกค้า (Netflix 4/6 หลัก ตามที่แอดมินตั้งของอีเมลนั้น) */
function otp_postprocess(array $resp, $config, $email, $app_name) {
    if (empty($resp['emails']) || !is_array($resp['emails']) || stripos((string)$app_name, 'netflix') === false) return $resp;
    $nf = nf_settings($config, $email);
    foreach ($resp['emails'] as &$m) {
        if (!is_array($m)) continue;
        $body = ($m['subject'] ?? '') . ' ' . otp_plain_text($m['html_body'] ?? '');
        if ($nf['four'] && preg_match('/(?:code|otp|รหัส|โค้ด|verification|passcode|pin)[^0-9]{0,40}(?<!\d)(\d{4})(?!\d)/iu', $body, $mm)) {
            $m['otp'] = $mm[1];
        }
        if (!$nf['six']) {
            $code = preg_replace('/\D/', '', (string)($m['otp'] ?? ''));
            $six = strlen($code) === 6
                || ($code === '' && preg_match('/(?:รหัสยืนยัน|รหัสผ่านชั่วคราว|OTP|code|รหัส|โค้ด)[^0-9]{0,40}\b\d(?: ?\d){5}\b/iu', $body));
            if ($six) {
                $m['otp'] = '';
                $m['ref'] = '';
                $m['blocked'] = true;
                $m['html_body'] = '<div style="font-family:sans-serif;padding:40px 20px;text-align:center;color:#be185d"><h3>รหัส Netflix 6 หลักถูกปิดไว้</h3><p>หากต้องการใช้รหัสนี้ กรุณาติดต่อแอดมินร้านค่ะ</p></div>';
            }
        }
    }
    unset($m);
    return $resp;
}

// ---------------------------------------------------------------- Discord

function discord_webhook_ok($url) {
    return (bool)preg_match('#^https://(?:ptb\.|canary\.)?(?:discord|discordapp)\.com/api/webhooks/\d+/[\w-]+$#', (string)$url);
}

function discord_post($url, $title, $description, $color) {
    if (!discord_webhook_ok($url)) return false;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => 'OTP แจ้งเตือน',
        'embeds' => [['title' => $title, 'description' => $description, 'color' => $color, 'timestamp' => gmdate('c')]],
        'allowed_mentions' => ['parse' => []],
    ], JSON_UNESCAPED_UNICODE));
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 300;
}

/** แจ้งร้านทาง Discord เมื่อลูกค้าขอรหัส (ไม่ส่งตัวรหัสออกไป) — "ยังไม่พบ" ของอีเมลเดียวกันแจ้งได้ 1 ครั้ง / 10 นาที */
function otp_notify($config, $email, $app_name, $resp) {
    $hook = $config['discord_webhook'] ?? '';
    if (!discord_webhook_ok($hook) || $email === '') return;
    $found = (!empty($resp['success']) && !empty($resp['emails']) && is_array($resp['emails'])) ? count($resp['emails']) : 0;
    if ($found === 0) {
        $mark = sys_get_temp_dir() . '/otp_notify_' . md5(cfg_path() . '|' . strtolower($email));
        if (file_exists($mark) && filemtime($mark) > time() - 600) return;
        @touch($mark);
    }
    if ($found > 0) {
        discord_post($hook, "✅ ลูกค้าขอรหัส $app_name", "อีเมล: $email\nพบรหัส $found รายการ", 0x1e7a57);
    } else {
        $why = trim((string)($resp['message'] ?? ''));
        discord_post($hook, "⚠️ ลูกค้าขอรหัส $app_name แต่ยังไม่พบ", "อีเมล: $email" . ($why !== '' ? "\n$why" : ''), 0xf5821f);
    }
}

/**
 * ครอบผลลัพธ์ของ get_otp.php ทุกจุดที่ตอบกลับ (มีหลายจุดที่ echo + exit) — เรียกครั้งเดียวหลังตรวจข้อมูลขาเข้า
 * ปรับผลลัพธ์ (Netflix) → ส่งให้ลูกค้าก่อน → ค่อยแจ้ง Discord (ลูกค้าไม่ต้องรอ)
 */
function otp_capture_output($email, $app_name) {
    ob_start();
    register_shutdown_function(function () use ($email, $app_name) {
        $out = '';
        while (ob_get_level() > 0) $out = ob_get_clean() . $out;
        $config = (isset($GLOBALS['config_data']) && is_array($GLOBALS['config_data'])) ? $GLOBALS['config_data'] : [];
        $resp = json_decode($out, true);
        if (is_array($resp)) {
            $resp = otp_postprocess($resp, $config, $email, $app_name);
            $out = json_encode($resp);
        }
        echo $out;
        if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
        elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
        else flush();
        if (is_array($resp)) otp_notify($config, $email, $app_name, $resp);
    });
}
