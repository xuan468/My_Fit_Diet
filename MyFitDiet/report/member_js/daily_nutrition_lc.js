document.addEventListener("DOMContentLoaded", function() {
    console.log("Labels:", labelsDailyNutrition);
    console.log("Calories:", caloriesData);
    console.log("Carbs:", carbsData);

    var ctx = document.getElementById('dailyNutritionLineChart').getContext('2d');

    var dailyNutritionLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labelsDailyNutrition,
            datasets: [
                {
                    label: 'Total Calories',
                    data: caloriesData,
                    borderColor: 'rgba(255, 99, 132, 1)', 
                    fill: false
                },
                {
                    label: 'Total Carbs (g)',
                    data: carbsData, 
                    borderColor: 'rgba(54, 162, 235, 1)',
                    fill: false
                },
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Daily Nutrition Overview',
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
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Nutrition Values'
                    },
                    beginAtZero: true
                }
            }
        }
    });
});