// age_pc.js

function renderAgePieChart() {
    var ctx = document.getElementById('agePieChart').getContext('2d');

    var agePieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: Object.keys(ageData),
            datasets: [{
                label: 'Age Distribution',
                data: Object.values(ageData).map(data => data.count), 
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)', 
                    'rgba(54, 162, 235, 0.2)', 
                    'rgba(255, 206, 86, 0.2)', 
                    'rgba(75, 192, 192, 0.2)', 
                    'rgba(153, 102, 255, 0.2)', 
                    'rgba(255, 159, 64, 0.2)', 
                    'rgba(199, 199, 199, 0.2)', 
                    'rgba(83, 102, 255, 0.2)', 
                    'rgba(255, 99, 132, 0.2)' 
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'left',
                    align: 'start',
                    labels: {
                        boxWidth: 20, 
                        padding: 10 
                    }
                },
                title: {
                    display: true,
                    text: 'Age Distribution',
                    font: {
                        size: 18, 
                        weight: 'bold'
                    },
                    padding: {
                        top: 10, 
                        bottom: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var ageGroup = context.label;
                            var count = ageData[ageGroup].count;
                            var percentage = ageData[ageGroup].percentage;
                            return `${ageGroup}: ${count} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', renderAgePieChart);