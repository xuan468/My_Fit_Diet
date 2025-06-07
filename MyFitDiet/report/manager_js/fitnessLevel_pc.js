function renderFitnessLevelPieChart() {
    var ctx = document.getElementById('fitnessLevelPieChart').getContext('2d');

    var fitnessLevelPieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: Object.keys(fitnessLevelData), 
            datasets: [{
                label: 'Target Fitness Level Distribution',
                data: Object.values(fitnessLevelData).map(data => data.count), 
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)', 
                    'rgba(54, 162, 235, 0.2)', 
                    'rgba(255, 206, 86, 0.2)', 
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true, 
                    position: 'top', 
                },
                title: {
                    display: true,
                    text: 'Users Fitness Level Distribution',
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
                            var fitnessLevel = context.label;
                            var count = fitnessLevelData[fitnessLevel].count;
                            var percentage = fitnessLevelData[fitnessLevel].percentage;
                            return `${fitnessLevel}: ${count} (${percentage}%)`; 
                        }
                    }
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', renderFitnessLevelPieChart);