<?php
// Global Proxy Manager - Refactored UI v3 (Mining Check Added)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Manager - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b' },
                        primary: '#6366f1',
                        accent: '#8b5cf6'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: { '0%': { transform: 'translateY(10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } }
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); 
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .toast-enter { transform: translateX(100%); opacity: 0; }
        .toast-enter-active { transform: translateX(0); opacity: 1; transition: all 300ms ease-out; }
        .toast-exit { transform: translateX(0); opacity: 1; }
        .toast-exit-active { transform: translateX(100%); opacity: 0; transition: all 300ms ease-in; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="text-white font-sans antialiased p-4 md:p-8 pb-32">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <header class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="../index.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-800 hover:bg-slate-700 transition-colors border border-slate-700 text-slate-400 group">
                    <span class="group-hover:-translate-x-0.5 transition-transform">←</span>
                </a>
                <div>
                    <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">Proxy Manager</h1>
                    <p class="text-slate-400 text-sm">Manage connection gateways</p>
                </div>
            </div>
            
            <div class="text-xs text-slate-500 font-mono bg-slate-900/50 px-3 py-1 rounded-lg border border-slate-800">
                v2.2 Mining Check
            </div>
        </header>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Input & Test (4 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Input Form -->
                <div class="glass p-6 rounded-2xl shadow-xl">
                    <form id="proxyForm" onsubmit="testProxy(event)" class="space-y-5">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Protocol</label>
                            <div class="grid grid-cols-4 gap-2">
                                 <label class="cursor-pointer">
                                     <input type="radio" name="type" value="HTTP" class="peer sr-only" checked>
                                     <div class="px-1 py-2 text-center rounded-lg bg-slate-900/50 border border-slate-700 text-slate-400 text-xs font-bold transition-all peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary peer-checked:shadow-lg peer-checked:shadow-primary/20 hover:bg-slate-800">HTTP</div>
                                 </label>
                                 <label class="cursor-pointer">
                                     <input type="radio" name="type" value="HTTPS" class="peer sr-only">
                                     <div class="px-1 py-2 text-center rounded-lg bg-slate-900/50 border border-slate-700 text-slate-400 text-xs font-bold transition-all peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary peer-checked:shadow-lg peer-checked:shadow-primary/20 hover:bg-slate-800">HTTPS</div>
                                 </label>
                                 <label class="cursor-pointer">
                                     <input type="radio" name="type" value="SOCKS4" class="peer sr-only">
                                     <div class="px-1 py-2 text-center rounded-lg bg-slate-900/50 border border-slate-700 text-slate-400 text-xs font-bold transition-all peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary peer-checked:shadow-lg peer-checked:shadow-primary/20 hover:bg-slate-800">SOCKS4</div>
                                 </label>
                                 <label class="cursor-pointer">
                                     <input type="radio" name="type" value="SOCKS5" class="peer sr-only">
                                     <div class="px-1 py-2 text-center rounded-lg bg-slate-900/50 border border-slate-700 text-slate-400 text-xs font-bold transition-all peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary peer-checked:shadow-lg peer-checked:shadow-primary/20 hover:bg-slate-800">SOCKS5</div>
                                 </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Host / IP</label>
                                <input type="text" id="host" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all text-white placeholder-slate-600 font-mono text-sm" placeholder="1.2.3.4">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Port</label>
                                <input type="number" id="port" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all text-white placeholder-slate-600 font-mono text-sm" placeholder="80">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">User <span class="text-slate-600 normal-case font-normal">(Opt)</span></label>
                                <input type="text" id="username" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all text-white placeholder-slate-600 text-sm" placeholder="auth">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pass <span class="text-slate-600 normal-case font-normal">(Opt)</span></label>
                                <input type="text" id="password" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/50 transition-all text-white placeholder-slate-600 text-sm" placeholder="secret">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" id="testBtn" class="w-full bg-gradient-to-r from-primary to-indigo-600 hover:to-indigo-500 active:scale-[0.98] py-3 rounded-xl font-bold shadow-lg shadow-primary/25 transition-all text-white flex items-center justify-center gap-2 group">
                                <span class="group-hover:rotate-12 transition-transform">⚡</span> Test Connection
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Instructions Info -->
                <div class="p-4 rounded-xl border border-slate-800 bg-slate-800/30 text-slate-400 text-sm flex gap-3">
                    <div class="text-xl">💡</div>
                    <div>
                        <p class="font-bold text-slate-300 mb-1">Quick Tip</p>
                        <p>You must <b>Check Response</b> to verify mining capability before assigning accounts.</p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Results & List (8 cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Dynamic Result Card -->
                <div id="resultCard" class="hidden glass rounded-xl border border-slate-700 overflow-hidden animate-fade-in">
                    <!-- Status Header -->
                    <div class="p-6 flex items-start gap-4" id="resultHeader">
                        <div id="statusIcon" class="w-12 h-12 rounded-full flex items-center justify-center text-2xl shrink-0 transition-colors"></div>
                        <div class="flex-1 min-w-0">
                            <h4 id="statusTitle" class="font-bold text-lg truncate">Testing...</h4>
                            <p id="statusMsg" class="text-slate-400 text-sm mt-1 break-words">Initializing connection check...</p>
                            
                            <div class="flex flex-wrap gap-2 mt-3">
                                <div id="latencyBadge" class="hidden inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-800 border border-slate-700 text-slate-300">
                                    <span>⏱️</span> <span id="latencyVal">0ms</span>
                                </div>
                                <div id="countryBadge" class="hidden inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    <span>🌍</span> <span id="countryVal">Unknown</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inline Assignment Section (Hidden by default) -->
                    <div id="assignSection" class="hidden border-t border-slate-700/50 bg-slate-800/30 p-6 animate-slide-up">
                        <h5 class="font-bold text-white text-sm mb-3 flex items-center gap-2">
                            <span>🔗</span> Assign to Accounts
                            <span class="text-[10px] text-slate-500 font-normal ml-auto">(Assigned accounts hidden)</span>
                        </h5>
                        
                        <div id="accountCheckboxes" class="max-h-48 overflow-y-auto space-y-1 pr-2 mb-4 scrollbar-thin">
                            <!-- Accounts loaded via JS -->
                            <div class="text-slate-500 text-sm italic">Loading accounts...</div>
                        </div>

                        <!-- Mining Response Area -->
                        <div id="miningResultArea" class="hidden mb-4 p-3 rounded-lg bg-black/50 border border-slate-700/50 text-[10px] font-mono text-slate-300 overflow-x-auto">
                            <div class="font-bold text-slate-500 mb-1">API Response:</div>
                            <pre id="miningResponseCode" class="whitespace-pre-wrap"></pre>
                        </div>

                        <div class="flex justify-between items-center pt-2 gap-4">
                            <button type="button" onclick="resetForm()" class="text-xs text-slate-500 hover:text-slate-300 underline decoration-slate-600">Cancel & Reset</button>
                            
                            <div class="flex gap-2">
                                <button type="button" id="miningCheckBtn" onclick="checkMiningResponse()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg font-bold text-white text-sm shadow-lg shadow-indigo-500/20 transition-all flex items-center gap-2">
                                    <span>📡</span> Check Response
                                </button>

                                <button type="button" id="saveBtn" onclick="saveAndAssign()" class="hidden px-5 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-bold text-white text-sm shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
                                    <span>💾</span> Save & Assign
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saved Proxies List -->
                <div>
                    <h2 class="text-lg font-bold mb-4 flex items-center justify-between">
                        <span class="flex items-center gap-2"><span>🛡️</span> Saved Proxies</span>
                        <button onclick="fetchProxies()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-1 rounded border border-slate-700 transition-colors">Refresh</button>
                    </h2>
                    
                    <div class="glass rounded-xl overflow-hidden shadow-xl border border-slate-700/50">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-800/80 text-slate-400 uppercase text-[10px] font-bold tracking-wider">
                                    <tr>
                                        <th class="px-5 py-3">Host</th>
                                        <th class="px-5 py-3">Type</th>
                                        <th class="px-5 py-3">Loc</th>
                                        <th class="px-5 py-3">Used By</th>
                                        <th class="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50 text-sm" id="proxiesList">
                                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500 animate-pulse">Loading proxies...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        let currentProxyData = null; 
        let allAccounts = [];
        let isMiningVerified = false;

        window.addEventListener('load', () => {
            fetchProxies();
            fetchAccounts();
        });

        // --- Toast System ---
        function showToast(msg, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            
            const colors = type === 'success' 
                ? 'bg-emerald-500/90 text-white border-emerald-400/50 shadow-emerald-500/20' 
                : 'bg-rose-500/90 text-white border-rose-400/50 shadow-rose-500/20';
            
            const icon = type === 'success' ? '✅' : '⚠️';

            toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl border shadow-lg backdrop-blur-sm min-w-[300px] max-w-sm transform transition-all duration-300 translate-x-10 opacity-0 ${colors}`;
            toast.innerHTML = `
                <span class="text-lg">${icon}</span>
                <p class="text-sm font-medium pr-2">${msg}</p>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-10', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // --- Data Fetching ---
        async function fetchAccounts() {
            try {
                const res = await fetch('api/accounts.php');
                const json = await res.json();
                if (json.success) {
                    allAccounts = json.data;
                    renderAccountCheckboxes();
                }
            } catch (e) { showToast('Error fetching accounts', 'error'); }
        }

        async function fetchProxies() {
            const list = document.getElementById('proxiesList');
            try {
                const res = await fetch('api/list.php');
                const json = await res.json();
                
                if (json.success && json.data.length > 0) {
                    list.innerHTML = json.data.map(p => {
                        const hasAccounts = p.accounts && p.accounts.length > 0;
                        const rowId = `row-${p.id}`;
                        
                        return `
                        <!-- Main Row -->
                        <tr class="hover:bg-slate-800/30 transition-colors group">
                            <td class="px-5 py-4 font-mono text-slate-300 text-xs">
                                <div class="flex flex-col">
                                    <span class="font-bold text-white text-sm">${p.host}</span>
                                    <span class="text-slate-500">:${p.port}</span>
                                    ${p.username ? `<span class="text-[10px] text-indigo-400 mt-1">🔑 ${p.username}</span>` : ''}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="bg-slate-800 text-slate-400 text-[10px] font-bold px-2 py-1 rounded border border-slate-700">${p.type}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-300 text-sm">
                                ${p.country !== 'Unknown' ? `<div class="flex items-center gap-1"><span>📍</span> ${p.country}</div>` : '-'}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    ${p.assigned_count > 0 
                                        ? `<button onclick="toggleDetails('${rowId}')" class="bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold px-2 py-1 rounded-lg transition-colors flex items-center gap-1">
                                             <span>👥</span> ${p.assigned_count} Accounts <span id="arrow-${rowId}" class="transition-transform text-[10px]">▼</span>
                                           </button>`
                                        : `<span class="text-slate-600 text-xs italic">Unused</span>`
                                    }
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button onclick="deleteProxyInit('${p.id}', this)" class="text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 p-2 rounded-lg transition-all text-xs font-bold uppercase tracking-wider relative group-btn">
                                    <span class="default-text">Delete</span>
                                    <span class="confirm-text hidden text-rose-500">Confirm?</span>
                                </button>
                            </td>
                        </tr>
                        <!-- Details Row (Hidden) -->
                        <tr id="${rowId}" class="hidden bg-slate-800/20">
                            <td colspan="5" class="px-5 py-3 border-t border-slate-700/50">
                                <div class="text-xs text-slate-400 flex items-start gap-4 ml-2">
                                    <span class="mt-1">↳</span>
                                    <div class="flex flex-wrap gap-2">
                                        ${hasAccounts ? p.accounts.map(acc => `
                                            <div class="bg-slate-900 border border-slate-700 px-2 py-1 rounded text-slate-300 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> ${acc}
                                            </div>
                                        `).join('') : '<span class="italic">No accounts connected</span>'}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `}).join('');
                } else {
                    list.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        <div class="text-4xl mb-2">🕸️</div>
                        No proxies saved yet.
                    </td></tr>`;
                }
            } catch (e) {
                list.innerHTML = `<tr><td colspan="5" class="px-6 py-8 text-center text-rose-500">Error loading proxies</td></tr>`;
            }
        }

        // --- UI Interactions ---
        function toggleDetails(rowId) {
            const row = document.getElementById(rowId);
            const arrow = document.getElementById(`arrow-${rowId}`);
            if (row) {
                row.classList.toggle('hidden');
                if (arrow) {
                    const isHidden = row.classList.contains('hidden');
                    arrow.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            }
        }

        function renderAccountCheckboxes() {
            const container = document.getElementById('accountCheckboxes');
            
            // FILTER: Only show accounts that do NOT have a proxyId
            const availableAccounts = allAccounts.filter(acc => !acc.proxyId);
            
            if (availableAccounts && availableAccounts.length > 0) {
                container.innerHTML = availableAccounts.map(acc => `
                    <label class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-slate-700/50 transition-colors cursor-pointer border border-transparent hover:border-slate-600/50 group">
                        <input type="checkbox" name="assignAcc" value="${acc.email}" class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-primary focus:ring-primary focus:ring-offset-slate-900 transition-colors" onchange="resetMiningVerification()">
                        <div class="flex-1">
                            <div class="font-bold text-sm text-slate-200 group-hover:text-white">${acc.email}</div>
                            <div class="text-[10px] text-slate-500 group-hover:text-slate-400">ID: ${acc.interlinkId}</div>
                        </div>
                    </label>
                `).join('');
            } else {
                container.innerHTML = `<div class="p-4 text-center text-slate-500 text-xs">All accounts already have proxies assigned.</div>`;
            }
        }

        function resetForm() {
            document.getElementById('proxyForm').reset();
            const resultCard = document.getElementById('resultCard');
            resultCard.classList.add('hidden');
            document.getElementById('assignSection').classList.add('hidden');
            currentProxyData = null;
            isMiningVerified = false;
            
            // Reset buttons
            document.getElementById('saveBtn').classList.add('hidden');
            document.getElementById('miningCheckBtn').classList.remove('hidden');
            document.getElementById('miningResultArea').classList.add('hidden');
        }

        function resetMiningVerification() {
             isMiningVerified = false;
             document.getElementById('saveBtn').classList.add('hidden');
             document.getElementById('miningCheckBtn').classList.remove('hidden');
             document.getElementById('miningResultArea').classList.add('hidden');
        }

        // --- Logic ---
        async function testProxy(e) {
            e.preventDefault();
            
            const btn = document.getElementById('testBtn');
            const resultCard = document.getElementById('resultCard');
            const resultHeader = document.getElementById('resultHeader');
            const statusIcon = document.getElementById('statusIcon');
            const statusTitle = document.getElementById('statusTitle');
            const statusMsg = document.getElementById('statusMsg');
            const assignSection = document.getElementById('assignSection');
            const latencyBadge = document.getElementById('latencyBadge');
            const countryBadge = document.getElementById('countryBadge');

            // Reset UI
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin text-xl">⏳</span> Testing...';
            
            resultCard.classList.remove('hidden', 'border-emerald-500/50', 'border-rose-500/50');
            resultCard.classList.add('border-slate-700');
            statusIcon.className = 'w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center text-2xl shrink-0';
            statusIcon.innerHTML = '🔄';
            statusTitle.textContent = 'Verifying Proxy...';
            statusMsg.textContent = 'Attempting check...';
            latencyBadge.classList.add('hidden');
            countryBadge.classList.add('hidden');
            assignSection.classList.add('hidden'); // Hide assignment until re-verified
            
            resetMiningVerification();

            const formData = {
                type: document.querySelector('input[name="type"]:checked').value,
                host: document.getElementById('host').value,
                port: document.getElementById('port').value,
                username: document.getElementById('username').value,
                password: document.getElementById('password').value
            };

            try {
                const req = await fetch('api/test.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                const res = await req.json();

                if (res.success) {
                    // Success State
                    resultCard.classList.remove('border-slate-700');
                    resultCard.classList.add('border-emerald-500/50', 'bg-emerald-500/5');
                    statusIcon.className = 'w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-emerald-500/10 border border-emerald-500/30';
                    statusIcon.innerHTML = '✅';
                    statusTitle.textContent = 'Proxy Online';
                    statusTitle.className = 'font-bold text-lg text-emerald-400';
                    statusMsg.textContent = res.message;
                    
                    if (res.data) {
                        if (res.data.latency) {
                            latencyBadge.classList.remove('hidden');
                            document.getElementById('latencyVal').textContent = res.data.latency + 'ms';
                        }
                        if (res.data.ip_data && res.data.ip_data.country) {
                            countryBadge.classList.remove('hidden');
                            document.getElementById('countryVal').textContent = res.data.ip_data.country;
                            formData.country = res.data.ip_data.country;
                        }
                    }

                    // Save verified data
                    currentProxyData = formData;
                    
                    // Show Assignment Section
                    assignSection.classList.remove('hidden');
                    
                    showToast('Proxy verified! Now check mining response.');

                } else {
                    throw new Error(res.message);
                }

            } catch (err) {
                console.error(err);
                resultCard.classList.remove('border-slate-700');
                resultCard.classList.add('border-rose-500/50', 'bg-rose-500/5');
                statusIcon.className = 'w-12 h-12 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center text-2xl shrink-0 shadow-lg shadow-rose-500/10 border border-rose-500/30';
                statusIcon.innerHTML = '❌';
                statusTitle.textContent = 'Connection Failed';
                statusTitle.className = 'font-bold text-lg text-rose-400';
                statusMsg.textContent = err.message || 'System Error';
                showToast('Proxy connection failed', 'error');
                currentProxyData = null;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>⚡</span> Test Connection';
            }
        }

        async function checkMiningResponse() {
            const checkboxes = document.querySelectorAll('input[name="assignAcc"]:checked');
            if (checkboxes.length === 0) {
                showToast('Select an account first!', 'error');
                return;
            }
            // Use first selected account for test
            const testEmail = checkboxes[0].value;
            
            const btn = document.getElementById('miningCheckBtn');
            const resultArea = document.getElementById('miningResultArea');
            const codeBlock = document.getElementById('miningResponseCode');
            const saveBtn = document.getElementById('saveBtn');

            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin">⏳</span> Checking API...';
            
            // Prepare payload
            const payload = { ...currentProxyData, testEmail: testEmail };

            try {
                const req = await fetch('api/verify_target.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await req.json();

                resultArea.classList.remove('hidden');
                
                // Show raw response for user transparency
                const debugData = res.data && (res.data.apiResponse || res.data.raw_response) 
                    ? (res.data.apiResponse || res.data.raw_response) 
                    : res;
                
                codeBlock.textContent = typeof debugData === 'object' ? JSON.stringify(debugData, null, 2) : debugData;

                if (res.success) {
                    showToast('Mining API Check Successful');
                    isMiningVerified = true;
                    btn.classList.add('hidden');
                    saveBtn.classList.remove('hidden');
                } else {
                    showToast('Mining API Check Failed', 'error');
                }

            } catch (e) {
                showToast('Verification Error: ' + e.message, 'error');
                resultArea.classList.remove('hidden');
                codeBlock.textContent = "Error: " + e.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>📡</span> Check Response';
            }
        }

        async function saveAndAssign() {
            if (!currentProxyData || !isMiningVerified) {
                showToast('Please verify mining response first', 'error');
                return;
            }
            
            const checkboxes = document.querySelectorAll('input[name="assignAcc"]:checked');
            const selectedEmails = Array.from(checkboxes).map(c => c.value);
            
            if (selectedEmails.length === 0) {
                 showToast('Please select at least one account', 'error');
                 return;
            }

            const payload = { ...currentProxyData, assignTo: selectedEmails };
            
            const btn = document.getElementById('saveBtn');
            const originalHtml = btn.innerHTML;
            
            btn.innerHTML = '<span>⏳</span> Saving...';
            btn.disabled = true;

            try {
                const req = await fetch('api/save.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const res = await req.json();
                
                if (res.success) {
                    showToast('Proxy saved and assigned successfully!');
                    resetForm(); // resets UI
                    fetchProxies(); // updates list
                    fetchAccounts(); // updates checkboxes (removes assigned)
                } else {
                    throw new Error(res.message);
                }
            } catch (e) {
                showToast(e.message, 'error');
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }

        // --- Delete Logic with 2-Step Confirmation ---
        let deleteTimeouts = {};

        function deleteProxyInit(id, btn) {
            if (btn.classList.contains('confirming')) {
                // Second click: Do Delete
                deleteProxyExecute(id, btn);
            } else {
                // First click: Show Confirm
                btn.classList.add('confirming');
                const defaultText = btn.querySelector('.default-text');
                const confirmText = btn.querySelector('.confirm-text');
                
                defaultText.classList.add('hidden');
                confirmText.classList.remove('hidden');
                
                // Reset after 3 seconds
                if (deleteTimeouts[id]) clearTimeout(deleteTimeouts[id]);
                deleteTimeouts[id] = setTimeout(() => {
                    btn.classList.remove('confirming');
                    defaultText.classList.remove('hidden');
                    confirmText.classList.add('hidden');
                }, 3000);
            }
        }

        async function deleteProxyExecute(id, btnElement) {
            // Optimistic UI
            const row = btnElement.closest('tr');
            if (row) {
                row.style.opacity = '0.5';
                row.style.pointerEvents = 'none';
            }

            try {
                const res = await fetch('api/delete.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                });
                const json = await res.json();
                
                if (json.success) {
                    showToast('Proxy deleted.');
                    if (row) row.remove();
                    const detailsRow = document.getElementById(`row-${id}`);
                    if (detailsRow) detailsRow.remove();
                    fetchAccounts(); // Refresh accounts just in case
                } else {
                    throw new Error(json.message);
                }
            } catch (e) {
                if (row) {
                    row.style.opacity = '1';
                    row.style.pointerEvents = 'auto';
                }
                showToast('Delete failed: ' + e.message, 'error');
            }
        }
    </script>
</body>
</html>
