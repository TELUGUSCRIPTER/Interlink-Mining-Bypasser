
// -- Init --
document.addEventListener('DOMContentLoaded', () => {
    // Only init if on dashboard
    if (document.getElementById('accountsList')) {
        loadAccounts();
        loadAnalyticsChart();
        startLiveUpdates();
    }
});
