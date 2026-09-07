<?php
/*
 * إعدادات إرسال البريد عبر SMTP (Gmail افتراضيًا).
 *
 * الأولوية:
 * 1. متغيرات بيئة (SMTP_HOST / SMTP_PORT / SMTP_SECURE / SMTP_USER / SMTP_PASS)
 * 2. الملف المحلي mail_private.php (يحتوي بياناتك الفعلية ولا يُرفع للعامة)
 *
 * للتشغيل على حسابك:
 * 1. فعِّل "التحقق بخطوتين" في حساب Google.
 * 2. أنشئ "كلمة مرور تطبيق" من: إعدادات Google > الأمان > كلمات مرور التطبيقات.
 * 3. ضع بياناتك في mail_private.php (أو عبر متغيرات البيئة).
 */
$private = [];
if (file_exists(__DIR__ . '/mail_private.php')) {
    $private = require __DIR__ . '/mail_private.php';
}

return [
    // SMTP Host (Gmail)
    'host'       => getenv('SMTP_HOST') ?: ($private['host'] ?? 'smtp.gmail.com'),
    // SMTP Port (587 = TLS / 465 = SSL) — نستخدم 587 لأن 465 محجوب على بعض الشبكات
    'port'       => getenv('SMTP_PORT') ?: ($private['port'] ?? 587),
    // التشفير: 'tls' أو 'ssl'
    'secure'     => getenv('SMTP_SECURE') ?: ($private['secure'] ?? 'tls'),
    // عنوان بريد المرسل + كلمة مرور التطبيق (من البيئة أو الملف المحلي)
    'username'   => getenv('SMTP_USER') ?: ($private['username'] ?? ''),
    'password'   => getenv('SMTP_PASS') ?: ($private['password'] ?? ''),
    // الاسم الظاهر للمرسل
    'from_name'  => 'ملكة الأناقة',
    // (اختياري) عند تعطيل الـ fallback المحلي يُرسل للبريد فقط ولا يُعرض الرمز في الصفحة
    'debug_mode' => false,
];