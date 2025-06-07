document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('primaryGoalBarChart').getContext('2d');

    var labels = Object.keys(primaryGoalData);
    var data = labels.map(function(label) {
        return primaryGoalData[label].count;
    });

    var primaryGoalBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels, 
            datasets: [{
                label: 'Number of Users',
                data: data, 
                backgroundColor: 'rgba(153, 102, 255, 0.2)', 
                borderColor: 'rgba(153, 102, 255, 1)', 
                borderWidth: 1 
            }]
        },
        options: {
            responsive: true, 
            plugins: {
                title: {
                    display: true, // Enable the title
                    text: 'User Distribution by Primary Goal',
                    font: {
                        size: 18, 
                        weight: 'bold' 
                    },
                    padding: {
                        top: 10, 
                        bottom: 20 
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                }
            }
        }
    });
});