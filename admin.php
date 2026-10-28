<?php
session_start();

$config_path = __DIR__ . '/config.json';
require_once __DIR__ . '/extras.php'; // ฟีเจอร์เสริมจากเว็บเช่า
$config = [
    'shop_name' => 'gooddaypremium',
    'logo_path' => '/static/logo.jpg',
    'disney_logo_path' => '',
    'trueid_logo_path' => '',
    'admin_username' => 'admin',
    'admin_password' => 'admin1234',
    'api_key_1' => 'sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1',
    'api_key_2' => 'otp_key_live_c376c68baadf1927b8459663e1511cbabdd71e46442025d944c87afae2f741b5',
    'apps' => [
        [
            'id' => 'app_disney',
            'name' => 'Disney+',
            'code' => 'DN',
            'logo_path' => '/static/logo_disney_1780413683.jpg',
            'keywords' => 'disney',
            'bg_color' => '#F0F7FF',
            'border_color' => '#D6E9FF',
            'active' => true
        ],
        [
            'id' => 'app_trueid',
            'name' => 'TrueID',
            'code' => 'TM',
            'logo_path' => '/static/logo_trueid_1780413707.jpg',
            'keywords' => 'true',
            'bg_color' => '#FFF5FA',
            'border_color' => '#FFE2F3',
            'active' => true
        ]
    ],
    'imap_emails' => [
        [
            'id' => 'email_1',
            'email' => 'amethystejenala@outlook.com',
            'provider' => 'hotmail',
            'host' => 'outlook.office365.com',
            'port' => 993,
            'password' => '',
            'app_id' => 'all',
            'active' => true
        ],
        [
            'id' => 'email_2',
            'email' => 'phakhbona@gmail.com',
            'provider' => 'gmail',
            'host' => 'imap.gmail.com',
            'port' => 993,
            'password' => '',
            'app_id' => 'all',
            'active' => true
        ]
    ],
    'api_providers' => [
        [
            'id' => 'maily_space_1',
            'name' => 'Maily Space Key 1',
            'type' => 'maily',
            'token' => 'sk_v1_jtv42y05jqab3e1is2xh85nfwuhnp5x1',
            'active' => true
        ],
        [
            'id' => 'maily_space_2',
            'name' => 'Maily Space Key 2',
            'type' => 'maily',
            'token' => 'otp_key_live_c376c68baadf1927b8459663e1511cbabdd71e46442025d944c87afae2f741b5',
            'active' => true
        ],
        [
            'id' => 'cloud_run_rdcw',
            'name' => 'Cloud Run (RDCW)',
            'type' => 'cloud_run',
            'url' => 'https://getemails-wfudlrftlq-uc.a.run.app/getEmails',
            'active' => true
        ]
    ]
];

// โหลดการตั้งค่าจากไฟล์ JSON
if (file_exists($config_path)) {
    $json_data = file_get_contents($config_path);
    $decoded = json_decode($json_data, true);
    if (is_array($decoded)) {
        $config = array_merge($config, $decoded);
    }
} else {
    file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// เช็คความปลอดภัยการล็อกอิน
$logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// ==========================================
// ส่วนประมวลผลคำขอร้องแบบ AJAX (POST Asynchronous)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($action === 'login') {
        header('Content-Type: application/json');
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if ($username === $config['admin_username'] && $password === $config['admin_password']) {
            $_SESSION['admin_logged_in'] = true;
            echo json_encode(['success' => true, 'message' => 'เข้าสู่ระบบสำเร็จ']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
        }
        exit;
    }

    // สิทธิ์การเข้าถึงสำหรับ AJAX ทั่วไป
    if (!$logged_in) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
        exit;
    }

    // --- Action: อัปเดตข้อมูลความปลอดภัย/ชื่อร้าน ---
    if ($action === 'update') {
        header('Content-Type: application/json');
        $shop_name = isset($_POST['shop_name']) ? trim($_POST['shop_name']) : '';
        $new_username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($shop_name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อร้าน']);
            exit;
        }

        $config['shop_name'] = $shop_name;
        if (!empty($new_username)) $config['admin_username'] = $new_username;
        if (!empty($new_password)) $config['admin_password'] = $new_password;
        if (isset($_POST['api_key_1'])) $config['api_key_1'] = trim($_POST['api_key_1']);
        if (isset($_POST['api_key_2'])) $config['api_key_2'] = trim($_POST['api_key_2']);

        if (file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
        }
        exit;
    }

    // =====================================================================
    // ฟีเจอร์เสริมจากเว็บเช่า (extras.php) — แก้ config.json ผ่าน cfg_update (อ่านไฟล์ล่าสุด แก้เฉพาะคีย์ใหม่)
    // =====================================================================
    $json_out = function ($ok, $message, $extra = []) {
        if (!$ok) http_response_code(400);
        echo json_encode(array_merge(['success' => (bool)$ok, 'message' => $message], $extra));
        exit;
    };

    // --- Netflix 4/6 หลัก รายอีเมล (f = four | six) ---
    if ($action === 'nf_toggle') {
        header('Content-Type: application/json');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $f = ($_POST['f'] ?? '') === 'four' ? 'four' : 'six';
        if ($email === '') $json_out(false, 'ไม่พบอีเมล');
        $now = null;
        $ok = cfg_update(function ($c) use ($email, $f, &$now) {
            $now = !nf_settings($c, $email)[$f];
            $c['netflix']['emails'][$email][$f] = $now;
            return $c;
        });
        $json_out($ok !== false, $ok === false ? 'บันทึกไม่สำเร็จ' : ('รหัส Netflix ' . ($f === 'four' ? '4' : '6') . ' หลัก: ' . ($now ? 'เปิด' : 'ปิด') . ' เรียบร้อยแล้ว'), ['on' => $now]);
    }

    // --- Netflix 4/6 หลัก ทุกอีเมล (ตั้งค่าเริ่มต้น + ทับค่ารายอีเมลทั้งหมด) ---
    if ($action === 'nf_bulk') {
        header('Content-Type: application/json');
        $f = ($_POST['f'] ?? '') === 'four' ? 'four' : 'six';
        $on = filter_var($_POST['on'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $ok = cfg_update(function ($c) use ($f, $on) {
            $c['netflix']['default'][$f] = $on;
            foreach (($c['netflix']['emails'] ?? []) as $em => $v) $c['netflix']['emails'][$em][$f] = $on;
            return $c;
        });
        $json_out($ok !== false, $ok === false ? 'บันทึกไม่สำเร็จ' : ('รหัส Netflix ' . ($f === 'four' ? '4' : '6') . ' หลัก: ' . ($on ? 'เปิด' : 'ปิด') . ' ทุกอีเมลแล้ว'));
    }

    // --- PIN 6 หลักของกล่อง Maily (ว่าง = ลบ) ---
    if ($action === 'save_maily_pin') {
        header('Content-Type: application/json');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pin = preg_replace('/\D/', '', $_POST['pin'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $json_out(false, 'อีเมลไม่ถูกต้อง');
        if ($pin !== '' && strlen($pin) !== 6) $json_out(false, 'PIN กล่อง Maily ต้องเป็นตัวเลข 6 หลัก');
        $ok = cfg_update(function ($c) use ($email, $pin) {
            if ($pin === '') unset($c['maily_pins'][$email]); else $c['maily_pins'][$email] = $pin;
            return $c;
        });
        $json_out($ok !== false, $ok === false ? 'บันทึกไม่สำเร็จ' : ($pin === '' ? 'ลบ PIN กล่อง Maily แล้ว' : 'บันทึก PIN กล่อง Maily แล้ว'));
    }

    // --- แจ้งเตือน Discord: บันทึก + ส่งข้อความทดสอบ / ยกเลิก ---
    if ($action === 'save_discord') {
        header('Content-Type: application/json');
        $hook = trim($_POST['webhook'] ?? '');
        if ($hook === '') {
            $ok = cfg_update(function ($c) { unset($c['discord_webhook']); return $c; });
            $json_out($ok !== false, $ok === false ? 'บันทึกไม่สำเร็จ' : 'ยกเลิกแจ้งเตือน Discord แล้ว');
        }
        if (!discord_webhook_ok($hook)) $json_out(false, 'ลิงก์ Webhook ไม่ถูกต้อง (ต้องขึ้นต้นด้วย https://discord.com/api/webhooks/)');
        if (!discord_post($hook, '🔔 เชื่อมต่อแจ้งเตือนร้าน ' . ($config['shop_name'] ?? '') . ' สำเร็จ', 'ห้องนี้จะได้รับแจ้งเตือนเมื่อลูกค้าขอรหัส OTP จากเว็บของคุณ', 0x5865f2)) {
            $json_out(false, 'ส่งข้อความไปที่ Webhook นี้ไม่ได้ — ตรวจว่าคัดลอกลิงก์ครบ และ Webhook ยังไม่ถูกลบ');
        }
        $ok = cfg_update(function ($c) use ($hook) { $c['discord_webhook'] = $hook; return $c; });
        $json_out($ok !== false, $ok === false ? 'บันทึกไม่สำเร็จ' : 'เชื่อมต่อแล้ว — ส่งข้อความทดสอบเข้าห้อง Discord ของคุณแล้ว');
    }

    // --- หน้าร้าน: พื้นหลัง + ข้อความ ---
    if ($action === 'save_shop_page') {
        header('Content-Type: application/json');
        $bg_type = ($_POST['bg_type'] ?? '') === 'image' ? 'image' : 'color';
        $bg_color = trim($_POST['bg_color'] ?? '#ffffff');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $bg_color)) $bg_color = '#ffffff';
        $texts = [];
        foreach (array_keys(SHOP_PAGE_TEXTS) as $k) {
            $v = trim($_POST['text_' . $k] ?? '');
            if ($v !== '') $texts[$k] = function_exists('mb_substr') ? mb_substr($v, 0, 120) : substr($v, 0, 360);
        }
        $ok = cfg_update(function ($c) use ($bg_type, $bg_color, $texts) {
            $page = shop_page_options($c);
            $page['bg_type'] = $bg_type;
            $page['bg_color'] = $bg_color;
            $page['texts'] = $texts;
            $c['shop_page'] = $page;
            return $c;
        });
        $json_out($ok !== false, $ok === false ? 'บันทึกพื้นหลัง/ข้อความหน้าร้านไม่สำเร็จ' : 'บันทึกหน้าร้านเรียบร้อยแล้ว');
    }

    // --- อัปโหลดรูปพื้นหลังหน้าร้าน ---
    if ($action === 'upload_bg') {
        header('Content-Type: application/json');
        $file = $_FILES['bg'] ?? null;
        $ext = $file ? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) : '';
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            $json_out(false, 'กรุณาเลือกไฟล์รูปภาพ (png, jpg, gif, webp)');
        }
        $filename = 'bg_custom_' . time() . '.' . $ext;
        $static_dir = __DIR__ . '/static/';
        if (!is_dir($static_dir)) mkdir($static_dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $static_dir . $filename)) $json_out(false, 'ไม่สามารถบันทึกไฟล์รูปภาพได้');
        $ok = cfg_update(function ($c) use ($filename) {
            $page = shop_page_options($c);
            $page['bg_image'] = '/static/' . $filename;
            $page['bg_type'] = 'image';
            $c['shop_page'] = $page;
            return $c;
        });
        $json_out($ok !== false, $ok === false ? 'บันทึกไม่สำเร็จ' : 'อัปโหลดรูปพื้นหลังสำเร็จ', ['bg_image' => '/static/' . $filename]);
    }

    // --- Action: เพิ่ม/แก้ไข อีเมล IMAP ---
    if ($action === 'add_imap_email') {
        header('Content-Type: application/json');
        $email_addr = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $provider = isset($_POST['provider']) ? trim($_POST['provider']) : 'gmail';
        $host = isset($_POST['host']) ? trim($_POST['host']) : '';
        $port = isset($_POST['port']) ? intval($_POST['port']) : 993;
        $app_id = isset($_POST['app_id']) ? trim($_POST['app_id']) : 'all';

        if (empty($email_addr)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุที่อยู่อีเมล']);
            exit;
        }

        // กำหนด host อัตโนมัติหากไม่ได้กรอก
        if (empty($host)) {
            if ($provider === 'gmail' || strpos(strtolower($email_addr), '@gmail') !== false) {
                $host = 'imap.gmail.com';
                $provider = 'gmail';
            } elseif ($provider === 'hotmail' || strpos(strtolower($email_addr), '@hotmail') !== false || strpos(strtolower($email_addr), '@outlook') !== false || strpos(strtolower($email_addr), '@live') !== false) {
                $host = 'outlook.office365.com';
                $provider = 'hotmail';
            } elseif ($provider === 'maily' || strpos(strtolower($email_addr), '@lico.moe') !== false || strpos(strtolower($email_addr), '@rdcw.plus') !== false || strpos(strtolower($email_addr), '@gooddaymail.com') !== false) {
                $host = 'api.maily.space';
                $provider = 'maily';
            } else {
                $host = 'imap.gmail.com';
            }
        }

        if (!isset($config['imap_emails']) || !is_array($config['imap_emails'])) {
            $config['imap_emails'] = [];
        }

        // เช็คว่ามีอยู่แล้วหรือไม่
        $found_index = -1;
        foreach ($config['imap_emails'] as $idx => $item) {
            if (strtolower($item['email']) === strtolower($email_addr)) {
                $found_index = $idx;
                break;
            }
        }

        // อีเมล Maily: PIN 6 หลักของกล่อง (ถ้าตั้งไว้ใน maily.space)
        $maily_pin = isset($_POST['maily_pin']) ? preg_replace('/\D/', '', $_POST['maily_pin']) : '';
        if ($provider === 'maily' && strlen($maily_pin) === 6) {
            $config['maily_pins'][strtolower($email_addr)] = $maily_pin;
        }

        $new_item = [
            'id' => $found_index >= 0 ? $config['imap_emails'][$found_index]['id'] : 'email_' . time(),
            'email' => $email_addr,
            'provider' => $provider,
            'host' => $host,
            'port' => $port,
            'password' => !empty($password) ? $password : ($found_index >= 0 ? $config['imap_emails'][$found_index]['password'] : ''),
            'app_id' => $app_id,
            'active' => true
        ];

        if ($found_index >= 0) {
            $config['imap_emails'][$found_index] = $new_item;
        } else {
            $config['imap_emails'][] = $new_item;
        }

        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'บันทึกอีเมล IMAP เรียบร้อยแล้ว', 'data' => $config['imap_emails']]);
        exit;
    }

    // --- Action: ลบอีเมล IMAP ---
    if ($action === 'delete_imap_email') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';

        if (!empty($id) && isset($config['imap_emails']) && is_array($config['imap_emails'])) {
            $config['imap_emails'] = array_values(array_filter($config['imap_emails'], function($item) use ($id) {
                return $item['id'] !== $id && $item['email'] !== $id;
            }));
            file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        echo json_encode(['success' => true, 'message' => 'ลบอีเมลเรียบร้อยแล้ว', 'data' => $config['imap_emails']]);
        exit;
    }

    // --- Action: สลับสถานะเปิด-ปิด/ล็อกอีเมล IMAP ---
    if ($action === 'toggle_email_active') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $active = isset($_POST['active']) ? filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN) : false;

        if (!empty($id) && isset($config['imap_emails']) && is_array($config['imap_emails'])) {
            foreach ($config['imap_emails'] as $idx => $item) {
                if (($item['id'] ?? '') === $id || ($item['email'] ?? '') === $id) {
                    $config['imap_emails'][$idx]['active'] = $active;
                    break;
                }
            }
            file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        echo json_encode(['success' => true, 'message' => $active ? 'ปลดล็อกเปิดใช้งานอีเมลแล้ว' : 'ล็อกการใช้งานอีเมลเรียบร้อยแล้ว', 'data' => $config['imap_emails']]);
        exit;
    }

    // --- Action: เพิ่ม/แก้ไข แอปพลิเคชัน (OTP Apps) ---
    if ($action === 'save_app') {
        header('Content-Type: application/json');
        $app_id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $keywords = isset($_POST['keywords']) ? trim($_POST['keywords']) : '';
        $bg_color = isset($_POST['bg_color']) ? trim($_POST['bg_color']) : '#F0F7FF';
        $border_color = isset($_POST['border_color']) ? trim($_POST['border_color']) : '#D6E9FF';
        $logo_path = isset($_POST['logo_path']) ? trim($_POST['logo_path']) : '';

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุชื่อแอปพลิเคชัน']);
            exit;
        }

        if (!isset($config['apps']) || !is_array($config['apps'])) {
            $config['apps'] = [];
        }

        // จัดการอัปโหลดโลโก้ถ้ามีส่งมา
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
            if (in_array($ext, $allowed)) {
                $filename = 'app_logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
                $static_dir = __DIR__ . '/static/';
                if (!is_dir($static_dir)) mkdir($static_dir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $static_dir . $filename)) {
                    $logo_path = '/static/' . $filename;
                }
            }
        }

        $found_index = -1;
        if (!empty($app_id)) {
            foreach ($config['apps'] as $idx => $item) {
                if ($item['id'] === $app_id) {
                    $found_index = $idx;
                    break;
                }
            }
        }

        $app_data = [
            'id' => $found_index >= 0 ? $config['apps'][$found_index]['id'] : 'app_' . time(),
            'name' => $name,
            'code' => !empty($code) ? strtoupper($code) : 'GPT',
            'logo_path' => !empty($logo_path) ? $logo_path : ($found_index >= 0 ? $config['apps'][$found_index]['logo_path'] : ''),
            'keywords' => strtolower($keywords),
            'bg_color' => $bg_color,
            'border_color' => $border_color,
            'active' => true
        ];

        if ($found_index >= 0) {
            $config['apps'][$found_index] = $app_data;
        } else {
            $config['apps'][] = $app_data;
        }

        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'บันทึกแอปพลิเคชันสำเร็จ', 'data' => $config['apps']]);
        exit;
    }

    // --- Action: ลบแอปพลิเคชัน ---
    if ($action === 'delete_app') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';

        if (!empty($id) && isset($config['apps']) && is_array($config['apps'])) {
            $config['apps'] = array_values(array_filter($config['apps'], function($item) use ($id) {
                return $item['id'] !== $id;
            }));
            file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        echo json_encode(['success' => true, 'message' => 'ลบแอปพลิเคชันเรียบร้อยแล้ว', 'data' => $config['apps']]);
        exit;
    }

    // --- Action: เพิ่ม/แก้ไข API Key ---
    if ($action === 'save_api_key') {
        header('Content-Type: application/json');
        $api_id = isset($_POST['id']) ? trim($_POST['id']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $token = isset($_POST['token']) ? trim($_POST['token']) : '';
        $type = isset($_POST['type']) ? trim($_POST['type']) : 'maily';

        if (empty($name) || empty($token)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อและคีย์ API']);
            exit;
        }

        if (!isset($config['api_providers']) || !is_array($config['api_providers'])) {
            $config['api_providers'] = [];
        }

        $found_index = -1;
        if (!empty($api_id)) {
            foreach ($config['api_providers'] as $idx => $item) {
                if ($item['id'] === $api_id) {
                    $found_index = $idx;
                    break;
                }
            }
        }

        $item_data = [
            'id' => $found_index >= 0 ? $config['api_providers'][$found_index]['id'] : 'api_' . time(),
            'name' => $name,
            'token' => $token,
            'type' => $type,
            'active' => true
        ];

        if ($found_index >= 0) {
            $config['api_providers'][$found_index] = $item_data;
        } else {
            $config['api_providers'][] = $item_data;
        }

        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'บันทึก API Key สำเร็จ', 'data' => $config['api_providers']]);
        exit;
    }

    // --- Action: ลบ API Key ---
    if ($action === 'delete_api_key') {
        header('Content-Type: application/json');
        $id = isset($_POST['id']) ? trim($_POST['id']) : '';

        if (!empty($id) && isset($config['api_providers']) && is_array($config['api_providers'])) {
            $config['api_providers'] = array_values(array_filter($config['api_providers'], function($item) use ($id) {
                return $item['id'] !== $id;
            }));
            file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        echo json_encode(['success' => true, 'message' => 'ลบ API Key เรียบร้อยแล้ว', 'data' => $config['api_providers']]);
        exit;
    }


    // --- Action: อัปโหลดโลโก้ร้าน ---
    if ($action === 'upload_logo') {
        header('Content-Type: application/json');
        if (!isset($_FILES['logo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์ภาพ']);
            exit;
        }

        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'อัปโหลดล้มเหลว']);
            exit;
        }

        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_extensions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไฟล์ต้องเป็นประเภทรูปภาพเท่านั้น']);
            exit;
        }

        $filename = 'logo_custom_' . time() . '.' . $ext;
        $static_dir = __DIR__ . '/static/';
        if (!is_dir($static_dir)) mkdir($static_dir, 0755, true);

        if (move_uploaded_file($file['tmp_name'], $static_dir . $filename)) {
            $new_logo_path = '/static/' . $filename;
            $config['logo_path'] = $new_logo_path;
            file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'message' => 'อัปโหลดโลโก้สำเร็จ', 'logo_path' => $new_logo_path]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพได้']);
        }
        exit;
    }

    // --- Action: บันทึก Client ID / Client Secret ของ Microsoft App (สำหรับเชื่อมต่อ Hotmail/Outlook ผ่าน Graph API) ---
    if ($action === 'save_microsoft_oauth') {
        header('Content-Type: application/json');
        $ms_client_id = isset($_POST['client_id']) ? trim($_POST['client_id']) : '';
        $ms_client_secret = isset($_POST['client_secret']) ? trim($_POST['client_secret']) : '';

        if (empty($ms_client_id) || empty($ms_client_secret)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณากรอก Client ID และ Client Secret ให้ครบ']);
            exit;
        }

        if (!isset($config['microsoft_oauth']) || !is_array($config['microsoft_oauth'])) {
            $config['microsoft_oauth'] = [];
        }
        $config['microsoft_oauth']['client_id'] = $ms_client_id;
        $config['microsoft_oauth']['client_secret'] = $ms_client_secret;

        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่า Microsoft App สำเร็จ']);
        exit;
    }
}

// ดำเนินการ Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

// เริ่มขั้นตอนเชื่อมต่อบัญชี Hotmail/Outlook ผ่าน Microsoft OAuth (ส่งแอดมินไปหน้า Login ของ Microsoft)
if (isset($_GET['action']) && $_GET['action'] === 'connect_microsoft') {
    if (!$logged_in) {
        header('Location: admin.php');
        exit;
    }

    $ms_client_id = $config['microsoft_oauth']['client_id'] ?? '';
    if (empty($ms_client_id)) {
        header('Location: admin.php?ms_error=missing_config#api');
        exit;
    }

    // สร้าง state แบบเซ็นลายเซ็นด้วย client_secret แทนการเก็บใน Session เพราะ browser ต้องกระโดดออกไปที่
    // login.microsoftonline.com แล้ววกกลับมา ทำให้ Session Cookie อาจไม่ติดกลับมาด้วย (ขึ้นอยู่กับ policy ของแต่ละโฮสติ้ง)
    // การเซ็นด้วย HMAC แบบนี้ตรวจสอบความถูกต้องได้โดยไม่ต้องพึ่ง Session เลย
    $ms_client_secret = $config['microsoft_oauth']['client_secret'] ?? '';
    $ms_nonce = bin2hex(random_bytes(16));
    $ms_state = $ms_nonce . '.' . hash_hmac('sha256', $ms_nonce, $ms_client_secret);

    // ต้องตรงกับ Redirect URI ที่ลงทะเบียนไว้ใน Azure App Registration เป๊ะๆ (รวมทั้ง https:// และ .php ต่อท้าย)
    $ms_redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/ms_callback.php';

    $ms_auth_url = 'https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize?' . http_build_query([
        'client_id'     => $ms_client_id,
        'response_type' => 'code',
        'redirect_uri'  => $ms_redirect_uri,
        'response_mode' => 'query',
        'scope'         => 'offline_access Mail.Read openid profile email',
        'state'         => $ms_state,
        'prompt'        => 'select_account' // บังคับให้ Microsoft โชว์หน้าเลือกบัญชีทุกครั้ง ไม่ auto-login ด้วยบัญชีเดิมที่เพิ่งล็อกอินไว้
    ]);

    header('Location: ' . $ms_auth_url);
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($config['shop_name']); ?> - ระบบจัดการหลังบ้าน Admin Control Panel</title>
    <!-- Google Fonts for premium typography: Inter (English) and Mitr (Thai) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Mitr:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Mitr', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', 'Mitr', 'sans-serif';
        }
        .main-card {
            background: #ffffff;
            box-shadow: 0 4px 25px -4px rgba(0, 0, 0, 0.05), 0 2px 10px -2px rgba(0, 0, 0, 0.02);
        }
        .tab-btn {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tab-btn.active {
            background-color: #F3E8FF;
            color: #7E22CE;
            border-color: #E9D5FF;
            font-weight: 700;
        }
        .tab-btn.inactive {
            background-color: #FFFFFF;
            color: #4B5563;
            border-color: #E5E7EB;
        }
        .tab-btn.inactive:hover {
            background-color: #F9FAFB;
            color: #111827;
        }
            .email-card.kind-hidden { display: none !important; }
        .app-chip { display:inline-flex; align-items:center; gap:.35rem; font-size:11px; font-weight:700; padding:.35rem .7rem; border-radius:999px; background:#fff; border:1px solid #E9D5FF; color:#6B21A8; cursor:pointer; transition:all .2s; }
        .app-chip span { font-size:10px; background:#F3E8FF; color:#7E22CE; border-radius:999px; padding:0 .4rem; }
        .app-chip:hover { background:#FAF5FF; }
        .app-chip.active { background:#7E22CE; border-color:transparent; color:#fff; box-shadow:0 4px 12px -4px rgba(126,34,206,.5); }
        .app-chip.active span { background:rgba(255,255,255,.3); color:#fff; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-start p-3 md:p-6 selection:bg-purple-600 selection:text-white">

    <!-- Container หลัก -->
    <div class="w-full max-w-4xl flex flex-col gap-5">

        <!-- Top Header & Brand -->
        <div class="flex items-center justify-between bg-white p-4 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" id="headerLogo" alt="<?php echo htmlspecialchars($config['shop_name']); ?> logo" 
                         class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                    <div class="absolute -bottom-0.5 -right-0.5 bg-purple-600 text-white w-4 h-4 rounded-full flex items-center justify-center text-[7px]">
                        <i class="fa-solid fa-gear"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold text-gray-900 leading-tight" id="headerShopName"><?php echo htmlspecialchars($config['shop_name']); ?></h1>
                    <p class="text-[11px] text-gray-400 font-medium">ระบบปรับแต่งและตั้งค่าหลังบ้าน (Admin Dashboard)</p>
                </div>
            </div>

            <?php if ($logged_in): ?>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-1.5 text-xs text-emerald-600 font-semibold bg-emerald-50 px-3 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                </span>
                <a href="admin.php?action=logout" class="text-xs font-bold text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-2 rounded-2xl transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden xs:inline">ออกจากระบบ</span>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$logged_in): ?>
        <!-- ========================================== -->
        <!-- 1. LOGIN CARD (กรณีไม่ได้เข้าสู่ระบบ) -->
        <!-- ========================================== -->
        <div class="main-card w-full max-w-md mx-auto rounded-3xl border border-gray-100 p-6 md:p-8 space-y-6">
            <div class="text-center space-y-1">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-2 text-xl shadow-sm">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-800">เข้าสู่ระบบผู้ดูแลระบบ</h2>
                <p class="text-xs text-gray-400">กรอกบัญชีผู้ดูแลเพื่อจัดการระบบหลังบ้าน</p>
            </div>

            <form id="loginForm" onsubmit="handleLogin(event)" class="space-y-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-600 pl-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-user text-xs"></i>
                        </div>
                        <input type="text" id="username" required placeholder="ชื่อผู้ใช้งาน" 
                               class="w-full pl-9 pr-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 outline-none text-sm">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-600 pl-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-key text-xs"></i>
                        </div>
                        <input type="password" id="password" required placeholder="รหัสผ่าน" 
                               class="w-full pl-9 pr-10 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-purple-600 focus:border-purple-600 outline-none text-sm">
                    </div>
                </div>

                <button type="submit" id="loginBtn"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 px-6 rounded-2xl transition-all shadow-md active:scale-[0.98] text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>เข้าสู่ระบบ</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="index.php" class="text-xs font-semibold text-gray-400 hover:text-black transition-colors">
                    <i class="fa-solid fa-arrow-left-long mr-1"></i>กลับไปที่หน้าหลักลูกค้า
                </a>
            </div>
        </div>

        <?php else: ?>

        <!-- ========================================== -->
        <!-- 2. ADMIN TABS NAVIGATION (แถบแท็บ 3 ส่วน) -->
        <!-- ========================================== -->
        <div class="grid grid-cols-3 gap-1.5 sm:gap-2 bg-white p-1.5 sm:p-2 rounded-2xl border border-gray-100 shadow-sm text-center">
            
            <!-- Tab 1: IMAP Email -->
            <button onclick="switchTab('imap')" id="tabBtnImap"
                    class="tab-btn active border px-2 sm:px-3 py-2.5 sm:py-3 rounded-xl flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 text-xs md:text-sm font-semibold">
                <i class="fa-solid fa-envelope text-sm sm:text-base"></i>
                <span class="block sm:hidden text-[11px] font-bold">อีเมล IMAP</span>
                <span class="hidden sm:block truncate">บัญชีอีเมล IMAP</span>
            </button>

            <!-- Tab 2: OTP Apps -->
            <button onclick="switchTab('apps')" id="tabBtnApps"
                    class="tab-btn inactive border px-2 sm:px-3 py-2.5 sm:py-3 rounded-xl flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 text-xs md:text-sm font-semibold">
                <i class="fa-solid fa-mobile-screen-button text-sm sm:text-base"></i>
                <span class="block sm:hidden text-[11px] font-bold">แอปพลิเคชัน</span>
                <span class="hidden sm:block truncate">ตั้งค่าแอปพลิเคชัน (OTP Apps)</span>
            </button>

            <!-- Tab 3: API Providers -->
            <button onclick="switchTab('api')" id="tabBtnApi"
                    class="tab-btn inactive border px-2 sm:px-3 py-2.5 sm:py-3 rounded-xl flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 text-xs md:text-sm font-semibold">
                <i class="fa-solid fa-key text-sm sm:text-base"></i>
                <span class="block sm:hidden text-[11px] font-bold">ตั้งค่า API</span>
                <span class="hidden sm:block truncate">ตั้งค่า API (OTP Providers)</span>
            </button>
        </div>

        <!-- Notification Banner -->
        <div id="noticeBanner" class="hidden bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                <span id="noticeText">บันทึกสำเร็จ!</span>
            </div>
            <button onclick="document.getElementById('noticeBanner').classList.add('hidden')" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1 CONTENT: จัดการอีเมล IMAP (รับ OTP อัตโนมัติ) -->
        <!-- ========================================== -->
<?php
    // ปุ่ม Netflix 4 / 6 ของแต่ละอีเมล (เขียว = เปิด แดง = ปิด)
    function nf_buttons_html($config, $email) {
        $nf = nf_settings($config, $email);
        $e = htmlspecialchars(strtolower($email), ENT_QUOTES);
        $btn = function ($f, $d) use ($nf, $e) {
            return '<button type="button" onclick="toggleNf(\'' . $e . '\', \'' . $f . '\')" class="w-6 h-5 rounded-md text-[10px] font-black text-white ' . ($nf[$f] ? 'bg-emerald-500' : 'bg-rose-500') . '">' . $d . '</button>';
        };
        return '<span class="inline-flex items-center gap-1 text-[10px] font-bold px-1.5 py-0.5 rounded-lg border border-gray-200 bg-white" title="Netflix: 4 = หารหัส 4 หลักก่อน · 6 = ให้ลูกค้าเห็นรหัส 6 หลัก (เขียว=เปิด แดง=ปิด)"><span class="text-red-600 font-black text-[9px]">NETFLIX</span>' . $btn('four', '4') . $btn('six', '6') . '</span>';
    }
?>
        <div id="tabContentImap" class="space-y-5">
            <?php
                // รายการอีเมลทั้งหมดของร้าน (Gmail / Hotmail / Maily) ในกล่องเดียว — แบบหน้าหลังบ้านเว็บเช่า
                $imap_emails = (isset($config['imap_emails']) && is_array($config['imap_emails'])) ? $config['imap_emails'] : [];
                $email_kind = function ($item) {
                    $p = $item['provider'] ?? '';
                    if ($p === 'microsoft_graph' || $p === 'hotmail') return 'hotmail';
                    return $p === 'maily' ? 'maily' : 'gmail';
                };
                $kind_count = ['gmail' => 0, 'hotmail' => 0, 'maily' => 0];
                $app_count = [];
                foreach ($imap_emails as $item) {
                    $kind_count[$email_kind($item)]++;
                    $aid = $item['app_id'] ?? 'all';
                    $app_count[$aid] = ($app_count[$aid] ?? 0) + 1;
                }
                $kind_icon = [
                    'gmail'   => ['fa-brands fa-google', 'bg-red-50 text-red-500'],
                    'hotmail' => ['fa-brands fa-microsoft', 'bg-sky-50 text-sky-600'],
                    'maily'   => ['fa-solid fa-inbox', 'bg-purple-50 text-purple-600'],
                ];
                $ms_config = isset($config['microsoft_oauth']) && is_array($config['microsoft_oauth']) ? $config['microsoft_oauth'] : [];
                $ms_client_id = $ms_config['client_id'] ?? '';
                $ms_client_secret = $ms_config['client_secret'] ?? '';
                $ms_ready = $ms_client_id !== '' && $ms_client_secret !== '';
            ?>
            <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-5">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2"><span class="text-purple-600 text-lg"><i class="fa-regular fa-envelope"></i></span><h2 class="text-base font-extrabold text-gray-900">อีเมลของร้าน</h2></div>
                        <p class="text-xs text-gray-400">เพิ่มอีเมลของร้าน แล้วลูกค้ากรอกอีเมลในหน้า OTP เพื่อขอรหัสได้เลย</p>
                    </div>
                    <button type="button" onclick="window.location.reload()" title="รีเฟรช" class="w-9 h-9 flex-shrink-0 bg-white hover:bg-gray-50 text-gray-500 border border-gray-200 rounded-xl flex items-center justify-center transition-all"><i class="fa-solid fa-rotate-right text-xs"></i></button>
                </div>

                <!-- ปุ่มเพิ่มอีเมลของแต่ละแบบ — 3 กล่องแถวเดียวกันทุกขนาดจอ -->
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    <button type="button" onclick="openAddEmailModal('gmail')"
                        class="relative bg-white hover:bg-red-50/40 border border-gray-200 hover:border-red-200 rounded-2xl px-2 py-3 sm:p-4 flex flex-col items-center text-center gap-1.5 sm:gap-2 transition-all">
                        <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg"><i class="fa-brands fa-google"></i></span>
                        <span class="text-xs sm:text-sm font-extrabold text-gray-900 leading-tight">Gmail</span>
                        <span class="hidden sm:block text-[11px] text-gray-400 leading-snug">อีเมล + App Password</span>
                        <span class="inline-flex items-center gap-1 whitespace-nowrap text-[10px] sm:text-[11px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-lg"><i class="fa-solid fa-plus"></i>เพิ่ม</span>
                    </button>
                    <?php if ($ms_ready): ?>
                    <a href="admin.php?action=connect_microsoft"
                        class="relative bg-white hover:bg-sky-50/40 border border-gray-200 hover:border-sky-200 rounded-2xl px-2 py-3 sm:p-4 flex flex-col items-center text-center gap-1.5 sm:gap-2 transition-all">
                    <?php else: ?>
                    <a href="#msOauthSection" onclick="showNotification('กรอก Client ID / Client Secret ของ Microsoft ด้านล่างก่อน แล้วกดบันทึก')"
                        class="relative bg-white border border-gray-200 rounded-2xl px-2 py-3 sm:p-4 flex flex-col items-center text-center gap-1.5 sm:gap-2 transition-all opacity-60">
                    <?php endif; ?>
                        <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg"><i class="fa-brands fa-microsoft"></i></span>
                        <span class="text-xs sm:text-sm font-extrabold text-gray-900 leading-tight">Hotmail</span>
                        <span class="hidden sm:block text-[11px] text-gray-400 leading-snug"><?php echo $ms_ready ? 'ล็อกอิน Microsoft ได้เลย' : 'ตั้งค่า Microsoft ด้านล่างก่อน'; ?></span>
                        <span class="inline-flex items-center gap-1 whitespace-nowrap text-[10px] sm:text-[11px] font-bold px-2 py-0.5 rounded-lg <?php echo $ms_ready ? 'text-sky-700 bg-sky-50' : 'text-gray-500 bg-gray-100'; ?>">
                            <i class="<?php echo $ms_ready ? 'fa-solid fa-arrow-right-to-bracket' : 'fa-solid fa-lock'; ?>"></i><?php echo $ms_ready ? 'เชื่อมต่อ' : 'ปิดอยู่'; ?>
                        </span>
                    </a>
                    <button type="button" onclick="openAddEmailModal('maily')"
                        class="relative bg-white hover:bg-purple-50/40 border border-gray-200 hover:border-purple-200 rounded-2xl px-2 py-3 sm:p-4 flex flex-col items-center text-center gap-1.5 sm:gap-2 transition-all">
                        <span class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg"><i class="fa-solid fa-inbox"></i></span>
                        <span class="text-xs sm:text-sm font-extrabold text-gray-900 leading-tight">Maily</span>
                        <span class="hidden sm:block text-[11px] text-gray-400 leading-snug">ใช้ Token ในหน้า API</span>
                        <span class="inline-flex items-center gap-1 whitespace-nowrap text-[10px] sm:text-[11px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-lg"><i class="fa-solid fa-plus"></i>เพิ่ม</span>
                    </button>
                </div>

                <div class="bg-blue-50/80 border border-blue-200/70 rounded-2xl p-4 text-xs text-blue-900">
                    <div class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-full bg-blue-500 text-white flex items-center justify-center text-[10px] flex-shrink-0 mt-0.5"><i class="fa-solid fa-info"></i></div>
                        <div class="space-y-1.5 leading-relaxed">
                            <p><b>วิธีใช้:</b> เพิ่มอีเมลของร้าน → ลูกค้ากรอกอีเมลในหน้า OTP แล้วกดขอรหัสได้ทันที</p>
                            <button type="button" onclick="openGuideModal('gmail')" class="inline-flex items-center gap-1.5 bg-white hover:bg-blue-100/70 text-purple-700 border border-purple-200 text-[11px] font-extrabold px-3 py-1.5 rounded-xl transition-all"><i class="fa-brands fa-google text-red-500"></i><span>วิธีหา App Password (Gmail)</span></button>
                        </div>
                    </div>
                </div>

                <!-- กรองตามประเภทอีเมล / แอป -->
                <div class="flex flex-wrap gap-1.5" id="emailKindChips">
                    <button type="button" data-kind-chip="all" onclick="filterEmailKind('all')" class="app-chip active">ทั้งหมด <span><?php echo count($imap_emails); ?></span></button>
                    <button type="button" data-kind-chip="gmail" onclick="filterEmailKind('gmail')" class="app-chip">Gmail <span><?php echo $kind_count['gmail']; ?></span></button>
                    <button type="button" data-kind-chip="hotmail" onclick="filterEmailKind('hotmail')" class="app-chip">Hotmail <span><?php echo $kind_count['hotmail']; ?></span></button>
                    <button type="button" data-kind-chip="maily" onclick="filterEmailKind('maily')" class="app-chip">Maily <span><?php echo $kind_count['maily']; ?></span></button>
                    <?php foreach (($config['apps'] ?? []) as $chip_app): if (empty($chip_app['id'])) continue; ?>
                    <button type="button" data-kind-chip="app:<?php echo htmlspecialchars($chip_app['id']); ?>" onclick="filterEmailKind('app:<?php echo htmlspecialchars($chip_app['id'], ENT_QUOTES); ?>')" class="app-chip"><?php echo htmlspecialchars($chip_app['name'] ?? ''); ?> <span><?php echo (int)($app_count[$chip_app['id']] ?? 0); ?></span></button>
                    <?php endforeach; ?>
                </div>

                <!-- Netflix 4 / 6 หลัก (เปิด/ปิดทุกอีเมลพร้อมกัน · รายอีเมลใช้ปุ่ม 4 / 6 ในแต่ละแถว) -->
                <div class="flex flex-col gap-2 bg-rose-50/50 border border-rose-100 rounded-2xl px-4 py-2.5">
                    <p class="text-[11px] text-gray-600 leading-relaxed"><span class="bg-red-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded mr-1.5">NETFLIX</span>
                        <b>4</b> = หารหัส 4 หลัก (ยืนยันทีวี / ครัวเรือน) ก่อน · <b>6</b> = ให้ลูกค้าเห็นรหัส 6 หลัก (รหัสเข้าสู่ระบบ) ปิดไว้ = ซ่อนรหัส 6 หลัก</p>
                    <?php foreach ([['four', '4'], ['six', '6']] as $nf_row): ?>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-bold text-gray-700">รหัส <?php echo $nf_row[1]; ?> หลัก (ทุกอีเมล)</p>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="bulkNf('<?php echo $nf_row[0]; ?>', true)" class="text-[11px] font-bold px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white">เปิดทั้งหมด</button>
                            <button type="button" onclick="bulkNf('<?php echo $nf_row[0]; ?>', false)" class="text-[11px] font-bold px-3 py-1.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white">ปิดทั้งหมด</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- ค้นหาอีเมล -->
                <div class="flex items-center justify-between gap-3 bg-gray-50/70 border border-gray-200/80 rounded-2xl p-2.5 sm:px-4">
                    <div class="flex items-center gap-2.5 flex-1">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 text-sm"></i>
                        <input type="text" id="emailSearchInput" oninput="filterEmailList(this.value)" placeholder="พิมพ์ค้นหาอีเมล..."
                               class="w-full bg-transparent text-xs sm:text-sm text-gray-800 focus:outline-none placeholder-gray-400 font-medium">
                        <button type="button" id="clearEmailSearchBtn" onclick="clearEmailSearch()" class="hidden text-gray-400 hover:text-gray-600 text-xs px-1.5 py-0.5 rounded-full hover:bg-gray-200/60 transition-all">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <span id="emailSearchCount" class="text-[11px] font-bold text-gray-400 whitespace-nowrap bg-white px-2.5 py-1 rounded-xl border border-gray-100 shadow-xs"><?php echo count($imap_emails); ?> รายการ</span>
                </div>

                <!-- รายการอีเมลของร้าน -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="emailListGrid">
                    <div id="noEmailSearchResult" class="hidden col-span-full text-center py-10 text-gray-400 text-xs font-medium">
                        <i class="fa-solid fa-magnifying-glass text-gray-300 text-2xl mb-2 block"></i>
                        ไม่พบอีเมลที่ตรงกับคำค้นหา
                    </div>
                    <?php if (empty($imap_emails)): ?>
                    <div class="col-span-full text-center py-10 text-gray-400 text-xs font-medium">ยังไม่มีอีเมลของร้าน กดปุ่มเพิ่มอีเมลด้านบน (Gmail / Hotmail / Maily)</div>
                    <?php else: foreach ($imap_emails as $item):
                        $is_active = isset($item['active']) ? (bool)$item['active'] : true;
                        $kind = $email_kind($item);
                        $id_attr = htmlspecialchars($item['id'] ?? '', ENT_QUOTES);
                    ?>
                    <div class="email-card rounded-2xl p-3 space-y-2.5 border transition-all <?php echo $is_active ? 'bg-white border-gray-200/80' : 'bg-rose-50/50 border-rose-200/70'; ?>"
                         data-email="<?php echo htmlspecialchars(strtolower($item['email'])); ?>" data-kind="<?php echo $kind; ?>" data-app="<?php echo htmlspecialchars($item['app_id'] ?? 'all'); ?>">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs flex-shrink-0 <?php echo $kind_icon[$kind][1]; ?>"><i class="<?php echo $kind_icon[$kind][0]; ?>"></i></span>
                                <span class="text-xs font-bold text-gray-800 truncate" title="<?php echo htmlspecialchars($item['email']); ?>"><?php echo htmlspecialchars($item['email']); ?></span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <label class="relative inline-flex items-center cursor-pointer" title="<?php echo $is_active ? 'คลิกเพื่อล็อกอีเมลนี้' : 'คลิกเพื่อปลดล็อกอีเมลนี้'; ?>">
                                    <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> onchange="toggleEmailActive('<?php echo $id_attr; ?>', this.checked)" class="sr-only peer">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                                <button type="button" onclick="deleteEmail('<?php echo $id_attr; ?>')" title="ลบอีเมลนี้" class="w-7 h-7 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg flex items-center justify-center transition-all"><i class="fa-regular fa-trash-can text-xs"></i></button>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <?php if ($kind === 'maily'): $has_pin = maily_pin_for($config, $item['email']) !== ''; ?>
                            <button type="button" onclick="setMailyPin('<?php echo htmlspecialchars(strtolower($item['email']), ENT_QUOTES); ?>')" title="PIN 6 หลักของกล่องนี้ใน maily.space (ถ้ากล่องตั้ง PIN ไว้)"
                                    class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-lg border <?php echo $has_pin ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'; ?>">
                                <i class="fa-solid fa-lock"></i><span><?php echo $has_pin ? 'PIN Maily ✓' : 'ตั้ง PIN Maily'; ?></span>
                            </button>
                            <?php endif; ?>
                            <?php echo nf_buttons_html($config, $item['email']); ?>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- ตั้งค่า Microsoft App (สำหรับปุ่มเชื่อมต่อ Hotmail ด้านบน) -->
            <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-6" id="msOauthSection">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sky-600 text-lg"><i class="fa-brands fa-microsoft"></i></span>
                            <h2 class="text-base font-extrabold text-gray-900">ตั้งค่า Microsoft App (สำหรับเชื่อมต่อ Hotmail)</h2>
                        </div>
                        <p class="text-xs text-gray-400">อ่านกล่องเมลตรงผ่าน API ทางการของ Microsoft — ตั้งครั้งเดียว แล้วกดการ์ด Hotmail ด้านบนเพื่อเชื่อมต่ออีเมล</p>
                    </div>
                    <span class="flex-shrink-0 text-[10px] font-bold px-2 py-1 rounded-lg <?php echo $ms_ready ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo $ms_ready ? 'พร้อมใช้งาน' : 'ยังไม่ได้ตั้งค่า'; ?></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">Application (client) ID</label>
                        <input type="text" id="msClientIdInput" value="<?php echo htmlspecialchars($ms_client_id); ?>" placeholder="เช่น 13b065c6-37bc-4922-..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">Client Secret (Value)</label>
                        <input type="password" id="msClientSecretInput" value="<?php echo htmlspecialchars($ms_client_secret); ?>" placeholder="เช่น WwW8Q~gZ0gGBP..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                </div>

                <div class="bg-blue-50/80 border border-blue-200/70 rounded-2xl p-3.5 text-[11px] text-blue-900 leading-relaxed">
                    Redirect URI ที่ต้องตั้งค่าใน Azure App Registration (เมนู Authentication) ให้ตรงกันเป๊ะๆ:<br>
                    <code class="font-mono font-bold break-all">https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/ms_callback.php</code>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" onclick="handleSaveMicrosoftOAuth()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all active:scale-[0.98]">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> บันทึกการตั้งค่า
                    </button>
                    <?php if ($ms_ready): ?>
                    <a href="admin.php?action=connect_microsoft" class="bg-white hover:bg-gray-50 text-sky-700 border border-sky-200 font-bold text-xs px-4 py-2.5 rounded-xl shadow-xs transition-all inline-flex items-center">
                        <i class="fa-brands fa-microsoft mr-1"></i> เชื่อมต่อบัญชี Hotmail/Outlook เพิ่ม
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- ========================================== -->
        <!-- TAB 2 CONTENT: ตั้งค่าแอปพลิเคชัน (OTP Apps) -->
        <!-- ========================================== -->
        <div id="tabContentApps" class="hidden space-y-5">
            <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-6">
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-purple-600 text-lg"><i class="fa-solid fa-mobile-screen-button"></i></span>
                            <h2 class="text-base font-extrabold text-gray-900">จัดการแอปพลิเคชัน (OTP Apps)</h2>
                        </div>
                        <p class="text-xs text-gray-400">เพิ่ม/แก้ไข/ลบแอปพลิเคชันที่จะปรากฏให้ผู้ใช้เลือกในหน้าหลัก</p>
                    </div>
                    
                    <button onclick="openAddAppModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all flex items-center gap-1.5 active:scale-[0.98] self-start sm:self-auto">
                        <i class="fa-solid fa-plus"></i>
                        <span>เพิ่มแอปพลิเคชัน</span>
                    </button>
                </div>

                <!-- Grid รายการแอป -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4" id="appsGrid">
                    <?php 
                    $apps = isset($config['apps']) ? $config['apps'] : [];
                    if (empty($apps)): 
                    ?>
                    <div class="col-span-full text-center py-10 text-gray-400 text-xs font-medium">
                        ยังไม่มีแอปพลิเคชันในระบบ กดปุ่ม "+ เพิ่มแอปพลิเคชัน" ด้านบนเพื่อเพิ่มแอปใหม่
                    </div>
                    <?php else: foreach ($apps as $app): ?>
                    <div class="border rounded-2xl p-4 flex flex-col justify-between gap-4 transition-all hover:shadow-md" style="background-color: <?php echo htmlspecialchars($app['bg_color'] ?? '#FFFFFF'); ?>; border-color: <?php echo htmlspecialchars($app['border_color'] ?? '#E5E7EB'); ?>;">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white rounded-xl shadow-sm border border-gray-100 flex items-center justify-center overflow-hidden p-1 flex-shrink-0">
                                <?php if (!empty($app['logo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($app['logo_path']); ?>" alt="logo" class="w-full h-full object-contain">
                                <?php else: ?>
                                <i class="fa-solid fa-cube text-gray-400 text-xl"></i>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-gray-900 truncate"><?php echo htmlspecialchars($app['name']); ?></h3>
                                <p class="text-[10px] text-gray-500 font-mono">Code: <?php echo htmlspecialchars($app['code'] ?? 'GPT'); ?></p>
                                <p class="text-[9px] text-gray-400 truncate">Keywords: <?php echo htmlspecialchars($app['keywords'] ?? '-'); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-2 border-t border-black/5">
                            <span class="text-[10px] font-bold text-emerald-600 bg-emerald-100/60 px-2 py-0.5 rounded-full">เปิดใช้งาน</span>
                            <div class="flex items-center gap-1">
                                <button onclick="openEditAppModal(<?php echo htmlspecialchars(json_encode($app)); ?>)" class="text-xs text-gray-600 hover:text-purple-700 bg-white hover:bg-gray-50 px-2.5 py-1 rounded-lg border border-gray-200 font-semibold shadow-xs">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i>แก้ไข
                                </button>
                                <button onclick="deleteApp('<?php echo htmlspecialchars($app['id']); ?>')" class="text-xs text-rose-500 hover:text-rose-700 bg-white hover:bg-rose-50 px-2 py-1 rounded-lg border border-gray-200">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3 CONTENT: ตั้งค่า API & ความปลอดภัย -->
        <!-- ========================================== -->
        <div id="tabContentApi" class="hidden space-y-5">
            <!-- แจ้งเตือน Discord ของร้าน (แบบเว็บเช่า) -->
            <?php $discord_saved = discord_webhook_ok($config['discord_webhook'] ?? ''); ?>
            <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-5">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2"><span class="text-indigo-500 text-lg"><i class="fa-brands fa-discord"></i></span><h2 class="text-base font-extrabold text-gray-900">แจ้งเตือน Discord ของร้าน</h2></div>
                        <p class="text-xs text-gray-400">รับแจ้งเตือนในเซิร์ฟเวอร์ Discord ของคุณเองทุกครั้งที่ลูกค้าขอรหัส OTP</p>
                    </div>
                    <span class="flex-shrink-0 text-[10px] font-bold px-2 py-1 rounded-lg <?php echo $discord_saved ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo $discord_saved ? 'เชื่อมต่อแล้ว' : 'ยังไม่เชื่อมต่อ'; ?></span>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-600 pl-1">ลิงก์ Webhook <?php echo $discord_saved ? '(วางลิงก์ใหม่เพื่อเปลี่ยนห้อง)' : ''; ?></label>
                    <input type="text" id="discordWebhookInput" placeholder="https://discord.com/api/webhooks/..." autocomplete="off" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-purple-600 outline-none">
                </div>
                <details class="text-[11px] text-gray-600 bg-indigo-50/50 border border-indigo-100 rounded-2xl p-3">
                    <summary class="font-extrabold text-indigo-600 cursor-pointer"><i class="fa-solid fa-book-open mr-1"></i>วิธีสร้างห้อง + Webhook</summary>
                    <ol class="list-decimal pl-5 pt-2 space-y-1 leading-relaxed">
                        <li>เปิด Discord → สร้างเซิร์ฟเวอร์ (หรือใช้เซิร์ฟเวอร์ของร้าน) → สร้างห้องข้อความ เช่น <b>#แจ้งเตือน-otp</b></li>
                        <li>กดรูปเฟือง ⚙️ ข้างชื่อห้อง (แก้ไขช่อง) → เมนู <b>การผสานการทำงาน (Integrations)</b></li>
                        <li>กด <b>Webhooks</b> → <b>Webhook ใหม่</b> → ตั้งชื่อได้ตามใจ</li>
                        <li>กด <b>คัดลอก URL ของ Webhook</b> แล้วนำมาวางในช่องด้านบน → กดบันทึก</li>
                    </ol>
                </details>
                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="button" onclick="saveDiscord()" class="flex-1 bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm flex items-center justify-center gap-1.5"><i class="fa-brands fa-discord"></i><span>บันทึก + ส่งข้อความทดสอบ</span></button>
                    <?php if ($discord_saved): ?>
                    <button type="button" onclick="removeDiscord()" class="bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-bold text-xs px-4 py-2.5 rounded-xl">ยกเลิกแจ้งเตือน</button>
                    <?php endif; ?>
                </div>
                <p class="text-[10px] text-gray-400"><i class="fa-solid fa-lock mr-1"></i>แจ้งเฉพาะว่ามีการขอรหัส (แอป / อีเมล / พบหรือไม่) — ไม่ส่งตัวรหัส OTP ออกไป</p>
            </div>

            <form id="settingsForm" onsubmit="handleSettingsUpdate(event)" class="space-y-5">
                
                <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-600 text-lg"><i class="fa-solid fa-key"></i></span>
                                <h2 class="text-base font-extrabold text-gray-900">ตั้งค่า API Keys (OTP Providers)</h2>
                            </div>
                            <p class="text-xs text-gray-400">เพิ่ม/แก้ไข API Tokens สำหรับดึงรหัส OTP จากผู้ให้บริการ</p>
                        </div>
                        
                        <button type="button" onclick="openAddApiKeyModal()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all flex items-center gap-1.5 active:scale-[0.98] self-start sm:self-auto">
                            <i class="fa-solid fa-plus"></i>
                            <span>เพิ่ม API</span>
                        </button>
                    </div>

                    <!-- Grid รายการ API Keys -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="apiKeysGrid">
                        <?php 
                        $api_providers = isset($config['api_providers']) && is_array($config['api_providers']) ? $config['api_providers'] : [];
                        if (empty($api_providers)): 
                        ?>
                        <div class="col-span-full text-center py-8 text-gray-400 text-xs font-medium">
                            ยังไม่มีรายการ API Key ในระบบ กดปุ่ม "+ เพิ่ม API" เพื่อเริ่มต้นใช้งาน
                        </div>
                        <?php else: foreach ($api_providers as $api_item): ?>
                        <div class="bg-purple-50/40 border border-purple-200/60 rounded-2xl p-3.5 sm:p-4 flex flex-col xs:flex-row xs:items-center justify-between gap-3 shadow-xs hover:border-purple-300 transition-all">
                            <div class="flex items-center gap-3 min-w-0 w-full xs:w-auto">
                                <div class="w-10 h-10 sm:w-11 sm:h-11 bg-purple-100 border border-purple-200 text-purple-700 rounded-xl flex items-center justify-center text-base sm:text-lg flex-shrink-0">
                                    <i class="fa-solid fa-key"></i>
                                </div>
                                <div class="min-w-0 flex-1 space-y-0.5">
                                    <h3 class="text-xs sm:text-sm font-extrabold text-gray-900 truncate" title="<?php echo htmlspecialchars($api_item['name']); ?>"><?php echo htmlspecialchars($api_item['name']); ?></h3>
                                    <p class="text-[10px] text-gray-500 font-mono break-all sm:truncate"><?php echo htmlspecialchars($api_item['token'] ?? ''); ?></p>
                                    <div class="pt-0.5">
                                        <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-700 text-[9px] font-bold px-2 py-0.5 rounded-md">
                                            <?php echo htmlspecialchars(strtoupper($api_item['type'] ?? 'MAILY')); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-1.5 w-full xs:w-auto border-t xs:border-t-0 pt-2 xs:pt-0 border-purple-100 flex-shrink-0">
                                <button type="button" onclick='openEditApiKeyModal(<?php echo htmlspecialchars(json_encode($api_item)); ?>)' title="แก้ไข" class="px-2.5 py-1 text-xs font-semibold text-purple-700 bg-purple-100/60 hover:bg-purple-200/60 rounded-lg flex items-center gap-1 transition-all">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                    <span>แก้ไข</span>
                                </button>
                                <button type="button" onclick="deleteApiKey('<?php echo htmlspecialchars($api_item['id']); ?>')" title="ลบ API Key นี้" class="w-7 h-7 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg flex items-center justify-center transition-all">
                                    <i class="fa-regular fa-trash-can text-sm"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-6">
                    <div class="border-b border-gray-100 pb-3 flex items-center gap-2 text-sm font-extrabold text-gray-900">
                        <i class="fa-solid fa-sliders text-purple-600"></i>
                        <span>ตั้งค่าโลโก้ร้านและชื่อร้านค้า</span>
                    </div>

                    <!-- Shop Logo Upload -->
                    <div class="flex items-center gap-4 bg-gray-50/60 p-4 rounded-2xl border border-gray-100">
                        <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" id="dashboardLogoPreview" alt="logo preview" 
                             class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                        
                        <div class="flex-1 space-y-1.5">
                            <p class="text-xs font-bold text-gray-800">เปลี่ยนรูปโลโก้ร้านค้า</p>
                            <label for="logoFileInput" class="inline-flex items-center gap-1.5 bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs px-3 py-1.5 rounded-xl border border-gray-200 cursor-pointer shadow-xs">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>เลือกไฟล์รูปภาพ</span>
                            </label>
                            <input type="file" id="logoFileInput" accept="image/*" class="hidden" onchange="uploadLogoFile()">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-600 pl-1">ชื่อร้านค้า (Shop Name)</label>
                        <input type="text" id="shopNameInput" required value="<?php echo htmlspecialchars($config['shop_name']); ?>"
                               placeholder="ชื่อร้านค้า" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                </div>

                <?php $shop_page = shop_page_options($config); ?>
                <!-- พื้นหลังหน้าร้าน -->
                <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-5">
                    <div class="border-b border-gray-100 pb-3 flex items-center gap-2 text-sm font-extrabold text-gray-900">
                        <i class="fa-solid fa-image text-purple-600"></i>
                        <span>พื้นหลังหน้าร้าน</span>
                    </div>
                    <input type="hidden" id="bgTypeInput" value="<?php echo $shop_page['bg_type']; ?>">
                    <div class="inline-flex rounded-xl border border-gray-200 overflow-hidden">
                        <button type="button" id="bgTypeColorBtn" onclick="setBgType('color')" class="px-4 py-2 text-xs font-bold <?php echo $shop_page['bg_type'] === 'color' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600'; ?>">สีพื้น</button>
                        <button type="button" id="bgTypeImageBtn" onclick="setBgType('image')" class="px-4 py-2 text-xs font-bold <?php echo $shop_page['bg_type'] === 'image' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600'; ?>">รูปภาพ</button>
                    </div>
                    <div id="bgColorBox" class="flex items-center gap-2 <?php echo $shop_page['bg_type'] === 'color' ? '' : 'hidden'; ?>">
                        <input type="color" id="bgColorInput" value="<?php echo htmlspecialchars($shop_page['bg_color']); ?>" class="w-11 h-9 p-0.5 rounded-xl border border-gray-200 cursor-pointer">
                        <span class="text-[11px] text-gray-400">สีขาว (#ffffff) = หน้าตาเดิม</span>
                    </div>
                    <div id="bgImageBox" class="flex items-center gap-4 bg-gray-50/60 p-4 rounded-2xl border border-gray-100 <?php echo $shop_page['bg_type'] === 'image' ? '' : 'hidden'; ?>">
                        <img id="bgImagePreview" src="<?php echo htmlspecialchars($shop_page['bg_image']); ?>" alt="" class="w-16 h-16 rounded-xl object-cover border border-gray-200 <?php echo $shop_page['bg_image'] ? '' : 'hidden'; ?>">
                        <label for="bgFileInput" class="inline-flex items-center gap-1.5 bg-white hover:bg-gray-100 text-gray-700 font-bold text-xs px-3 py-1.5 rounded-xl border border-gray-200 cursor-pointer shadow-xs">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>อัปโหลดรูปพื้นหลัง</span>
                        </label>
                        <input type="file" id="bgFileInput" accept="image/*" class="hidden" onchange="uploadBgFile()">
                    </div>
                </div>

                <!-- ข้อความบนหน้าร้าน -->
                <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-5">
                    <div class="border-b border-gray-100 pb-3 space-y-1">
                        <div class="flex items-center gap-2 text-sm font-extrabold text-gray-900">
                            <i class="fa-solid fa-font text-purple-600"></i>
                            <span>ข้อความบนหน้าร้าน</span>
                        </div>
                        <p class="text-[11px] text-gray-400">แก้คำที่ลูกค้าเห็นได้เอง · เว้นว่าง = ใช้ข้อความเดิม (ตัวอย่างสีเทาในช่อง)</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach (SHOP_PAGE_TEXTS as $tkey => $tdef): ?>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 pl-1"><?php echo htmlspecialchars($tdef[0]); ?></label>
                            <input type="text" data-shop-text="<?php echo $tkey; ?>" maxlength="120"
                                   value="<?php echo htmlspecialchars($shop_page['texts'][$tkey] ?? ''); ?>"
                                   placeholder="<?php echo htmlspecialchars($tdef[1]); ?>"
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="main-card rounded-3xl border border-gray-100 p-5 md:p-7 space-y-6">
                    <div class="border-b border-gray-100 pb-3 flex items-center gap-2 text-sm font-extrabold text-gray-900">
                        <i class="fa-solid fa-shield-halved text-purple-600"></i>
                        <span>เปลี่ยนบัญชีความปลอดภัยผู้ดูแลระบบ</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 pl-1">Username ใหม่ (เว้นว่างไว้ถ้าไม่เปลี่ยน)</label>
                            <input type="text" id="newUsernameInput" placeholder="Username ใหม่" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 pl-1">Password ใหม่ (เว้นว่างไว้ถ้าไม่เปลี่ยน)</label>
                            <input type="password" id="newPasswordInput" placeholder="Password ใหม่" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                        </div>
                    </div>
                </div>

                <button type="submit" id="saveSettingsBtn"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3.5 px-6 rounded-2xl transition-all shadow-md active:scale-[0.98] text-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>บันทึกการตั้งค่าทั้งหมด</span>
                </button>
            </form>
        </div>

        <?php endif; ?>

    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: เพิ่ม/แก้ไข อีเมล IMAP -->
    <!-- ========================================== -->
    <div id="emailModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-xl border border-gray-100 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                    <i class="fa-regular fa-envelope text-purple-600"></i>
                    <span>เพิ่มบัญชีอีเมล IMAP</span>
                </h3>
                <button onclick="closeEmailModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="emailForm" onsubmit="handleSaveEmail(event)" class="space-y-4">
                <input type="hidden" id="emailKindInput" value="gmail">
                <p class="text-xs text-gray-400" id="emailModalHint">สำหรับ Gmail เท่านั้น (Hotmail/Outlook ใช้ปุ่ม "เชื่อมต่อผ่าน Microsoft" แทนแล้วค่ะ)</p>

                <div class="space-y-1.5 hidden" id="mailyPinContainer">
                    <label class="text-xs font-bold text-gray-700">PIN กล่อง Maily (6 หลัก) <span class="text-gray-400 font-medium">— ถ้าตั้งไว้ใน maily.space</span></label>
                    <input type="password" id="emailMailyPinInput" inputmode="numeric" maxlength="6" autocomplete="new-password" placeholder="เช่น 123456"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono tracking-[0.3em] focus:ring-2 focus:ring-purple-600 outline-none">
                    <p class="text-[10px] text-gray-400">* ใช้ได้กับ @lico.moe, @rdcw.plus, @gooddaymail.com · กล่องที่ตั้ง PIN ไว้ต้องใส่ ไม่งั้น Maily จะไม่ให้อ่านเมล</p>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-700">ที่อยู่อีเมล (Email Address)</label>
                    <input type="email" id="emailAddrInput" required placeholder="เช่น example@gmail.com"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                </div>

                <div class="space-y-1.5" id="passInputContainer">
                    <label class="text-xs font-bold text-gray-700">App Password (รหัสผ่านแอป 16 หลัก)</label>
                    <input type="password" id="emailPassInput" placeholder="รหัสผ่าน App Password สำหรับ IMAP"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                    <p class="text-[10px] text-gray-400">* Gmail ต้องสร้าง App Password ในบัญชี Google เพื่อล็อกอิน IMAP</p>
                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <button type="button" onclick="openGuideModal('gmail')" class="text-[11px] font-bold text-purple-600 hover:text-purple-800 underline flex items-center gap-1 cursor-pointer">
                            <i class="fa-brands fa-google text-red-500"></i> ดูวิธีเอา App Password (Gmail)
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3" id="imapHostPortContainer">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">IMAP Host</label>
                        <input type="text" id="emailHostInput" value="imap.gmail.com" 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">Port (SSL)</label>
                        <input type="number" id="emailPortInput" value="993" 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeEmailModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-sm">บันทึกอีเมล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: เพิ่ม/แก้ไข แอปพลิเคชัน (OTP App) -->
    <!-- ========================================== -->
    <div id="appModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-xl border border-gray-100 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2" id="appModalTitle">
                    <i class="fa-solid fa-mobile-screen-button text-purple-600"></i>
                    <span>เพิ่มแอปพลิเคชันใหม่</span>
                </h3>
                <button onclick="closeAppModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="appForm" onsubmit="handleSaveApp(event)" class="space-y-4">
                <input type="hidden" id="appIdInput" value="">
                <input type="hidden" id="appLogoPathInput" value="">

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-700">ชื่อแอปพลิเคชัน (App Name)</label>
                    <input type="text" id="appNameInput" required placeholder="เช่น Disney+, Netflix, ChatGPT, Viu" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">App Code (ย่อ)</label>
                        <input type="text" id="appCodeInput" placeholder="เช่น DN, TM, NF, GPT" 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs font-mono uppercase focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">Keyword ค้นหาในเมล</label>
                        <input type="text" id="appKeywordsInput" placeholder="เช่น disney, netflix" 
                               class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-700">รูปภาพโลโก้แอปพลิเคชัน</label>
                    <div class="flex items-center gap-3">
                        <div id="appLogoPreviewBox" class="w-10 h-10 bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center overflow-hidden">
                            <i class="fa-solid fa-cube text-gray-400"></i>
                        </div>
                        <input type="file" id="appLogoFileInput" accept="image/*" class="text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">สีพื้นหลังการ์ด</label>
                        <input type="color" id="appBgColorInput" value="#F0F7FF" class="w-full h-9 p-0.5 rounded-xl border border-gray-200 cursor-pointer">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-700">สีเส้นขอบการ์ด</label>
                        <input type="color" id="appBorderColorInput" value="#D6E9FF" class="w-full h-9 p-0.5 rounded-xl border border-gray-200 cursor-pointer">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeAppModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-sm">บันทึกแอป</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 3: เพิ่ม/แก้ไข API Key (OTP Provider) -->
    <!-- ========================================== -->
    <div id="apiKeyModal" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-xl border border-gray-100 p-6 space-y-5 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h3 class="text-base font-extrabold text-gray-900 flex items-center gap-2" id="apiKeyModalTitle">
                    <i class="fa-solid fa-key text-purple-600"></i>
                    <span>เพิ่ม API Key ใหม่</span>
                </h3>
                <button type="button" onclick="closeApiKeyModal()" class="text-gray-400 hover:text-gray-600 w-8 h-8 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="apiKeyForm" onsubmit="handleSaveApiKey(event)" class="space-y-4">
                <input type="hidden" id="apiKeyIdInput" value="">

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-700">ชื่ออ้างอิง API Key (Name / Label)</label>
                    <input type="text" id="apiKeyNameInput" required placeholder="เช่น Maily Space Main Token, Key 2, ฯลฯ" 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none">
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-700">ประเภท API (Provider Type)</label>
                    <select id="apiKeyTypeInput" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-purple-600 outline-none bg-white">
                        <option value="maily">Maily Space API (Bearer Token)</option>
                        <option value="cloud_run">Cloud Run API (RDCW Co., Ltd.)</option>
                        <option value="custom">Custom Webhook API</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-700">API Key / Token String</label>
                    <input type="text" id="apiKeyValueInput" required placeholder="เช่น sk_v1_... หรือ otp_key_live_..." 
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-purple-600 outline-none">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="closeApiKeyModal()" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">ยกเลิก</button>
                    <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-sm">บันทึก API Key</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL GUIDE: คู่มือรูปภาพวิธีตั้งค่า -->
    <!-- ========================================== -->
    <div id="guideModal" class="hidden fixed inset-0 z-50 bg-black/70 backdrop-blur-xs flex items-center justify-center p-3 md:p-6 overflow-y-auto">
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-gray-100 overflow-hidden my-auto max-h-[92vh] flex flex-col animate-in fade-in zoom-in-95 duration-200">
            <div class="flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50/80 flex-shrink-0">
                <h3 class="text-sm md:text-base font-extrabold text-gray-900 flex items-center gap-2" id="guideModalTitle">
                    <i class="fa-solid fa-book-open text-purple-600"></i>
                    <span>คู่มือการใช้งาน</span>
                </h3>
                <button type="button" onclick="closeGuideModal()" class="text-gray-400 hover:text-gray-700 w-8 h-8 rounded-full flex items-center justify-center text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-3 md:p-5 overflow-y-auto flex-1 flex flex-col items-center justify-center bg-gray-900/5">
                <img id="guideModalImg" src="" alt="Guide tutorial" class="max-w-full h-auto rounded-2xl shadow-md border border-gray-200 object-contain">
            </div>
            <div class="p-4 border-t border-gray-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-3 flex-shrink-0">
                <div class="text-xs text-gray-600 font-medium" id="guideModalSub">กดดูขั้นตอนอย่างละเอียด</div>
                <button type="button" onclick="closeGuideModal()" class="w-full sm:w-auto px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-sm">
                    เข้าใจแล้ว / ปิดหน้าต่าง
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript Logic -->
    <script>

        function switchTab(tabName) {
            const tabs = ['imap', 'apps', 'api'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tabBtn${t.charAt(0).toUpperCase() + t.slice(1)}`);
                const content = document.getElementById(`tabContent${t.charAt(0).toUpperCase() + t.slice(1)}`);
                if (t === tabName) {
                    btn.className = "tab-btn active border px-2 sm:px-3 py-2.5 sm:py-3 rounded-xl flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 text-xs md:text-sm font-semibold";
                    content.classList.remove('hidden');
                } else {
                    btn.className = "tab-btn inactive border px-2 sm:px-3 py-2.5 sm:py-3 rounded-xl flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2 text-xs md:text-sm font-semibold";
                    content.classList.add('hidden');
                }
            });
            // ซ่อนระบบ Forwarding ไว้เพราะใช้ระบบสแกนอัตโนมัติแล้ว
            const fwdSection = document.getElementById('tabContentFwd');
            if (fwdSection) {
                fwdSection.classList.add('hidden');
            }
        }

        function showNotification(text) {
            const banner = document.getElementById('noticeBanner');
            const noticeText = document.getElementById('noticeText');
            noticeText.innerText = text;
            banner.classList.remove('hidden');
            setTimeout(() => { banner.classList.add('hidden'); }, 3500);
        }

        // --- Login Handler ---
        async function handleLogin(event) {
            event.preventDefault();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);

            try {
                const res = await fetch('admin.php?action=login', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'ไม่สามารถเข้าสู่ระบบได้');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย');
            }
        }

        // --- IMAP Email Handlers ---
        function openAddEmailModal(kind) {
            kind = kind === 'maily' ? 'maily' : 'gmail';
            const maily = kind === 'maily';
            document.getElementById('emailKindInput').value = kind;
            document.getElementById('emailModalHint').innerText = maily
                ? 'อีเมล Maily.space — ระบบอ่านด้วย API Key ในแท็บ "ตั้งค่า API" หรือด้วย PIN ของกล่อง'
                : 'สำหรับ Gmail เท่านั้น (Hotmail/Outlook ใช้ปุ่ม "เชื่อมต่อผ่าน Microsoft" แทนแล้วค่ะ)';
            document.getElementById('emailAddrInput').placeholder = maily ? 'เช่น shop01@lico.moe' : 'เช่น example@gmail.com';
            document.getElementById('passInputContainer').classList.toggle('hidden', maily);
            document.getElementById('imapHostPortContainer').classList.toggle('hidden', maily);
            document.getElementById('mailyPinContainer').classList.toggle('hidden', !maily);
            document.getElementById('emailModal').classList.remove('hidden');
        }
        function closeEmailModal() {
            document.getElementById('emailModal').classList.add('hidden');
        }

        async function handleSaveEmail(event) {
            event.preventDefault();
            const email = document.getElementById('emailAddrInput').value.trim();
            const password = document.getElementById('emailPassInput').value.trim();
            const host = document.getElementById('emailHostInput').value.trim();
            const port = document.getElementById('emailPortInput').value.trim();

            const kind = document.getElementById('emailKindInput').value;
            const mailyPin = document.getElementById('emailMailyPinInput').value.trim();
            if (kind === 'maily' && mailyPin && !/^\d{6}$/.test(mailyPin)) { alert('PIN กล่อง Maily ต้องเป็นตัวเลข 6 หลัก'); return; }

            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', kind === 'maily' ? '' : password);
            formData.append('provider', kind);
            formData.append('host', kind === 'maily' ? '' : host);
            formData.append('port', kind === 'maily' ? '443' : port);
            if (kind === 'maily') formData.append('maily_pin', mailyPin);

            try {
                const res = await fetch('admin.php?action=add_imap_email', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeEmailModal();
                    showNotification('บันทึกอีเมล IMAP เรียบร้อยแล้วค่ะ!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert(data.message || 'ไม่สามารถบันทึกอีเมลได้');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }
        }

        async function deleteEmail(id) {
            if (!confirm('ยืนยันการลบอีเมลนี้จากระบบ?')) return;
            const formData = new FormData();
            formData.append('id', id);

            try {
                const res = await fetch('admin.php?action=delete_imap_email', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showNotification('ลบรายการอีเมลเรียบร้อยแล้วค่ะ');
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการลบรายการ');
            }
        }

        async function toggleEmailActive(id, activeState) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('active', activeState);

            try {
                const res = await fetch('admin.php?action=toggle_email_active', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showNotification(activeState ? 'ปลดล็อกเปิดใช้งานอีเมลแล้วค่ะ' : 'ล็อกการใช้งานอีเมลเรียบร้อยแล้วค่ะ');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert(data.message || 'ไม่สามารถเปลี่ยนสถานะได้');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        }

        

        // --- OTP Apps Handlers ---
        function openAddAppModal() {
            document.getElementById('appIdInput').value = '';
            document.getElementById('appNameInput').value = '';
            document.getElementById('appCodeInput').value = '';
            document.getElementById('appKeywordsInput').value = '';
            document.getElementById('appLogoPathInput').value = '';
            document.getElementById('appModalTitle').innerHTML = '<i class="fa-solid fa-mobile-screen-button text-purple-600"></i><span>เพิ่มแอปพลิเคชันใหม่</span>';
            document.getElementById('appLogoPreviewBox').innerHTML = '<i class="fa-solid fa-cube text-gray-400"></i>';
            document.getElementById('appModal').classList.remove('hidden');
        }

        function openEditAppModal(app) {
            document.getElementById('appIdInput').value = app.id || '';
            document.getElementById('appNameInput').value = app.name || '';
            document.getElementById('appCodeInput').value = app.code || '';
            document.getElementById('appKeywordsInput').value = app.keywords || '';
            document.getElementById('appBgColorInput').value = app.bg_color || '#F0F7FF';
            document.getElementById('appBorderColorInput').value = app.border_color || '#D6E9FF';
            document.getElementById('appLogoPathInput').value = app.logo_path || '';
            document.getElementById('appModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-purple-600"></i><span>แก้ไขแอปพลิเคชัน</span>';
            
            if (app.logo_path) {
                document.getElementById('appLogoPreviewBox').innerHTML = `<img src="${app.logo_path}" class="w-full h-full object-contain">`;
            } else {
                document.getElementById('appLogoPreviewBox').innerHTML = '<i class="fa-solid fa-cube text-gray-400"></i>';
            }
            document.getElementById('appModal').classList.remove('hidden');
        }

        function closeAppModal() {
            document.getElementById('appModal').classList.add('hidden');
        }

        async function handleSaveApp(event) {
            event.preventDefault();
            const id = document.getElementById('appIdInput').value;
            const name = document.getElementById('appNameInput').value.trim();
            const code = document.getElementById('appCodeInput').value.trim();
            const keywords = document.getElementById('appKeywordsInput').value.trim();
            const bg_color = document.getElementById('appBgColorInput').value;
            const border_color = document.getElementById('appBorderColorInput').value;
            const logo_path = document.getElementById('appLogoPathInput').value;
            const logoFile = document.getElementById('appLogoFileInput').files[0];

            const formData = new FormData();
            formData.append('id', id);
            formData.append('name', name);
            formData.append('code', code);
            formData.append('keywords', keywords);
            formData.append('bg_color', bg_color);
            formData.append('border_color', border_color);
            formData.append('logo_path', logo_path);
            if (logoFile) formData.append('logo', logoFile);

            try {
                const res = await fetch('admin.php?action=save_app', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeAppModal();
                    showNotification('บันทึกแอปพลิเคชันสำเร็จ!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert(data.message || 'ไม่สามารถบันทึกแอปพลิเคชันได้');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        }

        async function deleteApp(id) {
            if (!confirm('ยืนยันการลบแอปพลิเคชันนี้?')) return;
            const formData = new FormData();
            formData.append('id', id);

            try {
                const res = await fetch('admin.php?action=delete_app', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showNotification('ลบแอปพลิเคชันเรียบร้อยแล้วค่ะ');
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการลบแอปพลิเคชัน');
            }
        }

        // --- Guide Modal Handlers ---
        function openGuideModal(type) {
            const titleEl = document.getElementById('guideModalTitle');
            const imgEl = document.getElementById('guideModalImg');
            const subEl = document.getElementById('guideModalSub');
            
            if (type === 'gmail') {
                titleEl.innerHTML = '<i class="fa-brands fa-google text-red-500"></i><span>วิธีการเข้าและสร้างรหัสผ่านสำหรับแอป (App Password - Gmail)</span>';
                imgEl.src = '/static/guide_gmail.png';
                subEl.innerHTML = 'ลิงก์ตรงสำหรับสร้าง App Password: <a href="https://myaccount.google.com/apppasswords" target="_blank" class="text-purple-600 font-bold underline">myaccount.google.com/apppasswords</a>';
            } else {
                titleEl.innerHTML = '<i class="fa-brands fa-microsoft text-blue-600"></i><span>วิธีเปิดการส่งต่ออีเมล (Forwarding) ใน Outlook.com</span>';
                imgEl.src = '/static/guide_outlook.jpg';
                subEl.innerText = 'ตั้งค่าส่งต่ออีเมลจาก Outlook/Hotmail เข้าไปยัง Gmail';
            }
            document.getElementById('guideModal').classList.remove('hidden');
        }

        function closeGuideModal() {
            document.getElementById('guideModal').classList.add('hidden');
        }

        // --- API Keys Handlers ---
        function openAddApiKeyModal() {
            document.getElementById('apiKeyIdInput').value = '';
            document.getElementById('apiKeyNameInput').value = '';
            document.getElementById('apiKeyValueInput').value = '';
            document.getElementById('apiKeyTypeInput').value = 'maily';
            document.getElementById('apiKeyModalTitle').innerHTML = '<i class="fa-solid fa-key text-purple-600"></i><span>เพิ่ม API Key ใหม่</span>';
            document.getElementById('apiKeyModal').classList.remove('hidden');
        }

        function openEditApiKeyModal(item) {
            document.getElementById('apiKeyIdInput').value = item.id || '';
            document.getElementById('apiKeyNameInput').value = item.name || '';
            document.getElementById('apiKeyValueInput').value = item.token || '';
            document.getElementById('apiKeyTypeInput').value = item.type || 'maily';
            document.getElementById('apiKeyModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-purple-600"></i><span>แก้ไข API Key</span>';
            document.getElementById('apiKeyModal').classList.remove('hidden');
        }

        function closeApiKeyModal() {
            document.getElementById('apiKeyModal').classList.add('hidden');
        }

        async function handleSaveApiKey(event) {
            event.preventDefault();
            const id = document.getElementById('apiKeyIdInput').value;
            const name = document.getElementById('apiKeyNameInput').value.trim();
            const token = document.getElementById('apiKeyValueInput').value.trim();
            const type = document.getElementById('apiKeyTypeInput').value;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('name', name);
            formData.append('token', token);
            formData.append('type', type);

            try {
                const res = await fetch('admin.php?action=save_api_key', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    closeApiKeyModal();
                    showNotification('บันทึก API Key สำเร็จ!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert(data.message || 'ไม่สามารถบันทึก API Key ได้');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        }

        async function deleteApiKey(id) {
            if (!confirm('ยืนยันการลบ API Key นี้?')) return;
            const formData = new FormData();
            formData.append('id', id);

            try {
                const res = await fetch('admin.php?action=delete_api_key', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showNotification('ลบ API Key เรียบร้อยแล้วค่ะ');
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการลบรายการ');
            }
        }

        // --- Logo & Settings Update ---
        async function uploadLogoFile() {
            const logoFileInput = document.getElementById('logoFileInput');
            const file = logoFileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('logo', file);

            try {
                const res = await fetch('admin.php?action=upload_logo', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showNotification('อัปโหลดโลโก้สำเร็จ!');
                    document.getElementById('dashboardLogoPreview').src = `${data.logo_path}?t=${Date.now()}`;
                    document.getElementById('headerLogo').src = `${data.logo_path}?t=${Date.now()}`;
                } else {
                    alert(data.message || 'อัปโหลดรูปไม่สำเร็จ');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดเครือข่ายระหว่างอัปโหลด');
            }
        }

        async function handleSettingsUpdate(event) {
            event.preventDefault();
            const shop_name = document.getElementById('shopNameInput').value.trim();
            const username = document.getElementById('newUsernameInput').value.trim();
            const password = document.getElementById('newPasswordInput').value.trim();

            const formData = new FormData();
            formData.append('shop_name', shop_name);
            formData.append('username', username);
            formData.append('password', password);

            try {
                const res = await fetch('admin.php?action=update', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    const pageOk = await saveShopPage();
                    showNotification(pageOk ? 'บันทึกการตั้งค่าร้านค้าและรหัสผ่านเรียบร้อยแล้วค่ะ' : 'บันทึกชื่อร้าน/รหัสผ่านแล้ว แต่พื้นหลัง/ข้อความหน้าร้านยังไม่ถูกบันทึก');
                    document.getElementById('headerShopName').innerText = shop_name;
                    document.getElementById('newUsernameInput').value = '';
                    document.getElementById('newPasswordInput').value = '';
                } else {
                    alert(data.message || 'ไม่สามารถอัปเดตการตั้งค่าได้');
                }
            } catch (err) {
                alert('เกิดความขัดข้องในการบันทึกข้อมูล');
            }
        }
        async function handleSaveMicrosoftOAuth() {
            const client_id = document.getElementById('msClientIdInput').value.trim();
            const client_secret = document.getElementById('msClientSecretInput').value.trim();

            if (!client_id || !client_secret) {
                alert('กรุณากรอก Client ID และ Client Secret ให้ครบ');
                return;
            }

            const formData = new FormData();
            formData.append('client_id', client_id);
            formData.append('client_secret', client_secret);

            try {
                const res = await fetch('admin.php?action=save_microsoft_oauth', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    showNotification('บันทึกการตั้งค่า Microsoft App เรียบร้อยแล้วค่ะ');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    alert(data.message || 'ไม่สามารถบันทึกการตั้งค่าได้');
                }
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        }

        // เช็ค query string หลังเชื่อมต่อ Microsoft OAuth กลับมา แล้วสลับไปแท็บ API + โชว์แจ้งเตือนให้อัตโนมัติ
        (function checkMicrosoftOAuthResult() {
            const params = new URLSearchParams(window.location.search);
            const msConnected = params.get('ms_connected');
            const msError = params.get('ms_error');
            if (!msConnected && !msError) return;

            // ลบ ms_connected/ms_error ออกจาก URL ทันที ไม่งั้นกด refresh ทีไรจะเจอ alert เดิมซ้ำทุกครั้ง
            const cleanUrl = window.location.pathname + window.location.hash;
            window.history.replaceState({}, document.title, cleanUrl);

            document.addEventListener('DOMContentLoaded', () => {
                if (typeof switchTab === 'function') switchTab('imap');
                if (msConnected) {
                    showNotification('เชื่อมต่อบัญชี ' + decodeURIComponent(msConnected) + ' ผ่าน Microsoft สำเร็จแล้วค่ะ');
                } else if (msError) {
                    alert('เชื่อมต่อ Microsoft ไม่สำเร็จ: ' + decodeURIComponent(msError));
                }
            });
        })();

        function filterEmailList(query) {
            const cleanQuery = query.trim().toLowerCase();
            const cards = document.querySelectorAll('.email-card');
            const clearBtn = document.getElementById('clearEmailSearchBtn');
            const countEl = document.getElementById('emailSearchCount');
            const noResultEl = document.getElementById('noEmailSearchResult');
            
            if (clearBtn) {
                if (cleanQuery.length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }

            let visibleCount = 0;
            cards.forEach(card => {
                const email = card.getAttribute('data-email') || '';
                if (!cleanQuery || email.includes(cleanQuery)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (countEl) {
                countEl.innerText = `${visibleCount} รายการ`;
            }

            if (noResultEl) {
                if (visibleCount === 0 && cards.length > 0) {
                    noResultEl.classList.remove('hidden');
                } else {
                    noResultEl.classList.add('hidden');
                }
            }
        }

        // --- ฟีเจอร์เสริมจากเว็บเช่า ---
        async function postAction(action, fields) {
            const formData = new FormData();
            Object.entries(fields).forEach(([k, v]) => formData.append(k, v));
            try {
                const res = await fetch('admin.php?action=' + action, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) alert(data.message || 'บันทึกไม่สำเร็จ');
                return data;
            } catch (err) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย');
                return { success: false };
            }
        }
        async function reloadAfter(action, fields) {
            const data = await postAction(action, fields);
            if (data.success) { showNotification(data.message); setTimeout(() => window.location.reload(), 800); }
        }
        function toggleNf(email, f) { reloadAfter('nf_toggle', { email, f }); }
        function bulkNf(f, on) {
            if (!confirm((on ? 'เปิด' : 'ปิด') + 'รหัส Netflix ' + (f === 'four' ? '4' : '6') + ' หลัก ทุกอีเมล?')) return;
            reloadAfter('nf_bulk', { f, on: on ? '1' : '0' });
        }
        function setMailyPin(email) {
            const v = prompt('PIN 6 หลักของกล่อง Maily ' + email + '\n(ปล่อยว่าง = ลบ PIN)', '');
            if (v === null) return;
            const pin = v.replace(/\D/g, '');
            if (pin && pin.length !== 6) { alert('PIN กล่อง Maily ต้องเป็นตัวเลข 6 หลัก'); return; }
            reloadAfter('save_maily_pin', { email, pin });
        }
        function saveDiscord() {
            const webhook = document.getElementById('discordWebhookInput').value.trim();
            if (!webhook) { alert('วางลิงก์ Webhook ก่อน'); return; }
            reloadAfter('save_discord', { webhook });
        }
        function removeDiscord() {
            if (!confirm('ยกเลิกแจ้งเตือน Discord?')) return;
            reloadAfter('save_discord', { webhook: '' });
        }
        function setBgType(type) {
            document.getElementById('bgTypeInput').value = type;
            document.getElementById('bgColorBox').classList.toggle('hidden', type !== 'color');
            document.getElementById('bgImageBox').classList.toggle('hidden', type !== 'image');
            document.getElementById('bgTypeColorBtn').className = 'px-4 py-2 text-xs font-bold ' + (type === 'color' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600');
            document.getElementById('bgTypeImageBtn').className = 'px-4 py-2 text-xs font-bold ' + (type === 'image' ? 'bg-purple-600 text-white' : 'bg-white text-gray-600');
        }
        async function saveShopPage() {
            const fields = { bg_type: document.getElementById('bgTypeInput').value, bg_color: document.getElementById('bgColorInput').value };
            document.querySelectorAll('[data-shop-text]').forEach((el) => { fields['text_' + el.dataset.shopText] = el.value; });
            const data = await postAction('save_shop_page', fields);
            return !!data.success;
        }
        async function uploadBgFile() {
            const input = document.getElementById('bgFileInput');
            if (!input.files || !input.files[0]) return;
            const data = await postAction('upload_bg', { bg: input.files[0] });
            if (data.success) {
                document.getElementById('bgImagePreview').src = data.bg_image;
                document.getElementById('bgImagePreview').classList.remove('hidden');
                setBgType('image');
                showNotification('อัปโหลดรูปพื้นหลังเรียบร้อยแล้วค่ะ');
            }
            input.value = '';
        }
        function filterEmailKind(kind) {
            document.querySelectorAll('[data-kind-chip]').forEach((b) => b.classList.toggle('active', b.dataset.kindChip === kind));
            document.querySelectorAll('.email-card').forEach((card) => {
                const show = kind === 'all' || card.dataset.kind === kind || ('app:' + card.dataset.app) === kind;
                card.classList.toggle('kind-hidden', !show);
            });
        }

        function clearEmailSearch() {
            const input = document.getElementById('emailSearchInput');
            if (input) {
                input.value = '';
                filterEmailList('');
            }
        }

        function filterMsEmailList(query) {
            const cleanQuery = query.trim().toLowerCase();
            const cards = document.querySelectorAll('.ms-email-card');
            const clearBtn = document.getElementById('clearMsEmailSearchBtn');
            const countEl = document.getElementById('msEmailSearchCount');
            const noResultEl = document.getElementById('noMsEmailSearchResult');

            if (clearBtn) {
                if (cleanQuery.length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }

            let visibleCount = 0;
            cards.forEach(card => {
                const email = card.getAttribute('data-email') || '';
                if (!cleanQuery || email.includes(cleanQuery)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (countEl) {
                countEl.innerText = `${visibleCount} รายการ`;
            }

            if (noResultEl) {
                if (visibleCount === 0 && cards.length > 0) {
                    noResultEl.classList.remove('hidden');
                } else {
                    noResultEl.classList.add('hidden');
                }
            }
        }

        function clearMsEmailSearch() {
            const input = document.getElementById('msEmailSearchInput');
            if (input) {
                input.value = '';
                filterMsEmailList('');
            }
        }
    </script>
</body>
</html>

