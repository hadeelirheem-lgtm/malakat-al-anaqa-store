<?php
session_start();
require_once 'DB.php';

$error = '';
$email = trim($_POST['email'] ?? '');

/*
 * الخطوة الأولى: إدخال البريد الإلكتروني -> إرسال رمز تحقق (6 أرقام)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_code'])) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "يرجى إدخال بريد إلكتروني صحيح.";
    } else {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', time() + (10 * 60)); // صالح لمدة 10 دقائق

            $upd = $pdo->prepare("UPDATE users SET reset_code = :code, reset_expiry = :expiry WHERE email = :email");
            $upd->execute(['code' => $code, 'expiry' => $expiry, 'email' => $email]);

            // إرسال الرمز عبر البريد الإلكتروني (PHPMailer + SMTP)
            require_once __DIR__ . '/mailer.php';
            $mailCfg = require __DIR__ . '/mail_config.php';
            $subject = "رمز استعادة كلمة المرور - ملكة الأناقة";
            $body = "مرحباً {$user['name']}،\r\n\r\n" .
                    "رمز التحقق الخاص بك لاستعادة كلمة المرور هو:\r\n\r\n" .
                    "      {$code}\r\n\r\n" .
                    "هذا الرمز صالح لمدة 10 دقائق.\r\n\r\n" .
                    "إذا لم تطلبي هذا الرمز، يرجى تجاهل هذه الرسالة.";

            $sent = send_mail($email, $subject, $body);
            $_SESSION['reset_email'] = $email;

            if ($sent['ok']) {
                unset($_SESSION['reset_fallback']);
                $step = 'verify';
            } elseif (!empty($mailCfg['debug_mode'])) {
                // وضع التطوير فقط: نعرض الرمز في الصفحة لأن SMTP غير مُهيأ بعد
                $_SESSION['reset_fallback'] = $code;
                $step = 'verify';
            } else {
                $error = "تعذر إرسال رمز التحقق إلى بريدك. يرجى المحاولة لاحقاً.";
            }
        } else {
            // رسالة عامة حتى لا نكشف وجود الحساب
            $error = "إذا كان هذا البريد مسجلاً لدينا، سيصلك رمز التحقق قريباً.";
        }
    }
}

/*
 * الخطوة الثانية: التحقق من الرمز ثم إدخال كلمة المرور الجديدة
 */
if (($_SESSION['reset_email'] ?? '') && isset($_POST['verify_code'])) {
    $email = $_SESSION['reset_email'];
    $code = trim($_POST['code'] ?? '');

    $stmt = $pdo->prepare("SELECT id, reset_code, reset_expiry FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    $stored = $user['reset_code'] ?? null;
    $fallback = $_SESSION['reset_fallback'] ?? null;

    if (!$user || $stored === null || ($code !== $stored && $code !== $fallback)) {
        $error = "الرمز غير صحيح، يرجى المحاولة مرة أخرى.";
        $step = 'verify';
    } elseif (strtotime($user['reset_expiry']) < time()) {
        $error = "انتهت صلاحية الرمز. يرجى طلب رمز جديد.";
        unset($_SESSION['reset_email'], $_SESSION['reset_fallback']);
        $step = 'email';
    } else {
        $step = 'new_password';
    }
}

/*
 * الخطوة الثالثة: حفظ كلمة المرور الجديدة مع التحقق من القيود
 */
$password_errors = [];
if (($_SESSION['reset_email'] ?? '') && isset($_POST['save_password'])) {
    $email = $_SESSION['reset_email'];
    $new_password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $password_errors[] = "يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.";
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        $password_errors[] = "يجب أن تحتوي كلمة المرور على حرف كبير واحد على الأقل (A-Z).";
    }
    if (!preg_match('/[a-z]/', $new_password)) {
        $password_errors[] = "يجب أن تحتوي كلمة المرور على حرف صغير واحد على الأقل (a-z).";
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        $password_errors[] = "يجب أن تحتوي كلمة المرور على رقم واحد على الأقل.";
    }
    if (!preg_match('/[@$!%*?&#]/', $new_password)) {
        $password_errors[] = "يجب أن تحتوي كلمة المرور على رمز خاص واحد على الأقل (@\$!%*?&#).";
    }
    if ($new_password !== $confirm) {
        $password_errors[] = "كلمتا المرور غير متطابقتين.";
    }

    if ($password_errors) {
        $step = 'new_password';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password = :password, reset_code = NULL, reset_expiry = NULL WHERE email = :email");
        $upd->execute(['password' => $hashed, 'email' => $email]);

        unset($_SESSION['reset_email'], $_SESSION['reset_fallback']);
        $success = true;
    }
}

$step = $step ?? (($_SESSION['reset_email'] ?? '') ? 'verify' : 'email');
if (isset($success)) $step = 'done';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور - ملكة الأناقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(-45deg, #fffaf0, #ffedd5, #fef3c7, #fff5eb);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-size: 400% 400%;
            animation: gradient 12s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .card-reset {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(234, 88, 12, 0.15);
        }
        .btn-orange {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            transition: all .25s;
        }
        .btn-orange:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(234,88,12,.35); }
        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper .toggle-eye {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 12px;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
        }
        .input-icon-wrapper .toggle-eye:hover { color: #f97316; }
        .fallback-box {
            background: #fff7ed;
            border: 1px dashed #fdba74;
            border-radius: 12px;
            padding: 12px;
            font-size: 1.2rem;
            letter-spacing: 4px;
            color: #ea580c;
            text-align: center;
        }
        /* رمز التحقق (6 خانات) */
        .code-inputs { display: flex; gap: 10px; justify-content: center; direction: ltr; }
        .code-input {
            width: 52px; height: 60px;
            text-align: center; font-size: 1.6rem; font-weight: bold;
            border: 2px solid #e5e7eb; border-radius: 12px;
            outline: none; color: #ea580c; background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .code-input:focus { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249,115,22,.2); }
        .password-rules { list-style: none; padding: 0; margin: 8px 0 0; font-size: .85rem; }
        .password-rules li { color: #6b7280; }
        .password-rules li.valid { color: #16a34a; }
        .password-rules li.invalid { color: #dc2626; }
        .error-msg { color: #dc2626; font-size: .8rem; margin-top: 4px; display: none; }
        .error-msg.show { display: block; }
        .shake { animation: shake .4s; }
        @keyframes shake {
            0%,100%{transform:translateX(0)}
            25%{transform:translateX(-6px)}
            75%{transform:translateX(6px)}
        }
    </style>
</head>
<body>

<div class="container col-12 col-sm-8 col-md-6 col-lg-4" id="cardWrap">
    <div class="card card-reset p-4 p-md-5 border-0" id="resetCard">
        <div class="text-center mb-4">
            <i class="fa-solid fa-key text-warning fa-3x mb-2"></i>

            <?php if ($step === 'done'): ?>
                <h3 class="fw-bold">تم بنجاح!</h3>
                <p class="text-muted small">تم تغيير كلمة المرور الخاصة بكِ</p>
            <?php elseif ($step === 'verify'): ?>
                <h3 class="fw-bold">رمز التحقق</h3>
                <p class="text-muted small">أدخلي الرمز المكوّن من 6 أرقام الذي وصل إلى بريدكِ</p>
            <?php else: ?>
                <h3 class="fw-bold">إعادة تعيين كلمة المرور</h3>
                <p class="text-muted small">أدخلي بريدكِ الإلكتروني لنرسل لكِ رمز التحقق</p>
            <?php endif; ?>
        </div>

        <div id="alertArea">
        <?php if ($error): ?>
            <div class="alert alert-danger rounded-3 text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success rounded-3 text-center">
                تم تغيير كلمة المرور بنجاح! <br>
                <a href="login.php" class="fw-bold text-success">تسجيل الدخول الآن</a>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['reset_fallback'])): ?>
            <div class="small text-muted mb-2 text-center bg-warning-subtle rounded-3 p-2">
                (البيئة المحلية بدون إرسال بريد فعلي — رمز الاختبار)
            </div>
            <div class="fallback-box mb-3" id="fallbackCode">
                <?php echo htmlspecialchars($_SESSION['reset_fallback']); ?>
            </div>
        <?php endif; ?>
        </div>

        <?php if ($step === 'done'): ?>
            <a href="login.php" class="btn btn-orange w-100 py-3">تسجيل الدخول الآن</a>

        <?php elseif ($step === 'email'): ?>
            <form method="POST" id="emailForm">
                <div class="mb-4">
                    <label class="form-label fw-bold">البريد الإلكتروني:</label>
                    <input type="email" name="email" id="email" class="form-control text-start py-2"
                           placeholder="name@example.com" value="<?php echo htmlspecialchars($email); ?>">
                    <div class="error-msg" id="emailError">يرجى إدخال بريد إلكتروني صحيح.</div>
                </div>
                <button type="submit" name="send_code" value="1" class="btn btn-orange w-100 py-3 mb-3">
                    <i class="fa-solid fa-paper-plane me-1"></i> إرسال رمز التحقق
                </button>
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none text-muted small">
                        <i class="fa-solid fa-arrow-right me-1"></i> العودة لتسجيل الدخول
                    </a>
                </div>
            </form>

        <?php elseif ($step === 'verify'): ?>
            <form method="POST" id="verifyForm">
                <div class="code-inputs mb-3" id="codeInputs">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" maxlength="1" inputmode="numeric" class="code-input" data-idx="<?php echo $i; ?>">
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="code" id="codeHidden">
                <div class="error-msg text-center" id="codeError" style="display:block;<?php echo $error ? '' : 'display:none;'; ?>">
                    الرمز غير مكتمل — أدخلي جميع الأرقام الستة.
                </div>
                <button type="submit" name="verify_code" value="1" class="btn btn-orange w-100 py-3 mb-3">
                    <i class="fa-solid fa-check me-1"></i> التحقق من الرمز
                </button>
                <div class="text-center">
                    <button type="button" id="resendBtn" class="btn btn-link text-muted small text-decoration-none">
                        لم يصلك الرمز؟ <span class="fw-bold text-warning">إعادة الإرسال</span>
                    </button>
                </div>
            </form>

        <?php elseif ($step === 'new_password'): ?>
            <form method="POST" id="passwordForm" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-bold">كلمة المرور الجديدة:</label>
                    <div class="input-icon-wrapper">
                        <input type="password" name="password" id="password" class="form-control py-2 ps-5"
                               placeholder="••••••••" autocomplete="new-password">
                        <button type="button" class="toggle-eye" id="togglePass" tabindex="-1">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <ul class="password-rules" id="passRules">
                        <li data-rule="length"><i class="fa-regular fa-circle me-1"></i>8 أحرف على الأقل</li>
                        <li data-rule="upper"><i class="fa-regular fa-circle me-1"></i>حرف كبير (A-Z)</li>
                        <li data-rule="lower"><i class="fa-regular fa-circle me-1"></i>حرف صغير (a-z)</li>
                        <li data-rule="number"><i class="fa-regular fa-circle me-1"></i>رقم واحد على الأقل</li>
                        <li data-rule="special"><i class="fa-regular fa-circle me-1"></i>رمز خاص (@$!%*?&#)</li>
                    </ul>
                    <div class="error-msg" id="passwordError"></div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">تأكيد كلمة المرور:</label>
                    <div class="input-icon-wrapper">
                        <input type="password" name="confirm_password" id="confirm" class="form-control py-2 ps-5"
                               placeholder="••••••••" autocomplete="new-password">
                        <button type="button" class="toggle-eye" id="toggleConfirm" tabindex="-1">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-msg" id="confirmError">كلمتا المرور غير متطابقتين.</div>
                </div>

                <?php if ($password_errors): ?>
                    <div class="alert alert-danger rounded-3 py-2 small">
                        <?php foreach ($password_errors as $pe): ?>
                            <div><i class="fa-solid fa-circle-xmark me-1"></i><?php echo htmlspecialchars($pe); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" name="save_password" value="1" class="btn btn-orange w-100 py-3 mb-3">
                    <i class="fa-solid fa-check me-1"></i> حفظ كلمة المرور الجديدة
                </button>
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none text-muted small">
                        <i class="fa-solid fa-arrow-right me-1"></i> العودة لتسجيل الدخول
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    // إظهار/إخفاء كلمة المرور
    function bindEye(btnId, inputId, iconSel) {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        if (!btn || !input) return;
        btn.addEventListener('click', function () {
            const isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            this.querySelector('i').className = isPass ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }
    bindEye('togglePass', 'password');
    bindEye('toggleConfirm', 'confirm');

    // قيود كلمة المرور - تحديث مباشر
    const passInput = document.getElementById('password');
    if (passInput) {
        const rules = {
            length: v => v.length >= 8,
            upper: v => /[A-Z]/.test(v),
            lower: v => /[a-z]/.test(v),
            number: v => /[0-9]/.test(v),
            special: v => /[@$!%*?&#]/.test(v)
        };
        passInput.addEventListener('input', function () {
            const v = this.value;
            Object.keys(rules).forEach(function (rule) {
                const li = document.querySelector('[data-rule="' + rule + '"]');
                const ok = rules[rule](v);
                li.classList.toggle('valid', ok);
                li.classList.toggle('invalid', !!v && !ok);
                li.querySelector('i').className = ok
                    ? 'fa-solid fa-circle-check me-1'
                    : (v ? 'fa-solid fa-circle-xmark me-1' : 'fa-regular fa-circle me-1');
            });
        });
    }

    // خانة رمز التحقق - تحويل تلقائي
    const codeInputs = document.querySelectorAll('.code-input');
    const codeHidden = document.getElementById('codeHidden');
    if (codeInputs.length) {
        codeInputs.forEach(function (inp, i) {
            inp.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value && i < codeInputs.length - 1) codeInputs[i + 1].focus();
                if (codeHidden) codeHidden.value = Array.from(codeInputs).map(x => x.value).join('');
            });
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && i > 0) codeInputs[i - 1].focus();
            });
            inp.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData.getData('text') || '').replace(/[^0-9]/g, '');
                for (let j = 0; j < pasted.length && j < codeInputs.length; j++) {
                    codeInputs[j].value = pasted[j];
                }
                if (codeHidden) codeHidden.value = Array.from(codeInputs).map(x => x.value).join('');
                if (pasted.length) codeInputs[Math.min(pasted.length, codeInputs.length) - 1].focus();
            });
        });

        // التحقق من اكتمال الرمز عند الإرسال
        document.getElementById('verifyForm').addEventListener('submit', function (e) {
            const full = Array.from(codeInputs).map(x => x.value).join('');
            codeHidden.value = full;
            const err = document.getElementById('codeError');
            if (full.length !== 6) {
                e.preventDefault();
                err.style.display = 'block';
                err.classList.add('show');
                codeInputs[0].focus();
            } else {
                err.style.display = 'none';
                err.classList.remove('show');
            }
        });
    }

    // التحقق من البريد الإلكتروني
    const emailForm = document.getElementById('emailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', function (e) {
            const email = document.getElementById('email');
            const err = document.getElementById('emailError');
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim() || !re.test(email.value.trim())) {
                e.preventDefault();
                email.classList.add('is-invalid');
                err.classList.add('show');
            }
        });
    }

    // إعادة إرسال الرمز
    const resendBtn = document.getElementById('resendBtn');
    if (resendBtn) {
        resendBtn.addEventListener('click', function () {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'email';
            input.value = document.querySelector('#codeHidden') ? '' : '';
            input.value = <?php echo json_encode($_SESSION['reset_email'] ?? ''); ?>;
            const btn = document.createElement('input');
            btn.type = 'hidden';
            btn.name = 'send_code';
            btn.value = '1';
            form.appendChild(input);
            form.appendChild(btn);
            document.body.appendChild(form);
            form.submit();
        });
    }
</script>

</body>
</html>
