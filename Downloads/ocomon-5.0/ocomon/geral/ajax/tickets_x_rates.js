/* $(document).ready(function () {
    showTotalGraph_02();
}); */


function tickets_x_rates(canvasId) {
    
    const color_great = $('#color-great').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
    const color_good = $('#color-good').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
    const color_regular = $('#color-regular').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
    const color_bad = $('#color-bad').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
    const color_not_rated = $('#color-not-rated').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
    
    const color_great_border = $('#color-great').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
    const color_good_border = $('#color-good').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
    const color_regular_border = $('#color-regular').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
    const color_bad_border = $('#color-bad').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
    const color_not_rated_border = $('#color-not-rated').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');


    let bgColors = {};
    bgColors['color-great'] = color_great;
    bgColors['color-good'] = color_good;
    bgColors['color-regular'] = color_regular;
    bgColors['color-bad'] = color_bad;
    bgColors['color-not-rated'] = color_not_rated;

    let borderColors = {};
    borderColors['color-great'] = color_great_border;
    borderColors['color-good'] = color_good_border;
    borderColors['color-regular'] = color_regular_border;
    borderColors['color-bad'] = color_bad_border;
    borderColors['color-not-rated'] = color_not_rated_border;

    $.ajax({
        url: "../geral/tickets_x_rates.php",
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
        var classe = [];
        var borderClasse = [];
        var chartTitle = [];

        //console.log(data.length);
        
        for (var i in data) {
            // formStatus is taken from JSON output (see above)
            
            if (data[i].rate !== undefined) {
                formStatusVar.push(data[i].rate);
            }
            if (data[i].quantidade !== undefined) {
                total.push(data[i].quantidade);
            }

            if (data[i].classe !== undefined) {
                classe.push(bgColors[data[i].classe]);
                borderClasse.push(borderColors[data[i].classe]);
            }

            if (data[i].chart_title !== undefined) {
                chartTitle.push(data[i].chart_title);
            }
        }

        var options = {
            responsive: true,
            title: {
                display: true,
                // text: "Ocorrências em aberto x Status",
                text: chartTitle[0],
            },
            legend: {
                display: true,
                position: "left",
                align: "start",
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
                    backgroundColor: classe,
                    borderColor: borderClasse,
                    // hoverBackgroundColor: "#CCCCCC",
                    // hoverBorderColor: "#666666",
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

