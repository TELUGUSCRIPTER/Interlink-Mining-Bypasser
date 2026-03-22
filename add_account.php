<?php
require_once __DIR__ . '/lib/SessionManager.php';
// Use secure session check.
// If valid, it returns true. If not, it returns an error string.
// We redirect to login if not valid.
$check = SessionManager::checkSession();
if ($check !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Account - Interlink</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0f172a', 800: '#1e293b' },
                        primary: '#6366f1',
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
        .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-white font-sans antialiased flex items-center justify-center p-4">

    <div class="max-w-md w-full glass p-8 rounded-2xl shadow-2xl relative">
        <a href="index.php" class="absolute top-6 left-6 text-slate-400 hover:text-white transition-colors">← Back</a>
        
        <div class="text-center mb-8 mt-4">
            <div class="w-16 h-16 bg-gradient-to-tr from-primary to-purple-500 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-primary/30 text-2xl">
                👤
            </div>
            <h1 class="text-2xl font-bold">Connect Account</h1>
            <p class="text-slate-400 text-sm mt-1">Enter details to start mining</p>
        </div>

        <!-- Step 1: Input Form -->
        <div id="step1">
            <form id="addAccountForm" onsubmit="handleRequestOtp(event)" class="space-y-4">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Email Address</label>
                    <input type="email" id="email" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-white placeholder-slate-600" placeholder="user@example.com">
                </div>
                
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Passcode (6 Digits)</label>
                    <input type="text" id="passcode" required maxlength="6" pattern="\d{6}" title="Passcode must be exactly 6 digits" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-white placeholder-slate-600" placeholder="123456" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)">
                </div>

                <div>
                    <label class="block text-sm text-slate-400 mb-1">Interlink ID</label>
                    <input type="text" id="interlinkId" required pattern="\d+" title="Interlink ID must contain numbers only" class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-white placeholder-slate-600" placeholder="888123..." oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>

                <button type="submit" id="requestBtn" class="w-full bg-gradient-to-r from-primary to-indigo-600 hover:from-primary/90 hover:to-indigo-600/90 py-3.5 rounded-xl font-bold shadow-lg shadow-primary/25 transition-all mt-2">
                    Request OTP
                </button>
            </form>
        </div>

        <!-- Step 2: OTP Form (Hidden initially) -->
        <div id="step2" class="hidden">
            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-4 mb-6 text-center">
                <p class="text-sm text-indigo-200">OTP sent to <span id="displayEmail" class="font-bold"></span></p>
            </div>
            
            <form id="verifyOtpForm" onsubmit="handleVerifyOtp(event)" class="space-y-4">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Enter OTP Code</label>
                    <input type="number" id="otp" required class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-white text-center text-xl tracking-widest" placeholder="• • • • • •">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button type="button" onclick="resetForm()" class="w-full bg-slate-700 hover:bg-slate-600 py-3 rounded-xl font-medium transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="verifyBtn" class="w-full bg-gradient-to-r from-emerald-500 to-emerald-700 hover:opacity-90 py-3 rounded-xl font-bold shadow-lg shadow-emerald-500/20 transition-all">
                        Verify & Save
                    </button>
                </div>
            </form>
        </div>

    </div>

    <!-- Notification Toast -->
    <div id="toast" class="fixed bottom-5 right-5 px-6 py-4 rounded-xl glass border-l-4 border-primary translate-y-20 opacity-0 transition-all duration-300 shadow-2xl z-50">
        <h4 class="font-bold" id="toastTitle">Notification</h4>
        <p class="text-sm text-slate-300" id="toastMessage">Message content</p>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>
