/**
 * Real-time Form Validation Utility
 * Project: Sri Lakshmi Residency & Mahal
 */

document.addEventListener('DOMContentLoaded', () => {
    const validationRules = {
        name: {
            regex: /^.{2,100}$/,
            error: 'Name must be between 2 and 100 characters.'
        },
        brand: {
            regex: /^[a-zA-Z0-9\s&.\-,']{3,60}$/,
            error: '3-60 chars (Letters, Numbers, & . - , \' allowed)'
        },
        email: {
            regex: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            error: 'Please enter a valid email address.'
        },
        phone: {
            regex: /^[6-9]\d{9}$/,
            error: '10-digit number starting with 6-9.'
        },
        password: {
            regex: /^.{6,20}$/,
            error: 'Password must be 6-20 characters.'
        },
        number: {
            regex: /^[0-9]+(\.[0-9]{1,2})?$/,
            error: 'Must be a positive number.'
        }
    };

    const validateField = (input) => {
        const value = input.value.trim();
        const type = input.dataset.validate || input.type;
        const group = input.closest('.form-group');
        let isValid = true;
        let errorMessage = '';

        // Required check
        if (input.hasAttribute('required') && value === '') {
            isValid = false;
            errorMessage = 'This field is required.';
        } else if (value !== '') {
            // Rule check
            const rule = validationRules[type] || (input.tagName === 'SELECT' ? null : null);
            if (rule && !rule.regex.test(value)) {
                isValid = false;
                errorMessage = rule.error;
            }

            // Custom Password Match check
            if (input.id === 'cpwd' || input.name === 'confirm_password') {
                const pwd = document.getElementById('pwd') || document.querySelector('input[name="password"]');
                if (pwd && value !== pwd.value) {
                    isValid = false;
                    errorMessage = 'Passwords do not match.';
                }
            }
        }

        updateUI(input, group, isValid, errorMessage);
        return isValid;
    };

    const updateUI = (input, group, isValid, message) => {
        if (!group) return;

        // Remove existing error message
        let errorEl = group.querySelector('.validation-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'validation-error';
            group.appendChild(errorEl);
        }

        if (isValid) {
            input.classList.remove('invalid-field');
            input.classList.add('valid-field');
            errorEl.style.color = 'var(--success)';
            if (input.value !== '') {
                errorEl.innerHTML = '<i class="fas fa-check-circle"></i> Looks good!';
            } else {
                errorEl.textContent = '';
            }
        } else {
            input.classList.remove('valid-field');
            input.classList.add('invalid-field');
            errorEl.textContent = message;
            errorEl.style.color = 'var(--danger)';
        }
    };


    // Attach listeners to all inputs in forms
    document.querySelectorAll('form input, form textarea, form select').forEach(input => {
        // Validation on input (while typing)
        input.addEventListener('input', () => {
            if (input.dataset.validatedOnce) {
                validateField(input);
            }
        });

        // Validation on blur (first time)
        input.addEventListener('blur', () => {
            input.dataset.validatedOnce = 'true';
            validateField(input);
        });
    });

    // Form submission check
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', (e) => {
            let formValid = true;
            form.querySelectorAll('input, textarea, select').forEach(input => {
                if (!validateField(input)) {
                    formValid = false;
                }
            });

            if (!formValid) {
                e.preventDefault();
                // Optional: Scroll to first error
                const firstError = form.querySelector('.validation-error:not(:empty)');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
});
