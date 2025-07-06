function showStep(stepId) {
    document.querySelectorAll('.smslogin-step').forEach(step => {
        step.classList.remove('active');
    });
    document.getElementById(stepId).classList.add('active');
}

function showMessage(message, type = 'info') {
    const container = document.getElementById('message-container');
    container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function sendCode() {
    const mobile = document.getElementById('mobile').value;
    
    if (!mobile.match(/^09\d{9}$/)) {
        showMessage('شماره موبایل باید 11 رقم و با 09 شروع شود', 'error');
        return;
    }
    
    currentMobile = mobile;
    
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=send_code&mobile=${mobile}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            userExists = data.user_exists;
            if (userExists) {
                showStep('step-login-type');
            } else {
                showStep('step-verification');
            }
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('خطا در ارسال درخواست', 'error');
    });
}

function showPasswordLogin() {
    showStep('step-password-login');
}

function showSmsLogin() {
    sendCodeForExistingUser();
}

function sendCodeForExistingUser() {
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=send_code&mobile=${currentMobile}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStep('step-verification');
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function loginWithPassword() {
    const password = document.getElementById('password').value;
    
    if (!password) {
        showMessage('لطفاً رمز عبور را وارد کنید', 'error');
        return;
    }
    
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=login_password&mobile=${currentMobile}&password=${encodeURIComponent(password)}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = returnUrl;
            }, 1000);
        } else {
            attempts++;
            if (attempts >= maxAttempts) {
                showMessage('با مدیریت تماس بگیرید', 'error');
            } else {
                showMessage(data.message, 'error');
            }
        }
    });
}

function verifyCode() {
    const code = document.getElementById('verification-code').value;
    
    if (!code || code.length !== 4) {
        showMessage('لطفاً کد 4 رقمی را وارد کنید', 'error');
        return;
    }
    
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=verify_code&mobile=${currentMobile}&code=${code}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (userExists) {
                // ورود کاربر موجود
                loginUserAfterVerification();
            } else {
                // کاربر جدید
                showStep('step-new-user-options');
            }
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function loginUserAfterVerification() {
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=login_user&mobile=${currentMobile}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            setTimeout(() => {
                window.location.href = returnUrl;
            }, 1000);
        }
    });
}

function viewSite() {
    // ایجاد کاربر با رمز تصادفی
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=create_user&mobile=${currentMobile}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = returnUrl;
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function showSetPassword() {
    showStep('step-set-password');
}

function setPassword() {
    const password = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    if (!password || password.length < 4) {
        showMessage('رمز عبور باید حداقل 4 کاراکتر باشد', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showMessage('رمز عبور و تکرار آن باید یکسان باشند', 'error');
        return;
    }
    
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=set_password&mobile=${currentMobile}&password=${encodeURIComponent(password)}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = returnUrl;
            }, 1000);
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function forgotPassword() {
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=forgot_password&mobile=${currentMobile}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStep('step-forgot-password');
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function verifyForgotCode() {
    const code = document.getElementById('forgot-code').value;
    
    if (!code || code.length !== 4) {
        showMessage('لطفاً کد 4 رقمی را وارد کنید', 'error');
        return;
    }
    
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=verify_code&mobile=${currentMobile}&code=${code}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStep('step-reset-password');
            showMessage(data.message, 'success');
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function resetPassword() {
    const password = document.getElementById('reset-password').value;
    const confirmPassword = document.getElementById('confirm-reset-password').value;
    
    if (!password || password.length < 4) {
        showMessage('رمز عبور باید حداقل 4 کاراکتر باشد', 'error');
        return;
    }
    
    if (password !== confirmPassword) {
        showMessage('رمز عبور و تکرار آن باید یکسان باشند', 'error');
        return;
    }
    
    fetch('index.php?option=com_smslogin&task=ajax', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=reset_password&mobile=${currentMobile}&password=${encodeURIComponent(password)}&${token}=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            setTimeout(() => {
                window.location.href = returnUrl;
            }, 1000);
        } else {
            showMessage(data.message, 'error');
        }
    });
}

function resendCode() {
    sendCodeForExistingUser();
}

function resendForgotCode() {
    forgotPassword();
}

function goBack(stepId) {
    showStep(stepId);
}

function goBackToMobile() {
    if (userExists) {
        showStep('step-login-type');
    } else {
        showStep('step-mobile');
    }
}
