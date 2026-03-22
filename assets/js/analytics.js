
// -- Analytics Logic --

let analyticsChart = null;

async function loadAnalyticsChart() {
    const ctx = document.getElementById('activityChart');
    if (!ctx) return;

    try {
        const res = await apiCall('analytics.php', null, 'GET');

        if (res.success) {
            const chartData = res.data; // Array of { label: '14:00', count: 5 }
            const labels = chartData.map(d => d.label);
            const dataPoints = chartData.map(d => d.count);

            if (analyticsChart) analyticsChart.destroy();

            analyticsChart = new Chart(ctx, {
                type: 'bar', // or 'line'
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Successful Claims',
                        data: dataPoints,
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                            gradient.addColorStop(0, '#8b5cf6'); // Violet
                            gradient.addColorStop(1, '#6366f1'); // Indigo
                            return gradient;
                        },
                        borderRadius: 4,
                        hoverBackgroundColor: '#a78bfa'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#fff',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#334155' }, // Slate 700
                            ticks: { color: '#94a3b8' } // Slate 400
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', maxTicksLimit: 8 }
                        }
                    }
                }
            });
        }
    } catch (e) {
        console.error("Chart Error", e);
    }
}
