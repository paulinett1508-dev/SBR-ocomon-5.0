function tagsCloud(divID) {
    $.ajax({
        url: "../geral/chartTags.php",
        method: "POST",
        // data: {
        //     'cod': cod
        // },
        dataType: "json",
    }).done(function (data) {

        $('.' + divID + '-tg-title').remove();
        $('#' + divID).parent().prepend('<p class="text-center text-secondary mt-2 font-weight-bold ' + divID + '-tg-title">' + data.title + '</p>');
        $('#myTagCloud').remove();
        $('#' + divID).empty().html($('<ul>', {id: 'myTagCloud'}));

        if (data.message_empty != "") {
            $('#' + divID).html();
            $('#' + divID).html(data.message_empty);
            // console.log('Mensagem: ' + data.message_empty);
        } else {
            for (var i in data) {
                if (data[i].label) {
                    $('#myTagCloud').append('<li data-weight="' + data[i].weight + '">' + data[i].label + ': ' + data[i].weight + '</li>');
                }
            }

            if ($("#myTagCloud").length > 0) {
                $("#myTagCloud").tagCloud({
                    container: {
                        backgroundColor: "#fafaf8",
                        width: $('#' + divID).innerWidth()
                    },
                    tag: {
                        maxFontSize: 30, // max font size in pixels
                        minFontSize: 10, // min font size in pixels
                        textShadow: true, // text shadow, enabled for better visibility
                    },
                });

                $(".jqTcTag")
                .off()
                .on("click", function () {
                /* Ajustando o data-name */
                    
                    let tagName = encodeURIComponent(
                        $(this).attr("data-name").trim().split(":")[0]
                    );

                    let postStartDate = encodeURIComponent($("#startDate").val());
                    let postEndDate = encodeURIComponent($("#endDate").val());
                    // let area = encodeURIComponent($("#area").val());
                    let area = $("#area").val();
                    let client = $("#client").val();

                    // console.log('area: ' + area);
                    let is_requester_area = ($('input[name="requester_areas"]:checked').val() == "yes" ? 1 : 0);

                    popup_alerta_wide(
                        "../../ocomon/geral/get_card_tickets.php?has_tags=" +
                        tagName +
                        "&data_abertura_from=" +
                        postStartDate +
                        "&data_abertura_to=" +
                        postEndDate +
                        "&area[]=" +
                        area + 
                        "&areas_filter=" +
                        area +
                        "&clients_filter=" +
                        client +
                        "&app_from=dashboard&is_requester_area=" +
                        is_requester_area

                    );
                })
                .css("cursor", "pointer");
            }
        }
    });
}
