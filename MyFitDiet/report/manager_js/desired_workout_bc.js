document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('desiredWorkoutBarChart').getContext('2d');


    var labels = Object.keys(desiredWorkoutData);
    var data = labels.map(function(label) {
        return desiredWorkoutData[label].count;
    });

    var desiredWorkoutBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Users',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Users Desired Workout Duration',
                    font: {
                        size: 18,
                        weight: 'bold' 
                    },
                    padding: {
                        top: 10,
                        bottom: 20 
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    });
});