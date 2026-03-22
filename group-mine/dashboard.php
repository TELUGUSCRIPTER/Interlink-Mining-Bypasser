<?php
require_once __DIR__ . '/../lib/SessionManager.php';
require_once __DIR__ . '/../lib/Utils.php';
$check = SessionManager::checkSession();
if ($check !== true) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Mining - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' },
                        primary: '#6366f1',
                        accent: '#8b5cf6'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            font-family: sans-serif;
        }
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<?php
$accounts = Utils::getAccounts();
?>
<body class="text-white font-sans antialiased">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-center mb-8 glass p-6 rounded-2xl shadow-2xl gap-4 md:gap-0">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-500/20 rounded-xl text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 005.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-accent">
                        Group Mining
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-4 w-full md:w-auto">
                <select id="accountSelector" class="w-full md:w-64 bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none" onchange="loadGroups()">
                    <?php if (empty($accounts)): ?>
                        <option value="">No Accounts Found</option>
                    <?php else: ?>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo htmlspecialchars($acc['email']); ?>">
                                <?php echo htmlspecialchars($acc['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                
                <a href="../index.php" class="px-4 py-2.5 rounded-xl bg-slate-700/50 hover:bg-slate-700 transition-all font-medium text-sm whitespace-nowrap">
                    Back
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left: Create Group -->
            <div class="glass rounded-2xl p-6 h-fit">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <span class="text-emerald-400">+</span> Create Group
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-semibold mb-2 uppercase tracking-wide">Group Name / ID</label>
                        <input type="text" id="newGroupName" class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition-all placeholder-slate-600" placeholder="e.g. MinersUnited01">
                    </div>
                    <button onclick="createGroup()" class="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 font-semibold shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98]">
                        Create Group
                    </button>
                </div>

                <div class="mt-8 p-4 bg-slate-800/50 rounded-xl border border-slate-700/50">
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Instructions</h4>
                    <ul class="text-sm text-slate-400 space-y-2 list-disc pl-4 text-xs">
                        <li>Each account can create or join groups using their Token.</li>
                        <li>Rewards scale with group size and activity.</li>
                        <li>Switch accounts using the dropdown above.</li>
                    </ul>
                </div>

                <!-- Join Group Section -->
                <div class="glass rounded-2xl p-6 h-fit mt-8 border border-white/5">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <span class="text-blue-400">➜</span> Join Group
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-slate-400 text-xs font-semibold mb-2 uppercase tracking-wide">Invite Code</label>
                            <input type="text" id="joinCode" class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all placeholder-slate-600" placeholder="e.g. MTVHBV">
                        </div>
                        <button onclick="joinGroup()" class="w-full py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 font-semibold shadow-lg shadow-blue-500/20 transition-all active:scale-[0.98]">
                            Join Group
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: My Groups List -->
            <div class="lg:col-span-2 glass rounded-2xl p-6 min-h-[400px]">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">My Groups</h3>
                    <button onclick="loadGroups()" class="text-sm text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
                
                <div id="groupsList" class="space-y-4">
                    <!-- Loaded dynamically -->
                    <div class="text-center py-20 text-slate-500 animate-pulse">
                        Loading groups...
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- JS Logic -->
    <script>
        // On Load
        document.addEventListener('DOMContentLoaded', () => {
            loadGroups();
            setInterval(updateCountdowns, 1000);
        });

        async function loadGroups() {
            const email = document.getElementById('accountSelector').value;
            const container = document.getElementById('groupsList');
            
            if (!email) {
                container.innerHTML = '<div class="text-center py-10 text-slate-500">Please select an account</div>';
                return;
            }

            container.innerHTML = '<div class="text-center py-20 text-slate-500 animate-pulse">Loading groups...</div>';

            try {
                const res = await fetch(`api/proxy.php?action=list&email=${encodeURIComponent(email)}`, {
                    method: 'POST',
                    body: JSON.stringify({})
                });
                const json = await res.json();

                if (!json.success) {
                    throw new Error(json.message);
                }

                const data = json.data?.data?.groups || [];
                const nextTimeClaim = json.data?.data?.nextTimeClaim || 0;
                const activeGroupMiner = json.activeGroupMiner || null; // From local DB
                
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-center py-20 text-slate-500">No groups found for this account. Create one!</div>';
                    return;
                }

                let html = '';
                data.forEach(group => {
                    const isClaimable = group.canClaim;
                    const statusColor = group.status === 'CLAIMED' ? 'text-green-400' : 'text-yellow-400';
                    const memberAvatars = group.previewAvatars || [];
                    const isAuto = (group.groupId === activeGroupMiner);

                    html += `
                    <div class="bg-slate-800/40 border border-slate-700/50 rounded-xl p-5 hover:bg-slate-800/60 transition-all">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-xl font-bold text-white mb-1">${group.groupId}</h4>
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="${statusColor} font-mono px-2 py-0.5 rounded bg-black/20 text-xs border border-white/5">${group.statusLabel}</span>
                                    <span class="text-slate-400">${group.counts?.totalMembers || 0} Members</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-amber-400">${group.totalReward} <span class="text-xs text-amber-500/80">PTS</span></div>
                                <!-- Countdown Timer -->
                                <div class="text-xs font-mono text-slate-400 mt-1 countdown-timer" data-target="${nextTimeClaim}">
                                    Checking time...
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col md:flex-row items-center justify-between gap-4">
                            <div class="flex -space-x-2 justify-center md:justify-start w-full md:w-auto">
                                ${memberAvatars.map(m => `
                                    <div class="w-8 h-8 rounded-full bg-indigo-900 border-2 border-slate-800 flex items-center justify-center text-xs font-bold" title="${m.username}">
                                        ${m.username.charAt(0).toUpperCase()}
                                    </div>
                                `).join('')}
                                ${group.counts?.totalMembers > memberAvatars.length ? `<div class="w-8 h-8 rounded-full bg-slate-700 border-2 border-slate-800 flex items-center justify-center text-xs text-slate-400">+${group.counts.totalMembers - memberAvatars.length}</div>` : ''}
                            </div>
                            
                            <div class="flex flex-wrap items-center justify-center md:justify-end gap-2 w-full md:w-auto">
                                <!-- Members Button -->
                                <button onclick="openMembersModal('${group.groupId}')" class="flex-1 md:flex-none px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 text-xs font-bold transition-all flex items-center justify-center gap-1 border border-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Members
                                </button>

                                <!-- Invite Button / Full Indicator -->
                                ${group.counts?.totalMembers >= 5 ? 
                                    `<button disabled class="flex-1 md:flex-none px-3 py-2 rounded-lg bg-slate-700 text-slate-500 text-xs font-bold cursor-not-allowed flex items-center justify-center gap-1 border border-slate-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        Full
                                    </button>` :
                                    `<button onclick="openInviteModal('${group.groupId}')" class="flex-1 md:flex-none px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all flex items-center justify-center gap-1 shadow-lg shadow-indigo-500/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Invite
                                    </button>`
                                }

                                <!-- Auto Miner Toggle -->
                                <label class="inline-flex items-center cursor-pointer px-2">
                                    <input type="checkbox" class="sr-only peer" ${isAuto ? 'checked' : ''} onchange="toggleAutoMine('${group.groupId}', this.checked)">
                                    <div class="relative w-9 h-5 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ms-2 text-xs font-medium text-slate-400">Auto</span>
                                </label>

                                ${isClaimable ? 
                                    `<button onclick="claimGroup('${group.groupId}')" class="flex-1 md:flex-none px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold shadow-lg shadow-emerald-500/20 transition-all">Claim</button>`:
                                    `<button disabled class="flex-1 md:flex-none px-5 py-2 rounded-lg bg-slate-700 text-slate-500 text-sm font-bold cursor-not-allowed">Claimed</button>`
                                }
                            </div>
                        </div>
                    </div>
                    `;
                });

                container.innerHTML = html;
                updateCountdowns(); // Initial update

            } catch (e) {
                container.innerHTML = `<div class="text-center py-10 text-red-400">Error: ${e.message}</div>`;
            }
        }

        async function toggleAutoMine(groupId, status) {
            const email = document.getElementById('accountSelector').value;
            // Optimistic update handled by reload, or we could prevent UI flicker manually.
            // But reloading ensures state is consistent with backend mutual exclusion logic.
            
            try {
                const res = await fetch(`api/proxy.php?action=set_auto&email=${encodeURIComponent(email)}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ groupId, status })
                });
                const json = await res.json();
                
                if (json.success) {
                    showToast('Auto Miner Updated', 'success');
                    loadGroups(); // Reload to reflect mutual exclusion (disable others)
                } else {
                    showToast(json.message || 'Failed to update', 'error');
                }
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        function updateCountdowns() {
            const timers = document.querySelectorAll('.countdown-timer');
            const now = new Date().getTime();

            timers.forEach(timer => {
                const target = parseInt(timer.getAttribute('data-target'));
                if (!target) {
                    timer.innerText = '';
                    return;
                }

                const diff = target - now;

                if (diff <= 0) {
                    timer.innerText = 'Ready to Claim!';
                    timer.classList.add('text-emerald-400');
                    timer.classList.remove('text-slate-400');
                } else {
                    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);
                    timer.innerText = `Next: ${h}h ${m}m ${s}s`;
                    timer.classList.remove('text-emerald-400');
                    timer.classList.add('text-slate-400');
                }
            });
        }

        async function createGroup() {
            const email = document.getElementById('accountSelector').value;
            const groupId = document.getElementById('newGroupName').value;
            
            if (!email) return showToast('Please select an account first', 'error');
            if (!groupId) return showToast('Please enter a group name', 'error');

            const btn = document.querySelector('button[onclick="createGroup()"]');
            const originalText = btn.innerText;
            btn.innerText = 'Creating...';
            btn.disabled = true;

            try {
                const res = await fetch(`api/proxy.php?action=create&email=${encodeURIComponent(email)}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ groupId })
                });
                const json = await res.json();

                if (json.success) {
                    showToast(json.message || 'Group Created Successfully!', 'success');
                    document.getElementById('newGroupName').value = '';
                    loadGroups();
                } else {
                    showToast(json.message || 'Failed to create group', 'error');
                }
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        async function joinGroup() {
            const email = document.getElementById('accountSelector').value;
            const code = document.getElementById('joinCode').value;
            
            if (!email) return showToast('Select an account first', 'error');
            if (!code) return showToast('Enter an invite code', 'error');

            const btn = document.querySelector('button[onclick="joinGroup()"]');
            const originalText = btn.innerText;
            btn.innerText = 'Joining...';
            btn.disabled = true;

            try {
                const res = await fetch(`api/proxy.php?action=join&email=${encodeURIComponent(email)}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ code })
                });
                
                const rawText = await res.text();
                let json;
                try {
                    json = JSON.parse(rawText);
                } catch (parseErr) {
                    // JSON parse failed, likely server error or timeout
                    console.error("Raw response:", rawText);
                    const safeMsg = rawText.substring(0, 150) + (rawText.length > 150 ? '...' : '');
                    throw new Error(`Server Response: ${safeMsg || 'Empty Response'}`);
                }

                if (json.success) {
                    showToast('Joined Group Successfully!', 'success');
                    document.getElementById('joinCode').value = '';
                    loadGroups();
                } else {
                    showToast(json.message || 'Failed to join group', 'error');
                }
            } catch (e) {
                showToast(e.message, 'error');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        // Invite Modal Logic
        function openInviteModal(groupId) {
            document.getElementById('inviteGroupId').value = groupId;
            document.getElementById('targetUserId').value = '';
            
            document.getElementById('inviteInputSection').classList.remove('hidden');
            document.getElementById('inviteResultSection').classList.add('hidden');
            
            const modal = document.getElementById('inviteModal');
            modal.classList.remove('pointer-events-none', 'opacity-0');
            modal.querySelector('div.transform').classList.remove('scale-95');
            modal.querySelector('div.transform').classList.add('scale-100');
        }

        function closeInviteModal() {
            const modal = document.getElementById('inviteModal');
            modal.classList.add('pointer-events-none', 'opacity-0');
            modal.querySelector('div.transform').classList.add('scale-95');
            modal.querySelector('div.transform').classList.remove('scale-100');
        }

        async function generateInvite() {
            const email = document.getElementById('accountSelector').value;
            const groupId = document.getElementById('inviteGroupId').value;
            const targetId = document.getElementById('targetUserId').value;

            if (!targetId) return showToast('Please enter Target User ID', 'error');

            const btn = document.querySelector('button[onclick="generateInvite()"]');
            const originalText = btn.innerText;
            btn.innerText = 'Go...';
            btn.disabled = true;

            try {
                const res = await fetch(`api/proxy.php?action=create_invite&email=${encodeURIComponent(email)}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ groupId, loginId: targetId })
                });
                const json = await res.json();

                if (json.success) {
                    const code = json.data?.data?.code;
                    const url = json.data?.data?.inviteUrl;

                    document.getElementById('resultCode').innerText = code || 'N/A';
                    document.getElementById('resultUrl').innerText = url || 'N/A';

                    document.getElementById('inviteInputSection').classList.add('hidden');
                    document.getElementById('inviteResultSection').classList.remove('hidden');
                } else {
                    showToast(json.message || 'Failed to create invite', 'error');
                }
            } catch (e) {
                showToast('Error: ' + e.message, 'error');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }

        async function copyText(elementId) {
            const text = document.getElementById(elementId).innerText;
            
            // Try modern Clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(text);
                    showToast('Copied to clipboard!', 'success');
                    return;
                } catch (err) {
                    console.error('Clipboard API failed', err);
                }
            }

            // Fallback for non-secure context or failure
            try {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    showToast('Copied to clipboard!', 'success');
                } else {
                    throw new Error('execCommand failed');
                }
            } catch (err) {
                 prompt("Copy manually:", text);
            }
        }
        async function claimGroup(groupId) {
            const email = document.getElementById('accountSelector').value;
             if (!confirm(`Claim rewards for group ${groupId}?`)) return;

             try {
                const res = await fetch(`api/proxy.php?action=claim&email=${encodeURIComponent(email)}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ groupId })
                });
                const json = await res.json();

                if (json.success) {
                    showToast('Claimed Successfully! Reward: ' + (json.data?.data?.totalReward || 'Unknown'), 'success');
                    loadGroups();
                } else {
                    showToast(json.message || 'Claim failed', 'error');
                }
             } catch (e) {
                 showToast('Error: ' + e.message, 'error');
             }
        }

        function showToast(message, type = 'success') {
            // Safe handle for objects
            if (typeof message === 'object' && message !== null) {
                // Try to extract readable message if possible, or stringify
                message = message.message || message.error || JSON.stringify(message);
            }

            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-emerald-600' : 'bg-red-600';
            const icon = type === 'success' ? 
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : 
                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

            toast.className = `${bgColor} text-white px-6 py-4 rounded-xl shadow-2xl flex items-start gap-3 transform transition-all duration-300 translate-y-10 opacity-0 min-w-[300px] z-[9999]`;
            toast.innerHTML = `
                <div class="mt-0.5">${icon}</div>
                <div class="font-medium text-sm">${message}</div>
            `;

            container.appendChild(toast);

            // Animate in
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });

            // Remove after 4 seconds
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Members Modal Logic (New)
        async function openMembersModal(groupId) {
             const modal = document.getElementById('membersModal');
             const listContainer = document.getElementById('membersList');
             const email = document.getElementById('accountSelector').value;

             // Show Modal
             modal.classList.remove('pointer-events-none', 'opacity-0');
             modal.querySelector('div.transform').classList.remove('scale-95');
             modal.querySelector('div.transform').classList.add('scale-100');
             
             listContainer.innerHTML = '<div class="text-center py-8 text-slate-500 animate-pulse">Loading members...</div>';

             try {
                const res = await fetch(`api/proxy.php?action=get_members&email=${encodeURIComponent(email)}&groupId=${groupId}`);
                const json = await res.json();
                
                if (json.success) {
                    const members = json.data?.data?.members || [];
                    
                    if (members.length === 0) {
                        listContainer.innerHTML = '<div class="text-center py-8 text-slate-500">No members found</div>';
                        return;
                    }
                    
                    let html = '';
                    members.forEach(member => {
                        html += `
                        <div class="flex items-center justify-between bg-slate-900/50 p-3 rounded-xl border border-white/5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-sm font-bold text-white shadow-lg">
                                    ${member.username.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <div class="font-bold text-sm text-white">${member.username}</div>
                                    <div class="text-xs text-slate-400 font-mono">ID: ${member.loginId || 'N/A'}</div>
                                </div>
                            </div>
                            <div class="text-xs font-mono text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded">
                                Member
                            </div>
                        </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                    
                } else {
                    listContainer.innerHTML = `<div class="text-center py-8 text-red-400">${json.message || 'Failed to load members'}</div>`;
                }
             } catch (e) {
                 listContainer.innerHTML = `<div class="text-center py-8 text-red-400">Error: ${e.message}</div>`;
             }
        }

        function closeMembersModal() {
            const modal = document.getElementById('membersModal');
            modal.classList.add('pointer-events-none', 'opacity-0');
            modal.querySelector('div.transform').classList.add('scale-95');
            modal.querySelector('div.transform').classList.remove('scale-100');
        }
    </script>
    
    <!-- Invite Modal -->
    <div id="inviteModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeInviteModal()"></div>
        <div class="bg-slate-800 rounded-2xl p-6 w-full max-w-md border border-white/10 shadow-2xl transform scale-95 transition-all duration-300 relative z-10">
            <h3 class="text-xl font-bold text-white mb-4">Create Invite Link</h3>
            
            <div id="inviteInputSection">
                <p class="text-slate-400 text-sm mb-4">Enter the Interlink ID of the user you want to invite.</p>
                <input type="hidden" id="inviteGroupId">
                <div class="mb-4">
                    <label class="block text-slate-400 text-xs font-semibold mb-2 uppercase tracking-wide">Target User Interlink ID</label>
                    <input type="text" id="targetUserId" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all placeholder-slate-600" placeholder="e.g. 8688858227">
                </div>
                <div class="flex gap-3">
                    <button onclick="closeInviteModal()" class="flex-1 py-3 rounded-xl bg-slate-700 hover:bg-slate-600 font-semibold transition-all">Cancel</button>
                    <button onclick="generateInvite()" class="flex-1 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 font-semibold shadow-lg shadow-blue-500/20 transition-all">Generate Link</button>
                </div>
            </div>

            <div id="inviteResultSection" class="hidden">
                 <div class="bg-black/30 rounded-xl p-4 mb-4 border border-white/5">
                    <p class="text-xs text-slate-400 mb-1">Invite Code</p>
                    <div class="flex items-center justify-between">
                        <code class="text-amber-400 text-lg font-mono" id="resultCode"></code>
                        <button onclick="copyText('resultCode')" class="text-blue-400 text-xs hover:text-blue-300">Copy</button>
                    </div>
                 </div>
                 <div class="bg-black/30 rounded-xl p-4 mb-6 border border-white/5">
                    <p class="text-xs text-slate-400 mb-1">Invite Link</p>
                     <div class="flex items-center justify-between gap-2">
                        <code class="text-slate-300 text-xs font-mono break-all line-clamp-2" id="resultUrl"></code>
                        <button onclick="copyText('resultUrl')" class="text-blue-400 text-xs hover:text-blue-300 shrink-0">Copy</button>
                    </div>
                 </div>
                 <button onclick="closeInviteModal()" class="w-full py-3 rounded-xl bg-slate-700 hover:bg-slate-600 font-semibold">Close</button>
            </div>
        </div>
    </div>

    <!-- Members Modal (New) -->
    <div id="membersModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeMembersModal()"></div>
        <div class="bg-slate-800 rounded-2xl p-6 w-full max-w-md border border-white/10 shadow-2xl transform scale-95 transition-all duration-300 relative z-10">
            <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Group Members
            </h3>
            
            <div id="membersList" class="space-y-3 max-h-[60vh] overflow-y-auto mb-6 pr-2 custom-scrollbar">
                <!-- Loading State -->
                <div class="text-center py-8 text-slate-500 animate-pulse">Loading members...</div>
            </div>

            <button onclick="closeMembersModal()" class="w-full py-3 rounded-xl bg-slate-700 hover:bg-slate-600 font-semibold text-white">Close</button>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-8 right-8 flex flex-col gap-3 pointer-events-none z-[9999]"></div>
</body>
</html>
