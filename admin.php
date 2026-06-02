<?php
session_start();

$config_path = __DIR__ . '/config.json';
$config = [
    'shop_name' => 'gooddaypremium',
    'logo_path' => '/static/logo.jpg',
    'admin_username' => 'admin',
    'admin_password' => 'admin1234'
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

    if ($action === 'update') {
        header('Content-Type: application/json');
        if (!$logged_in) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
            exit;
        }

        $shop_name = isset($_POST['shop_name']) ? trim($_POST['shop_name']) : '';
        $new_username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';

        if (empty($shop_name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อร้าน']);
            exit;
        }

        $config['shop_name'] = $shop_name;
        if (!empty($new_username)) {
            $config['admin_username'] = $new_username;
        }
        if (!empty($new_password)) {
            $config['admin_password'] = $new_password;
        }

        if (file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
        }
        exit;
    }

    if ($action === 'upload_logo') {
        header('Content-Type: application/json');
        if (!$logged_in) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
            exit;
        }

        if (!isset($_FILES['logo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไม่พบไฟล์ภาพ']);
            exit;
        }

        $file = $_FILES['logo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'อัปโหลดล้มเหลว รหัสข้อผิดพลาด: ' . $file['error']]);
            exit;
        }

        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_extensions)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ไฟล์ต้องเป็นประเภทรูปภาพเท่านั้น (png, jpg, jpeg, gif, webp)']);
            exit;
        }

        // ตั้งชื่อไฟล์ภาพใหม่เป็นแบบ Timestamp เพื่อป้องกัน Caching
        $filename = 'logo_custom_' . time() . '.' . $ext;
        $static_dir = __DIR__ . '/static/';

        if (!is_dir($static_dir)) {
            mkdir($static_dir, 0755, true);
        }

        // ลบไฟล์โลโก้เดิมทิ้งเพื่อไม่ให้ขยะรกเซิร์ฟเวอร์
        $old_logo_path = $config['logo_path'];
        if (!empty($old_logo_path) && strpos($old_logo_path, 'logo.jpg') === false) {
            $old_parts = explode('/static/', $old_logo_path);
            $old_file = end($old_parts);
            $old_full_path = $static_dir . $old_file;
            if (file_exists($old_full_path)) {
                @unlink($old_full_path);
            }
        }

        $target_path = $static_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $new_logo_path = '/static/' . $filename;
            $config['logo_path'] = $new_logo_path;
            file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'message' => 'อัปโหลดโลโก้สำเร็จ', 'logo_path' => $new_logo_path]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถเซฟไฟล์รูปภาพลงโฟลเดอร์ static ได้']);
        }
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>gooddaypremiumotp - ระบบตั้งค่าระบบหลังบ้าน</title>
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
        /* Pure minimalist white styling matching main dashboard */
        body {
            background-color: #fcfcfc;
            font-family: 'Inter', 'Mitr', 'sans-serif';
        }
        .main-card {
            background: #ffffff;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 8px -1px rgba(0, 0, 0, 0.03);
        }
        /* Custom pastel hover settings */
        .glass-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 md:p-6 selection:bg-black selection:text-white">

    <!-- Container หลัก (Fully Responsive) -->
    <div class="w-full max-w-lg flex flex-col gap-6">

        <!-- Header -->
        <div class="text-center space-y-3">
            <div class="flex flex-col items-center justify-center gap-3">
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" id="headerLogo" alt="<?php echo htmlspecialchars($config['shop_name']); ?> logo" 
                         class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-md transform hover:scale-105 transition-transform duration-300">
                    <div class="absolute -bottom-1 -right-1 bg-black text-white w-5 h-5 rounded-full flex items-center justify-center text-[8px] border border-white shadow">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight" id="headerShopName"><?php echo htmlspecialchars($config['shop_name']); ?></h1>
                    <p class="text-xs text-gray-400 font-semibold tracking-wide">ระบบปรับแต่งข้อมูลหลังบ้าน Admin Control Panel</p>
                </div>
            </div>
        </div>

        <?php if (!$logged_in): ?>
        <!-- ========================================== -->
        <!-- 1. LOGIN CARD (หากยังไม่ได้เข้าสู่ระบบ) -->
        <!-- ========================================== -->
        <div class="main-card w-full rounded-[2.5rem] border border-gray-100 p-6 md:p-8 space-y-6">
            <div class="text-center space-y-1">
                <h2 class="text-lg font-bold text-gray-800">🔐 เข้าสู่ระบบผู้ดูแลหลังบ้าน</h2>
                <p class="text-xs text-gray-400 font-light">กรอกบัญชีผู้ดูแลเพื่อเปลี่ยนโลโก้และชื่อร้านค้า</p>
            </div>

            <form id="loginForm" onsubmit="handleLogin(event)" class="space-y-4">
                <!-- Username Input -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-600 pl-1">ชื่อผู้ใช้งาน (Username)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-user text-xs"></i>
                        </div>
                        <input type="text" id="username" required
                               placeholder="กรอกชื่อผู้ใช้งานหลัก" 
                               class="w-full pl-9 pr-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all text-gray-800 text-sm placeholder-gray-400">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-gray-600 pl-1">รหัสผ่าน (Password)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-lock text-xs"></i>
                        </div>
                        <input type="password" id="password" required
                               placeholder="กรอกรหัสผ่านลับของคุณ" 
                               class="w-full pl-9 pr-10 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all text-gray-800 text-sm placeholder-gray-400">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-black transition-colors">
                            <i class="fa-solid fa-eye text-xs" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="loginBtn"
                        class="w-full bg-black hover:bg-zinc-800 text-white font-bold py-3.5 px-6 rounded-2xl transition-all duration-300 flex items-center justify-center gap-2 shadow active:scale-[0.98] text-sm">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>เข้าสู่ระบบจัดการ</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="index.php" class="text-xs font-semibold text-gray-400 hover:text-black transition-colors">
                    <i class="fa-solid fa-arrow-left-long mr-1"></i>กลับไปที่หน้าค้นหา OTP
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- ========================================== -->
        <!-- 2. SETTINGS DASHBOARD (เมื่อเข้าสู่ระบบสำเร็จ) -->
        <!-- ========================================== -->
        <div class="main-card w-full rounded-[2.5rem] border border-gray-100 p-6 md:p-8 space-y-6">
            
            <div class="flex items-center justify-between border-b border-gray-50 pb-4">
                <div class="flex items-center gap-2">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <h2 class="text-sm font-bold text-gray-800">ผู้ดูแลระบบ: Online</h2>
                </div>
                <a href="admin.php?action=logout" class="text-xs font-bold text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100/60 px-3 py-1.5 rounded-full transition-all">
                    <i class="fa-solid fa-right-from-bracket mr-1"></i>ออกจากระบบ
                </a>
            </div>

            <!-- หมวด A: การปรับแต่งโลโก้ร้านค้า -->
            <div class="space-y-4">
                <div class="border-b border-gray-50 pb-1 flex items-center gap-1.5 text-xs font-bold text-gray-800">
                    <i class="fa-solid fa-image text-gray-400"></i>
                    <span>อัปโหลดโลโก้ร้านค้าภาพใหม่ (Shop Logo Upload)</span>
                </div>

                <div class="flex items-center gap-4 bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                    <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" id="dashboardLogoPreview" alt="logo preview" 
                         class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                    
                    <div class="flex-1 min-w-0 space-y-2">
                        <p class="text-[10px] text-gray-400 leading-tight">แนะนำภาพสัดส่วน 1:1 ชนิดไฟล์ PNG, JPG หรือ WEBP ขอบเรียบกลมมน</p>
                        
                        <label for="logoFileInput" class="inline-flex items-center gap-1.5 bg-zinc-100 hover:bg-zinc-200 text-gray-700 font-bold text-xs px-3.5 py-2 rounded-xl cursor-pointer transition-all active:scale-[0.98]">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>เลือกไฟล์รูปภาพ</span>
                        </label>
                        <input type="file" id="logoFileInput" accept="image/*" class="hidden" onchange="uploadLogoFile()">
                    </div>
                </div>
            </div>

            <!-- หมวด B: การปรับแต่งชื่อร้านค้า & ข้อมูลความปลอดภัย -->
            <form id="settingsForm" onsubmit="handleSettingsUpdate(event)" class="space-y-5">
                
                <div class="space-y-4">
                    <div class="border-b border-gray-50 pb-1 flex items-center gap-1.5 text-xs font-bold text-gray-800">
                        <i class="fa-solid fa-sliders text-gray-400"></i>
                        <span>ตั้งชื่อร้านค้า (Shop Name Setting)</span>
                    </div>

                    <!-- Input ชื่อร้าน -->
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-gray-500 pl-1">ชื่อร้านที่ปรากฏบนหน้าจอหลัก</label>
                        <input type="text" id="shopNameInput" required value="<?php echo htmlspecialchars($config['shop_name']); ?>"
                               placeholder="ระบุชื่อร้านค้า เช่น gooddaypremium" 
                               class="w-full px-4 py-3 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all text-gray-800 text-sm font-semibold shadow-sm">
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="border-b border-gray-50 pb-1 flex items-center gap-1.5 text-xs font-bold text-gray-800">
                        <i class="fa-solid fa-shield-halved text-gray-400"></i>
                        <span>เปลี่ยนบัญชีความปลอดภัยผู้ดูแล (เปลี่ยนรหัสผ่าน)</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 pl-1">ชื่อผู้ใช้งานใหม่ (เว้นว่างหากไม่เปลี่ยน)</label>
                            <input type="text" id="newUsernameInput" 
                                   placeholder="Username ใหม่" 
                                   class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all text-gray-800 text-xs shadow-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-gray-500 pl-1">รหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน)</label>
                            <input type="password" id="newPasswordInput" 
                                   placeholder="Password ใหม่" 
                                   class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all text-gray-800 text-xs shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="saveSettingsBtn"
                        class="w-full bg-black hover:bg-zinc-800 text-white font-bold py-3.5 px-6 rounded-2xl transition-all duration-300 flex items-center justify-center gap-2 shadow active:scale-[0.98] text-sm">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>บันทึกการตั้งค่าทั้งหมด</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="index.php" class="text-xs font-bold text-zinc-500 hover:text-black transition-colors">
                    <i class="fa-solid fa-arrow-left-long mr-1.5"></i>กลับไปที่หน้าหลักลูกค้า (ค้นหา OTP)
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Alert Notification Box (ข้อความป๊อปอัพแจ้งสถานะแบบลอยตัว) -->
        <div id="alertBox" class="hidden fixed top-5 left-1/2 -translate-x-1/2 z-50 w-full max-w-sm px-4">
            <div id="alertContent" class="flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-lg border text-xs font-bold transition-all duration-300 transform scale-95 opacity-0">
                <i id="alertIcon" class="fa-solid"></i>
                <span id="alertMessage" class="flex-1"></span>
            </div>
        </div>

    </div>

    <!-- Footer เครดิตร้าน -->
    <div class="mt-8 text-center text-[10px] md:text-xs text-gray-400 font-light">
        © 2026 gooddaypremiumotp. Admin Panel Config.
    </div>

    <script>
        // เปิดปิดการแสดงผลรหัสผ่านล็อกอิน
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.className = 'fa-solid fa-eye-slash text-xs';
            } else {
                passwordInput.type = 'password';
                eyeIcon.className = 'fa-solid fa-eye text-xs';
            }
        }

        // แสดงแจ้งเตือนอย่างสวยงามสไตล์ iOS Toast
        function showNotification(message, isSuccess = true) {
            const alertBox = document.getElementById('alertBox');
            const alertContent = document.getElementById('alertContent');
            const alertIcon = document.getElementById('alertIcon');
            const alertMessage = document.getElementById('alertMessage');

            alertMessage.innerText = message;

            if (isSuccess) {
                alertContent.className = "flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-lg border border-emerald-100 bg-white text-emerald-800";
                alertIcon.className = "fa-solid fa-circle-check text-emerald-500 text-sm";
            } else {
                alertContent.className = "flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-lg border border-rose-100 bg-white text-rose-800";
                alertIcon.className = "fa-solid fa-triangle-exclamation text-rose-500 text-sm";
            }

            alertBox.classList.remove('hidden');
            setTimeout(() => {
                alertContent.classList.remove('scale-95', 'opacity-0');
                alertContent.classList.add('scale-100', 'opacity-100');
            }, 50);

            // ซ่อนอัตโนมัติภายใน 3 วินาที
            setTimeout(() => {
                alertContent.classList.remove('scale-100', 'opacity-100');
                alertContent.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    alertBox.classList.add('hidden');
                }, 300);
            }, 3000);
        }

        // ดำเนินการเข้าสู่ระบบแบบปลอดภัยผ่าน AJAX
        async function handleLogin(event) {
            event.preventDefault();
            const usernameInput = document.getElementById('username').value.trim();
            const passwordInput = document.getElementById('password').value.trim();
            const loginBtn = document.getElementById('loginBtn');

            loginBtn.disabled = true;
            loginBtn.classList.add('opacity-50', 'cursor-not-allowed');

            try {
                const formData = new FormData();
                formData.append('username', usernameInput);
                formData.append('password', passwordInput);

                const response = await fetch('admin.php?action=login', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('เข้าสู่ระบบสำเร็จ กำลังพาเข้าหน้าระบบ...', true);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'ไม่สามารถเข้าสู่ระบบได้', false);
                    loginBtn.disabled = false;
                    loginBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } catch (error) {
                showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย', false);
                loginBtn.disabled = false;
                loginBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        // ดำเนินการอัปเดตรูปภาพโลโก้ร้าน
        async function uploadLogoFile() {
            const logoFileInput = document.getElementById('logoFileInput');
            const file = logoFileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('logo', file);

            showNotification('กำลังอัปโหลดไฟล์รูปภาพโลโก้ใหม่...', true);

            try {
                const response = await fetch('admin.php?action=upload_logo', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('อัปโหลดและเปลี่ยนโลโก้ร้านค้าสำเร็จ!', true);
                    
                    // ป้องกัน Caching โดยการเพิ่ม timestamp ใน URL ของภาพพรีวิว
                    const cacheBusterPath = `${data.logo_path}?t=${Date.now()}`;
                    
                    // อัปเดตรูปภาพพรีวิวในหน้าจอแอดมินทันที
                    const dashboardLogoPreview = document.getElementById('dashboardLogoPreview');
                    const headerLogo = document.getElementById('headerLogo');
                    if (dashboardLogoPreview) dashboardLogoPreview.src = cacheBusterPath;
                    if (headerLogo) headerLogo.src = cacheBusterPath;
                } else {
                    showNotification(data.message || 'อัปโหลดภาพไม่สำเร็จ', false);
                }
            } catch (error) {
                showNotification('เกิดข้อผิดพลาดเครือข่ายระหว่างอัปโหลดรูปภาพ', false);
            } finally {
                logoFileInput.value = ''; // รีเซ็ตอินพุตให้เลือกไฟล์เดิมซ้ำได้อีก
            }
        }

        // ดำเนินการอัปเดตการตั้งค่าชื่อและรหัสผ่าน
        async function handleSettingsUpdate(event) {
            event.preventDefault();
            const shopNameInput = document.getElementById('shopNameInput').value.trim();
            const newUsernameInput = document.getElementById('newUsernameInput').value.trim();
            const newPasswordInput = document.getElementById('newPasswordInput').value.trim();
            const saveSettingsBtn = document.getElementById('saveSettingsBtn');

            saveSettingsBtn.disabled = true;
            saveSettingsBtn.classList.add('opacity-50', 'cursor-not-allowed');

            try {
                const formData = new FormData();
                formData.append('shop_name', shopNameInput);
                formData.append('username', newUsernameInput);
                formData.append('password', newPasswordInput);

                const response = await fetch('admin.php?action=update', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('บันทึกการตั้งค่าร้านค้าและรหัสผ่านเรียบร้อยแล้วค่ะ!', true);
                    
                    // ปรับแต่งชื่อร้านในหน้าแอดมินทันที
                    const headerShopName = document.getElementById('headerShopName');
                    if (headerShopName) headerShopName.innerText = shopNameInput;
                    
                    // ล้างค่าฟิลด์เปล่าสำหรับรหัสผ่าน
                    document.getElementById('newUsernameInput').value = '';
                    document.getElementById('newPasswordInput').value = '';
                } else {
                    showNotification(data.message || 'ไม่สามารถอัปเดตการตั้งค่าได้', false);
                }
            } catch (error) {
                showNotification('เกิดความขัดข้องเครือข่ายเซิร์ฟเวอร์', false);
            } finally {
                saveSettingsBtn.disabled = false;
                saveSettingsBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    </script>
</body>
</html>
