$(function () {

    $('#table_lists').DataTable({
        paging: true,
        deferRender: true,
        columnDefs: [{
            searchable: false,
            orderable: false,
            targets: ['editar', 'remover']
        }],
        "language": {
            "url": "../../includes/components/datatables/datatables.pt-br.json"
        }
    });

    $(function() {
        $('[data-toggle="popover"]').popover({
            html: true
        });
    });

    $('.popover-dismiss').popover({
        trigger: 'focus'
    });


    closeOrReturn();

    $('input, select, textarea').on('change', function () {
        $(this).removeClass('is-invalid');
    });
    $('#idSubmit').on('click', function (e) {
        e.preventDefault();
        var loading = $(".loading");
        $(document).ajaxStart(function () {
            loading.show();
        });
        $(document).ajaxStop(function () {
            loading.hide();
        });

        $("#idSubmit").prop("disabled", true);
        $.ajax({
            url: './assets_categories_process.php',
            method: 'POST',
            data: $('#formAssetsCategories').serialize(),
            dataType: 'json',
        }).done(function (response) {

            if (!response.success) {
                $('#divResult').html(response.message);
                $('input, select, textarea').removeClass('is-invalid');
                if (response.field_id != "") {
                    $('#' + response.field_id).focus().addClass('is-invalid');
                }
                $("#idSubmit").prop("disabled", false);
            } else {

                $('input, select, textarea').removeClass('is-invalid');
                $("#idSubmit").prop("disabled", false);


                if (isPopup()) {
                    window.opener.loadCategories();
                }

                $('#divResult').html('');
                var url = 'assets_categories.php';
                $(location).prop('href', url);
                return false;
            }
        });
        return false;
    });

    $('#idBtIncluir').on("click", function () {
        $('#idLoad').css('display', 'block');
        var url = 'assets_categories.php?action=new';
        $(location).prop('href', url);
    });
    
});


function confirmDeleteModal(id) {
    $('#deleteModal').modal();
    $('#deleteButton').html('<a class="btn btn-danger" onclick="deleteData(' + id + ')">'+$('#trans_remove').val()+'</a>');
}

function deleteData(id) {

    var loading = $(".loading");
    $(document).ajaxStart(function () {
        loading.show();
    });
    $(document).ajaxStop(function () {
        loading.hide();
    });

    $.ajax({
        url: './assets_categories_process.php',
        method: 'POST',
        data: {
            cod: id,
            action: 'delete'
        },
        dataType: 'json',
    }).done(function (response) {
        var url = 'assets_categories.php';
        $(location).prop('href', url);
        return false;
    });
    return false;
}

function closeOrReturn(jumps = 1) {
    buttonValue();
    $('.close-or-return').on('click', function () {
        
        if (isPopup()) {
            window.close();
        } else {
            // window.history.back(jumps);
            let url = '../../invmon/geral/assets_categories.php';
            window.location.href = url;
        }
    });

    $('#btReturnOrClose').on('click', function () {
        if (isPopup()) {
            window.close();
        } else {
            let url = '../../invmon/geral/assets_categories.php';
            window.location.href = url;
        }
    });
}

function buttonValue() {
    if (isPopup()) {
        $('.close-or-return, #btReturnOrClose').text($('#trans_bt_close').val());
    }
}