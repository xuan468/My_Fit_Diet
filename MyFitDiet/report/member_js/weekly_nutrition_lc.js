document.addEventListener("DOMContentLoaded", function() {
    var ctx = document.getElementById('weeklyNutritionLineChart').getContext('2d');
    var weeklyNutritionLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsNutrition,
            datasets: [{
                label: 'Average Weekly Calories',
                data: avgCaloriesData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: 'Weekly Nutrition Summary',
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
                    title: {
                        display: true,
                        text: 'Calories'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Week Start Date'
                    }
                }
            },  
        }
    });
});