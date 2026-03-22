<?php
require_once __DIR__ . '/../lib/SessionManager.php';
SessionManager::start();

// Admin Auth Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
$isLoggedIn = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b', 700: '#334155' },
                        primary: '#f43f5e', // Rose
                        accent: '#8b5cf6'
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #3f1926 100%);
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
<body class="text-white font-sans antialiased overflow-hidden">

    <!-- Page Loader -->
    <div id="pageLoader" class="fixed inset-0 z-[100] bg-[#0f172a] flex flex-col items-center justify-center transition-opacity duration-500">
        <div class="relative mb-4">
             <div class="w-16 h-16 border-4 border-rose-500/30 border-t-rose-500 rounded-full animate-spin"></div>
        </div>
        <p class="text-slate-400 text-sm font-medium animate-pulse">Loading Admin Panel...</p>
    </div>

    <script>
        // Set API Base immediately for Admin Context
        window.API_BASE = '../api/';

        window.addEventListener('load', () => {
             const loader = document.getElementById('pageLoader');
             setTimeout(() => {
                 loader.classList.add('opacity-0');
                 setTimeout(() => {
                     loader.remove();
                     document.body.classList.remove('overflow-hidden');
                 }, 500);
             }, 300);
        });
    </script>

    <div class="container mx-auto px-4 py-8">
        
        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-center mb-10 glass p-4 md:p-6 rounded-2xl shadow-2xl gap-6 md:gap-0 border-l-4 border-rose-500">
            <div class="text-center md:text-left">
                <h1 class="text-2xl md:text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-rose-500 to-purple-600">
                    Admin Panel
                </h1>
                <p class="text-slate-400 text-xs md:text-sm mt-1">Global System Overview</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden md:block px-4 py-2 bg-slate-800/50 rounded-lg text-xs font-mono text-slate-400 border border-slate-700">
                    USER: <span class="text-white font-bold"><?php echo $_SESSION['username'] ?? 'Admin'; ?></span>
                </div>
                <a href="logout.php" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-xs md:text-sm font-medium transition-colors">
                    Logout
                </a>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Total Accounts -->
            <div class="glass p-6 rounded-2xl border-l-4 border-indigo-500">
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider">Total Interlink Accounts</h3>
                <p class="text-4xl font-bold mt-2" id="totalAccounts">0</p>
                <div class="mt-2 text-xs text-slate-500">Across all users</div>
            </div>
            
            <!-- Active Miners -->
            <div class="glass p-6 rounded-2xl border-l-4 border-emerald-500">
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider">Active Miners</h3>
                <p class="text-4xl font-bold mt-2 text-emerald-400" id="activeMiners">0</p>
                <div class="mt-2 text-xs text-slate-500">Currently scheduled</div>
            </div>

            <!-- Total Claims Today -->
            <div class="glass p-6 rounded-2xl border-l-4 border-amber-500">
                <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider">Total Claims (Today)</h3>
                <p class="text-4xl font-bold mt-2 text-amber-400" id="totalClaims">0</p>
                <div class="mt-2 text-xs text-slate-500">Global successful claims</div>
            </div>
        </div>
        
        <!-- Administrative Actions Card -->
        <div class="glass p-6 rounded-2xl mb-10 border border-slate-700/50">
            <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                <span>⚡</span> Administrative Actions
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <button onclick="runGlobalMiners()" class="col-span-2 md:col-span-1 px-4 py-3 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-rose-900/20 flex flex-col items-center justify-center gap-1">
                    <span class="text-xl">⚡</span> 
                    <span>Run All Miners</span>
                </button>
                <button onclick="runGlobalGroupMiners()" class="col-span-2 md:col-span-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-indigo-900/20 flex flex-col items-center justify-center gap-1">
                    <span class="text-xl">👥</span> 
                    <span>Run All Groups</span>
                </button>
                <button onclick="rotateFingerprints()" class="px-4 py-3 bg-cyan-900/30 hover:bg-cyan-800/50 text-cyan-200 border border-cyan-800/50 rounded-xl text-xs font-bold transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-xl">🔄</span> 
                    <span>Rotate FPS</span>
                </button>
                <a href="doctor.php" class="px-4 py-3 bg-teal-900/30 hover:bg-teal-800/50 text-teal-200 border border-teal-800/50 rounded-xl text-xs font-bold transition-all flex flex-col items-center justify-center gap-1 text-center decoration-0">
                    <span class="text-xl">🩺</span> 
                    <span>System Doctor</span>
                </a>
                <button onclick="clearCompletedEvents()" class="px-4 py-3 bg-amber-900/30 hover:bg-amber-800/50 text-amber-200 border border-amber-800/50 rounded-xl text-xs font-bold transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-xl">🧹</span> 
                    <span>Clear Completed</span>
                </button>
                <button onclick="clearAllEvents()" class="px-4 py-3 bg-pink-900/30 hover:bg-pink-800/50 text-pink-200 border border-pink-800/50 rounded-xl text-xs font-bold transition-all flex flex-col items-center justify-center gap-1">
                    <span class="text-xl">☢️</span> 
                    <span>Reset All</span>
                </button>
            </div>
        </div>

        <!-- Global Graph -->
        <div class="glass p-6 rounded-2xl mb-10 border border-slate-700/50">
            <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                <span>📈</span> Global Claims Activity (24h)
            </h3>
            <div class="h-64 w-full">
                <canvas id="adminChart"></canvas>
            </div>
        </div>

        <!-- Section: Interlink Accounts -->
        <div class="mb-10">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <span>🔗</span> All Connected Interlink Accounts
            </h2>
            <div class="glass rounded-2xl overflow-hidden shadow-2xl overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                    <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase font-bold border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-4">Account</th>
                            <th class="px-6 py-4">Owner (User)</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Auto-Mining</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="interlinkList" class="divide-y divide-slate-800/30">
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">Loading data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: User Management -->
        <div class="mb-20">
             <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <span>👥</span> User Management (Panel Users)
            </h2>
            <div id="usersGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- User Cards Injected Here -->
                <div class="col-span-full py-8 text-center text-slate-500">Loading users...</div>
            </div>
        </div>

    </div>



    <!-- Global Runner Modal -->
    <div id="runModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm hidden">
        <div class="bg-slate-900 w-full max-w-2xl rounded-2xl border border-rose-500/30 shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">
            <div class="p-4 border-b border-slate-800 bg-slate-800/50 flex justify-between items-center">
                <h3 class="font-bold text-white flex items-center gap-2"><span>⚡</span> Global Miner Execution</h3>
                <!-- Optional Close X -->
                <button onclick="if(!isRunningGlobal) document.getElementById('runModal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">✕</button>
            </div>
            <div class="p-4 bg-black/20 font-mono text-sm text-amber-400 border-b border-slate-800" id="runStatus">
                Ready to start...
            </div>
            <div class="p-4 overflow-y-auto flex-1 bg-black/40 font-mono text-xs space-y-1 min-h-[300px]" id="runLog">
               <!-- Logs injected here -->
            </div>
            <div class="p-4 border-t border-slate-800 bg-slate-800/50 flex justify-end">
                <button onclick="document.getElementById('runModal').classList.add('hidden')" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm font-medium transition-colors">Close Window</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-5 right-5 px-6 py-4 rounded-xl glass border-l-4 border-primary translate-y-20 opacity-0 transition-all duration-300 shadow-2xl z-50">
        <h4 class="font-bold" id="toastTitle">Notification</h4>
        <p class="text-sm text-slate-300" id="toastMessage">Message content</p>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/utils.js?v=<?php echo time() + 2; ?>"></script>
    <script src="../assets/js/admin.js?v=<?php echo time() + 2; ?>"></script>

</body>
</html>
