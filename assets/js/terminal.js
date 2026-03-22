document.addEventListener('DOMContentLoaded', () => {
    const terminalContent = document.getElementById('terminalContent');
    const MAX_LINES = 100;

    function appendLog(text) {
        const div = document.createElement('div');
        div.className = 'text-green-500/80 font-mono text-xs md:text-sm border-b border-green-900/10 py-0.5';

        // Highlight keywords
        if (text.includes('Error') || text.includes('Failed')) {
            div.className = 'text-red-400 font-mono text-xs md:text-sm border-b border-red-900/10 py-0.5 font-bold';
        } else if (text.includes('Success') || text.includes('Claimed')) {
            div.className = 'text-emerald-400 font-mono text-xs md:text-sm border-b border-emerald-900/10 py-0.5 font-bold';
        } else if (text.includes('SCHEDULER')) {
            div.className = 'text-blue-400 font-mono text-xs md:text-sm border-b border-blue-900/10 py-0.5';
        }

        // Add Timestamp if not present (simple check)
        if (!text.match(/^\d{4}-\d{2}-\d{2}/)) {
            const time = new Date().toLocaleTimeString();
            text = `[${time}] ${text}`;
        }

        div.textContent = text;
        terminalContent.appendChild(div);

        // Auto Scroll
        terminalContent.scrollTop = terminalContent.scrollHeight;

        // Limit lines
        while (terminalContent.children.length > MAX_LINES) {
            terminalContent.removeChild(terminalContent.firstChild);
        }
    }

    // Polling Logic instead of SSE (to prevent single-thread blocking)
    let lastLogPos = 0;
    let isFetching = false;

    async function fetchLogs() {
        if (isFetching) return;
        isFetching = true;

        try {
            const res = await fetch(`${window.API_BASE || 'api/'}live_logs.php?last_pos=${lastLogPos}`);
            const data = await res.json();

            if (data.success && data.logs.length > 0) {
                data.logs.forEach(line => appendLog(line));
                lastLogPos = data.newPos;
            }

            // If newPos is explicitly sent even with empty logs (e.g. log rotation)
            if (data.newPos && data.newPos !== lastLogPos) {
                lastLogPos = data.newPos;
            }

        } catch (e) {
            // console.error("Log Poll Error", e);
        } finally {
            isFetching = false;
        }
    }

    // Poll every 2 seconds
    setInterval(fetchLogs, 2000);
    fetchLogs(); // Initial call
});
