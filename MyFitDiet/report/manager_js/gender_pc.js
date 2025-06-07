function renderGenderPieChart() {
    var ctx = document.getElementById('genderPieChart').getContext('2d');

    var genderPieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: Object.keys(genderData), 
            datasets: [{
                label: 'Gender Distribution',
                data: Object.values(genderData).map(data => data.count), 
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)'  
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)'
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
                    text: 'Gender Distribution',
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
                            var gender = context.label;
                            var count = genderData[gender].count;
                            var percentage = genderData[gender].percentage;
                            return `${gender}: ${count} (${percentage}%)`; 
                        }
                    }
                }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', renderGenderPieChart);