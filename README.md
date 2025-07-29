# Database Installer - نصب‌کننده دیتابیس

<div dir="rtl">

یک ابزار ساده و قدرتمند برای نصب خودکار دیتابیس MySQL/MariaDB از طریق رابط کاربری وب.

</div>

## 🌟 Features | ویژگی‌ها

<div dir="rtl">

- ✅ رابط کاربری زیبا و responsive
- ✅ اسکن خودکار فایل‌های SQL
- ✅ ایجاد خودکار دیتابیس
- ✅ پشتیبانی از فایل‌های SQL بزرگ
- ✅ نمایش مراحل نصب با انیمیشن
- ✅ ایجاد فایل config.php
- ✅ حالت دیباگ برای عیب‌یابی
- ✅ پشتیبانی از UTF-8 و زبان فارسی
- ✅ مدیریت خطاها و نمایش پیام‌های مناسب

</div>

## 📋 Requirements | پیش‌نیازها

- PHP 5.6 or higher
- MySQL 5.5+ or MariaDB 10.0+
- Web server (Apache, Nginx, etc.)

## 🚀 Installation | نصب

<div dir="rtl">

### روش اول: دانلود مستقیم

1. فایل `installer.php` را دانلود کنید
2. فایل SQL دیتابیس خود را در کنار فایل installer.php قرار دهید
3. در مرورگر به آدرس فایل installer.php بروید

### روش دوم: استفاده از Git

</div>

```bash
# Clone the repository
git clone https://github.com/msd0s/database-installer.git

# Navigate to directory
cd database-installer

# Copy your SQL file
cp /path/to/your/database.sql .
```

## 📖 Usage | نحوه استفاده

<div dir="rtl">

1. **آماده‌سازی فایل‌ها:**
   - فایل `installer.php` را در سرور خود قرار دهید
   - فایل SQL دیتابیس خود را در همان دایرکتوری کپی کنید

2. **اجرای Installer:**
   - در مرورگر به آدرس `http://yourdomain.com/installer.php` بروید
   - اطلاعات دیتابیس را وارد کنید:
     - **Host**: معمولاً `localhost`
     - **Username**: نام کاربری MySQL
     - **Password**: رمز عبور MySQL
     - **Database Name**: نام دیتابیس مورد نظر
     - **SQL File**: فایل SQL خود را انتخاب کنید

3. **نصب:**
   - دکمه "نصب دیتابیس" را کلیک کنید
   - منتظر بمانید تا عملیات نصب تکمیل شود

4. **پس از نصب:**
   - فایل `config.php` ایجاد می‌شود (در صورتی که نیاز به این فایل ندارید آن را حذف کنید.)
   - برای امنیت بیشتر، فایل `installer.php` را حذف کنید

</div>

## 🔧 Configuration | پیکربندی

<div dir="rtl">

### فایل config.php

پس از نصب موفق، فایلی با محتوای زیر ایجاد می‌شود:

</div>

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'your_database');
define('DB_CHARSET', 'utf8mb4');
?>
```

<div dir="rtl">

می‌توانید از این فایل در پروژه خود استفاده کنید:

</div>

```php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
$conn->set_charset(DB_CHARSET);
```

## 🐛 Debugging | عیب‌یابی

<div dir="rtl">

### حالت دیباگ

برای فعال کردن حالت دیباگ و دیدن جزئیات خطاها:
1. تیک "حالت دیباگ" را بزنید
2. در این حالت کوئری‌های مشکل‌دار به طور کامل نمایش داده می‌شوند

### مشکلات رایج

#### فایل SQL شناسایی نمی‌شود
- مطمئن شوید فایل پسوند `.sql` دارد
- فایل باید در همان پوشه `installer.php` باشد
- دسترسی‌های پوشه را بررسی کنید

#### خطای Syntax در SQL
- فایل SQL را با یک ادیتور متن باز کنید
- مطمئن شوید encoding فایل UTF-8 است
- کاراکترهای خاص باید properly escape شده باشند

#### خطای اتصال به دیتابیس
- اطلاعات host را بررسی کنید
- مطمئن شوید MySQL/MariaDB در حال اجرا است
- دسترسی‌های کاربر را چک کنید

</div>

## 🔒 Security | امنیت

<div dir="rtl">

⚠️ **نکات امنیتی مهم:**

1. **حذف فایل installer**: پس از نصب موفق، حتماً فایل `installer.php` را حذف کنید
2. **تغییر دسترسی‌ها**: دسترسی فایل `config.php` را به 644 تغییر دهید
3. **استفاده در محیط امن**: این ابزار فقط برای محیط‌های توسعه یا نصب اولیه طراحی شده است

</div>

```bash
# Remove installer after use
rm installer.php

# Set proper permissions
chmod 644 config.php
```

## 📸 Screenshots | تصاویر

<div dir="rtl">

### صفحه اصلی
![Main Interface](screenshots/main-interface.png)

### در حال نصب
![Installation Progress](screenshots/installation-progress.png)

### نصب موفق
![Success Message](screenshots/success-message.png)

</div>

## 🤝 Contributing | مشارکت

<div dir="rtl">

مشارکت‌های شما با آغوش باز پذیرفته می‌شود! برای مشارکت:

1. Fork کنید
2. یک branch جدید بسازید (`git checkout -b feature/amazing-feature`)
3. تغییرات خود را commit کنید (`git commit -m 'Add amazing feature'`)
4. به branch خود push کنید (`git push origin feature/amazing-feature`)
5. یک Pull Request باز کنید

</div>

## 📝 License | مجوز

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author | نویسنده

<div dir="rtl">

ساخته شده با ❤️ توسط [سعید آذریان]

- GitHub: [@msd0s](https://github.com/msd0s)
- Email: msdosmsdoos1@gmail.com

</div>

## 🙏 Acknowledgments | تشکر

<div dir="rtl">

- تشکر از تمام کسانی که در توسعه این پروژه کمک کردند
- الهام گرفته از نیاز به یک ابزار ساده برای نصب دیتابیس

</div>

---

<div align="center" dir="rtl">

⭐ اگر این پروژه برایتان مفید بود، لطفاً ستاره دهید!

</div>
