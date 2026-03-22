
// -- Account Addition Functions --
async function handleRequestOtp(e) {
    e.preventDefault();
    const btn = document.getElementById('requestBtn');
    setLoading(btn, true, 'Sending...');

    const data = {
        email: document.getElementById('email').value,
        passcode: document.getElementById('passcode').value,
        interlinkId: document.getElementById('interlinkId').value
    };

    if (!/^\d{6}$/.test(data.passcode)) {
        showToast('Validation Error', 'Passcode must be exactly 6 digits', 'error');
        setLoading(btn, false);
        return;
    }

    if (!/^\d+$/.test(data.interlinkId)) {
        showToast('Validation Error', 'Interlink ID must be numbers only', 'error');
        setLoading(btn, false);
        return;
    }

    const res = await apiCall('auth_request.php', data);

    if (res.success) {
        showToast('Success', res.message || 'OTP Sent Successfully', 'success');

        // Hide Step 1
        const step1 = document.getElementById('step1');
        step1.classList.add('hidden');
        step1.style.display = 'none'; // Fallback

        // Show Step 2
        const step2 = document.getElementById('step2');
        step2.classList.remove('hidden');
        step2.style.display = 'block'; // Fallback

        document.getElementById('displayEmail').innerText = data.email;
    } else {
        showToast('Error', res.message, 'error');
        console.error('API Error:', res);
    }
    setLoading(btn, false);
}

async function handleVerifyOtp(e) {
    e.preventDefault();
    const btn = document.getElementById('verifyBtn');
    setLoading(btn, true, 'Verifying...');

    const data = {
        email: document.getElementById('email').value,
        passcode: document.getElementById('passcode').value,
        interlinkId: document.getElementById('interlinkId').value,
        otp: document.getElementById('otp').value
    };

    const res = await apiCall('auth_verify.php', data);

    if (res.success) {
        showToast('Success', 'Account Added Successfully!', 'success');
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 1500);
    } else {
        showToast('Error', res.message, 'error');
        setLoading(btn, false);
    }
}

function resetForm() {
    document.getElementById('step1').classList.remove('hidden');
    document.getElementById('step2').classList.add('hidden');
    const btn = document.getElementById('requestBtn');
    if (btn) {
        btn.innerText = "Request OTP";
        btn.disabled = false;
    }
}
