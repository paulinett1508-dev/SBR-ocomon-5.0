function report_assets_general(dataFull, dataChartIndex, collumm, canvasId, chartType = 'doughnut') {

    /* Possible chartTypes: doughnut | horizontalBar | line | bar */

    var ctx = $('#' + canvasId);
    var dataToChart = dataFull[dataChartIndex];
    var chartTitle = dataFull[dataChartIndex + '_chart_title'];

    var labels = []; // X Axis Label
    var total = []; // Value and Y Axis basis

    for (var i in dataToChart) {
        if (dataToChart[i][collumm] !== undefined) {
            labels.push(dataToChart[i][collumm]);
            total.push(dataToChart[i].quantidade);
        }
    }


    return new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: chartTitle,
                data: total,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: chartTitle,
            },
            scales: {
                yAxes: [{
                    display: false,
                    ticks: {
                        beginAtZero: true
                    }
                }]
            },

            plugins: {
                colorschemes: {
                    // scheme: 'brewer.Paired12'
                    scheme: 'tableau.Tableau20'
                },
            },
        }
    });
}

