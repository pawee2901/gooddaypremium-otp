<?php
// =========================================================================
// Microsoft OAuth Callback — รับ authorization code กลับมาจาก Microsoft แล้วแลกเป็น
// access token + refresh token เพื่อเก็บไว้อ่านกล่องเมล Hotmail/Outlook ผ่าน Graph API
// =========================================================================
error_reporting(0);
ini_set('display_errors', '0');

$config_path = __DIR__ . '/../../../config.json';
$config_data = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];
if (!is_array($config_data)) {
    $config_data = [];
}

function redirect_with_error($message) {
    header('Location: /admin.php?ms_error=' . urlencode($message));
    exit;
}

// ผู้ใช้กดยกเลิก หรือ Microsoft ปฏิเสธคำขอ
if (isset($_GET['error'])) {
    $desc = $_GET['error_description'] ?? $_GET['error'];
    redirect_with_error($desc);
}

$client_id = $config_data['microsoft_oauth']['client_id'] ?? '';
$client_secret = $config_data['microsoft_oauth']['client_secret'] ?? '';
if (empty($client_id) || empty($client_secret)) {
    redirect_with_error('ยังไม่ได้ตั้งค่า Client ID / Client Secret ในหน้า Admin');
}

// ตรวจสอบ state เพื่อป้องกัน CSRF โดยเช็คลายเซ็น HMAC แทนการเทียบกับค่าใน Session
// (ไม่ใช้ Session เพราะ browser ต้องกระโดดออกไปที่ login.microsoftonline.com แล้ววกกลับมา
// ซึ่ง Session Cookie อาจไม่ติดกลับมาด้วย ขึ้นอยู่กับ policy ของแต่ละโฮสติ้ง)
$state = $_GET['state'] ?? '';
$state_parts = explode('.', $state, 2);
if (count($state_parts) !== 2) {
    redirect_with_error('state ไม่ถูกต้อง กรุณาลองเชื่อมต่อใหม่อีกครั้ง');
}
list($ms_nonce, $ms_sig) = $state_parts;
$expected_sig = hash_hmac('sha256', $ms_nonce, $client_secret);
if (!hash_equals($expected_sig, $ms_sig)) {
    redirect_with_error('state ไม่ตรงกัน กรุณาลองเชื่อมต่อใหม่อีกครั้ง');
}

$code = $_GET['code'] ?? '';
if (empty($code)) {
    redirect_with_error('ไม่พบ authorization code จาก Microsoft');
}

// ต้องเหมือนกับ Redirect URI ที่ใช้ตอนเริ่มขั้นตอน (ใน admin.php) และที่ลงทะเบียนไว้ใน Azure เป๊ะๆ
$redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/api/auth/microsoft/callback.php';

// -------------------------------------------------------------------------
// แลก authorization code เป็น access token + refresh token
// -------------------------------------------------------------------------
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://login.microsoftonline.com/consumers/oauth2/v2.0/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id'     => $client_id,
    'client_secret' => $client_secret,
    'code'          => $code,
    'redirect_uri'  => $redirect_uri,
    'grant_type'    => 'authorization_code',
    'scope'         => 'offline_access Mail.Read'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$token_res = curl_exec($ch);
$token_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($token_http_code !== 200 || !$token_res) {
    redirect_with_error('แลก token ไม่สำเร็จ (HTTP ' . $token_http_code . ')');
}

$token_data = json_decode($token_res, true);
$access_token = $token_data['access_token'] ?? '';
$refresh_token = $token_data['refresh_token'] ?? '';

if (empty($access_token) || empty($refresh_token)) {
    redirect_with_error('ไม่ได้รับ access token หรือ refresh token กลับมา');
}

// -------------------------------------------------------------------------
// เรียก Graph API เพื่อดูว่า token นี้เป็นของบัญชีอีเมลไหน
// -------------------------------------------------------------------------
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'https://graph.microsoft.com/v1.0/me?$select=mail,userPrincipalName');
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
$me_res = curl_exec($ch2);
$me_http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

if ($me_http_code !== 200 || !$me_res) {
    redirect_with_error('ดึงข้อมูลบัญชีจาก Microsoft ไม่สำเร็จ');
}

$me_data = json_decode($me_res, true);
$account_email = $me_data['mail'] ?? ($me_data['userPrincipalName'] ?? '');
if (empty($account_email)) {
    redirect_with_error('ไม่พบที่อยู่อีเมลของบัญชีนี้');
}

// -------------------------------------------------------------------------
// บันทึกบัญชีนี้ลงใน imap_emails (เพิ่มใหม่ หรืออัปเดตถ้ามีอยู่แล้ว)
// -------------------------------------------------------------------------
if (!isset($config_data['imap_emails']) || !is_array($config_data['imap_emails'])) {
    $config_data['imap_emails'] = [];
}

$found_idx = -1;
foreach ($config_data['imap_emails'] as $idx => $item) {
    if (strtolower($item['email'] ?? '') === strtolower($account_email)) {
        $found_idx = $idx;
        break;
    }
}

$new_item = [
    'id'            => $found_idx >= 0 ? $config_data['imap_emails'][$found_idx]['id'] : 'msgraph_' . time(),
    'email'         => $account_email,
    'provider'      => 'microsoft_graph',
    'refresh_token' => $refresh_token,
    'app_id'        => 'all',
    'active'        => true
];

if ($found_idx >= 0) {
    $config_data['imap_emails'][$found_idx] = $new_item;
} else {
    $config_data['imap_emails'][] = $new_item;
}

file_put_contents($config_path, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: /admin.php?ms_connected=' . urlencode($account_email));
exit;
