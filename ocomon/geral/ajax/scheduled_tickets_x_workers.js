/* $(document).ready(function () {
    showTotalGraph_02();
}); */


function scheduled_tickets_x_workers(canvasId) {
    $.ajax({
        url: "../geral/scheduled_tickets_x_workers.php",
        method: "POST",
        data: {
            "codigo": "1"
        },
        dataType: "json",
    })
    .done(function (data) {
        // Declare the variables for your graph (for X and Y Axis)

        var formStatusVar = []; // X Axis Label
        var total = []; // Value and Y Axis basis
        var chartTitle = [];

        //console.log(data.length);

        for (var i in data) {
            // formStatus is taken from JSON output (see above)
            
            if (data[i].nome !== undefined) {
                formStatusVar.push(data[i].nome);
            }
            if (data[i].quantidade !== undefined) {
                total.push(data[i].quantidade);
            }
            if (data[i].chart_title !== undefined) {
                chartTitle.push(data[i].chart_title);
            }
        }


        var options = {
            responsive: true,
            title: {
                display: true,
                text: chartTitle[0],
            },
            legend: {
                display: true,
                position: "left",
                align: "start",
            },
            plugins: {
                colorschemes: {
                    scheme: 'tableau.Tableau20'
                }
            },
            scales: {
                xAxes: [
                {
                    display: false,
                },
                ],
            },
        };

        var chartdata = {
            labels: formStatusVar,
            datasets: [
                {
                    label: "Total",
                    
                    data: total,
                },
            ],
        };

        //This is the div ID (within the HTML content) where you want to display the chart
        // var graphTarget = $("#dashboard-02");
        var graphTarget = $('#' + canvasId);
        var barGraph = new Chart(graphTarget, {
            // type: "pie",
            type: "doughnut",
            data: chartdata,
            options: options,
        });
    })
    .fail(function () {
        // $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
    });
    
    return false;
}

