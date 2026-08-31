/**
 * Multi-Step Registration Form Controller
 * SeDaP Healthcare Portal
 */
document.addEventListener('DOMContentLoaded', () => {
    // Steps & Containers
    let currentStep = 1;
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const stepSubtitle = document.getElementById('stepSubtitle');
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');

    // Inputs
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const registerForm = document.getElementById('registerForm');

    // Helper to toggle inline field error state (zero vertical shift)
    function setFieldError(inputEl, hasError, customMsg) {
        const fieldContainer = inputEl.closest('.flex-col');
        if (!fieldContainer) return;
        const errorMsgEl = fieldContainer.querySelector('.error-msg');
        const iconEl = fieldContainer.querySelector('.field-icon');

        if (hasError) {
            inputEl.classList.add('border-error', 'focus:border-error', 'focus:ring-error/20', 'bg-error-container/10');
            inputEl.classList.remove('border-outline/70', 'focus:border-primary', 'focus:ring-primary/20');
            if (iconEl) {
                iconEl.classList.add('text-error');
                iconEl.classList.remove('text-on-surface-variant');
            }
            if (errorMsgEl) {
                if (customMsg) errorMsgEl.textContent = customMsg;
                errorMsgEl.classList.remove('invisible');
            }
        } else {
            inputEl.classList.remove('border-error', 'focus:border-error', 'focus:ring-error/20', 'bg-error-container/10');
            inputEl.classList.add('border-outline/70', 'focus:border-primary', 'focus:ring-primary/20');
            if (iconEl) {
                iconEl.classList.remove('text-error');
                iconEl.classList.add('text-on-surface-variant');
            }
            if (errorMsgEl) {
                errorMsgEl.classList.add('invisible');
            }
        }
    }

    // Real-time clearing of error styles as user types
    [nameInput, emailInput, usernameInput, passwordInput, confirmPasswordInput].forEach(input => {
        if (input) {
            input.addEventListener('input', () => {
                setFieldError(input, false);
            });
        }
    });

    function updateStepUI(step) {
        currentStep = step;

        // Hide all steps
        step1.classList.add('hidden');
        step2.classList.add('hidden');
        step3.classList.add('hidden');

        // Reset bars
        bar1.className = 'h-1.5 rounded-full transition-all duration-300 ' + (step >= 1 ? 'bg-primary' : 'bg-surface-variant/80');
        bar2.className = 'h-1.5 rounded-full transition-all duration-300 ' + (step >= 2 ? 'bg-primary' : 'bg-surface-variant/80');
        bar3.className = 'h-1.5 rounded-full transition-all duration-300 ' + (step >= 3 ? 'bg-primary' : 'bg-surface-variant/80');

        if (step === 1) {
            step1.classList.remove('hidden');
            stepSubtitle.textContent = 'Personal Information';
            nameInput.focus();
        } else if (step === 2) {
            step2.classList.remove('hidden');
            stepSubtitle.textContent = 'Account Credentials';
            emailInput.focus();
        } else if (step === 3) {
            step3.classList.remove('hidden');
            stepSubtitle.textContent = 'Set Password';
            passwordInput.focus();
        }
    }

    // Step 1 -> Step 2
    const btnStep1Next = document.getElementById('btnStep1Next');
    if (btnStep1Next) {
        btnStep1Next.addEventListener('click', () => {
            const name = nameInput.value.trim();
            let valid = true;

            if (!name) {
                setFieldError(nameInput, true, 'Please enter full name');
                valid = false;
            }

            if (!valid) {
                nameInput.focus();
                return;
            }

            updateStepUI(2);
        });
    }

    // Step 2 -> Step 1
    const btnStep2Back = document.getElementById('btnStep2Back');
    if (btnStep2Back) {
        btnStep2Back.addEventListener('click', () => {
            updateStepUI(1);
        });
    }

    // Step 2 -> Step 3
    const btnStep2Next = document.getElementById('btnStep2Next');
    if (btnStep2Next) {
        btnStep2Next.addEventListener('click', () => {
            const email = emailInput.value.trim();
            const username = usernameInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let valid = true;

            if (!email) {
                setFieldError(emailInput, true, 'Please enter email');
                valid = false;
            } else if (!emailRegex.test(email)) {
                setFieldError(emailInput, true, 'Invalid email format');
                valid = false;
            }

            if (!username) {
                setFieldError(usernameInput, true, 'Please choose username');
                valid = false;
            }

            if (!valid) {
                if (!email || !emailRegex.test(email)) emailInput.focus();
                else usernameInput.focus();
                return;
            }

            updateStepUI(3);
        });
    }

    // Step 3 -> Step 2
    const btnStep3Back = document.getElementById('btnStep3Back');
    if (btnStep3Back) {
        btnStep3Back.addEventListener('click', () => {
            updateStepUI(2);
        });
    }

    // Form Submit Validation on Step 3
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            let valid = true;

            if (!password) {
                setFieldError(passwordInput, true, 'Please enter a password');
                valid = false;
            } else if (password.length < 6) {
                setFieldError(passwordInput, true, 'Min. 6 characters required');
                valid = false;
            }

            if (!confirmPassword) {
                setFieldError(confirmPasswordInput, true, 'Please confirm your password');
                valid = false;
            } else if (password && password !== confirmPassword) {
                setFieldError(confirmPasswordInput, true, 'Passwords do not match');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                if (!password || password.length < 6) passwordInput.focus();
                else confirmPasswordInput.focus();
            }
        });
    }

    // Password visibility toggles
    const toggleButtons = document.querySelectorAll('.toggle-password-btn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            const icon = btn.querySelector('.material-symbols-outlined');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility_off';
            }
        });
    });
});
