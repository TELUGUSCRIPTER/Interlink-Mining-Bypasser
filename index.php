<?php
require_once __DIR__ . '/lib/SessionManager.php';
$check = SessionManager::checkSession();
$isLoggedIn = ($check === true);
if (!$isLoggedIn && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    // If checking specifically for protection, we might want to redirect here?
    // The logic is handled by JS below if not logged in.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interlink Auto Claimer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            900: '#0f172a',
                            800: '#1e293b',
                            700: '#334155',
                        },
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
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="text-white font-sans antialiased selection:bg-primary selection:text-white overflow-hidden">

    <!-- Page Loader -->
    <div id="pageLoader" class="fixed inset-0 z-[100] bg-[#0f172a] flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="relative mb-4">
            <div class="w-16 h-16 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center font-bold text-xs text-indigo-500">INT</div>
        </div>
        <p class="text-slate-400 text-sm font-medium animate-pulse">
            <?php echo $isLoggedIn ? 'Initializing Panel...' : 'Checking Session...'; ?>
        </p>
    </div>

    <script>
        window.addEventListener('load', () => {
             const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
             const loader = document.getElementById('pageLoader');
             
             if (isLoggedIn) {
                 setTimeout(() => {
                     loader.classList.add('opacity-0');
                     setTimeout(() => {
                         loader.remove();
                         document.body.classList.remove('overflow-hidden');
                     }, 500);
                 }, 500);
             } else {
                 setTimeout(() => {
                     window.location.href = 'login.php';
                 }, 1000);
             }
        });
    </script>

    <?php if ($isLoggedIn): ?>
    <div class="container mx-auto px-4 py-8">
        
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-center mb-6 md:mb-10 glass p-6 rounded-2xl shadow-2xl gap-6 md:gap-0">
            <div class="text-center md:text-left">
                <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-accent">
                    Interlink Claimer
                </h1>
                <p class="text-slate-400 text-sm mt-1">Automated Mining & Management Panel</p>
            </div>
            <div class="flex flex-wrap justify-center gap-3 items-center w-full md:w-auto">
                <a href="add_account.php" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-primary to-accent hover:opacity-90 transition-all shadow-lg shadow-primary/20 font-medium text-sm">
                    + Add
                </a>
                <a href="group-mine/dashboard.php" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 transition-all shadow-lg font-medium text-sm flex items-center gap-2">
                    👥 Group Mine
                </a>
                <button onclick="runMiner()" id="minerBtn" class="px-4 py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 border border-slate-600 transition-all font-medium flex items-center gap-2 text-sm">
                    <span id="minerIcon">⛏️</span> <span>Run Miner</span>
                </button>
                <a href="logout.php" class="px-4 py-2.5 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-600/30 transition-all font-medium text-sm">
                    Exit
                </a>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="glass p-6 rounded-2xl border-l-4 border-indigo-500 hover:translate-y-[-2px] transition-transform">
                <h3 class="text-slate-400 text-sm font-medium uppercase tracking-wider">Total Accounts</h3>
                <p class="text-3xl font-bold mt-2" id="totalAccounts">0</p>
            </div>
            <div class="glass p-6 rounded-2xl border-l-4 border-emerald-500 hover:translate-y-[-2px] transition-transform">
                <h3 class="text-slate-400 text-sm font-medium uppercase tracking-wider">Active Miners</h3>
                <p class="text-3xl font-bold mt-2" id="activeMiners">0</p>
            </div>
             <div class="glass p-6 rounded-2xl border-l-4 border-amber-500 hover:translate-y-[-2px] transition-transform">
                <h3 class="text-slate-400 text-sm font-medium uppercase tracking-wider">Total Claims</h3>
                <p class="text-3xl font-bold mt-2" id="totalClaims">0</p>
            </div>
            <div class="glass p-6 rounded-2xl border-l-4 border-amber-500 hover:translate-y-[-2px] transition-transform">
                <h3 class="text-slate-400 text-sm font-medium uppercase tracking-wider">Total Earnings</h3>
                <p class="text-3xl font-bold mt-2 text-amber-400" id="totalITLG">0.00</p>
                <p class="text-xs text-amber-500/50 font-bold tracking-wider mt-1">ITLG</p>
            </div>
        </div>
        
        <!-- Analytics Chart -->
        <div class="glass p-6 rounded-2xl mb-10 border border-slate-700/50">
            <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                <span>📊</span> Only Successful Claims Activity (24h)
            </h3>
            <div class="h-64 w-full">
                <canvas id="activityChart"></canvas>
            </div>
        </div>



        <!-- Accounts Table -->
        <div class="glass rounded-2xl overflow-hidden shadow-2xl mb-10">
            <div class="p-6 border-b border-slate-700/50 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white">Connected Accounts</h2>
                <button onclick="loadAccounts()" class="text-sm text-slate-400 hover:text-white transition-colors">
                    ↻ Refresh
                </button>
            </div>
            <div id="accountsList" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-6 min-h-[200px]">
                <!-- Cards will be injected here -->
                <div class="col-span-full text-center text-slate-500 py-10 animate-pulse">Loading accounts...</div>
            </div>
        </div>



    <!-- Modals & Overlays -->
    
    <!-- Auto Miner Confirmation Modal -->
    <div id="cronModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-slate-800 p-8 rounded-2xl shadow-2xl max-w-md w-full border border-slate-700">
            <h3 class="text-xl font-bold mb-4">Setup Auto Miner</h3>
            <p class="text-slate-400 mb-6">
                You are about to schedule an automated mining job for <span id="cronAccountCount" class="text-white font-bold">0</span> accounts.
                This will run every 4 hours automatically.
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeCronModal()" class="px-4 py-2 text-slate-400 hover:text-white transition-colors">Cancel</button>
                <button onclick="confirmAutoMiner()" id="confirmCronBtn" class="px-6 py-2 bg-primary hover:bg-indigo-500 rounded-xl font-bold shadow-lg shadow-primary/20 transition-all">
                    Confirm & Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- Auto Miner Suggestion Modal -->
    <div id="suggestModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-[70]">
        <div class="bg-slate-800 p-8 rounded-2xl shadow-2xl max-w-md w-full border border-slate-700">
            <h3 class="text-xl font-bold mb-4 text-white">Automate Mining?</h3>
            <p class="text-slate-400 mb-6">
                The next mining cycle is available at <span id="suggestTime" class="text-white font-bold">--:--</span>.
                <br><br>
                Would you like to enable the Auto Miner to run automatically starting at this time?
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeSuggestModal()" class="px-4 py-2 text-slate-400 hover:text-white transition-colors">No, Thanks</button>
                <button onclick="confirmSuggestAutoMiner()" id="confirmSuggestBtn" class="px-6 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 rounded-xl font-bold shadow-lg shadow-emerald-500/20 text-white transition-all">
                    Enable & Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-5 right-5 px-6 py-4 rounded-xl glass border-l-4 border-primary translate-y-20 opacity-0 transition-all duration-300 shadow-2xl z-50">
        <h4 class="font-bold" id="toastTitle">Notification</h4>
        <p class="text-sm text-slate-300" id="toastMessage">Message content</p>
    </div>

    <!-- History Modal -->
    <div id="historyModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-slate-900 rounded-2xl shadow-2xl max-w-4xl w-full border border-slate-700 h-[80vh] flex flex-col overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-slate-700 flex justify-between items-center bg-slate-800/50">
                <div>
                    <h3 class="text-xl font-bold text-white">Mining History</h3>
                    <p class="text-slate-400 text-sm" id="historyEmail">user@example.com</p>
                </div>
                <button onclick="closeHistoryModal()" class="text-slate-400 hover:text-white transition-colors bg-slate-800 p-2 rounded-lg hover:bg-slate-700">✕ Close</button>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-slate-700 bg-slate-800/30">
                <button onclick="switchHistoryTab('logs')" id="tabBtn-logs" class="flex-1 py-4 text-sm font-bold text-white border-b-2 border-primary bg-slate-800/50">
                    Activity Logs
                </button>
                <button onclick="switchHistoryTab('cron')" id="tabBtn-cron" class="flex-1 py-4 text-sm font-bold text-slate-400 border-b-2 border-transparent hover:text-slate-200">
                    Cron Schedule
                </button>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 overflow-hidden relative">
                
                <!-- Tab: Logs -->
                <div id="tabContent-logs" class="absolute inset-0 overflow-y-auto overflow-x-auto p-0">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-800 text-slate-400 uppercase font-semibold sticky top-0 shadow-lg z-10">
                            <tr>
                                <th class="px-6 py-4">Time</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300" id="historyList">
                            <!-- Rows -->
                        </tbody>
                    </table>
                </div>

                <!-- Tab: Cron Details -->
                <div id="tabContent-cron" class="absolute inset-0 overflow-y-auto p-8 hidden">
                    
                    <div id="cronNotActive" class="hidden flex flex-col items-center justify-center h-full text-slate-500">
                        <div class="text-5xl mb-4">💤</div>
                        <p class="text-lg">Auto-Mining is disabled for this account.</p>
                    </div>

                    <div id="cronActiveContent" class="hidden space-y-6">
                        <!-- Main Status Card -->
                        <div class="bg-slate-800/50 p-6 rounded-2xl border border-slate-700">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-lg font-bold text-white flex items-center gap-2">
                                         <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span> 
                                         Active Schedule
                                    </h4>
                                    <p class="text-slate-400 text-sm mt-1" id="cronTitle">Interlink Miner</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-bold border border-emerald-500/20">ENABLED</span>
                                </div>
                            </div>
                        </div>

                        <!-- Timing Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div class="bg-slate-800/30 p-6 rounded-2xl border border-slate-700/50">
                                <h5 class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-2">Next Run</h5>
                                <p class="text-2xl font-bold text-white font-mono" id="nextRunTime">Calculating...</p>
                                <p class="text-slate-500 text-xs mt-1">Estimated based on schedule</p>
                             </div>
                             
                             <div class="bg-slate-800/30 p-6 rounded-2xl border border-slate-700/50">
                                <h5 class="text-slate-400 text-xs uppercase font-bold tracking-wider mb-2">Configuration</h5>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Timezone</span>
                                        <span class="text-white font-mono" id="cronTimezone">Asia/Kolkata</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Frequency</span>
                                        <span class="text-white">Every 4 Hours</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-400">Event ID</span>
                                        <span class="text-slate-500 font-mono text-xs" id="cronEventId">...</span>
                                    </div>
                                </div>
                             </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/miner.js"></script>
    <script src="assets/js/history.js"></script>
    <script src="assets/js/analytics.js"></script>


    <script src="assets/js/init.js"></script>
    <?php endif; ?>
</body>
</html>
