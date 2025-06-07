document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('countryBarChart').getContext('2d');

    var labels = Object.keys(countryData);
    var data = labels.map(function(label) {
        return countryData[label].count;
    });

    var countryBarChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Number of Users',
                data: data, 
                backgroundColor: 'rgba(75, 192, 192, 0.2)', 
                borderColor: 'rgba(75, 192, 192, 1)', 
                borderWidth: 1 
            }]
        },
        options: {
            responsive: true, 
            plugins: {
                title: {
                    display: true, 
                    text: 'Users Country Distribution', 
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