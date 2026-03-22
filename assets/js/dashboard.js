
// -- Dashboard Functions --

let liveUpdateInterval = null;

async function loadAccounts() {
    const tbody = document.getElementById('accountsList');
    if (!tbody) return; // Not on dashboard

    // Skeleton Loader (Cards)
    tbody.innerHTML = Array(3).fill(0).map(() => `
        <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700/50 animate-pulse flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-slate-700"></div>
                <div class="space-y-2 flex-1">
                    <div class="h-4 bg-slate-700 rounded w-3/4"></div>
                    <div class="h-3 bg-slate-700 rounded w-1/2"></div>
                </div>
            </div>
            <div class="h-16 bg-slate-700/30 rounded-xl w-full"></div>
            <div class="flex justify-between items-center mt-auto pt-4 border-t border-slate-700/30">
                <div class="h-8 w-16 bg-slate-700 rounded"></div>
                <div class="h-8 w-24 bg-slate-700 rounded"></div>
            </div>
        </div>
    `).join('');

    const res = await apiCall('accounts.php', null, 'GET');

    if (res.success && res.data.length > 0) {
        tbody.innerHTML = '';
        document.getElementById('totalAccounts').innerText = res.data.length;
        document.getElementById('activeMiners').innerText = res.data.length; // Placeholder mostly

        let totalClaimsCount = 0;
        let activeCount = 0;

        res.data.forEach(async (acc, index) => {
            // Use dailyClaims if available (and check date if doing client-side logic, but we rely on API for total)
            // Ideally we sum it up here for initial view, but we are fetching stats separately or we can sum here.
            // Let's rely on api/stats.php for the global counter to be consistent.
            // But for now, let's sum what we have if the date matches.
            const today = new Date().toISOString().split('T')[0];
            if (acc.lastClaimDate === today) {
                totalClaimsCount += (parseInt(acc.dailyClaims) || 0);
            }

            if (acc.isActive) activeCount++;

            const card = document.createElement('div');
            card.className = "group bg-slate-800/40 hover:bg-slate-800/60 transition-all duration-300 rounded-2xl p-6 border border-slate-700/50 hover:border-primary/30 flex flex-col gap-5 relative overflow-hidden";
            // Store email for updates
            card.setAttribute('data-email', acc.email);

            // Dynamic Status Colors
            const isExpired = acc.health === 'EXPIRED';

            card.innerHTML = `
                <!-- Header -->
                <div class="flex items-center gap-4 z-10">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-sm font-bold shadow-lg shadow-indigo-500/20">
                        ${acc.email.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-white truncate" title="${acc.email}">${acc.email}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-slate-500 font-mono bg-slate-900/50 px-1.5 py-0.5 rounded">ID: ${acc.interlinkId}</span>
                            ${acc.next_claim ? `<span class="text-[10px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded font-bold tracking-wide">🕒 Next: ${acc.next_claim}</span>` : ''}
                            ${acc.proxyId ? '<span class="px-1.5 py-0.5 bg-indigo-500/10 text-indigo-400 rounded text-[10px] border border-indigo-500/20 font-bold tracking-wider">🛡️ PROXY</span>' : ''}
                        </div>
                    </div>
                    <!-- Menu/Actions -->
                   <button onclick="viewHistory('${acc.email}')" class="p-2 text-slate-400 hover:text-white bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors" title="View Logs">
                        📜
                    </button>
                    <button onclick="deleteAccount('${acc.email}')" class="p-2 text-rose-400 hover:text-white bg-rose-900/20 hover:bg-rose-600 rounded-lg transition-colors border border-rose-900/50" title="Delete Account">
                        🗑️
                    </button>
                </div>

                <!-- Balance Card -->
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 p-4 rounded-xl border border-slate-700/50 text-center relative overflow-hidden group-hover:border-primary/20 transition-colors">
                    <div class="absolute top-0 right-0 p-2 opacity-10">💰</div>
                    
                    <p class="text-[10px] font-bold text-amber-500/80 uppercase tracking-wider mb-0.5">$ITLG</p>
                    <h3 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-200 to-yellow-400" id="gold-${index}">
                        ${acc.cached_balance ? (acc.cached_balance.interlinkGoldTokenAmount || '0.00') : '0.00'}
                    </h3>

                    <div class="my-2 border-t border-slate-700/50 w-1/2 mx-auto"></div>

                    <p class="text-[10px] font-bold text-indigo-400/80 uppercase tracking-wider mb-0.5">Tokens</p>
                    <h4 class="text-lg font-bold text-white" id="token-${index}">
                        ${acc.cached_balance ? (acc.cached_balance.interlinkTokenAmount || '0.00') : '0.00'}
                    </h4>
                    
                    <div class="hidden">
                        <span id="silver-${index}"></span>
                        <span id="diamond-${index}"></span>
                    </div>
                     <div id="loading-${index}" class="absolute inset-0 bg-slate-900/80 flex items-center justify-center backdrop-blur-[1px] ${acc.cached_balance ? 'hidden' : ''}">
                        <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>

                <!-- Status & Toggle -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-700/30">
                     <div class="flex items-center gap-2">
                        ${isExpired
                    ? `<span class="flex items-center gap-1.5 text-xs font-bold text-rose-500"><span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Expired</span>`
                    : `<span class="flex items-center gap-1.5 text-xs font-bold text-emerald-500"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Connected</span>`
                }
                     </div>

                     <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-400 font-medium">Auto-Mine</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" onchange="toggleAccountStatus('${acc.email}', this.checked)" class="sr-only peer" ${acc.isActive ? 'checked' : ''} ${isExpired ? 'disabled' : ''}>
                             <div class="w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary ${isExpired ? 'opacity-50 cursor-not-allowed' : ''}"></div>
                        </label>
                     </div>
                </div>

                <!-- Notification Area -->
                <div id="msg-${index}" class="hidden absolute top-0 left-0 w-full bg-slate-800/95 backdrop-blur text-center text-xs py-1 border-b border-slate-700 transition-all"></div>
            `;
            tbody.appendChild(card);

            // Fetch only if not cached to speed up load
            if (!acc.cached_balance) {
                setTimeout(() => {
                    fetchBalance(acc.email, index);
                }, index * 150);
            }
        });

        document.getElementById('activeMiners').innerText = activeCount;
        // Trigger generic stats update to get Lifetime Claims & Total ITLG immediately
        updateLiveStats();
    } else {
        tbody.innerHTML = '<div class="col-span-full py-16 text-center text-slate-400 bg-slate-800/30 rounded-3xl border border-dashed border-slate-700"><p class="text-xl mb-2">No accounts connected</p><a href="add_account.php" class="text-primary hover:text-indigo-400 font-bold">Add your first account +</a></div>';
        document.getElementById('totalAccounts').innerText = 0;
    }
}

async function fetchBalance(email, index, retryCount = 2) {
    const loader = document.getElementById(`loading-${index}`);

    try {
        const res = await fetch(`api/balance.php?email=${email}`);
        const json = await res.json();

        if (json.success) {
            const data = json.data;
            // Update Visible Gold Balance (ITLG)
            const el = document.getElementById(`gold-${index}`);
            if (el) el.innerText = data.interlinkGoldTokenAmount || '0.00';

            // Update Token Balance
            const elToken = document.getElementById(`token-${index}`);
            if (elToken) elToken.innerText = data.interlinkTokenAmount || '0.00';

        } else {
            handleBalanceError(email, index, retryCount);
        }
    } catch (e) {
        handleBalanceError(email, index, retryCount);
    } finally {
        if (loader) loader.classList.add('hidden');
    }
}

function handleBalanceError(email, index, retryCount) {
    if (retryCount > 0) {
        const delay = (3 - retryCount) * 1000 + Math.random() * 500;
        setTimeout(() => {
            fetchBalance(email, index, retryCount - 1);
        }, delay);
    } else {
        const el = document.getElementById(`gold-${index}`);
        if (el) {
            el.innerHTML = '<span class="text-rose-500 text-lg cursor-pointer icon-refresh" onclick="fetchBalance(\'' + email + '\', ' + index + ', 3)" title="Click to Retry">Error ↻</span>';
        }
    }
}

async function toggleAccountStatus(email, isActive) {
    // 1. Toggle Status in Backend
    const res = await apiCall('toggle_mining.php', { email, isActive });

    if (res.success) {
        if (isActive) {
            // -- ENABLE FLOW --
            showToast('Miner Initialized', `Starting immediate run for ${email}...`, 'info');

            // UI Feedback: Show loading state on the card immediately
            // Find card index/element to show loading
            const cards = document.querySelectorAll('#accountsList > div');
            let foundCard = null;
            let foundIndex = -1;
            cards.forEach((card, idx) => {
                if (card.getAttribute('data-email') === email) {
                    foundCard = card;
                    foundIndex = idx;
                }
            });

            if (foundCard) {
                const msgDiv = document.getElementById(`msg-${foundIndex}`);
                if (msgDiv) {
                    msgDiv.innerText = "Mining in progress...";
                    msgDiv.className = "absolute top-0 left-0 w-full bg-blue-900/90 backdrop-blur text-center text-xs py-1 border-b border-blue-700 text-blue-200 z-20 animate-pulse";
                    msgDiv.classList.remove('hidden');
                }
            }

            // 2. Trigger Immediate Mine
            try {
                const mineRes = await apiCall(`miner.php?email=${encodeURIComponent(email)}`);

                // Process Result
                if (mineRes.success && mineRes.data && mineRes.data.length > 0) {
                    const log = mineRes.data[0];
                    showToast(log.status === 'Error' ? 'Error' : 'Success', log.message, log.status === 'Error' ? 'error' : 'success');
                } else {
                    showToast('Info', mineRes.message || 'Mining run completed', 'info');
                }

                // Refresh UI to show new status/schedule/balance
                setTimeout(loadAccounts, 1000);

            } catch (e) {
                showToast('Error', 'Mining run failed: ' + e.message, 'error');
                setTimeout(loadAccounts, 1000);
            }

        } else {
            // -- DISABLE FLOW --
            showToast('Success', `Auto-Miner Disabled for ${email}`, 'success');
            // Refresh logic handled by toggle_mining response usually? 
            // Better to reload to reflect unchecked state correctly if needed.
            setTimeout(loadAccounts, 500);
        }

    } else {
        showToast('Error', 'Failed to update status: ' + res.message, 'error');
        setTimeout(loadAccounts, 1000); // Revert switch UI
    }
}

async function deleteAccount(email) {
    if (!confirm(`Are you sure you want to delete ${email}? This action cannot be undone and will remove all history and schedules.`)) return;

    showToast('Info', 'Deleting account...', 'info');

    try {
        const res = await apiCall(`delete_account.php?email=${encodeURIComponent(email)}`);

        if (res.success) {
            showToast('Success', 'Account deleted successfully', 'success');
            // Optimistic removal or reload
            loadAccounts();
        } else {
            showToast('Error', res.message || 'Deletion failed', 'error');
        }
    } catch (e) {
        showToast('Error', 'Deletion failed: ' + e.message, 'error');
    }
}

function startLiveUpdates() {
    if (liveUpdateInterval) clearInterval(liveUpdateInterval);

    // Auto-Refresh every 30 seconds
    liveUpdateInterval = setInterval(() => {
        const cards = document.querySelectorAll('#accountsList > div');
        cards.forEach((card, index) => {
            const email = card.getAttribute('data-email');
            if (email) {
                // Silent refresh (1 retry)
                fetchBalance(email, index, 1);
            }
        });
    }, 30000);

    // Live Stats Update (Total Claims Today & Active Miners)
    setInterval(updateLiveStats, 5000);
}

async function updateLiveStats() {
    try {
        const res = await apiCall('stats.php', null, 'GET');
        if (res.success) {
            // Update Total Claims (Today Only)
            if (document.getElementById('totalClaims')) {
                document.getElementById('totalClaims').innerText = res.data.totalDailyClaims || 0;
            }
            // Update Active Miners
            if (document.getElementById('activeMiners')) {
                document.getElementById('activeMiners').innerText = res.data.activeMiners || 0;
            }
            // Update Total ITLG (Earnings)
            if (document.getElementById('totalITLG')) {
                document.getElementById('totalITLG').innerText = res.data.totalITLG || '0.00';
            }
        }
    } catch (e) {
        console.error("Stats Fetch Error", e);
    }
}
