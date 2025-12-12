<?php session_start();
/*                        Copyright 2023 Flávio Ribeiro

         This file is part of OCOMON.

         OCOMON is free software; you can redistribute it and/or modify
         it under the terms of the GNU General Public License as published by
         the Free Software Foundation; either version 3 of the License, or
         (at your option) any later version.
         OCOMON is distributed in the hope that it will be useful,
         but WITHOUT ANY WARRANTY; without even the implied warranty of
         MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
         GNU General Public License for more details.

         You should have received a copy of the GNU General Public License
         along with Foobar; if not, write to the Free Software
         Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
  */

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$imgsPath = "../../includes/imgs/";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

$actionUrl = "../../invmon/geral/get_full_equipments_table.php";

/* Campos customizados */
$custom_fields_full = getCustomFields($conn, null, 'equipamentos');
$custom_fields_classes = [];
foreach ($custom_fields_full as $cfield) {
    $custom_fields_classes[] = $cfield['field_name'];
}
$custom_fields_classes_text = implode(",", $custom_fields_classes);


$logo = '../../includes/logos/MAIN_LOGO.png';
// Read image path, convert to base64 encoding
$logoType = pathinfo($logo, PATHINFO_EXTENSION);
$logoData = file_get_contents($logo);
$imgData = base64_encode($logoData);
// Format the image SRC:  data:{mime};base64,{data};
$imgSrc = 'data:image/' . $logoType . ';base64,'.$imgData;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

</head>

<body>
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div class="container-fluid">

        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails" style="position:relative">
                        <iframe id="assetInfo"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
                    </div>
                </div>
            </div>
        </div>


        <?php

            if (empty($_GET)) {
                echo message ('warning', 'Ooops!', TRANS('INFO_MISSING_TO_PROCEED'), '', '', 1);
                return;
            }

            $dataGet = json_encode($_GET);
           

            if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
                echo $_SESSION['flash'];
                $_SESSION['flash'] = '';
            }
        ?>
    </div>


    <div id="print-info" class="d-none">&nbsp;</div>
    <input type="hidden" name="custom_fields_classes_text" id="custom_fields_classes_text" value="<?= $custom_fields_classes_text; ?>">

    <div class="container-fluid">
        <div id="divAssetsList">
        </div>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="./js/smart_search_assets_columns.js"></script>

    <script>
        $(function() {


            let hiddenColunsCookie = getCookie('oc_assets_cf_hidden_columns');
            let hiddenColunsCookieArray = hiddenColunsCookie.split(',');
            let customFieldsClassesText = $('#custom_fields_classes_text').val();
            let customFieldsClassesArray = customFieldsClassesText.split(',');

            var allColumns = reportAllColumns
            .concat(customFieldsClassesArray);
            
            var defaultHiddenColumns = hiddenColunsCookieArray

            if (defaultHiddenColumns == null || defaultHiddenColumns.length == 0 || defaultHiddenColumns == '') {
                defaultHiddenColumns = reportDefaultHiddenColumns.concat(customFieldsClassesArray);
            }

            let columnsOrderCookie = getCookie('oc_assets_cf_columns_order');
            let colunsOrderCookieArray = columnsOrderCookie.split(',');
            var defaultColumnsOrder = colunsOrderCookieArray;

            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });

            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: '<?= $actionUrl;?>',
                method: 'POST',
                data: <?= $dataGet; ?>,
            }).done(function(response) {
                $('#divAssetsList').html(response);
            });

            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs2 = $.initialize("#table_info", function() {
                $('#table_info').html($('#table_info_hidden').html());
                $('#print-info').html($('#table_info').html());
                
                /* Collumn resize */
                var pressed = false;
                var start = undefined;
                var startX, startWidth;

                $("table td").mousedown(function(e) {
                    start = $(this);
                    pressed = true;
                    startX = e.pageX;
                    startWidth = $(this).width();
                    $(start).addClass("resizing");
                });

                $(document).mousemove(function(e) {
                    if (pressed) {
                        $(start).width(startWidth + (e.pageX - startX));
                    }
                });

                $(document).mouseup(function() {
                    if (pressed) {
                        $(start).removeClass("resizing");
                        pressed = false;
                    }
                });
                /* end Collumn resize */

            }, {
                target: document.getElementById('divAssetsList')
            }); /* o target limita o scopo do mutate observer */



            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs = $.initialize("#table_tickets_queue", function() {
                
                var criterios = $('#divCriterios').text();
                
                var table = $('#table_tickets_queue').DataTable({

                    searching: false,
                    info: false,
                    paging: true,
                    // pageLength: 10,
                    deferRender: true,
                    // fixedHeader: true,
                    // scrollX: 300, /* para funcionar a coluna fixa */
                    // fixedColumns: true,
                    columnDefs: [{
                            targets: defaultHiddenColumns,
                            visible: false,
                        },
                        {
                            targets: reportNotOrderable,
                            orderable: false,
                            searchable: false,
                        },
                        {
                            targets: reportNotSearchable,
                            searchable: false,
                        },
                    ],

                    colReorder: {
                        iFixedColumns: 1,
                        order : defaultColumnsOrder
                    },

                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    },

                });

                // new $.fn.dataTable.ColReorder(table);

                new $.fn.dataTable.Buttons(table,{
                    
                    buttons: [{
                            extend: 'print',
                            text: '<?= TRANS('SMART_BUTTON_PRINT', '', 1)?>',
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1)?>',
                            // message: 'Relatório de Ocorrências',
                            message: $('#print-info').html(),
                            autoPrint: true,

                            customize: function(win) {
                                $(win.document.body).find('table').addClass('display').css('font-size', '10px');
                                $(win.document.body).find('tr:nth-child(odd) td').each(function(index) {
                                    $(this).css('background-color', '#f9f9f9');
                                });
                                $(win.document.body).find('h1').css('text-align', 'center');
                            },
                            exportOptions: {
                                columns: ':visible'
                            },
                        },
                        {
                            extend: 'copyHtml5',
                            text: '<?= TRANS('SMART_BUTTON_COPY', '', 1)?>',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excel',
                            text: "Excel",
                            exportOptions: {
                                columns: ':visible'
                            },
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1);?>-<?= date('d-m-Y-H:i:s');?>',
                        },
                        {
                            extend: 'csvHtml5',
                            text: "CVS",
                            exportOptions: {
                                columns: ':visible'
                            },

                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1);?>-<?= date('d-m-Y-H:i:s');?>',
                        },
                        {
                            extend: 'pdfHtml5',
                            text: "PDF",

                            exportOptions: {
                                columns: ':visible',
                            },
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1);?>',
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1);?>-<?= date('d-m-Y-H:i:s');?>',
                            orientation: 'landscape',
                            pageSize: 'A4',

                            customize: function(doc) {
                                var criterios = $('#divCriterios').text()
                                var rdoc = doc;
                                var rcout = doc.content[doc.content.length - 1].table.body.length - 1;
                                doc.content.splice(0, 1);
                                var now = new Date();
                                var jsDate = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
                                doc.pageMargins = [30, 70, 30, 30];
                                doc.defaultStyle.fontSize = 8;
                                doc.styles.tableHeader.fontSize = 9;

                                doc['header'] = (function(page, pages) {
                                    return {
                                        columns: [
                                            {
                                                margin: [20, 10, 0, 0],
                                                image: '<?= $imgSrc; ?>'
                                            } ,
                                            {
                                                table: {
                                                    widths: ['100%'],
                                                    headerRows: 0,
                                                    body: [
                                                        [{
                                                            text: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                                                            alignment: 'center',
                                                            
                                                            fontSize: 14,
                                                            bold: true,
                                                            margin: [0, 20, 0, 0]
                                                            
                                                        }],
                                                    ]
                                                },
                                                layout: 'noBorders',
                                                margin: 10,
                                            }
                                        ],
                                        
                                    }
                                });

                                doc['footer'] = (function(page, pages) {
                                    return {
                                        columns: [{
                                                alignment: 'left',
                                                text: ['Criado em: ', {
                                                    text: jsDate.toString()
                                                }]
                                            },
                                            {
                                                alignment: 'center',
                                                text: 'Total ' + rcout.toString() + ' linhas'
                                            },
                                            {
                                                alignment: 'right',
                                                text: ['página ', {
                                                    text: page.toString()
                                                }, ' de ', {
                                                    text: pages.toString()
                                                }]
                                            }
                                        ],
                                        margin: 10
                                    }
                                });

                                var objLayout = {};
                                objLayout['hLineWidth'] = function(i) {
                                    return .8;
                                };
                                objLayout['vLineWidth'] = function(i) {
                                    return .5;
                                };
                                objLayout['hLineColor'] = function(i) {
                                    return '#aaa';
                                };
                                objLayout['vLineColor'] = function(i) {
                                    return '#aaa';
                                };
                                objLayout['paddingLeft'] = function(i) {
                                    return 5;
                                };
                                objLayout['paddingRight'] = function(i) {
                                    return 35;
                                };
                                doc.content[doc.content.length - 1].layout = objLayout;

                            }

                        },
                        {
                            extend: 'colvis',
                            text: '<?= TRANS('SMART_BUTTON_MANAGE_COLLUMNS', '', 1)?>',
                            // className: 'btn btn-primary',
                            // columns: ':gt(0)'
                            collectionLayout: 'dropdown four-column',
                        },
                        {
                            text: '<?= TRANS('REMEMBER_VISIBLE_COLUMNS', '', 1) ?>',
                            attr: {
                                title: '<?= TRANS('REMEMBER_VISIBLE_COLUMNS', '', 1) ?>',
                                id: 'customButton'
                            },
                        }
                    ]
                });

                table.buttons().container()
                    .appendTo($('.display-buttons:eq(0)', table.table().container()));


            }, {
                target: document.getElementById('divAssetsList')
            }); /* o target limita o scopo do mutate observer */


            /* Observando o gerenciamento de colunas*/
            var obsColvis = $.initialize("#table_tickets_queue", function() {

                var table2 = $('#table_tickets_queue').DataTable();

                $('#customButton').on('click', function(){
                    defaultHiddenColumns = getHiddenColumns(table2, allColumns);

                    defaultColumnsOrder = getColumnsOrder(table2);
                });

            }, {
                target: document.getElementById('divAssetsList')
            }); /* o target limita o scopo do mutate observer */

        });


        function getHiddenColumns(table, columnsClasses) {
            // console.log(table.column('.aberto_por').visible() === true ? 'visible' : 'not visible');
            let hiddenColumns = []

            for (let i in columnsClasses) {
                // console.log(columnsClasses[i]);
                // console.log(table.column('.' + columnsClasses[i]).visible() === true ? columnsClasses[i] + ' visible' : columnsClasses[i] + ' not visible');
                if (table.column('.' + columnsClasses[i]).visible() !== true) {
                    hiddenColumns.push(columnsClasses[i]) 
                }
            }

            /* Fazer um ajax para gravar cookies com o array de colunas ocultas - Esse array deve ser consultado 
            toda a vez que o datatables for carregado */
            $.ajax({
                url: 'set_cookie_assets_recent_columns.php',
                type: 'POST',
                data: {
                    columnsClasses: hiddenColumns,
                    app: 'cardTickets'
                },
                success: function(data) {
                    // console.log(data);
                }
            });

            defaultHiddenColumns = hiddenColumns;
            return hiddenColumns;
        }

        function getColumnsOrder(table) {
            let columnsOrder = []

            columnsOrder = table.colReorder.order();

            $.ajax({
                url: 'set_cookie_assets_columns_order.php',
                type: 'POST',
                data: {
                    columnsOrder: columnsOrder,
                    app: 'cardTickets'
                },
                success: function(data) {
                    // console.log(data);
                }
            });

            defaultColumnsOrder = columnsOrder;
            return columnsOrder;
        }


        function openAssetInfo(assetId) {
            let location = 'asset_show.php?asset_id=' + assetId;
            $("#assetInfo").attr('src',location)
            $('#modal').modal();
        }

    </script>
</body>

</html>