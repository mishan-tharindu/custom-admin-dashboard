document.addEventListener('DOMContentLoaded', function() {
    // Check if ChartData object exists and contains data
    if (typeof CustomChartData !== 'undefined' && CustomChartData.labels.length > 0) {
        
        // Target the canvas element
        const ctx = document.getElementById('pageViewsChart');
        
        if (ctx) {
            new Chart(ctx, {
                type: 'line', // Line chart for views over time
                data: {
                    labels: CustomChartData.labels, // Data passed from PHP
                    datasets: [{
                        label: 'Total Page Views',
                        data: CustomChartData.data, // Data passed from PHP
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.4, // Smooth curve
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }
});