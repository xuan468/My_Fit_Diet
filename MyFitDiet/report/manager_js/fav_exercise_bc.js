document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('favExerciseBarChart').getContext('2d');

    var labels = Object.keys(favExerciseData);
    var data = labels.map(function(label) {
        return favExerciseData[label].count;
    });

    var favExerciseBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels, 
            datasets: [{
                label: 'Number of Users',
                data: data, 
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)', 
                borderWidth: 1 
            }]
        },
        options: {
            responsive: true, 
            plugins: {
                title: {
                    display: true, 
                    text: 'User Distribution by Favorite Exercise', 
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