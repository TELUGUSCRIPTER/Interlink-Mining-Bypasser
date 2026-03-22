<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Doctor - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/utils.js"></script>
    <style>
        body { background: #0f172a; color: white; }
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="p-6 md:p-12">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-teal-400 to-emerald-500">
                    🩺 System Doctor
                </h1>
                <p class="text-slate-400 text-sm mt-1">Diagnostics & Maintenance Hub</p>
            </div>
            <a href="index.php" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition text-sm font-medium">
                ← Back to Dashboard
            </a>
        </div>
        
        <!-- Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- 1. Disk & Logs -->
            <div class="glass p-6 rounded-2xl border-t-4 border-emerald-500">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span>💾</span> Disk & Logs
                </h2>
                <div id="diskStatus" class="space-y-4">
                    <div class="animate-pulse h-12 bg-slate-800 rounded"></div>
                </div>
                <div class="mt-6 pt-4 border-t border-slate-700">
                    <button onclick="cleanLogs()" class="w-full py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-600/30 rounded-lg transition font-bold text-sm">
                        🗑️ Purge All Logs
                    </button>
                    <p class="text-xs text-slate-500 text-center mt-2">Truncates 'system.log' and deletes others.</p>
                </div>
            </div>

            <!-- 2. PHP Environment -->
            <div class="glass p-6 rounded-2xl border-t-4 border-teal-500">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span>⚙️</span> Environment
                </h2>
                <div id="envStatus" class="space-y-2 text-sm">
                    <div class="animate-pulse h-20 bg-slate-800 rounded"></div>
                </div>
            </div>

            <!-- 3. Cronicle Sync (Stub for now) -->
            <div class="glass p-6 rounded-2xl border-t-4 border-indigo-500 col-span-1 md:col-span-2 opacity-75">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-xl font-bold mb-1 flex items-center gap-2">
                            <span>🔄</span> Cronicle Connectivity
                        </h2>
                        <p class="text-slate-400 text-sm">Verify setup with external scheduler.</p>
                    </div>
                    <span class="px-2 py-1 bg-indigo-500/20 text-indigo-300 text-xs rounded uppercase font-bold">Passive Check</span>
                </div>
                <div class="mt-4 p-4 bg-slate-800/50 rounded-lg font-mono text-xs text-slate-300">
                    Host: <?php echo defined('CRONICLE_API_URL') ? parse_url(CRONICLE_API_URL, PHP_URL_HOST) : 'Unknown'; ?><br>
                    Plugin ID: <?php echo defined('CRONICLE_PLUGIN_ID') ? CRONICLE_PLUGIN_ID : 'Unknown'; ?>
                </div>
            </div>

        </div>
        
    </div>

    <!-- Notification -->
    <div id="toast" class="fixed bottom-5 right-5 px-6 py-4 rounded-xl glass border-l-4 border-teal-500 translate-y-20 opacity-0 transition-all duration-300 shadow-2xl z-50">
        <h4 class="font-bold text-teal-400" id="toastTitle">Diag</h4>
        <p class="text-sm text-slate-300" id="toastMessage">...</p>
    </div>

    <script>
        async function loadHealth() {
            const diskEl = document.getElementById('diskStatus');
            const envEl = document.getElementById('envStatus');
            
            try {
                const req = await fetch('../api/admin_doctor.php?action=check_health');
                const res = await req.json();
                
                if (res.success) {
                    const data = res.data;
                    
                    // Logs
                    diskEl.innerHTML = `
                        <div class="flex justify-between items-center p-3 bg-slate-800/50 rounded-lg">
                            <span class="text-slate-400">Total Log Size</span>
                            <span class="font-mono font-bold text-emerald-400">${data.logs.size_mb} MB</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-slate-800/50 rounded-lg">
                            <span class="text-slate-400">Log Count</span>
                            <span class="font-mono font-bold text-white">${data.logs.count} File(s)</span>
                        </div>
                        <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden mt-2">
                            <div class="bg-emerald-500 h-full" style="width: ${Math.min(data.logs.size_mb, 100)}%"></div>
                        </div>
                    `;
                    
                    // Env
                    envEl.innerHTML = `
                        <div class="grid grid-cols-2 gap-2">
                            <div class="p-2 bg-slate-800/50 rounded flex justify-between"><span>PHP Version</span> <span class="text-white">${data.php.version}</span></div>
                            <div class="p-2 bg-slate-800/50 rounded flex justify-between"><span>cURL</span> <span class="${data.php.curl ? 'text-green-400' : 'text-red-400'}">${data.php.curl ? 'OK' : 'MISSING'}</span></div>
                            <div class="p-2 bg-slate-800/50 rounded flex justify-between"><span>JSON</span> <span class="${data.php.json ? 'text-green-400' : 'text-red-400'}">${data.php.json ? 'OK' : 'MISSING'}</span></div>
                            <div class="p-2 bg-slate-800/50 rounded flex justify-between"><span>Memory Limit</span> <span class="text-white">${data.php.memory_limit}</span></div>
                        </div>
                    `;
                }
            } catch (e) {
                diskEl.innerHTML = '<div class="text-red-400">Error loading diagnostics.</div>';
            }
        }

        async function cleanLogs() {
            if(!confirm('Are you sure? This will delete all history logs.')) return;
            
            showToast('Maintenance', 'Cleaning logs...');
            try {
                 const req = await fetch('../api/admin_doctor.php?action=clean_logs');
                 const res = await req.json();
                 if (res.success) {
                     showToast('Success', res.message);
                     loadHealth(); // Refresh
                 }
            } catch(e) {
                showToast('Error', 'Failed to clean logs');
            }
        }

        function showToast(title, msg) {
            const t = document.getElementById('toast');
            document.getElementById('toastTitle').innerText = title;
            document.getElementById('toastMessage').innerText = msg;
            t.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => t.classList.add('translate-y-20', 'opacity-0'), 3000);
        }

        loadHealth();
    </script>
</body>
</html>
