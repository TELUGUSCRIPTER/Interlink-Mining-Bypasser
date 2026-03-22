
// -- Miner Functions --

let suggestionFound = false;
let suggestedEmail = null;
let suggestedTime = null;

async function runMiner() {
    const btn = document.getElementById('minerBtn');
    if (!btn || btn.classList.contains('cursor-not-allowed')) return;

    // Get all account cards
    const cards = Array.from(document.querySelectorAll('#accountsList > div'));
    if (cards.length === 0) {
        showToast('Info', 'No accounts to mine', 'info');
        return;
    }

    setLoading(btn, true, 'Running...');
    showToast('Miner Started', `Processing ${cards.length} accounts sequentially...`, 'info');

    let processedCount = 0;
    let errorCount = 0;
    let updatedBalances = false; // Flag to reload balances

    // Sequential Loop
    for (let index = 0; index < cards.length; index++) {
        const card = cards[index];
        const email = card.getAttribute('data-email');
        if (!email) continue;

        // UI: Show Mining State on Card
        const msgDiv = document.getElementById(`msg-${index}`);
        if (msgDiv) {
            msgDiv.innerText = "Mining in progress...";
            msgDiv.className = "absolute top-0 left-0 w-full bg-blue-900/90 backdrop-blur text-center text-xs py-1 border-b border-blue-700 text-blue-200 z-20 animate-pulse";
            msgDiv.classList.remove('hidden');
        }

        try {
            // API Call for Single Account
            const res = await apiCall(`miner.php?email=${encodeURIComponent(email)}`);

            let log = null;
            if (res.success && res.data && res.data.length > 0) {
                log = res.data[0]; // Should refer to this email
            }

            // Update UI with Result
            if (msgDiv) {
                if (log) {
                    msgDiv.innerText = log.message;
                    if (log.status === 'Error') {
                        msgDiv.className = "absolute top-0 left-0 w-full bg-rose-900/95 backdrop-blur text-center text-xs py-1 border-b border-rose-700 text-rose-200 z-20";
                    } else if (log.status === 'Claimed' || log.status === 'Success') {
                        msgDiv.className = "absolute top-0 left-0 w-full bg-emerald-900/95 backdrop-blur text-center text-xs py-1 border-b border-emerald-700 text-emerald-200 z-20";
                    } else {
                        // Skipped or other
                        msgDiv.className = "absolute top-0 left-0 w-full bg-slate-800/95 backdrop-blur text-center text-xs py-1 border-b border-slate-600 text-slate-300 z-20";
                    }
                } else {
                    msgDiv.innerText = res.message || "Unknown error";
                    msgDiv.className = "absolute top-0 left-0 w-full bg-slate-800/95 backdrop-blur text-center text-xs py-1 border-b border-slate-600 text-slate-300 z-20";
                }
                // Auto-hide after delay
                setTimeout(() => { msgDiv.classList.add('hidden'); }, 10000);
            }

            // Suggestion Logic
            if (log && !suggestionFound && log.isAutoMinerActive === false && log.nextClaimTime && log.nextClaimTime > Date.now()) {
                suggestionFound = true;
                suggestedEmail = log.email;
                suggestedTime = log.nextClaimTime;
                setTimeout(() => { showSuggestModal(log.nextClaimTime); }, 500);
            }

            // Refresh Balance for this account immediately
            fetchBalance(email, index);
            processedCount++;

        } catch (e) {
            console.error(`Error mining ${email}:`, e);
            if (msgDiv) {
                msgDiv.innerText = "Connection Error";
                msgDiv.className = "absolute top-0 left-0 w-full bg-rose-900/95 backdrop-blur text-center text-xs py-1 border-b border-rose-700 text-rose-200 z-20";
            }
            errorCount++;
        }

        // Small delay between requests to look nice and prevent flooding if super fast
        await new Promise(r => setTimeout(r, 500));
    }

    setLoading(btn, false);
    showToast('Miner Completed', `Cycle finished. Processed: ${processedCount}, Errors: ${errorCount}`, 'success');
}

function showSuggestModal(timestamp) {
    const modal = document.getElementById('suggestModal');
    const timeStr = new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    document.getElementById('suggestTime').innerText = timeStr;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSuggestModal() {
    const modal = document.getElementById('suggestModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function confirmSuggestAutoMiner() {
    if (!suggestedEmail || !suggestedTime) return;

    const btn = document.getElementById('confirmSuggestBtn');
    setLoading(btn, true, 'Scheduling...');

    const res = await apiCall('toggle_mining.php', {
        email: suggestedEmail,
        isActive: true,
        startTime: suggestedTime
    });

    if (res.success) {
        showToast('Success', `Auto-Miner Scheduled for ${suggestedEmail}`, 'success');
        closeSuggestModal();
        loadAccounts();
    } else {
        showToast('Error', res.message, 'error');
    }
    setLoading(btn, false);
}

function closeCronModal() {
    const modal = document.getElementById('cronModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

async function confirmAutoMiner() {
    const btn = document.getElementById('confirmCronBtn');
    setLoading(btn, true, 'Scheduling...');

    const res = await apiCall('schedule_cron.php');

    if (res.success) {
        showToast('Success', 'Auto Miner Scheduled Successfully!', 'success');
        closeCronModal();
    } else {
        showToast('Error', res.message, 'error');
        closeCronModal();
    }
    setLoading(btn, false);
}
