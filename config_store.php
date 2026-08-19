<?php
// =========================================================================
// config_store.php — ชั้นเก็บข้อมูลตั้งค่าระบบผ่าน MySQL แทนไฟล์ config.json เดิม
//
// load_config()/save_config($config) คืนค่า/รับค่าเป็น array รูปร่างเดียวกับที่
// config.json เคยให้ทุกประการ (shop_name, apps[], imap_emails[], api_providers[] ฯลฯ)
// เพื่อให้ admin.php, get_otp.php, index.php, ms_callback.php ไม่ต้องแก้ตรรกะอื่นเลย
// นอกจากจุดอ่าน/เขียนไฟล์เดิม
//
// รหัสผ่านฐานข้อมูลจริงอยู่ใน db_credentials.php ซึ่ง "ไม่ track ใน git" (ดู .gitignore)
// ต้องอัปโหลดไฟล์นั้นเองครั้งเดียวผ่าน Hostinger File Manager (ดู db_credentials.example.php)
// =========================================================================

$__db_credentials_file = __DIR__ . '/db_credentials.php';
if (file_exists($__db_credentials_file)) {
    require_once $__db_credentials_file;
} else {
    // ค่า default เผื่อยังไม่ได้อัปโหลด db_credentials.php — ตั้งค่าจริงในไฟล์นั้นแทนการแก้ตรงนี้
    if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
    if (!defined('DB_NAME')) define('DB_NAME', 'u458279693_pspapp_db');
    if (!defined('DB_USER')) define('DB_USER', 'u458279693_admin01');
    if (!defined('DB_PASS')) define('DB_PASS', '');
}

function db_connect() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

function load_config() {
    $pdo = db_connect();

    $settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
    if (!$settings) {
        $settings = [
            'shop_name' => 'gooddaypremium', 'logo_path' => '/static/logo.jpg',
            'disney_logo_path' => '', 'trueid_logo_path' => '',
            'admin_username' => 'admin', 'admin_password' => 'admin1234',
            'api_key_1' => '', 'api_key_2' => '', 'ms_client_id' => '', 'ms_client_secret' => '',
        ];
    }

    $config = [
        'shop_name'        => $settings['shop_name'],
        'logo_path'        => $settings['logo_path'],
        'disney_logo_path' => $settings['disney_logo_path'],
        'trueid_logo_path' => $settings['trueid_logo_path'],
        'admin_username'   => $settings['admin_username'],
        'admin_password'   => $settings['admin_password'],
        'api_key_1'        => $settings['api_key_1'],
        'api_key_2'        => $settings['api_key_2'],
    ];

    if (!empty($settings['ms_client_id']) || !empty($settings['ms_client_secret'])) {
        $config['microsoft_oauth'] = [
            'client_id'     => $settings['ms_client_id'],
            'client_secret' => $settings['ms_client_secret'],
        ];
    }

    $apps = $pdo->query("SELECT * FROM apps ORDER BY sort_order, created_at")->fetchAll();
    $config['apps'] = array_map(function ($row) {
        return [
            'id'            => $row['id'],
            'name'          => $row['name'],
            'code'          => $row['code'],
            'logo_path'     => $row['logo_path'],
            'keywords'      => $row['keywords'],
            'bg_color'      => $row['bg_color'],
            'border_color'  => $row['border_color'],
            'active'        => (bool)$row['active'],
        ];
    }, $apps);

    $emails = $pdo->query("SELECT * FROM imap_emails ORDER BY created_at")->fetchAll();
    $config['imap_emails'] = array_map(function ($row) {
        $item = [
            'id'       => $row['id'],
            'email'    => $row['email'],
            'provider' => $row['provider'],
            'host'     => $row['host'],
            'port'     => $row['port'] !== null ? (int)$row['port'] : null,
            'password' => $row['password'],
            'app_id'   => $row['app_id'],
            'active'   => (bool)$row['active'],
        ];
        if (!empty($row['refresh_token'])) {
            $item['refresh_token'] = $row['refresh_token'];
        }
        return $item;
    }, $emails);

    $providers = $pdo->query("SELECT * FROM api_providers ORDER BY id")->fetchAll();
    $config['api_providers'] = array_map(function ($row) {
        $item = [
            'id'     => $row['id'],
            'name'   => $row['name'],
            'type'   => $row['type'],
            'active' => (bool)$row['active'],
        ];
        if ($row['type'] === 'cloud_run') {
            $item['url'] = $row['url'];
        } else {
            $item['token'] = $row['token'];
        }
        return $item;
    }, $providers);

    $config['gmail_central_accounts'] = $pdo
        ->query("SELECT email AS user, password AS pass FROM gmail_central_accounts ORDER BY id")
        ->fetchAll();

    $config['maily_central_accounts'] = $pdo
        ->query("SELECT email FROM maily_central_accounts ORDER BY id")
        ->fetchAll(PDO::FETCH_COLUMN);

    return $config;
}

// sync ตารางลูก (apps / imap_emails / api_providers) ให้ตรงกับ array ที่ส่งมาทั้งชุด
// (upsert ทุกแถวที่มี id ตรงกัน + ลบแถวที่ไม่มีใน array แล้ว)
function __config_store_sync_table($pdo, $table, $rows, $mapFn) {
    $keepIds = [];
    $i = 0;
    foreach ($rows as $row) {
        $data = $mapFn($row, $i++);
        $keepIds[] = $data['id'];
        $cols = array_keys($data);
        $placeholders = array_map(function ($c) { return ':' . $c; }, $cols);
        $updates = array_map(function ($c) { return "`$c` = VALUES(`$c`)"; }, $cols);
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(',', $updates);
        $stmt = $pdo->prepare($sql);
        $params = [];
        foreach ($data as $k => $v) {
            $params[':' . $k] = $v;
        }
        $stmt->execute($params);
    }
    if (!empty($keepIds)) {
        $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id NOT IN ($placeholders)");
        $stmt->execute($keepIds);
    } else {
        $pdo->exec("DELETE FROM `$table`");
    }
}

function save_config($config) {
    $pdo = db_connect();
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO settings (id, shop_name, logo_path, disney_logo_path, trueid_logo_path, admin_username, admin_password, api_key_1, api_key_2, ms_client_id, ms_client_secret)
            VALUES (1, :shop_name, :logo_path, :disney_logo_path, :trueid_logo_path, :admin_username, :admin_password, :api_key_1, :api_key_2, :ms_client_id, :ms_client_secret)
            ON DUPLICATE KEY UPDATE
                shop_name = VALUES(shop_name), logo_path = VALUES(logo_path),
                disney_logo_path = VALUES(disney_logo_path), trueid_logo_path = VALUES(trueid_logo_path),
                admin_username = VALUES(admin_username), admin_password = VALUES(admin_password),
                api_key_1 = VALUES(api_key_1), api_key_2 = VALUES(api_key_2),
                ms_client_id = VALUES(ms_client_id), ms_client_secret = VALUES(ms_client_secret)
        ");
        $stmt->execute([
            ':shop_name'        => $config['shop_name'] ?? 'gooddaypremium',
            ':logo_path'        => $config['logo_path'] ?? '/static/logo.jpg',
            ':disney_logo_path' => $config['disney_logo_path'] ?? '',
            ':trueid_logo_path' => $config['trueid_logo_path'] ?? '',
            ':admin_username'   => $config['admin_username'] ?? 'admin',
            ':admin_password'   => $config['admin_password'] ?? 'admin1234',
            ':api_key_1'        => $config['api_key_1'] ?? null,
            ':api_key_2'        => $config['api_key_2'] ?? null,
            ':ms_client_id'     => $config['microsoft_oauth']['client_id'] ?? null,
            ':ms_client_secret' => $config['microsoft_oauth']['client_secret'] ?? null,
        ]);

        __config_store_sync_table($pdo, 'apps', $config['apps'] ?? [], function ($row, $i) {
            return [
                'id'           => $row['id'],
                'name'         => $row['name'],
                'code'         => $row['code'] ?? '',
                'logo_path'    => $row['logo_path'] ?? null,
                'keywords'     => $row['keywords'] ?? null,
                'bg_color'     => $row['bg_color'] ?? null,
                'border_color' => $row['border_color'] ?? null,
                'sort_order'   => $i,
                'active'       => !empty($row['active']) ? 1 : 0,
            ];
        });

        __config_store_sync_table($pdo, 'imap_emails', $config['imap_emails'] ?? [], function ($row, $i) {
            return [
                'id'            => $row['id'],
                'email'         => $row['email'],
                'provider'      => $row['provider'] ?? 'gmail',
                'host'          => $row['host'] ?? null,
                'port'          => $row['port'] ?? null,
                'password'      => $row['password'] ?? null,
                'refresh_token' => $row['refresh_token'] ?? null,
                'app_id'        => $row['app_id'] ?? 'all',
                'active'        => !empty($row['active']) ? 1 : 0,
            ];
        });

        __config_store_sync_table($pdo, 'api_providers', $config['api_providers'] ?? [], function ($row, $i) {
            return [
                'id'     => $row['id'],
                'name'   => $row['name'],
                'type'   => $row['type'] ?? 'maily',
                'token'  => $row['token'] ?? null,
                'url'    => $row['url'] ?? null,
                'active' => !empty($row['active']) ? 1 : 0,
            ];
        });

        // gmail_central_accounts / maily_central_accounts ไม่มี id คงที่ใน config เดิม -> ล้างแล้วใส่ใหม่ทั้งชุด
        $pdo->exec("DELETE FROM gmail_central_accounts");
        $insGmail = $pdo->prepare("INSERT INTO gmail_central_accounts (email, password) VALUES (:email, :password)");
        foreach (($config['gmail_central_accounts'] ?? []) as $acc) {
            $insGmail->execute([':email' => $acc['user'], ':password' => $acc['pass']]);
        }

        $pdo->exec("DELETE FROM maily_central_accounts");
        $insMaily = $pdo->prepare("INSERT INTO maily_central_accounts (email) VALUES (:email)");
        foreach (($config['maily_central_accounts'] ?? []) as $email) {
            $insMaily->execute([':email' => $email]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('save_config failed: ' . $e->getMessage());
        return false;
    }
}
