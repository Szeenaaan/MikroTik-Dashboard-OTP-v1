const sortedChartData = [...chartData].sort((a, b) => {
    return new Date(a.time) - new Date(b.time);
});

const chartLabels = sortedChartData.map(item => {
    const date = new Date(item.time);

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit'
    }) + ', ' +
        date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
});


const reversedLabels = [...chartLabels].reverse();

const totalPoints = sortedChartData.length;

const downloadData = sortedChartData.map((item, index) => ({
    x: totalPoints - 1 - index,
    y: item.download
}));

const uploadData = sortedChartData.map((item, index) => ({
    x: totalPoints - 1 - index,
    y: item.upload
}));

const totalData = sortedChartData.map((item, index) => ({
    x: totalPoints - 1 - index,
    y: item.total
}));


const ctx = document.getElementById('usageChart');

new Chart(ctx, {
    type: 'line',

    data: {
        labels: reversedLabels,

        datasets: [
            {
                label: 'Download',
                data: downloadData,
                borderColor: '#3b82f6',
                backgroundColor: '#3b82f6',
                borderWidth: 2,
                tension: 0.35,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#3b82f6'
            },

            {
                label: 'Upload',
                data: uploadData,
                borderColor: '#ef4444',
                backgroundColor: '#ef4444',
                borderWidth: 2,
                tension: 0.35,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#ef4444'
            },

            {
                label: 'Total',
                data: totalData,
                borderColor: '#eab308',
                backgroundColor: '#eab308',
                borderWidth: 2,
                tension: 0.35,
                pointRadius: 2,
                pointHoverRadius: 5,
                pointBackgroundColor: '#eab308',
                pointBorderColor: '#eab308'
            }
        ]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        layout: {
            padding: {
                left: 0,
                right: 30
            }
        },

        animations: {
            x: {
                duration: 1500,
                easing: 'easeOutQuart',
                from: 0
            },

            y: {
                duration: 1500,
                easing: 'easeOutQuart'
            }
        },

        interaction: {
            intersect: false,
            mode: 'index'
        },

        plugins: {
            legend: {
                display: true,
                position: 'top',

                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 8,
                    boxHeight: 8
                }
            },

            tooltip: {
                callbacks: {
                    label: function (context) {
                        return context.dataset.label + ': ' +
                            formatBytes(context.parsed.y);
                    }
                }
            }
        },

        scales: {
            x: {
                reverse: true,
                offset: false,

                ticks: {
                    align: 'start',
                    padding: 0
                },

                grid: {
                    display: false
                }
            },

            y: {
                position: 'right',
                beginAtZero: true,

                afterFit: function (axis) {
                    axis.width = 55;
                },

                ticks: {
                    callback: function (value) {
                        return formatBytes(value);
                    }
                }
            }
        }
    }
});


function formatBytes(bytes) {

    if (bytes < 1024) {
        return bytes + ' Bytes';
    }

    if (bytes < 1024 ** 2) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }

    if (bytes < 1024 ** 3) {
        return (bytes / (1024 ** 2)).toFixed(2) + ' MB';
    }

    if (bytes < 1024 ** 4) {
        return (bytes / (1024 ** 3)).toFixed(2) + ' GB';
    }

    return (bytes / (1024 ** 4)).toFixed(2) + ' TB';
}

const totalDataElement = document.getElementById('totalData');
const downloadDataElement = document.getElementById('downloadData');
const uploadDataElement = document.getElementById('uploadData');

totalDataElement.textContent = formatBytes(Number(totalDataElement.textContent.trim()));
downloadDataElement.textContent = formatBytes(Number(downloadDataElement.textContent.trim()));
uploadDataElement.textContent = formatBytes(Number(uploadDataElement.textContent.trim()));