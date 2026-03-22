# ⛏️ Interlink Mining Bypasser

<div align="center">

**Fully automated mining bot for [Interlink Labs (ITLG)](https://interlinklabs.ai)** — claims airdrop rewards every 4 hours with advanced anti-detection, group mining, proxy support, and real-time Telegram alerts.

[![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Telegram](https://img.shields.io/badge/Telegram-Bot-2CA5E0?style=for-the-badge&logo=telegram&logoColor=white)](https://core.telegram.org/bots)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

</div>

---

## 🌐 Live Demo — Try It Now!

Want to see the panel in action **without setting it up yourself?** A live hosted instance is available for everyone to try:

<div align="center">

### 🔗 **[Open Interlink Panel (Live)](http://13.62.94.74/InterLink/)**

</div>

**Demo Login Credentials:**

| Field | Value |
|-------|-------|
| **Panel URL** | [http://13.62.94.74/InterLink/](http://13.62.94.74/InterLink/) |
| **Username** | `demouser` |
| **Password** | `demouser1` |

> **How to login:** Go to the panel link above → Enter the username and password → You'll have full access to the dashboard where you can add your Interlink accounts, run the miner, view analytics, and more.

### 🌐 Proxy Manager

If you have proxies (HTTP/HTTPS/SOCKS4/SOCKS5), you can add them and assign proxy support to your specific Interlink accounts for better IP isolation and anti-detection:

| Feature | Link |
|---------|------|
| **Proxy Manager** | [http://13.62.94.74/InterLink/proxy/](http://13.62.94.74/InterLink/proxy/) |

> **How it works:** Navigate to the Proxy Manager → Add your proxy details (host, port, type, credentials) → Test the connection → Assign the proxy to any of your connected Interlink accounts. Each account will then route its mining requests through its assigned proxy, ensuring unique IP addresses per account.

---

## 📌 What Is This?

Interlink Mining Bypasser is a **self-hosted PHP web panel** that fully automates the Interlink Labs token (ITLG) mining lifecycle. Instead of manually opening the app every 4 hours to claim mining rewards, this tool does everything for you — 24/7, across multiple accounts simultaneously.

### The Problem It Solves

Interlink Labs requires users to manually claim mining rewards every **4 hours** from their mobile app. Missing a cycle means losing tokens. This tool:

- 🔄 **Automates the entire claim cycle** — login, check claimability, claim, and reschedule
- 👥 **Manages multiple accounts** from a single dashboard
- 🛡️ **Bypasses bot detection** by mimicking the real mobile app's behavior
- 📲 **Sends Telegram alerts** so you always know what's happening

---

## ✨ Features

### ⛏️ Automated Solo Mining
| Feature | Description |
|---------|-------------|
| **4-Hour Auto-Claim** | Uses Cronicle job scheduler to trigger mining at the exact claimable timestamp |
| **Anti-Ban Jitter** | Adds 5–20 min random delay to each cycle to avoid detection patterns |
| **Smart Retry Logic** | Retries failed claims up to 3× with 2-second backoff |
| **Fallback Scheduling** | Falls back to 1-hour retry if claim timestamp is invalid |
| **Per-Account Toggle** | Enable/disable auto-mining for individual accounts |

### 👥 Group Mining
| Feature | Description |
|---------|-------------|
| **Create & Join Groups** | Form mining groups of up to 5 members for bonus rewards |
| **Invite System** | Generate shareable invite codes & links |
| **Auto Group Mining** | Toggle per-group auto-claim with separate cron jobs |
| **Member Management** | View group members, rewards, and claim status |
| **Live Countdowns** | Real-time countdown to next claimable window |

### 🛡️ Advanced Anti-Bot Evasion

This is where the tool shines — it mimics the **real Interlink mobile app** at every level:

| Technique | What It Does |
|-----------|-------------|
| **TLS Fingerprint Spoofing** | Mimics `okhttp/4.12.0` cipher suites to match JA3/JA4 fingerprints |
| **Device Fingerprinting** | Unique `deviceId`, `model`, `brand`, and `User-Agent` per account |
| **HMAC-SHA256 Signing** | Generates `x-signature` and `x-content-hash` matching the app's signing scheme |
| **Anti-Replay Timestamps** | Unique millisecond timestamps per request — no duplicates ever |
| **Header Order Matching** | HTTP headers sent in the exact order captured from real app traffic |
| **IP Rotation** | Randomized `X-Forwarded-For` / `X-Real-IP` headers per client instance |
| **HTTP/2 + ALPN** | Uses HTTP/2 with ALPN negotiation like the real app |
| **Connection Pooling** | Persistent cURL handles with keep-alive for realistic session behavior |

### 🌐 Proxy Support
| Feature | Description |
|---------|-------------|
| **Proxy Manager Dashboard** | Full UI to add, test, and assign proxies per account |
| **Multi-Protocol** | HTTP, HTTPS, SOCKS4, SOCKS5 support |
| **Proxy Auth** | Username/password authentication |
| **Health Testing** | Verify proxy connectivity before assigning |
| **Per-Account Isolation** | Each account routes through its own proxy for unique IP |

### 📲 Telegram Notifications

Real-time alerts pushed directly to your Telegram:

- ✅ **Successful claims** — with ITLG amount earned
- ❌ **Errors & failures** — with detailed error context
- 📅 **Schedule updates** — created / updated / recovered
- ♻️ **Auto-recovery** — when cron events are found and recovered
- 🗑️ **One-tap delete** — remove problematic accounts directly from Telegram inline buttons

### 📊 User Dashboard
| Feature | Description |
|---------|-------------|
| **Stats Overview** | Total accounts, active miners, total claims, total ITLG earnings |
| **24h Activity Chart** | Real-time Chart.js analytics for claim activity |
| **Account Cards** | Per-account status, balance, last claim time, and mining toggle |
| **Mining History** | Full activity logs + cron schedule details per account |
| **Auto-Miner Prompt** | Suggests scheduling after manual mining |

### 🔐 Admin Panel
| Feature | Description |
|---------|-------------|
| **Separate Admin Auth** | Independent admin login with session management |
| **Global Miner** | Run miner across ALL accounts at once with live streaming logs |
| **Global Group Miner** | Run group mining across all accounts simultaneously |
| **Fingerprint Rotation** | Rotate device fingerprints for all accounts in one click |
| **Doctor Mode** | Diagnose and fix orphaned/broken cron events |
| **Reset All** | Nuclear option for full cleanup of all cron events |
| **User Management** | Manage panel users (multi-user support) |
| **Cross-Account Analytics** | Global claims activity graph |

### 🔑 Secure Account Onboarding
| Feature | Description |
|---------|-------------|
| **OTP-Based Login** | Mirrors the real app's auth flow: email → passcode → OTP verification |
| **JWT Token Management** | Auto-detects token expiry, warns on expiring tokens |
| **Session Security** | PHP session management with auth guards on all pages |
| **Auto-Cleanup** | Expired accounts are auto-deleted and notified via Telegram |

---

## 🏗️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 7.4+ (vanilla, no framework) |
| **Frontend** | HTML, JavaScript, TailwindCSS (CDN) |
| **Charts** | Chart.js |
| **Job Scheduler** | [Cronicle](https://github.com/jhuckaby/Cronicle) (self-hosted cron) |
| **Notifications** | Telegram Bot API |
| **Data Storage** | JSON flat-file (no database required) |
| **Hosting** | Any PHP-capable server (Apache / Nginx / PHP built-in) |

---

## 📁 Project Structure

```
Interlink-Mining-Bypasser/
├── index.php                  # Main user dashboard
├── login.php                  # User authentication (username + security code)
├── add_account.php            # OTP-based Interlink account onboarding
├── logout.php                 # Session logout
│
├── admin/                     # 🔐 Admin Panel
│   ├── index.php              # Admin dashboard (stats, controls, user management)
│   ├── login.php              # Admin authentication
│   ├── doctor.php             # Cron event diagnostics & repair
│   └── logout.php             # Admin logout
│
├── api/                       # ⚡ Backend API Endpoints
│   ├── config.php             # App configuration (API keys, timezone)
│   ├── config.example.php     # Template config for new setups
│   ├── miner.php              # Solo mining engine (core logic)
│   ├── group_miner.php        # Group mining engine
│   ├── balance.php            # Account balance checker
│   ├── auth_request.php       # OTP request handler
│   ├── auth_verify.php        # OTP verification handler
│   ├── accounts.php           # Account listing API
│   ├── account_details.php    # Single account details API
│   ├── schedule_cron.php      # Cron job scheduling API
│   ├── schedule_events.php    # Cron event listing API
│   ├── toggle_mining.php      # Mining enable/disable toggle
│   ├── delete_account.php     # Account deletion API
│   ├── telegram_delete_account.php  # Telegram-triggered account deletion
│   ├── stats.php              # Dashboard statistics API
│   ├── analytics.php          # Claims analytics data API
│   ├── recent_claims.php      # Recent claims feed
│   ├── history.php            # Mining history API
│   ├── live_logs.php          # Live log streaming
│   ├── stream_logs.php        # SSE log streaming endpoint
│   ├── admin_dashboard.php    # Admin stats API
│   ├── admin_actions.php      # Admin bulk operations API
│   ├── admin_run_miner.php    # Global miner execution API
│   ├── admin_run_group.php    # Global group miner execution API
│   ├── admin_doctor.php       # Cron diagnostics API
│   ├── admin_cleanup_events.php # Orphan cron cleanup API
│   └── update_status.php      # Account status update API
│
├── group-mine/                # 👥 Group Mining Module
│   ├── dashboard.php          # Group mining dashboard UI
│   ├── index.php              # Redirect
│   └── api/
│       └── proxy.php          # Group mining API proxy
│
├── lib/                       # 📚 Core Libraries
│   ├── InterlinkClient.php    # Interlink API client (anti-bot, TLS, signing)
│   ├── MiningService.php      # Mining business logic & orchestration
│   ├── SchedulerService.php   # Cronicle job scheduling service
│   ├── Cronicle.php           # Cronicle API wrapper (CRUD events)
│   ├── TelegramBot.php        # Telegram Bot notification service
│   ├── SessionManager.php     # Auth, sessions & remember-me tokens
│   ├── ProxyTester.php        # Proxy connectivity tester
│   └── Utils.php              # Data management, logging & utilities
│
├── proxy/                     # 🌐 Proxy Manager
│   ├── index.php              # Proxy management dashboard UI
│   └── api/                   # Proxy CRUD APIs
│       ├── save.php           # Save proxy config
│       ├── list.php           # List proxies
│       ├── delete.php         # Delete proxy
│       ├── test.php           # Test proxy connectivity
│       ├── accounts.php       # Proxy-account assignments
│       └── verify_target.php  # Verify proxy target
│
├── assets/js/                 # 🎨 Frontend JavaScript
│   ├── dashboard.js           # Dashboard logic
│   ├── miner.js               # Miner controls
│   ├── auth.js                # Authentication flows
│   ├── admin.js               # Admin panel logic
│   ├── analytics.js           # Chart.js analytics
│   ├── history.js             # Mining history viewer
│   ├── timeline.js            # Timeline visualization
│   ├── terminal.js            # Live log terminal
│   ├── utils.js               # Shared utilities
│   └── init.js                # Dashboard initialization
│
├── data/                      # 💾 Data Store (gitignored)
│   ├── accounts.json          # Interlink account credentials
│   ├── tokens.json            # JWT auth tokens
│   ├── remember_tokens.json   # Session remember-me tokens
│   ├── proxies.json           # Proxy configurations
│   ├── visitors.txt           # Visitor counter
│   ├── users/                 # Per-user data directories
│   └── logs/                  # System & mining logs
│
└── .gitignore                 # Git exclusions (data, config, logs)
```

---

## ⚙️ Setup & Installation

### Prerequisites

- **PHP 7.4+** with extensions: `curl`, `json`, `openssl`, `mbstring`
- **Web Server** — Apache, Nginx, or PHP built-in dev server
- **[Cronicle](https://github.com/jhuckaby/Cronicle)** — Self-hosted job scheduler (for automated 4-hour cycles)
- **Telegram Bot** _(optional)_ — For real-time notification alerts

### Step 1: Clone the Repository

```bash
git clone https://github.com/TELUGUSCRIPTER/Interlink-Mining-Bypasser.git
cd Interlink-Mining-Bypasser
```

### Step 2: Configure

Copy the example config and fill in your values:

```bash
cp api/config.example.php api/config.php
```

Edit `api/config.php`:

```php
// Timezone
define('APP_TIMEZONE', 'Asia/Kolkata');

// Cronicle Job Scheduler
define('CRONICLE_API_URL', 'http://your-server:3012/api/app/create_event/v1');
define('CRONICLE_API_KEY', 'your-cronicle-api-key');

// Telegram Bot (optional but recommended)
define('TELEGRAM_BOT_TOKEN', 'your-bot-token-from-botfather');
define('TELEGRAM_USER_ID', 'your-chat-id');
```

### Step 3: Set Up Cronicle

1. Install [Cronicle](https://github.com/jhuckaby/Cronicle) on your server
2. Create a **URL Plugin** (or use the built-in HTTP plugin)
3. Note down the **API Key** and **Plugin ID**
4. Update `config.php` with these values

### Step 4: Set Up Telegram Bot _(Optional)_

1. Message [@BotFather](https://t.me/BotFather) on Telegram → `/newbot`
2. Copy the bot token → paste into `config.php`
3. Get your Chat ID from [@userinfobot](https://t.me/userinfobot)
4. Paste into `config.php`

### Step 5: Deploy & Run

```bash
# Option A: PHP Built-in Server (development)
php -S 0.0.0.0:8080

# Option B: Apache/Nginx (production)
# Point document root to the project directory
```

### Step 6: Update Admin Credentials

Edit `admin/login.php` and change the default credentials:

```php
// Default: admin / admin123 — CHANGE THESE!
if ($username === 'your_username' && $password === 'your_password') {
```

---

## 🚀 Usage

### Adding Your First Account

1. **Login** to the web panel at `http://your-server:8080`
2. Click **"+ Add"** to add an Interlink account
3. Enter your **Email**, **Passcode** (6 digits), and **Interlink ID**
4. Click **Request OTP** → check your email → enter the OTP code
5. Account is now connected and ready to mine!

### Manual Mining

- Click the **"⛏️ Run Miner"** button on the dashboard
- The system will check all accounts, claim available rewards, and show results
- After mining, you'll be prompted to **enable Auto Miner**

### Automated Mining (Recommended)

1. After a manual mine, click **"Enable & Schedule"** when prompted
2. The system creates a Cronicle cron job that runs every 4 hours automatically
3. Each cycle: check → claim → reschedule → notify via Telegram
4. The jitter system adds random delays to avoid bot detection

### Group Mining

1. Navigate to **"👥 Group Mine"** from the dashboard
2. **Create a group** or **join** using an invite code
3. **Invite members** by their Interlink ID
4. Toggle **Auto** to enable automatic group claim scheduling
5. Group rewards scale with the number of active members

### Admin Panel

1. Navigate to `http://your-server:8080/admin/`
2. Login with your admin credentials
3. Access global controls:
   - **Run Global Miner** — mines all accounts at once with live logs
   - **Run Group Miner** — claims all group rewards globally
   - **Rotate Fingerprints** — refreshes device IDs for all accounts
   - **Doctor** — diagnoses and repairs broken cron events
4. View cross-account analytics and manage panel users

### Proxy Manager

1. Navigate to `http://your-server:8080/proxy/`
2. Add proxies (HTTP/HTTPS/SOCKS4/SOCKS5)
3. Test connectivity with one click
4. Assign proxies to specific accounts for IP isolation

---

## 🔒 How the Anti-Bot System Works

The tool mimics the Interlink mobile app at the protocol level:

```
┌─────────────────────────────────────────────────────┐
│                  REQUEST FLOW                        │
├─────────────────────────────────────────────────────┤
│                                                      │
│  1. Generate unique device fingerprint (per account) │
│  2. Create millisecond timestamp (anti-replay)       │
│  3. Build JSON body → SHA256 → x-content-hash        │
│  4. Compute HMAC-SHA256 signature using device key   │
│  5. Arrange headers in exact app traffic order       │
│  6. Apply TLS cipher suites (okhttp/4.12.0 match)   │
│  7. Route through assigned proxy (if configured)     │
│  8. Send via HTTP/2 with persistent connection       │
│  9. Parse response → handle errors → retry if needed │
│ 10. Schedule next run with randomized jitter         │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 📡 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/miner.php?email=...` | GET | Run solo miner for an account |
| `/api/group_miner.php?email=...` | GET | Run group miner for an account |
| `/api/balance.php?email=...` | GET | Check account balance |
| `/api/accounts.php` | GET | List all connected accounts |
| `/api/stats.php` | GET | Get dashboard statistics |
| `/api/analytics.php` | GET | Get 24h claims analytics |
| `/api/schedule_cron.php` | POST | Schedule auto-mining cron job |
| `/api/toggle_mining.php` | POST | Enable/disable mining for account |
| `/api/auth_request.php` | POST | Request OTP for account linking |
| `/api/auth_verify.php` | POST | Verify OTP and save account |
| `/api/delete_account.php` | POST | Delete an account |
| `/api/admin_run_miner.php` | GET | Run miner for ALL accounts (admin) |
| `/api/admin_run_group.php` | GET | Run group miner for ALL accounts |
| `/api/admin_actions.php` | POST | Admin bulk operations |
| `/api/admin_doctor.php` | GET | Diagnose cron health |

---

## 🤝 Contributing

Contributions are welcome! Feel free to:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## ⚠️ Disclaimer

This tool is provided for **educational and research purposes only**. Use at your own risk. The authors are not responsible for any account restrictions, bans, or violations of Interlink Labs' Terms of Service. By using this software, you acknowledge that automated interaction with third-party services may violate their terms.

---

## 📜 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

<div align="center">

**Built with ❤️ by [teluguscripter](https://t.me/teluguscripter)**

⭐ **Star this repo if it helped you!** ⭐

</div>
