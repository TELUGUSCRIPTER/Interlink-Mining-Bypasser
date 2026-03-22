
async function loadTimeline() {
    const container = document.getElementById('timelineContainer');
    if (!container) return;

    try {
        const res = await fetch('api/schedule_events.php');
        const json = await res.json();

        if (json.success && json.data.length > 0) {
            renderTimeline(json.data);
        } else {
            container.innerHTML = '<div class="text-slate-500 text-center text-sm py-4">No active schedules found. Activate auto-mining to see the timeline.</div>';
        }
    } catch (e) {
        console.error("Timeline Load Error", e);
        container.innerHTML = '<div class="text-rose-500 text-center text-sm py-4">Failed to load schedule.</div>';
    }
}

function renderTimeline(events) {
    const container = document.getElementById('timelineContainer');
    container.innerHTML = '';

    // Wrapper: Added snap-x for carousel effect
    const wrapper = document.createElement('div');
    wrapper.className = "flex items-center gap-0 overflow-x-auto pb-6 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-800/50 px-4 snap-x snap-mandatory";

    const now = Date.now() / 1000;

    events.forEach((evt, index) => {
        const isPast = evt.timestamp < now;

        const node = document.createElement('div');
        // snap-center keeps item in middle when scrolling, shrink-0 prevents squashing
        node.className = "flex items-center shrink-0 relative group snap-center pl-1";

        // Connector Line (except first)
        // Responsive width: shorter on mobile (w-6), longer on desktop (w-12)
        let connector = '';
        if (index > 0) {
            connector = `<div class="w-6 md:w-12 h-1 bg-slate-700 mx-1 md:mx-2 rounded-full overflow-hidden shrink-0">
                            <div class="h-full bg-indigo-500/50 w-0 group-hover:w-full transition-all duration-500"></div>
                         </div>`;
        }

        // Card Styling
        const statusColor = isPast ? 'bg-slate-700 border-slate-600 opacity-60' : (index === 0 ? 'bg-indigo-600 border-indigo-500 shadow-lg shadow-indigo-500/20' : 'bg-slate-800 border-slate-700');
        const textColor = isPast ? 'text-slate-400' : 'text-white';

        // Responsive min-width: 120px on mobile, 140px on md+
        const cardHtml = `
            ${connector}
            <div class="flex flex-col items-center">
                <div class="${statusColor} border px-3 py-3 md:px-4 rounded-xl transition-all hover:scale-105 flex flex-col items-center min-w-[120px] md:min-w-[140px] relative z-10 box-border">
                    
                    ${index === 0 ? '<div class="absolute -top-3 bg-emerald-500 text-white text-[9px] md:text-[10px] font-bold px-2 py-0.5 rounded-full shadow-lg animate-bounce whitespace-nowrap">NEXT UP</div>' : ''}
                    
                    <span class="text-[10px] md:text-xs font-mono opacity-70 mb-1 ${textColor}">${evt.timeStr}</span>
                    <span class="font-bold text-xs md:text-sm ${textColor} truncate max-w-[100px] md:max-w-[120px]" title="${evt.email}">${evt.email.split('@')[0]}</span>
                    
                    <div class="mt-2 text-[10px] flex gap-1 md:gap-2">
                        ${evt.proxy === 'Yes' ? '<span class="text-emerald-400">🛡️ Proxied</span>' : '<span class="text-slate-500">🌐 Direct</span>'}
                    </div>
                </div>
                
                <!-- Time Diff (visible on hover or active touch) -->
                <div class="opacity-100 md:opacity-0 group-hover:opacity-100 absolute -bottom-5 text-[10px] text-slate-400 transition-opacity">
                    ${getTimeDiff(evt.timestamp)}
                </div>
            </div>
        `;

        node.innerHTML = cardHtml;
        wrapper.appendChild(node);
    });

    container.appendChild(wrapper);
}

function getTimeDiff(ts) {
    const diff = ts - (Date.now() / 1000);
    if (diff < 0) return "Overdue";
    const mins = Math.floor(diff / 60);
    const hrs = Math.floor(mins / 60);
    if (hrs > 0) return `in ${hrs}h ${mins % 60}m`;
    return `in ${mins}m`;
}

// Auto Refresh Timeline
setInterval(loadTimeline, 60000);
loadTimeline();
