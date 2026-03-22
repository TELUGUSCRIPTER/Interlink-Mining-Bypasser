
// -- Admin Dashboard Logic --

// Store global data
window.adminData = {};
let isRunningGlobal = false;

document.addEventListener('DOMContentLoaded', () => {
    loadAdminDashboard();
    // Auto-refresh every 30 seconds
    setInterval(() => {
        if (!isRunningGlobal) { // Don't refresh if manual run is in progress (it handles its own updates)
            loadAdminDashboard();
        }
    }, 30000);
});

async function loadAdminDashboard() {
    const res = await apiCall('admin_dashboard.php', null, 'GET');

    if (res.success && res.data) {
        const d = res.data;
        window.adminData = d;

        // 1. Stats
        document.getElementById('totalAccounts').innerText = d.total_accounts;
        document.getElementById('activeMiners').innerText = d.active_miners;
        document.getElementById('totalClaims').innerText = d.total_claims_today;

        // 2. Graph
        renderAdminGraph(d.graph_24h);

        // 3. Interlink Accounts Table
        renderInterlinkTable(d.interlink_accounts);

        // 4. Users Cards
        renderUsersList(d.users);
    } else {
        showToast('Error', res.message || 'Failed to load admin data', 'error');
        console.error(res); // Log for inspection
    }
}

async function runGlobalMiners() {
    if (isRunningGlobal) return;
    if (!window.adminData || !window.adminData.interlink_accounts || window.adminData.interlink_accounts.length === 0) {
        showToast('Info', 'No accounts to run', 'info');
        return;
    }

    const accounts = window.adminData.interlink_accounts;
    const modal = document.getElementById('runModal');
    const logContainer = document.getElementById('runLog');
    const statusText = document.getElementById('runStatus');
    const modalTitle = modal.querySelector('h3');

    // Reset Modal
    modal.classList.remove('hidden');
    if (modalTitle) modalTitle.innerHTML = '<span>⚡</span> Global Miner Execution';

    logContainer.innerHTML = '';
    statusText.innerText = `Starting sequence for ${accounts.length} accounts...`;
    isRunningGlobal = true;

    // Process One by One
    for (let i = 0; i < accounts.length; i++) {
        const acc = accounts[i];
        const count = i + 1;
        const total = accounts.length;

        statusText.innerText = `Running (${count}/${total}): ${acc.email} (User: ${acc.owner})`;

        appendMinerLog(logContainer, `[${count}/${total}] processing ${acc.email}...`, 'info');

        try {
            const result = await apiCall('admin_run_miner.php', {
                owner: acc.owner,
                email: acc.email
            });

            if (result.success) {
                appendMinerLog(logContainer, `✅ SUCCESS: ${result.message}`, 'success');
            } else {
                appendMinerLog(logContainer, `❌ FAILED: ${result.message}`, 'error');
            }
        } catch (e) {
            appendMinerLog(logContainer, `⚠️ ERROR: ${e.message}`, 'error');
        }

        // Small delay
        await new Promise(r => setTimeout(r, 500));
    }

    statusText.innerText = "Completed. All accounts processed.";
    appendMinerLog(logContainer, "--- CHAIN COMPLETE ---", 'info');
    isRunningGlobal = false;

    // Refresh Stats
    loadAdminDashboard();
}

function appendMinerLog(container, text, type) {
    const div = document.createElement('div');
    div.className = "mb-1 font-mono text-xs";
    if (type === 'success') div.className += " text-emerald-400";
    else if (type === 'error') div.className += " text-rose-400";
    else div.className += " text-slate-400";

    div.innerText = text;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

let adminChart = null;
function renderAdminGraph(data) {
    const ctx = document.getElementById('adminChart');
    if (!ctx) return;

    const labels = data.map(i => i.label); // 10:00
    const counts = data.map(i => i.count);

    if (adminChart) adminChart.destroy();

    adminChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Global Claims',
                data: counts,
                borderColor: '#f43f5e', // Rose
                backgroundColor: 'rgba(244, 63, 94, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#334155' } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderInterlinkTable(accounts) {
    const tbody = document.getElementById('interlinkList');
    if (!tbody) return;

    tbody.innerHTML = accounts.map(acc => `
        <tr class="hover:bg-slate-800/50 transition-colors border-b border-slate-800/50 last:border-0">
            <td class="py-3 px-4 flex items-center gap-3">
                 <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300">
                    ${acc.email.substring(0, 2).toUpperCase()}
                 </div>
                 <div>
                    <div class="text-white font-medium text-sm sm:max-w-[200px] truncate" title="${acc.email}">${acc.email}</div>
                    <div class="text-[10px] text-slate-500 font-mono">ID: ${acc.interlinkId}</div>
                 </div>
            </td>
            <td class="py-3 px-4 text-slate-400 text-sm font-medium">${acc.owner}</td>
            <td class="py-3 px-4">
                ${acc.isActive
            ? '<span class="text-emerald-400 text-xs font-bold px-2 py-1 rounded bg-emerald-400/10 border border-emerald-400/20">Active</span>'
            : '<span class="text-slate-500 text-xs font-bold px-2 py-1 rounded bg-slate-700/50">Inactive</span>'}
            </td>
            <td class="py-3 px-4">
                <button onclick="toggleAutoMining('${acc.owner}', '${acc.email}', ${!acc.isActive}, this)"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all ${acc.isActive
            ? 'text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20'
            : 'text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20'}">
                    ${acc.isActive ? '⏸ Pause' : '▶ Start'}
                </button>
            </td>
            <td class="py-3 px-4 text-right">
                <button onclick="deleteAccount('${acc.owner}', '${acc.email}')" class="text-rose-400 hover:text-rose-300 bg-rose-500/10 hover:bg-rose-500/20 px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
                    Delete
                </button>
            </td>
        </tr>
    `).join('');
}

function renderUsersList(users) {
    const container = document.getElementById('usersGrid');
    if (!container) return;

    container.innerHTML = users.map(user => `
        <div class="bg-slate-800/40 p-5 rounded-2xl border border-slate-700/50 flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-lg font-bold shadow-lg shadow-purple-500/20">
                    ${user.username.substring(0, 1).toUpperCase()}
                </div>
                <div>
                    <h4 class="font-bold text-white text-lg">${user.username}</h4>
                    <div class="flex items-center gap-3 mt-1 text-sm text-slate-400">
                        <span class="flex items-center gap-1">📁 ${user.accounts_count} Accounts</span>
                        <span class="text-slate-600">•</span>
                        <span>Since ${user.joined}</span>
                    </div>
                </div>
            </div>
            <button onclick="deleteUser('${user.username}')" class="text-slate-400 hover:text-rose-400 transition-colors p-2" title="Delete User">
                🗑️
            </button>
        </div>
    `).join('');
}

async function toggleAutoMining(owner, email, enable, btn) {
    btn.disabled = true;
    btn.innerText = '...';

    try {
        const res = await apiCall('admin_actions.php', { action: 'toggle_mining', owner, email, enable });
        if (res.success) {
            showToast(enable ? 'Enabled' : 'Disabled', res.message, 'success');
            loadAdminDashboard();
        } else {
            showToast('Error', res.message, 'error');
            btn.disabled = false;
            btn.innerText = enable ? '▶ Start' : '⏸ Pause';
        }
    } catch (e) {
        showToast('Error', 'Failed to toggle mining', 'error');
        btn.disabled = false;
        btn.innerText = enable ? '▶ Start' : '⏸ Pause';
    }
}

async function deleteAccount(owner, email) {
    if (!confirm(`Permanently delete account ${email} from user ${owner}? This will remove all crons, tokens, and history for this account only.`)) return;

    const res = await apiCall('admin_actions.php', { action: 'delete_account', owner, email });
    if (res.success) {
        showToast('Deleted', res.message, 'success');
        loadAdminDashboard();
    } else {
        showToast('Error', res.message, 'error');
    }
}

async function deleteUser(username) {
    if (!confirm(`WARNING: Permanently delete USER ${username} and ALL their data? This cannot be undone.`)) return;

    const res = await apiCall('admin_actions.php', { action: 'delete_user', username });
    if (res.success) {
        showToast('Deleted', res.message, 'success');
        loadAdminDashboard(); // Refresh
    } else {
        showToast('Error', res.message, 'error');
    }
}

async function purgeSchedulers() {
    if (!confirm('DANGER: This will delete ALL \'Miner - *\' events from Cronicle and reset local scheduler IDs.\n\nUse this to clean up duplicates. Mining will stop until you run \'Run All Miners\' again.\n\nAre you sure?')) return;

    showToast('Processing', 'Purging schedulers...', 'info');
    try {
        const res = await apiCall('../api/admin_cleanup_events.php', null, 'GET');
        if (res.success) {
            showToast('Success', res.message, 'success');
            // Wait and reload to show fresh state
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error', res.message, 'error');
        }
    } catch (e) {
        showToast('Error', 'Failed to purge schedulers', 'error');
        console.error(e);
    }
}

async function clearAllEvents() {
    if (!confirm('DANGER: This will delete ALL \'Miner - *\' events from Cronicle and reset local scheduler IDs.\n\nUse this to clean up EVERYTHING. Mining will stop until you run \'Run All Miners\' again.\n\nAre you sure?')) return;

    showToast('Processing', 'Clearing ALL events...', 'info');
    try {
        const res = await apiCall('../api/admin_cleanup_events.php?mode=all', null, 'GET');
        if (res.success) {
            showToast('Success', res.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error', res.message, 'error');
        }
    } catch (e) {
        showToast('Error', 'Failed to clear events', 'error');
        console.error(e);
    }
}

async function clearCompletedEvents() {
    showToast('Processing', 'Clearing orphaned/completed events...', 'info');
    try {
        const res = await apiCall('../api/admin_cleanup_events.php?mode=completed', null, 'GET');
        if (res.success) {
            showToast('Success', res.message, 'success');
            // No reload needed usually, but good to refresh stats
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error', res.message, 'error');
        }
    } catch (e) {
        showToast('Error', 'Failed to clear completed events', 'error');
        console.error(e);
    }
}

async function clearGroupEvents() {
    if (!confirm('DANGER: This will delete ALL \'Group Miner - *\' events from Cronicle and reset local group scheduler IDs.\n\nAll Group Miners will STOP. You or the users will need to toggle them on again manually.\n\nAre you sure?')) return;

    showToast('Processing', 'Clearing ALL Group Mining events...', 'info');
    try {
        const res = await apiCall('../api/admin_cleanup_events.php?mode=group', null, 'GET');
        if (res.success) {
            showToast('Success', res.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error', res.message, 'error');
        }
    } catch (e) {
        showToast('Error', 'Failed to clear group events', 'error');
        console.error(e);
    }
}

async function rotateFingerprints() {
    if (!confirm('Update User-Agent and Fingerprints for ALL accounts? \n\nThis makes devices appear to have updated their software. Recommended once a week.')) return;

    showToast('Processing', 'Rotating device fingerprints...', 'info');
    try {
        const res = await apiCall('../api/admin_rotate_fingerprints.php?action=rotate_all', null, 'GET');
        if (res.success) {
            showToast('Success', res.message, 'success');
        } else {
            showToast('Error', res.message, 'error');
        }
    } catch (e) {
        showToast('Error', 'Failed to rotate fingerprints', 'error');
        console.error(e);
    }
}
async function runGlobalGroupMiners() {
    if (isRunningGlobal) return;
    if (!window.adminData || !window.adminData.interlink_accounts) {
        showToast('Info', 'No accounts loaded', 'warning');
        return;
    }

    const allAccounts = window.adminData.interlink_accounts;
    // Filter for accounts that actually HAVE a group miner set
    const accounts = allAccounts.filter(acc => acc.hasGroupMiner);

    if (accounts.length === 0) {
        showToast('Info', 'No accounts have Group Mining enabled.', 'warning');
        return;
    }

    const modal = document.getElementById('runModal');
    const logContainer = document.getElementById('runLog');
    const statusText = document.getElementById('runStatus');
    const modalTitle = modal.querySelector('h3'); // Get title element

    // Reset Modal
    modal.classList.remove('hidden');
    if (modalTitle) modalTitle.innerHTML = '<span>👥</span> Global Group Miner Execution';

    logContainer.innerHTML = '';
    statusText.innerText = `Starting GROUP sequence for ${accounts.length} accounts (Skipped ${allAccounts.length - accounts.length} empty)...`;
    isRunningGlobal = true;

    // Process One by One
    for (let i = 0; i < accounts.length; i++) {
        const acc = accounts[i];
        const count = i + 1;
        const total = accounts.length;

        statusText.innerText = `Running (${count}/${total}): ${acc.email} (User: ${acc.owner})`;

        appendMinerLog(logContainer, `[${count}/${total}] processing Group Mine for ${acc.email}...`, 'info');

        try {
            const result = await apiCall('admin_run_group.php', {
                owner: acc.owner,
                email: acc.email
            });

            if (result.success) {
                const msg = typeof result.message === 'object' ? JSON.stringify(result.message) : result.message;
                appendMinerLog(logContainer, `✅ SUCCESS: ${msg}`, 'success');
            } else {
                const msg = typeof result.message === 'object' ? JSON.stringify(result.message) : result.message;
                appendMinerLog(logContainer, `❌ FAILED: ${msg}`, 'error');
            }
        } catch (e) {
            appendMinerLog(logContainer, `⚠️ ERROR: ${e.message}`, 'error');
        }

        // Small delay
        await new Promise(r => setTimeout(r, 500));
    }

    statusText.innerText = "Completed. All Group Mining processed.";
    appendMinerLog(logContainer, "--- CHAIN COMPLETE ---", 'info');
    isRunningGlobal = false;

    // Refresh Stats
    loadAdminDashboard();
}
