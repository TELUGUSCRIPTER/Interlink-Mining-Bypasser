
// -- History Functions --

// -- Tab Switching --
function switchHistoryTab(tabName) {
    // Buttons
    const btnLogs = document.getElementById('tabBtn-logs');
    const btnCron = document.getElementById('tabBtn-cron');

    // Content
    const contentLogs = document.getElementById('tabContent-logs');
    const contentCron = document.getElementById('tabContent-cron');

    if (tabName === 'logs') {
        // Active: Logs
        btnLogs.classList.add('border-primary', 'text-white', 'bg-slate-800/50');
        btnLogs.classList.remove('border-transparent', 'text-slate-400');

        btnCron.classList.add('border-transparent', 'text-slate-400');
        btnCron.classList.remove('border-primary', 'text-white', 'bg-slate-800/50');

        contentLogs.classList.remove('hidden');
        contentCron.classList.add('hidden');
    } else {
        // Active: Cron
        btnCron.classList.add('border-primary', 'text-white', 'bg-slate-800/50');
        btnCron.classList.remove('border-transparent', 'text-slate-400');

        btnLogs.classList.add('border-transparent', 'text-slate-400');
        btnLogs.classList.remove('border-primary', 'text-white', 'bg-slate-800/50');

        contentCron.classList.remove('hidden');
        contentLogs.classList.add('hidden');
    }
}

async function viewHistory(email) {
    const modal = document.getElementById('historyModal');
    const list = document.getElementById('historyList');

    // Cron Elements
    const cronActiveContent = document.getElementById('cronActiveContent');
    const cronNotActive = document.getElementById('cronNotActive');

    document.getElementById('historyEmail').innerText = email;

    // Default to Logs tab
    switchHistoryTab('logs');

    // Reset UI state
    list.innerHTML = Array(3).fill(0).map(() => `
    <tr class="animate-pulse border-b border-slate-800">
        <td class="px-6 py-4"><div class="h-4 bg-slate-700 rounded w-32"></div></td>
        <td class="px-6 py-4"><div class="h-4 bg-slate-700 rounded w-16"></div></td>
        <td class="px-6 py-4"><div class="h-4 bg-slate-700 rounded w-full"></div></td>
    </tr>
`).join('');

    // Reset Cron info
    if (cronActiveContent) cronActiveContent.classList.add('hidden');
    if (cronNotActive) cronNotActive.classList.add('hidden');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const res = await apiCall(`account_details.php?email=${email}`, null, 'GET');

    if (res.success) {
        // 1. History
        if (res.data.history && res.data.history.length > 0) {
            list.innerHTML = '';
            res.data.history.forEach(log => {
                const row = document.createElement('tr');
                let statusClass = 'text-slate-400';
                if (log.status === 'Success' || log.status === 'Claimed') statusClass = 'text-emerald-400';
                else if (log.status === 'Failed' || log.status === 'Error') statusClass = 'text-rose-400';
                else if (log.status === 'Skipped') statusClass = 'text-amber-400';

                let msg = log.message;
                try {
                    if (msg.includes('Reward:')) {
                        const parts = msg.split('Reward:');
                        const jsonPart = JSON.parse(parts[1]);
                        msg = parts[0] + ' <span class="bg-indigo-500/20 text-indigo-300 px-1 rounded text-xs">+ ' + (jsonPart.total_reward || 'Reward') + '</span>';
                    }
                } catch (e) { }

                row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-slate-500 text-xs">${log.timestamp}</td>
                <td class="px-6 py-4 font-medium ${statusClass}">${log.status}</td>
                <td class="px-6 py-4 whitespace-normal break-words min-w-[200px]">${msg}</td>
            `;
                list.appendChild(row);
            });
        } else {
            list.innerHTML = '<tr><td colspan="3" class="px-6 py-8 text-center text-slate-500">No history found for this account.</td></tr>';
        }

        // 2. Cron Schedule
        if (cronActiveContent && cronNotActive) {
            if (res.data.cron && res.data.cron.enabled) {
                cronActiveContent.classList.remove('hidden');

                document.getElementById('cronTitle').innerText = res.data.cron.title || `Miner - ${email}`;
                document.getElementById('cronTimezone').innerText = res.data.cron.timezone || 'Asia/Kolkata';
                document.getElementById('cronEventId').innerText = res.data.cron.id;

                let nextRunText = "Scheduled (Every 4h)";
                // Try logs
                if (res.data.history && res.data.history.length > 0) {
                    const lastLog = res.data.history[0];
                    if (lastLog.message && lastLog.message.includes('Next claim at:')) {
                        nextRunText = lastLog.message.split('Next claim at:')[1].trim();
                    }
                }
                // Fallback calc
                if (nextRunText === "Scheduled (Every 4h)") {
                    const now = new Date();
                    const hours = [0, 4, 8, 12, 16, 20];
                    let nextH = hours.find(h => h > now.getHours());
                    if (nextH === undefined) nextH = 0;

                    const nextDate = new Date();
                    if (nextH === 0) nextDate.setDate(now.getDate() + 1);
                    nextDate.setHours(nextH, 0, 0, 0);

                    nextRunText = nextDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }

                document.getElementById('nextRunTime').innerText = nextRunText;

            } else {
                cronNotActive.classList.remove('hidden');
            }
        }

    } else {
        list.innerHTML = `<tr><td colspan="3" class="px-6 py-8 text-center text-rose-500">Error: ${res.message}</td></tr>`;
    }
}

function closeHistoryModal() {
    const modal = document.getElementById('historyModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
