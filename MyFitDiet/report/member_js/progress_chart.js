document.addEventListener("DOMContentLoaded", function() {
    var container = document.getElementById('challengeProgressCharts');

    challengeProgress.forEach(function(challenge) {
        var canvas = document.createElement('canvas');
        canvas.width = 200;
        canvas.height = 200; 
        container.appendChild(canvas);

        var ctx = canvas.getContext('2d');
        var progressChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Progress', 'Remaining'],
                datasets: [{
                    data: [challenge.progress, 100 - challenge.progress],
                    backgroundColor: ['rgba(54, 162, 235, 1)', 'rgb(140, 140, 140)'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: false,
                plugins: {
                    title: {
                        display: true,
                        text: challenge.challengeName, 
                        font: {
                            size: 14, 
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        enabled: false
                    },

                    customText: {
                        level: `Level ${challenge.currentLevel.levelid}`,
                        progress: `${Math.round(challenge.progress)}%`
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            },
            plugins: [{
                id: 'customText',
                beforeDraw: function(chart) {
                    var width = chart.width,
                        height = chart.height,
                        ctx = chart.ctx;

                    ctx.restore();

                    var fontSize = (height / 100).toFixed(2); 
                    ctx.font = `bold ${fontSize * 0.5}em sans-serif`;
                    ctx.textBaseline = 'middle';

                    ctx.fillStyle = '#000';

                    var levelText = chart.options.plugins.customText.level;
                    var levelTextX = Math.round((width - ctx.measureText(levelText).width) / 2);
                    var levelTextY = height / 1.3 - fontSize * 10;

                    ctx.fillText(levelText, levelTextX, levelTextY);

                    ctx.font = `bold ${fontSize * 0.5}em sans-serif`;

                    var progressText = chart.options.plugins.customText.progress;
                    var progressTextX = Math.round((width - ctx.measureText(progressText).width) / 2);
                    var progressTextY = height / 1.4 + fontSize * 10;

                    ctx.fillText(progressText, progressTextX, progressTextY);

                    ctx.save();
                }
            }]
        });
    });
});