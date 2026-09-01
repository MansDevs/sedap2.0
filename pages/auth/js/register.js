document.addEventListener('DOMContentLoaded', () => {
    // ── Multi-Step Form Logic ──
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const steps = [step1, step2, step3];

    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');
    const bars = [bar1, bar2, bar3];

    const subtitles = ['Personal Information', 'Account Information', 'Set Password'];
    const stepSubtitle = document.getElementById('stepSubtitle');

    function goToStep(index) {
        steps.forEach((s, i) => {
            if (!s) return;
            if (i === index) {
                s.style.display = 'flex';
                s.classList.remove('hidden');
            } else {
                s.style.display = 'none';
                s.classList.add('hidden');
            }
        });

        bars.forEach((b, i) => {
            if (!b) return;
            if (i <= index) {
                b.style.backgroundColor = '#0058bd';
                b.classList.remove('bg-surface-variant/80');
                b.classList.add('bg-primary');
            } else {
                b.style.backgroundColor = '#e0e3e5';
                b.classList.remove('bg-primary');
                b.classList.add('bg-surface-variant/80');
            }
        });

        if (stepSubtitle) {
            stepSubtitle.textContent = subtitles[index] || '';
        }
    }

    // Validation helpers
    function showFieldError(input, message) {
        if (!input) return;
        input.classList.add('border-error', 'ring-2', 'ring-error/20');
        input.classList.remove('border-outline/70');
        const container = input.closest('.flex.flex-col');
        if (container) {
            const msg = container.querySelector('.error-msg');
            if (msg) {
                if (message) msg.textContent = message;
                msg.classList.remove('invisible');
            }
        }
        const icon = input.closest('.relative')?.querySelector('.field-icon');
        if (icon) icon.classList.add('text-error');
        input.focus();
    }

    function clearFieldError(input) {
        if (!input) return;
        input.classList.remove('border-error', 'ring-2', 'ring-error/20');
        input.classList.add('border-outline/70');
        const container = input.closest('.flex.flex-col');
        if (container) {
            const msg = container.querySelector('.error-msg');
            if (msg) msg.classList.add('invisible');
        }
        const icon = input.closest('.relative')?.querySelector('.field-icon');
        if (icon) icon.classList.remove('text-error');
    }

    // Clear errors on typing
    ['name', 'email', 'username', 'password', 'confirm_password'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => clearFieldError(el));
        }
    });

    function validateStep1() {
        const name = document.getElementById('name');
        if (!name || !name.value.trim()) {
            showFieldError(name, 'Please enter full name');
            return false;
        }
        clearFieldError(name);
        return true;
    }

    function validateStep2() {
        const email = document.getElementById('email');
        const username = document.getElementById('username');
        let isValid = true;

        if (!username || !username.value.trim()) {
            showFieldError(username, 'Please choose username');
            isValid = false;
        } else {
            clearFieldError(username);
        }

        if (!email || !email.value.trim() || !email.value.includes('@')) {
            showFieldError(email, 'Please enter valid email');
            isValid = false;
        } else {
            clearFieldError(email);
        }

        return isValid;
    }

    function validateStep3() {
        const pass = document.getElementById('password');
        const confirmPass = document.getElementById('confirm_password');
        let isValid = true;

        if (!pass || pass.value.length < 6) {
            showFieldError(pass, 'Min. 6 characters');
            isValid = false;
        } else {
            clearFieldError(pass);
        }

        if (!confirmPass || confirmPass.value !== (pass ? pass.value : '')) {
            showFieldError(confirmPass, 'Passwords must match');
            isValid = false;
        } else {
            clearFieldError(confirmPass);
        }

        return isValid;
    }

    // Step navigation buttons
    const btnStep1Next = document.getElementById('btnStep1Next');
    if (btnStep1Next) {
        btnStep1Next.addEventListener('click', (e) => {
            e.preventDefault();
            if (validateStep1()) {
                goToStep(1);
            }
        });
    }

    const btnStep2Back = document.getElementById('btnStep2Back');
    if (btnStep2Back) {
        btnStep2Back.addEventListener('click', (e) => {
            e.preventDefault();
            goToStep(0);
        });
    }

    const btnStep2Next = document.getElementById('btnStep2Next');
    if (btnStep2Next) {
        btnStep2Next.addEventListener('click', (e) => {
            e.preventDefault();
            if (validateStep2()) {
                goToStep(2);
            }
        });
    }

    const btnStep3Back = document.getElementById('btnStep3Back');
    if (btnStep3Back) {
        btnStep3Back.addEventListener('click', (e) => {
            e.preventDefault();
            goToStep(1);
        });
    }

    // Form submit validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            if (!validateStep1()) {
                e.preventDefault();
                goToStep(0);
                return;
            }
            if (!validateStep2()) {
                e.preventDefault();
                goToStep(1);
                return;
            }
            if (!validateStep3()) {
                e.preventDefault();
                goToStep(2);
                return;
            }
        });
    }

    // Password visibility toggles
    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const input = btn.closest('.relative')?.querySelector('input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            const icon = btn.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = isPassword ? 'visibility' : 'visibility_off';
        });
    });

    // Start on step 1
    goToStep(0);
});
