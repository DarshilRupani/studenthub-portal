// Practical 5: registration form validation. Loaded with defer on register.html only.
'use strict';

(function () {
    const form = document.getElementById('register-form');
    if (!form) return;

    // ---------- 1. Regular expressions ----------
    const PATTERNS = {
        name: /^[A-Za-z][A-Za-z .'-]{1,49}$/,          // 2-50 letters, spaces, . ' -
        email: /^[^\s@]+@[^\s@]+\.[A-Za-z]{2,}$/,      // text@domain.tld
        mobile: /^[6-9][0-9]{9}$/,                     // 10 digits starting with 6-9
        upper: /[A-Z]/,
        lower: /[a-z]/,
        digit: /[0-9]/,
        symbol: /[^A-Za-z0-9\s]/
    };

    const $ = (id) => document.getElementById(id);
    const status = $('form-status');
    let submitted = false;

    // ---------- 2. Password strength ----------
    const STRENGTH_LABELS = ['Very weak', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'];

    function passwordChecks(value) {
        return [
            { ok: value.length >= 8, missing: 'at least 8 characters' },
            { ok: PATTERNS.upper.test(value), missing: 'an uppercase letter' },
            { ok: PATTERNS.lower.test(value), missing: 'a lowercase letter' },
            { ok: PATTERNS.digit.test(value), missing: 'a number' },
            { ok: PATTERNS.symbol.test(value), missing: 'a symbol such as ! @ # $' }
        ];
    }

    function updateStrengthMeter(value) {
        const bar = $('strength-bar');
        const text = $('strength-text');
        if (!value) {
            bar.style.width = '0';
            bar.dataset.level = '0';
            text.textContent = '';
            return;
        }
        const score = passwordChecks(value).filter((check) => check.ok).length;
        bar.style.width = (score / 5) * 100 + '%';
        bar.dataset.level = String(score);
        text.textContent = 'Password strength: ' + STRENGTH_LABELS[score];
    }

    // ---------- 3. CAPTCHA drawn on a canvas (advanced extension) ----------
    const captchaCanvas = $('captcha-canvas');
    const CAPTCHA_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no look-alike characters
    let captchaCode = '';

    function randomInt(max) {
        const buffer = new Uint32Array(1);
        window.crypto.getRandomValues(buffer);
        return buffer[0] % max;
    }

    function drawCaptcha() {
        if (!captchaCanvas || !captchaCanvas.getContext) return;
        const ctx = captchaCanvas.getContext('2d');
        const width = captchaCanvas.width;
        const height = captchaCanvas.height;
        captchaCode = '';
        for (let i = 0; i < 5; i++) captchaCode += CAPTCHA_CHARS[randomInt(CAPTCHA_CHARS.length)];

        ctx.fillStyle = '#eef2ff';
        ctx.fillRect(0, 0, width, height);

        // noise lines and dots make the text harder for simple programs to read
        for (let i = 0; i < 6; i++) {
            ctx.strokeStyle = 'hsl(' + randomInt(360) + ', 45%, 60%)';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(randomInt(width), randomInt(height));
            ctx.lineTo(randomInt(width), randomInt(height));
            ctx.stroke();
        }
        for (let i = 0; i < 40; i++) {
            ctx.fillStyle = 'rgba(30, 41, 59, 0.35)';
            ctx.fillRect(randomInt(width), randomInt(height), 2, 2);
        }

        ctx.font = 'bold 30px Arial, sans-serif';
        ctx.textBaseline = 'middle';
        const slot = width / (captchaCode.length + 1);
        for (let i = 0; i < captchaCode.length; i++) {
            ctx.save();
            ctx.translate(slot * (i + 0.8), height / 2 + (randomInt(9) - 4));
            ctx.rotate((randomInt(41) - 20) * Math.PI / 180);
            ctx.fillStyle = 'hsl(' + randomInt(360) + ', 55%, 30%)';
            ctx.fillText(captchaCode[i], 0, 0);
            ctx.restore();
        }
    }

    // ---------- 4. One rule per field: returns an error message, or '' if valid ----------
    const rules = [
        {
            id: 'fullname', error: 'fullname-error',
            validate: (v) => {
                v = v.trim();
                if (!v) return 'Please enter your full name.';
                if (!PATTERNS.name.test(v)) return 'Use 2 to 50 letters. Spaces, dots, apostrophes and hyphens are allowed; numbers are not.';
                return '';
            }
        },
        {
            id: 'reg-email', error: 'email-error',
            validate: (v) => {
                v = v.trim();
                if (!v) return 'Please enter your email address.';
                if (!PATTERNS.email.test(v)) return 'Enter a valid email address, for example name@college.edu.';
                return '';
            }
        },
        {
            id: 'mobile', error: 'mobile-error',
            validate: (v) => {
                v = v.trim();
                if (!v) return 'Please enter your mobile number.';
                if (!/^[0-9]+$/.test(v)) return 'Use digits only, without spaces or symbols.';
                if (v.length !== 10) return 'The mobile number must have exactly 10 digits.';
                if (!PATTERNS.mobile.test(v)) return 'The mobile number must start with 6, 7, 8 or 9.';
                return '';
            }
        },
        {
            id: 'reg-password', error: 'password-error',
            validate: (v) => {
                if (!v) return 'Please create a password.';
                const missing = passwordChecks(v).filter((check) => !check.ok).map((check) => check.missing);
                return missing.length ? 'Your password still needs ' + missing.join(', ') + '.' : '';
            }
        },
        {
            id: 'confirm-password', error: 'confirm-error',
            validate: (v) => {
                if (!v) return 'Please re-enter your password.';
                if (v !== $('reg-password').value) return 'The passwords do not match.';
                return '';
            }
        },
        { id: 'course', error: 'course-error', validate: (v) => (v ? '' : 'Please select your course.') },
        { id: 'year', error: 'year-error', validate: (v) => (v ? '' : 'Please select your year.') },
        {
            id: 'gender-group', error: 'gender-error', group: true,
            validate: () => (form.querySelector('input[name="gender"]:checked') ? '' : 'Please select your gender.')
        },
        {
            id: 'captcha-input', error: 'captcha-error',
            validate: (v) => {
                v = v.trim();
                if (!v) return 'Please type the characters shown in the image.';
                if (v.toUpperCase() !== captchaCode) return 'The characters do not match. Try again or click New code.';
                return '';
            }
        },
        {
            id: 'terms', error: 'terms-error', checkbox: true,
            validate: () => ($('terms').checked ? '' : 'You must accept the terms and conditions to register.')
        }
    ];

    const touched = new Set();

    function fieldValue(rule) {
        const el = $(rule.id);
        return rule.group || rule.checkbox ? '' : el.value;
    }

    // Shows or clears the message and the invalid/valid styling for one field.
    function showResult(rule, message, display) {
        const el = $(rule.id);
        const errorBox = $(rule.error);
        errorBox.textContent = display ? message : '';
        if (rule.group) {
            el.classList.toggle('has-error', display && !!message);
            return;
        }
        if (display && message) {
            el.setAttribute('aria-invalid', 'true');
            el.classList.remove('is-valid');
        } else {
            el.removeAttribute('aria-invalid');
            el.classList.toggle('is-valid', !message && (rule.checkbox ? el.checked : el.value !== ''));
        }
    }

    // Validates one field. Errors appear once the user typed, left the field, or tried to submit.
    function validateField(rule, forceShow) {
        const message = rule.validate(fieldValue(rule));
        const hasContent = rule.group || rule.checkbox ? true : $(rule.id).value !== '';
        const display = forceShow || submitted || touched.has(rule.id) || (hasContent && !rule.group && !rule.checkbox);
        showResult(rule, message, display);
        return message;
    }

    function firstFocusable(rule) {
        return rule.group ? form.querySelector('input[name="gender"]') : $(rule.id);
    }

    function validateAll() {
        const invalid = [];
        rules.forEach((rule) => {
            if (validateField(rule, true)) invalid.push(rule);
        });
        return invalid;
    }

    // ---------- 5. Real-time validation (intermediate extension) ----------
    rules.forEach((rule) => {
        const targets = rule.group ? form.querySelectorAll('input[name="gender"]') : [$(rule.id)];
        targets.forEach((el) => {
            ['keyup', 'input', 'change'].forEach((type) => {
                el.addEventListener(type, () => {
                    if (rule.id === 'reg-password') {
                        updateStrengthMeter(el.value);
                        const confirm = rules.find((r) => r.id === 'confirm-password');
                        if ($('confirm-password').value) validateField(confirm, false);
                    }
                    if (rule.group || rule.checkbox) touched.add(rule.id);
                    validateField(rule, false);
                });
            });
            el.addEventListener('blur', () => {
                touched.add(rule.id);
                validateField(rule, false);
            });
        });
    });

    // ---------- 6. Submit handling ----------
    function setStatus(message, type) {
        status.hidden = false;
        status.textContent = message;
        status.className = 'form-status form-status-' + type;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        submitted = true;
        const invalid = validateAll();
        if (invalid.length) {
            const count = invalid.length;
            setStatus('Please fix ' + count + (count === 1 ? ' error' : ' errors') + ' below and try again.', 'error');
            if (invalid.some((rule) => rule.id === 'captcha-input') && $('captcha-input').value) {
                drawCaptcha();
                $('captcha-input').value = '';
            }
            firstFocusable(invalid[0]).focus();
            return;
        }
        setStatus('Registration successful. All details passed validation. (Demo only: nothing is stored or sent.)', 'success');
        const login = document.createElement('a');
        login.href = 'index.html';
        login.textContent = ' Go to Login →';
        status.appendChild(login);
        form.reset();
        submitted = false;
        touched.clear();
        rules.forEach((rule) => showResult(rule, '', false));
        form.querySelectorAll('.is-valid').forEach((el) => el.classList.remove('is-valid'));
        updateStrengthMeter('');
        drawCaptcha();
        status.focus();
    });

    if ($('captcha-refresh')) {
        $('captcha-refresh').addEventListener('click', () => {
            drawCaptcha();
            $('captcha-input').value = '';
            $('captcha-input').focus();
        });
    }

    drawCaptcha();
})();
