<?php
$config_path = __DIR__ . '/config.json';
$config = [
    'shop_name' => 'gooddaypremium',
    'logo_path' => '/static/logo.jpg',
    'disney_logo_path' => '',
    'trueid_logo_path' => '',
    'admin_username' => 'admin',
    'admin_password' => 'admin1234'
];

if (file_exists($config_path)) {
    $json_data = file_get_contents($config_path);
    $decoded = json_decode($json_data, true);
    if (is_array($decoded)) {
        $config = array_merge($config, $decoded);
    }
} else {
    file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>gooddaypremiumotp - ค้นหารหัส OTP ตัวล่าสุด</title>
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
                    },
                    screens: {
                        'xs': '375px',
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Pure minimalist white styling */
        body {
            background-color: #ffffff;
            font-family: 'Inter', 'Mitr', 'sans-serif';
        }
        .main-card {
            background: #ffffff;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 2px 8px -1px rgba(0, 0, 0, 0.03);
        }
        /* Custom pastel shadow for app buttons */
        .app-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .app-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.04);
        }
        /* Hide scrollbars for ultra-clean mobile appearance */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #e4e4e7;
            border-radius: 10px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 md:p-6 bg-white selection:bg-black selection:text-white">

    <!-- Container หลัก (Fully Responsive) -->
    <div class="w-full max-w-lg flex flex-col gap-6">
        
        <!-- Header & Shop Logo -->
        <div class="text-center space-y-4">
            <div class="flex flex-col items-center justify-center gap-3">
                <!-- Shop Official Logo -->
                <div class="relative">
                    <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="<?php echo htmlspecialchars($config['shop_name']); ?> logo" 
                         class="w-20 h-20 md:w-24 md:h-24 rounded-full object-cover border-4 border-gray-50 shadow-md transform hover:scale-105 transition-transform duration-300"
                         onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=100&auto=format&fit=crop';">
                    <div class="absolute -bottom-1 -right-1 bg-green-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-[10px] border-2 border-white shadow">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight leading-none"><?php echo htmlspecialchars($config['shop_name']); ?></h1>
                    <p class="text-xs font-semibold text-gray-400 tracking-wide">กดรับ otp เองได้ ตลอด 24 ชั่วโมง</p>
                </div>
            </div>
            
            <!-- Announcement Warning Badge -->
            <div class="inline-flex items-center gap-2 bg-amber-50/80 border border-amber-200/60 text-amber-800 text-[11px] md:text-xs px-4 py-2 rounded-2xl font-medium shadow-sm transition-all duration-300 hover:scale-[1.02] max-w-full">
                <span class="flex h-2 w-2 relative flex-shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                <span class="truncate">🔒 ค้นหารหัส OTP สำหรับแอปพลิชัน Disney+ และ TrueID</span>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ส่วนหน้าแรก: เลือกแอปพลิเคชัน (App Selection Screen) -->
        <!-- ========================================== -->
        <div id="appSelectionScreen" class="space-y-6 transition-all duration-500">
            <div class="text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">เลือกแอปที่ต้องการรับรหัสยืนยัน</h2>
            </div>

            <!-- Grid ของปุ่มเลือกแอป (สไตล์น่ารักมินิมอลสีขาวพาสเทล) -->
            <div class="grid grid-cols-2 gap-4">
                
                <!-- 1. Disney+ Card -->
                <div onclick="selectApp('Disney+')" 
                     class="app-btn bg-[#F0F7FF] border border-[#D6E9FF] rounded-[2rem] p-5 cursor-pointer flex flex-col items-center justify-center gap-3 text-center active:scale-[0.98] group">
                    <div class="w-14 h-14 bg-white rounded-2xl shadow-sm border border-[#D6E9FF] flex items-center justify-center overflow-hidden flex-shrink-0 transition-all duration-300 group-hover:border-[#D6E9FF]/10">
                        <?php if (!empty($config['disney_logo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($config['disney_logo_path']); ?>" alt="Disney+ logo" class="w-full h-full object-cover transition-all duration-300 group-hover:scale-115 group-hover:rotate-1 group-hover:drop-shadow-[0_0_8px_rgba(30,58,138,0.5)]">
                        <?php else: ?>
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg" alt="Disney+ logo" class="w-12 h-6 object-contain transition-all duration-300 group-hover:scale-115 group-hover:rotate-1 group-hover:drop-shadow-[0_0_8px_rgba(30,58,138,0.5)]">
                        <?php endif; ?>
                    </div>
                    <span class="text-base md:text-lg font-extrabold text-gray-800">Disney+</span>
                </div>

                <!-- 2. TrueID Card -->
                <div onclick="selectApp('TrueID')" 
                     class="app-btn bg-[#FFF5FA] border border-[#FFE2F3] rounded-[2rem] p-5 cursor-pointer flex flex-col items-center justify-center gap-3 text-center active:scale-[0.98] group">
                    <div class="w-14 h-14 <?php echo !empty($config['trueid_logo_path']) ? 'bg-white border-[#FFE2F3]' : 'bg-[#E50914] border-[#E50914]'; ?> rounded-2xl shadow-sm flex items-center justify-center overflow-hidden flex-shrink-0 border transition-all duration-300 group-hover:scale-110 group-hover:rotate-[-3deg] group-hover:drop-shadow-[0_0_8px_rgba(229,9,20,0.5)]">
                        <?php if (!empty($config['trueid_logo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($config['trueid_logo_path']); ?>" alt="TrueID logo" class="w-full h-full object-cover transition-all duration-300 group-hover:scale-110 group-hover:rotate-[-3deg] group-hover:drop-shadow-[0_0_8px_rgba(229,9,20,0.5)]">
                        <?php else: ?>
                        <span class="text-[12px] font-black text-white tracking-tighter leading-none select-none uppercase">trueID</span>
                        <?php endif; ?>
                    </div>
                    <span class="text-base md:text-lg font-extrabold text-gray-800">TrueID</span>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- ส่วนหน้าต่างค้นหา (Search Box Card - ซ่อนอยู่ตอนเริ่มต้น) -->
        <!-- ========================================== -->
        <div id="searchBoxCard" class="main-card w-full rounded-3xl border border-gray-100 p-5 md:p-8 transition-all duration-500 hidden transform scale-95 opacity-0">
            <div class="space-y-5">
                
                <!-- แถบด้านบน: ปุ่มย้อนกลับและตราสัญลักษณ์บริการที่เลือก -->
                <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                    <button onclick="goBackToSelection()" class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-black font-semibold transition-all">
                        <i class="fa-solid fa-arrow-left-long"></i>
                        <span>ย้อนกลับ</span>
                    </button>
                    <!-- ป้ายแท็กแอปพลิเคชันที่เลือก -->
                    <span id="selectedAppTag" class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider shadow-inner transition-colors">
                        -
                    </span>
                </div>

                <div class="text-center space-y-1">
                    <h2 class="text-base md:text-lg font-bold text-gray-800">ค้นหารหัสผ่านทางอีเมล</h2>
                    <p class="text-xs md:text-sm text-gray-500">ใส่ที่อยู่อีเมลของคุณเพื่อดึงรหัสยืนยันตัวล่าสุดได้ทันที</p>
                </div>

                <div class="space-y-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </div>
                        <input type="email" id="email" 
                               placeholder="กรอกอีเมลของคุณ เช่น example@gmail.com" 
                               class="w-full pl-10 pr-4 py-3.5 rounded-2xl border border-gray-200 focus:ring-2 focus:ring-black focus:border-black outline-none transition-all text-gray-800 text-center font-medium placeholder-gray-400 text-sm shadow-sm"
                               onkeydown="if(event.key === 'Enter') fetchOTP()">
                    </div>

                    <button id="searchBtn" onclick="fetchOTP()" 
                            class="w-full bg-black hover:bg-zinc-800 text-white font-bold py-3.5 px-6 rounded-2xl transition-all duration-300 flex items-center justify-center gap-2.5 shadow-sm active:scale-[0.98] text-sm">
                        <i id="btnIcon" class="fa-solid fa-magnifying-glass"></i>
                        <span id="btnText">ค้นหารหัสยืนยัน (OTP)</span>
                    </button>
                </div>

                <!-- ข้อความสถานะการโหลด -->
                <div id="statusBox" class="hidden flex flex-col items-center justify-center py-4 space-y-2">
                    <div class="w-8 h-8 border-3 border-zinc-200 border-t-black rounded-full animate-spin"></div>
                    <p class="text-xs md:text-sm font-medium text-gray-500 animate-pulse">กำลังดึงข้อมูลรหัส OTP ล่าสุด...</p>
                </div>

                <!-- ข้อความแจ้งข้อผิดพลาด -->
                <div id="errorBox" class="hidden bg-rose-50 border border-rose-100 text-rose-800 px-4 py-3 rounded-2xl text-center text-xs md:text-sm font-medium space-y-1">
                    <div class="flex items-center justify-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                        <span id="errorTitle" class="font-bold">เกิดข้อผิดพลาด</span>
                    </div>
                    <p id="errorMessage" class="text-[11px] text-rose-600 font-light"></p>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- ส่วนการ์ดจำลองอีเมลจริง (Email Inbox Mockup Card) -->
        <!-- ========================================== -->
        <div id="resultBox" class="hidden bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden transform scale-95 opacity-0 transition-all duration-500 w-full">
            
            <!-- Email Header Bar with Back Button & Recipient Info -->
            <div class="bg-gray-50/80 border-b border-gray-100 px-4 md:px-6 py-4 flex items-center justify-between">
                <button onclick="goBackToSelection()" class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-black font-semibold transition-all">
                    <i class="fa-solid fa-arrow-left-long"></i>
                    <span>ย้อนกลับ</span>
                </button>
            </div>

            <!-- 1. Inbox List View (กล่องแสดงรายการจดหมายเข้า) -->
            <div id="inboxListView" class="p-4 md:p-6 space-y-4">
                <div class="flex items-center justify-between text-xs font-bold text-gray-800 border-b border-gray-50 pb-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-inbox text-sm text-gray-400"></i>
                        <span>📨 กล่องจดหมายเข้าล่าสุด (เลือกดู OTP)</span>
                    </div>
                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 text-[10px] px-2.5 py-0.5 rounded-full font-bold shadow-inner">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>สืบค้นสดอัตโนมัติ (5s)</span>
                    </span>
                </div>
                
                <!-- dynamic container for email cards -->
                <div id="emailListContainer" class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    <!-- Rendered via JS -->
                </div>
                
                <div class="text-center text-[10px] text-gray-400 font-light truncate pt-1">
                    กล่องข้อความสำหรับ: <span id="recipientEmailList" class="underline font-medium text-gray-600"></span>
                </div>
            </div>

            <!-- 2. Detailed Email View (หน้าจอแรนเดอร์จดหมายจริงฉบับเต็ม) -->
            <div id="emailDetailView" class="hidden p-4 md:p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-50 pb-2">
                    <button onclick="goBackToSelection()" class="flex items-center gap-1.5 text-xs text-zinc-500 hover:text-black font-semibold transition-all">
                        <i class="fa-solid fa-chevron-left text-[9px]"></i>
                        <span>ย้อนกลับไปหน้าค้นหา</span>
                    </button>
                    <span id="detailEmailTime" class="text-[10px] text-gray-400 font-medium"></span>
                </div>

                <!-- OTP Display Card (แสดงรหัส OTP แบบพรีเมียมขนาดใหญ่) -->
                <div id="otpDisplayCard" class="bg-zinc-50 border border-zinc-100 rounded-3xl p-6 text-center space-y-3 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-zinc-200 via-zinc-400 to-zinc-200"></div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">รหัสยืนยันตัวตน (OTP)</p>
                    <div class="flex flex-col items-center justify-center gap-1">
                        <span id="otpCodeNumber" class="text-4xl font-extrabold text-black tracking-widest select-all">-</span>
                        <p id="otpRefText" class="text-[10px] text-zinc-400 font-light mt-1"></p>
                    </div>
                    <div class="pt-2 flex justify-center">
                        <button onclick="copyOTP()" class="bg-black hover:bg-zinc-800 text-white text-xs font-bold px-4 py-2 rounded-2xl flex items-center gap-1.5 transition-all active:scale-[0.97] shadow-sm">
                            <i class="fa-solid fa-copy text-[10px]"></i>
                            <span>คัดลอกรหัส OTP</span>
                        </button>
                    </div>
                </div>

                <div class="text-center pt-2">
                    <button onclick="toggleEmailIframe()" id="toggleIframeBtn" class="inline-flex items-center gap-1.5 text-xs text-zinc-400 hover:text-zinc-700 font-bold transition-all">
                        <i class="fa-solid fa-eye-slash"></i>
                        <span id="toggleIframeText">แสดงจดหมายฉบับจริง (Show Email Preview)</span>
                    </button>
                </div>
                
                <div id="iframeContainer" class="hidden border border-gray-100 rounded-2xl overflow-hidden shadow-inner bg-white transition-all duration-300">
                    <iframe id="emailIframe" class="w-full h-[450px] border-0 bg-white" title="Email preview content"></iframe>
                </div>
                
                <div class="text-center text-[10px] text-gray-400 font-light truncate">
                    กล่องข้อความสำหรับ: <span id="recipientEmail" class="underline font-medium text-gray-600"></span>
                </div>
            </div>
            
            <!-- Bottom Accent Bar -->
            <div class="h-1.5 bg-black" id="accentBar"></div>
        </div>

    </div>

    <!-- Footer เครดิตร้าน -->
    <div class="mt-8 text-center text-[10px] md:text-xs text-gray-400 font-light">
        © 2026 gooddaypremiumotp. All rights reserved.
    </div>

    <script>
        // กำหนดตัวแปรสำหรับเก็บบริการและสถานะการตรวจสอบอัตโนมัติ
        let selectedApp = '';
        let currentEmails = []; // เก็บรายการจดหมายล่าสุด
        let pollingInterval = null; // เก็บตัวแปรสืบค้นอัตโนมัติ
        let isPolling = false;

        // ฟังก์ชันคลิกเลือกแอปและเปลี่ยนหน้า
        function selectApp(appName) {
            selectedApp = appName;
            
            const appSelectionScreen = document.getElementById('appSelectionScreen');
            const searchBoxCard = document.getElementById('searchBoxCard');
            const selectedAppTag = document.getElementById('selectedAppTag');

            // แสดงชื่อแอปในป้ายแท็กด้านบนฟอร์ม
            selectedAppTag.innerText = appName;

            // กำหนดสีป้ายแท็กตามบริการ
            const lowerName = appName.toLowerCase();
            if (lowerName.includes('netflix')) {
                selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-800 uppercase tracking-wider shadow-inner";
            } else if (lowerName.includes('disney')) {
                selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 uppercase tracking-wider shadow-inner";
            } else if (lowerName.includes('true')) {
                selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-600 uppercase tracking-wider shadow-inner";
            } else if (lowerName.includes('chatgpt')) {
                selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 uppercase tracking-wider shadow-inner";
            } else {
                selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-violet-100 text-violet-800 uppercase tracking-wider shadow-inner";
            }

            // ซ่อนหน้าหลักเลือกแอป และโชว์หน้าค้นหาด้วยแอนิเมชัน
            appSelectionScreen.classList.add('hidden');
            searchBoxCard.classList.remove('hidden');
            setTimeout(() => {
                searchBoxCard.classList.remove('scale-95', 'opacity-0');
                searchBoxCard.classList.add('scale-100', 'opacity-100');
            }, 50);
        }

        // ฟังก์ชันย้อนกลับไปยังหน้าเลือกแอปพลิเคชัน
        function goBackToSelection() {
            const appSelectionScreen = document.getElementById('appSelectionScreen');
            const searchBoxCard = document.getElementById('searchBoxCard');
            const resultBox = document.getElementById('resultBox');
            
            // หยุดการดึงข้อมูลสด
            stopOTPPolling();
            
            // เคลียร์ค่า
            selectedApp = '';
            currentEmails = [];
            document.getElementById('email').value = '';
            
            // ซ่อนกล่องข้อมูล
            searchBoxCard.classList.add('scale-95', 'opacity-0');
            resultBox.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                searchBoxCard.classList.add('hidden');
                resultBox.classList.add('hidden');
                appSelectionScreen.classList.remove('hidden');
            }, 300);
        }

        // กลับไปแสดงรายการกล่องจดหมายเข้าทั้งหมด
        function showInboxList() {
            const inboxListView = document.getElementById('inboxListView');
            const emailDetailView = document.getElementById('emailDetailView');
            
            emailDetailView.classList.add('hidden');
            inboxListView.classList.remove('hidden');
        }

        // เปิดแสดงดีเทลเนื้อหาจดหมายจริงฉบับนั้น ๆ ใน iframe
        function openEmailDetail(index) {
            const inboxListView = document.getElementById('inboxListView');
            const emailDetailView = document.getElementById('emailDetailView');
            const emailIframe = document.getElementById('emailIframe');
            const detailEmailTime = document.getElementById('detailEmailTime');
            const recipientEmail = document.getElementById('recipientEmail');
            
            const otpCodeNumber = document.getElementById('otpCodeNumber');
            const otpRefText = document.getElementById('otpRefText');
            const iframeContainer = document.getElementById('iframeContainer');
            const toggleIframeBtn = document.getElementById('toggleIframeBtn');
            
            const emailData = currentEmails[index];
            if (!emailData) return;
            
            if (detailEmailTime) {
                detailEmailTime.innerText = emailData.time;
            }
            
            if (recipientEmail) {
                recipientEmail.innerText = document.getElementById('email').value.trim();
            }
            
            // เคลียร์การแสดงผลตัวเก่าของ IFrame
            if (iframeContainer) iframeContainer.classList.add('hidden');
            if (toggleIframeBtn) toggleIframeBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> <span id="toggleIframeText">แสดงจดหมายฉบับจริง (Show Email Preview)</span>';
            
            // แสดงรหัส OTP และรหัสอ้างอิง
            if (otpCodeNumber) {
                if (emailData.otp) {
                    otpCodeNumber.innerText = emailData.otp;
                } else {
                    otpCodeNumber.innerText = 'ไม่พบรหัส OTP';
                }
            }
            
            if (otpRefText) {
                if (emailData.ref) {
                    otpRefText.innerText = `รหัสอ้างอิง (Ref): ${emailData.ref}`;
                    otpRefText.classList.remove('hidden');
                } else {
                    otpRefText.innerText = '';
                    otpRefText.classList.add('hidden');
                }
            }
            
            if (emailIframe && emailData.html_body) {
                setTimeout(() => {
                    const iframeDoc = emailIframe.contentDocument || emailIframe.contentWindow.document;
                    iframeDoc.open();
                    iframeDoc.write(emailData.html_body);
                    iframeDoc.close();
                }, 50);
            }
            
            inboxListView.classList.add('hidden');
            emailDetailView.classList.remove('hidden');
        }

        // คัดลอกรหัส OTP
        function copyOTP() {
            const otpText = document.getElementById('otpCodeNumber').innerText;
            if (otpText && otpText !== '-' && otpText !== 'ไม่พบรหัส OTP') {
                navigator.clipboard.writeText(otpText).then(() => {
                    const notify = document.createElement('div');
                    notify.className = "fixed top-5 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs font-bold px-4 py-2 rounded-2xl shadow-lg z-50 animate-bounce";
                    notify.innerText = "คัดลอกรหัส OTP สำเร็จแล้วค่ะ!";
                    document.body.appendChild(notify);
                    setTimeout(() => notify.remove(), 2000);
                }).catch(err => {
                    console.error('Could not copy text: ', err);
                });
            }
        }

        // แสดง/ซ่อน จดหมายฉบับเต็ม
        function toggleEmailIframe() {
            const iframeContainer = document.getElementById('iframeContainer');
            const toggleIframeBtn = document.getElementById('toggleIframeBtn');
            
            if (iframeContainer) {
                if (iframeContainer.classList.contains('hidden')) {
                    iframeContainer.classList.remove('hidden');
                    toggleIframeBtn.innerHTML = '<i class="fa-solid fa-eye"></i> <span id="toggleIframeText">ซ่อนจดหมายฉบับจริง (Hide Email Preview)</span>';
                } else {
                    iframeContainer.classList.add('hidden');
                    toggleIframeBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> <span id="toggleIframeText">แสดงจดหมายฉบับจริง (Show Email Preview)</span>';
                }
            }
        }

        // ฟังก์ชันอัปเดต UI รายการอีเมลแบบ Real-time
        function renderEmails(emails, appName) {
            const emailListContainer = document.getElementById('emailListContainer');
            if (!emailListContainer) return;

            emailListContainer.innerHTML = ''; // เคลียร์ตัวเก่า

            if (!emails || emails.length === 0) {
                // แสดงสถานะการรอรับ OTP ล่าสุดแบบสีกริตเตอร์สวยงาม
                emailListContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-10 px-4 text-center space-y-4 bg-zinc-50/50 rounded-3xl border border-dashed border-zinc-200 animate-pulse select-none">
                        <div class="relative flex items-center justify-center">
                            <div class="w-12 h-12 border-3 border-zinc-100 border-t-black rounded-full animate-spin"></div>
                            <i class="fa-solid fa-hourglass-half absolute text-xs text-zinc-400 animate-bounce"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-zinc-700">⏳ กำลังรอรับข้อความ OTP ล่าสุด...</p>
                            <p class="text-[10px] text-zinc-400 font-light leading-snug">ระบบจะดึงอีเมลและอัปเดตบนหน้าจอนี้โดยอัตโนมัติทุกๆ 5 วินาที<br>กรุณากดส่งรหัสยืนยันจากหน้าแอปพลิเคชันปลายทางค่ะ</p>
                        </div>
                    </div>
                `;
                return;
            }

            // แสดงเฉพาะรายการจดหมายเข้าล่าสุดฉบับเดียวเท่านั้น (ไม่แสดงประวัติเก่า)
            const emailData = emails[0];
            const card = document.createElement('div');
            card.className = "group border border-gray-100 hover:border-black/10 rounded-2xl p-4 bg-zinc-50/50 hover:bg-white transition-all duration-300 cursor-pointer flex items-start gap-3.5 shadow-sm hover:shadow active:scale-[0.99] select-none text-left";
            card.onclick = () => openEmailDetail(0);
            
            // จัดแต่งชื่อผู้ส่งให้สะอาดเป็นทางการ
            let senderClean = emailData.from.split('<')[0].replace(/"/g, '').trim();
            if (!senderClean) senderClean = appName + " Security";
            
            // ดึงเฉพาะพาร์ทเวลาออกมา
            const timePart = emailData.time.split(' เวลา ')[1] || '';
            const datePart = emailData.time.split(' เวลา ')[0] || '';
            
            card.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-white shadow-sm border border-zinc-100/60 flex items-center justify-center text-zinc-400 group-hover:text-black group-hover:bg-zinc-50 transition-all duration-300 flex-shrink-0">
                    <i class="fa-solid fa-envelope text-sm"></i>
                </div>
                <div class="flex-1 min-w-0 space-y-1">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-zinc-800 truncate">${senderClean}</span>
                        <span class="text-[9px] font-semibold text-zinc-400 whitespace-nowrap">${timePart ? 'เวลา ' + timePart.replace(' (ตามเวลาประเทศไทย)', '') : ''}</span>
                    </div>
                    <p class="text-[11px] font-bold text-zinc-900 leading-snug truncate">${emailData.subject}</p>
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[9px] text-zinc-400">${datePart}</span>
                        <span class="inline-flex items-center gap-1 text-[9px] font-extrabold text-black group-hover:translate-x-1 transition-transform duration-300">
                            <span>เปิดดู OTP</span>
                            <i class="fa-solid fa-arrow-right-long text-[8px]"></i>
                        </span>
                    </div>
                </div>
            `;
            emailListContainer.appendChild(card);
        }

        // เริ่มต้นการดึงจดหมายสดทุก 5 วินาที (Auto Polling)
        function startOTPPolling(emailInput) {
            stopOTPPolling(); // ป้องกันมี Interval ซ้อนทับกัน
            isPolling = true;

            pollingInterval = setInterval(async () => {
                const resultBox = document.getElementById('resultBox');
                if (resultBox.classList.contains('hidden') || !isPolling) {
                    stopOTPPolling();
                    return;
                }

                try {
                    const response = await fetch('get_otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `email=${encodeURIComponent(emailInput)}&app_name=${encodeURIComponent(selectedApp)}`
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.emails && data.emails.length > 0) {
                            // เก็บในแคชโลคอล
                            currentEmails = data.emails;
                            // เรนเดอร์ใหม่เฉพาะตอนพบจดหมาย
                            renderEmails(data.emails, selectedApp);
                            
                            // ลบหน้าต่าง Error หากพบข้อมูลแล้ว
                            document.getElementById('errorBox').classList.add('hidden');
                            
                            // แสดงหน้าดีเทลและคัดลอกรหัสทันที
                            openEmailDetail(0);
                            copyOTP();
                            
                            // หยุดดึงข้อมูลสด
                            stopOTPPolling();
                        }
                    }
                } catch (e) {
                    console.log("Polling failure:", e);
                }
            }, 5000);
        }

        // สั่งหยุดการดึงข้อมูลสด
        function stopOTPPolling() {
            isPolling = false;
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }

        // ค้นหาหลัก
        async function fetchOTP() {
            const emailInput = document.getElementById('email').value.trim();
            const searchBtn = document.getElementById('searchBtn');
            const statusBox = document.getElementById('statusBox');
            const errorBox = document.getElementById('errorBox');
            const resultBox = document.getElementById('resultBox');

            errorBox.classList.add('hidden');
            stopOTPPolling(); // ล้างตัวเก่าวินาทีแรก
            
            if (!emailInput) {
                showError('กรุณากรอกอีเมลของคุณเพื่อดำเนินการค้นหา');
                return;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailInput)) {
                showError('รูปแบบอีเมลไม่ถูกต้อง กรุณากรอกอีเมลให้ตรงตามรูปแบบ เช่น name@domain.com');
                return;
            }

            // ตั้งค่าสถานะการโหลด
            searchBtn.disabled = true;
            searchBtn.classList.add('opacity-40', 'cursor-not-allowed');
            statusBox.classList.remove('hidden');
            resultBox.classList.add('hidden');
            resultBox.classList.remove('scale-100', 'opacity-100');
            resultBox.classList.add('scale-95', 'opacity-0');

            try {
                // ส่งคำขอแบบ POST Asynchronous ไปยังหลังบ้าน (get_otp.php)
                const response = await fetch('get_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(emailInput)}&app_name=${encodeURIComponent(selectedApp)}`
                });

                const data = await response.json();
                const appName = selectedApp || 'Disney+';

                const recipientEmailListEl = document.getElementById('recipientEmailList');
                if (recipientEmailListEl) recipientEmailListEl.innerText = emailInput;

                // รีเซ็ตการแสดงผลย่อย ให้กล่อง Inbox โชว์ และซ่อนกล่อง Iframe รายละเอียดก่อน
                document.getElementById('inboxListView').classList.remove('hidden');
                document.getElementById('emailDetailView').classList.add('hidden');

                // ปรับแต่งสีหัวบริการ tag ในหน้าผลลัพธ์
                const selectedAppTag = document.getElementById('selectedAppTag') || document.getElementById('resultAppTag');
                if (selectedAppTag) {
                    selectedAppTag.innerText = appName;
                    const lowerName = appName.toLowerCase();
                    if (lowerName.includes('netflix')) {
                        selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-800 uppercase tracking-wider shadow-inner";
                    } else if (lowerName.includes('disney')) {
                        selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 uppercase tracking-wider shadow-inner";
                    } else if (lowerName.includes('true')) {
                        selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-600 uppercase tracking-wider shadow-inner";
                    } else if (lowerName.includes('chatgpt')) {
                        selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 uppercase tracking-wider shadow-inner";
                    } else {
                        selectedAppTag.className = "text-[10px] font-bold px-2.5 py-1 rounded-full bg-violet-100 text-violet-800 uppercase tracking-wider shadow-inner";
                    }
                }

                renderBrandAssets(appName);

                if (data.success && data.emails && data.emails.length > 0) {
                    currentEmails = data.emails;
                    renderEmails(data.emails, appName);
                    // แสดงหน้าดีเทลและคัดลอกรหัสทันที
                    openEmailDetail(0);
                    copyOTP();
                } else {
                    // หากไม่พบจดหมายในวิแรก: ให้เปลี่ยนหน้ามาหน้ารอรับรหัส Dynamic แบบไหลลื่น!
                    currentEmails = [];
                    renderEmails([], appName);
                    // เริ่มกระบวนการสืบค้นสดอัปเดตอัตโนมัติ (Live Polling)
                    startOTPPolling(emailInput);
                }

                setTimeout(() => {
                    statusBox.classList.add('hidden');
                    resultBox.classList.remove('hidden');
                    setTimeout(() => {
                        resultBox.classList.remove('scale-95', 'opacity-0');
                        resultBox.classList.add('scale-100', 'opacity-100');
                    }, 50);
                }, 400);

            } catch (error) {
                showError(error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย');
                statusBox.classList.add('hidden');
            } finally {
                searchBtn.disabled = false;
                searchBtn.classList.remove('opacity-40', 'cursor-not-allowed');
            }
        }

        function showError(message) {
            const errorBox = document.getElementById('errorBox');
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.innerText = message;
            errorBox.classList.remove('hidden');
        }

        // แต่งสีเส้นตกแต่งขอบล่างตามแต่ละค่ายแบรนด์
        function renderBrandAssets(brandName) {
            const accentBar = document.getElementById('accentBar');
            if (!accentBar) return;
            
            const lowerName = brandName.toLowerCase();
            
            if (lowerName.includes('disney')) {
                accentBar.className = 'h-1.5 bg-blue-900';
            } else if (lowerName.includes('true')) {
                accentBar.className = 'h-1.5 bg-red-600';
            } else if (lowerName.includes('netflix')) {
                accentBar.className = 'h-1.5 bg-[#E50914]';
            } else if (lowerName.includes('chatgpt') || lowerName.includes('openai') || lowerName.includes('chat')) {
                accentBar.className = 'h-1.5 bg-[#10a37f]';
            } else if (lowerName.includes('prime') || lowerName.includes('amazon')) {
                accentBar.className = 'h-1.5 bg-[#6d28d9]';
            } else {
                accentBar.className = 'h-1.5 bg-black';
            }
        }
    </script>
</body>
</html>