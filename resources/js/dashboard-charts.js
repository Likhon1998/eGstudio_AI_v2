import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Legend,
    Tooltip,
    ArcElement,
    DoughnutController,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Legend,
    Tooltip,
    ArcElement,
    DoughnutController,
);

function makeGradient(ctx, area, from, to) {
    if (!area) {
        return from;
    }
    const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
    g.addColorStop(0, from);
    g.addColorStop(1, to);
    return g;
}

function lineDataset(label, data, border, fillFrom, fillTo) {
    return {
        label,
        data,
        tension: 0.42,
        borderWidth: 2.5,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: border,
        pointBorderColor: '#0a0a0a',
        pointBorderWidth: 2,
        borderColor: border,
        backgroundColor(context) {
            const { chart } = context;
            const { ctx, chartArea } = chart;
            return makeGradient(ctx, chartArea, fillFrom, fillTo);
        },
        fill: true,
    };
}

export function initDashboardCharts(payload) {
    const lineEl = document.getElementById('neural-line-chart');
    const pieEl = document.getElementById('neural-pie-chart');
    if (!lineEl || !pieEl || !payload) {
        return;
    }

    const { line, pie } = payload;

    new Chart(lineEl, {
        type: 'line',
        data: {
            labels: line.labels,
            datasets: [
                lineDataset('Neural Prompt', line.total, '#22d3ee', 'rgba(34, 211, 238, 0.35)', 'rgba(34, 211, 238, 0)'),
                lineDataset('CGI Images', line.cgiImages, '#3b82f6', 'rgba(59, 130, 246, 0.28)', 'rgba(59, 130, 246, 0)'),
                lineDataset('CGI Videos', line.cgiVideos, '#a855f7', 'rgba(168, 85, 247, 0.28)', 'rgba(168, 85, 247, 0)'),
                lineDataset('Occasion', line.occasion, '#ec4899', 'rgba(236, 72, 153, 0.25)', 'rgba(236, 72, 153, 0)'),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#9ca3af',
                        boxWidth: 10,
                        boxHeight: 10,
                        font: { size: 10, weight: '600' },
                        padding: 16,
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(10,10,10,0.92)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: '#d1d5db',
                    padding: 12,
                    cornerRadius: 10,
                },
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: '#6b7280', font: { size: 10 } },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 10 },
                        precision: 0,
                    },
                    border: { display: false },
                },
            },
        },
    });

    const pieLabels = pie.length ? pie.map((s) => s.label) : ['No output yet'];
    const pieValues = pie.length ? pie.map((s) => s.value) : [1];
    const pieColors = pie.length ? pie.map((s) => s.color) : ['#374151'];

    new Chart(pieEl, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: pieColors,
                borderColor: '#0a0a0a',
                borderWidth: 3,
                hoverOffset: 10,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '#9ca3af',
                        boxWidth: 10,
                        boxHeight: 10,
                        font: { size: 9, weight: '600' },
                        padding: 10,
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(10,10,10,0.92)',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleColor: '#fff',
                    bodyColor: '#d1d5db',
                    padding: 12,
                    cornerRadius: 10,
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('dashboard-chart-data');
    if (!el) {
        return;
    }
    try {
        initDashboardCharts(JSON.parse(el.textContent));
    } catch (e) {
        console.error('Dashboard charts failed to load', e);
    }
});
