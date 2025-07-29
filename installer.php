<?php
// PHP Backend - باید در ابتدای فایل باشد
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'getSqlFiles') {
        // لیست فایل‌های SQL در دایرکتوری فعلی
        $files = array();
        $dir = dirname(__FILE__);
        
        // اسکن دایرکتوری برای فایل‌های SQL
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }
        
        // اگر فایلی پیدا نشد
        if (empty($files)) {
            $files[] = "هیچ فایل SQL یافت نشد";
        }
        
        echo json_encode($files);
        exit;
    }
    
    if ($_GET['action'] === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $host = $input['host'] ?? 'localhost';
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $database = $input['database'] ?? '';
        $sqlFile = $input['sqlFile'] ?? '';
        $debugMode = isset($input['debugMode']) && $input['debugMode'] == '1';
        
        try {
            // اتصال به MySQL بدون انتخاب دیتابیس
            $conn = new mysqli($host, $username, $password);
            
            if ($conn->connect_error) {
                throw new Exception("خطا در اتصال: " . $conn->connect_error);
            }
            
            // تنظیم charset
            $conn->set_charset("utf8mb4");
            
            // ایجاد دیتابیس اگر وجود ندارد
            $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!$conn->query($sql_create_db)) {
                throw new Exception("خطا در ایجاد دیتابیس: " . $conn->error);
            }
            
            // انتخاب دیتابیس
            if (!$conn->select_db($database)) {
                throw new Exception("خطا در انتخاب دیتابیس: " . $conn->error);
            }
            
            // بررسی وجود فایل SQL
            $sqlFilePath = dirname(__FILE__) . '/' . $sqlFile;
            if (!file_exists($sqlFilePath)) {
                throw new Exception("فایل SQL یافت نشد: $sqlFile");
            }
            
            // خواندن فایل SQL
            $sql = file_get_contents($sqlFilePath);
            
            if ($sql === false) {
                throw new Exception("خطا در خواندن فایل SQL");
            }
            
            // حذف BOM اگر وجود داشته باشد
            $sql = str_replace("\xEF\xBB\xBF", '', $sql);
            
            // روش بهتر برای تقسیم و اجرای کوئری‌ها
            $conn->query("SET NAMES 'utf8mb4'");
            $conn->query("SET CHARACTER SET utf8mb4");
            $conn->query("SET SESSION sql_mode = ''");
            
            // استفاده از mysqli_multi_query برای اجرای کل فایل
            $successCount = 0;
            $errorMessages = array();
            
            // روش 1: استفاده از multi_query (برای فایل‌های بزرگ و پیچیده)
            if ($conn->multi_query($sql)) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                    $successCount++;
                    
                    if ($conn->errno) {
                        $errorMessages[] = "خطا: " . $conn->error;
                        if ($debugMode) {
                            $errorMessages[] = "کد خطا: " . $conn->errno;
                        }
                    }
                } while ($conn->more_results() && $conn->next_result());
                
                if ($conn->errno) {
                    $errorMessages[] = "خطای نهایی: " . $conn->error;
                }
            } else {
                // اگر multi_query کار نکرد، از روش خط به خط استفاده می‌کنیم
                $templine = '';
                $lines = explode("\n", $sql);
                
                foreach ($lines as $line) {
                    // حذف کامنت‌ها و خطوط خالی
                    if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 1) == '#')
                        continue;
                    
                    // حذف فضاهای خالی
                    $line = trim($line);
                    
                    // اضافه کردن خط به کوئری موقت
                    $templine .= $line . "\n";
                    
                    // اگر به انتهای کوئری رسیدیم (;)
                    if (substr($line, -1, 1) == ';') {
                        // حذف ; از انتها برای اطمینان
                        $templine = rtrim($templine, ";\n");
                        
                        if (!empty($templine)) {
                            if ($conn->query($templine)) {
                                $successCount++;
                            } else {
                                // در صورت خطا
                                $shortQuery = mb_substr($templine, 0, 200) . (mb_strlen($templine) > 200 ? '...' : '');
                                $errorMessages[] = "خطا: " . $conn->error;
                                
                                if ($debugMode) {
                                    $errorMessages[] = "کوئری مشکل‌دار:\n" . $templine;
                                }
                            }
                        }
                        
                        // ریست کردن برای کوئری بعدی
                        $templine = '';
                    }
                }
            }
            
            $conn->close();
            
            // ایجاد فایل کانفیگ
            $config = "<?php\n";
            $config .= "// Database Configuration\n";
            $config .= "// Generated by Database Installer\n\n";
            $config .= "define('DB_HOST', '" . addslashes($host) . "');\n";
            $config .= "define('DB_USERNAME', '" . addslashes($username) . "');\n";
            $config .= "define('DB_PASSWORD', '" . addslashes($password) . "');\n";
            $config .= "define('DB_NAME', '" . addslashes($database) . "');\n";
            $config .= "define('DB_CHARSET', 'utf8mb4');\n";
            $config .= "?>";
            
            file_put_contents('config.php', $config);
            
            $message = "دیتابیس با موفقیت نصب شد! تعداد $successCount عملیات انجام شد.";
            if (!empty($errorMessages)) {
                $message .= "\n\nخطاها یا هشدارها:\n" . implode("\n\n", $errorMessages);
            }
            
            echo json_encode([
                'success' => empty($errorMessages) || $successCount > 0,
                'message' => $message
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب دیتابیس</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8f9fa;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideIn 0.5s ease-in-out;
            white-space: pre-line;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .step {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            opacity: 0.5;
            transition: all 0.3s;
        }

        .step.active {
            opacity: 1;
        }

        .step.completed {
            opacity: 1;
            color: #28a745;
        }

        .step-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #e0e0e0;
            margin-left: 10px;
            transition: all 0.3s;
        }

        .step.active .step-icon {
            background: #667eea;
        }

        .step.completed .step-icon {
            background: #28a745;
        }

        .file-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>نصب دیتابیس</h1>
        
        <div id="messageArea"></div>
        
        <form id="installForm" method="POST">
            <div class="form-group">
                <label for="host">آدرس دیتابیس (Host):</label>
                <input type="text" id="host" name="host" value="localhost" required>
            </div>
            
            <div class="form-group">
                <label for="username">نام کاربری:</label>
                <input type="text" id="username" name="username" placeholder="root" required>
            </div>
            
            <div class="form-group">
                <label for="password">رمز عبور:</label>
                <input type="password" id="password" name="password" placeholder="اختیاری">
            </div>
            
            <div class="form-group">
                <label for="database">نام دیتابیس:</label>
                <input type="text" id="database" name="database" placeholder="my_database" required>
            </div>
            
            <div class="form-group">
                <label for="sqlFile">فایل SQL:</label>
                <select id="sqlFile" name="sqlFile" required>
                    <option value="">در حال بارگذاری...</option>
                </select>
                <div class="file-info" id="fileInfo"></div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="debugMode" name="debugMode" value="1">
                    حالت دیباگ (نمایش جزئیات خطاها)
                </label>
            </div>
            
            <button type="submit" id="installBtn">نصب دیتابیس</button>
        </form>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px;">در حال نصب...</p>
            <div id="steps" style="margin-top: 20px; text-align: right;">
                <div class="step" id="step1">
                    <span class="step-icon"></span>
                    <span>اتصال به سرور دیتابیس</span>
                </div>
                <div class="step" id="step2">
                    <span class="step-icon"></span>
                    <span>ایجاد دیتابیس</span>
                </div>
                <div class="step" id="step3">
                    <span class="step-icon"></span>
                    <span>اجرای دستورات SQL</span>
                </div>
                <div class="step" id="step4">
                    <span class="step-icon"></span>
                    <span>تکمیل نصب</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // بررسی فایل‌های SQL موجود
        window.addEventListener('DOMContentLoaded', function() {
            fetch('?action=getSqlFiles')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(files => {
                    const select = document.getElementById('sqlFile');
                    select.innerHTML = '';
                    
                    if (files.length === 0 || files[0] === "هیچ فایل SQL یافت نشد") {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'هیچ فایل SQL یافت نشد';
                        select.appendChild(option);
                        document.getElementById('fileInfo').innerHTML = 
                            '<span style="color: #e74c3c;">⚠ لطفاً فایل SQL خود را در کنار فایل installer.php قرار دهید</span>';
                    } else {
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = 'انتخاب فایل SQL...';
                        select.appendChild(defaultOption);
                        
                        files.forEach(file => {
                            const option = document.createElement('option');
                            option.value = file;
                            option.textContent = file;
                            select.appendChild(option);
                        });
                        
                        document.getElementById('fileInfo').innerHTML = 
                            `<span style="color: #27ae60;">✓ ${files.length} فایل SQL یافت شد</span>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('sqlFile').innerHTML = 
                        '<option value="">خطا در بارگذاری فایل‌ها</option>';
                    showMessage('error', 'خطا در بارگذاری لیست فایل‌های SQL');
                });
        });

        // نمایش اطلاعات فایل انتخاب شده
        document.getElementById('sqlFile').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('fileInfo').innerHTML = 
                    `<span style="color: #3498db;">📄 فایل انتخاب شده: ${this.value}</span>`;
            }
        });

        // مدیریت فرم
        document.getElementById('installForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            // بررسی انتخاب فایل
            if (!data.sqlFile) {
                showMessage('error', 'لطفاً یک فایل SQL انتخاب کنید');
                return;
            }
            
            // نمایش loading
            document.getElementById('loading').style.display = 'block';
            document.getElementById('installBtn').disabled = true;
            document.getElementById('messageArea').innerHTML = '';
            
            // ریست steps
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            
            try {
                // مرحله 1: اتصال
                document.getElementById('step1').classList.add('active');
                await new Promise(resolve => setTimeout(resolve, 500));
                
                const response = await fetch('?action=install', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // نمایش مراحل
                    document.getElementById('step1').classList.add('completed');
                    document.getElementById('step2').classList.add('active');
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    document.getElementById('step2').classList.add('completed');
                    document.getElementById('step3').classList.add('active');
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    document.getElementById('step3').classList.add('completed');
                    document.getElementById('step4').classList.add('active');
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    document.getElementById('step4').classList.add('completed');
                    
                    showMessage('success', result.message);
                } else {
                    showMessage('error', result.message);
                }
            } catch (error) {
                showMessage('error', 'خطا در ارتباط با سرور: ' + error.message);
            } finally {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('installBtn').disabled = false;
            }
        });
        
        function showMessage(type, message) {
            const messageArea = document.getElementById('messageArea');
            messageArea.innerHTML = `<div class="message ${type}">${message}</div>`;
            messageArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
</body>
</html>