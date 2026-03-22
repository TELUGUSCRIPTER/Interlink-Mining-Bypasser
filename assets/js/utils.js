
// -- Toast Notification --
function showToast(title, message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');

    if (!toast) return;

    toastTitle.innerText = title;
    toastMessage.innerText = message;

    // Reset styles
    toast.className = "fixed bottom-5 right-5 px-6 py-4 rounded-xl glass border-l-4 translate-y-20 opacity-0 transition-all duration-300 shadow-2xl z-[60]";

    if (type === 'success') toast.classList.add('border-emerald-500');
    else if (type === 'error') toast.classList.add('border-rose-500');
    else toast.classList.add('border-primary');

    // Show
    setTimeout(() => {
        toast.classList.remove('translate-y-20', 'opacity-0');
    }, 100);

    // Hide
    setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
    }, 4000);
}

// -- Loader Helper --
function setLoading(btn, isLoading, text = 'Processing...') {
    if (!btn) return;
    if (isLoading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        btn.innerHTML = `<span class="animate-spin inline-block mr-2">⌛</span> ${text}`;
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    }
}

// -- Helpers --
async function apiCall(endpoint, data = null, method = 'POST') {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' }
    };
    if (data && method === 'POST') options.body = JSON.stringify(data);

    try {
        const baseUrl = window.API_BASE || 'api/';
        const res = await fetch(`${baseUrl}${endpoint}`, options);

        // Read as text first to avoid crash on empty/non-JSON responses
        const text = await res.text();

        if (!text || text.trim() === '') {
            return { success: false, message: `Empty response from server (HTTP ${res.status})` };
        }

        try {
            return JSON.parse(text);
        } catch (parseErr) {
            // Response exists but isn't valid JSON (PHP error/warning output)
            const snippet = text.substring(0, 200);
            console.error('Invalid JSON response:', snippet);
            return { success: false, message: `Server returned invalid response (HTTP ${res.status})` };
        }
    } catch (e) {
        return { success: false, message: e.message };
    }
}
