<?php
require 'vendor/autoload.php';

/**
 * Create Database connection
 */

$config = include "config.php";
include "code.php";
?>

<!DOCTYPE html>
<html class="heightmax">
    <head>
        <title>MyTableTest</title>

        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="Content-Language" content="de-DE" />

        <link rel="stylesheet" href="bootstrap/css/bootstrap.css">
        <link rel="stylesheet" href="datatables/datatables.min.css">
        <script src="jquery/jquery-3.2.1.min.js"></script>
        <script src="bootstrap/js/bootstrap.min.js"></script>
        <script src="datatables/datatables.min.js"></script>
        <script>
            var sock = new WebSocket("ws://localhost:5001");
            sock.onopen = function (event) {
                alert("Socket connected successfully");
            };

            var hfspace = 200;

            var h = window.innerHeight-hfspace;
            $(document).ready(function() {
                window.addEventListener('resize', function () {
                    h = window.innerHeight-hfspace;
                    $('div.dataTables_scrollBody').height( h );
                });
                // Setup - add a text input to each footer cell
                $('#datatable thead td').each( function () {
                    var title = $(this).text();
                    $(this).html( '<input type="text" id="input_'+title+'" class="form-control input-sm" placeholder="Filter '+title+'"/>' );
                } );

                var table = $('#datatable').DataTable({
                    scrollX:     true,
                    scrollY:     h,
                    scroller:    true,
                    columnDefs: [
                        { targets: [1, 2, 3, 4, 5], visible: true},
                        { targets: '_all', visible: false }
                    ],
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            extend: 'copyHtml5',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excel',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'csv',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        'colvis',
                        {
                            text: 'Save Settings',
                            action: function (e, dt, node, config) {
                                // CREATING AN JSON OBJECT CONTAINING THE AKTUELL FILTERS, SORTING AND VISIBILITIES

                                var sname = prompt("Enter Savename to Save");
                                if(sname != null && /^\w+$/.test(sname)) {
                                    var setting = {"columns": {}};
                                    setting.name = sname;
                                    table.columns().every(function () {
                                        var column = {
                                            "id": this.index(),
                                            "title": this.header().innerHTML,
                                            "visibility": this.visible(),
                                            "filter": $("#input_" + this.header().innerHTML).val()
                                        };
                                        setting.columns[this.index()] = column;
                                    });
                                    setting.order = table.order();
                                    setting.user = null;
                                    sock.send(JSON.stringify({"type":"settingsave", "data":setting}));
                                }
                            }
                        },
                        {
                            text: 'Load Settings',
                            action: function (e, dt, node, config) {
                                // LOADING AN JSON OBJECT CONTAINING THE AKTUELL FILTERS, SORTING AND VISIBILITIES
                                // SETTING PAGE TO OBJECTS PARAMETERS
                                var setting = JSON.parse(prompt("Enter settings JSON"));
                                table.order(setting.order);
                                jQuery.each(setting.columns, function (key, set) {
                                    table.column(set.id).visible(set.visibility);
                                    if(set.filter){
                                        $("#input_" + table.column(set.id).header().innerHTML).val(set.filter);
                                        table.column(set.id).search(set.filter);
                                    }
                                });
                                table.draw();
                            }
                        }
                    ],
                    "ordering": true,
                    "info":     false
                });
                table.columns().every( function () {
                    var that = this;
                    $('#input_'+this.header().innerHTML).on( 'keyup change', function () {
                        if ( that.search() !== this.value ) {
                            that
                                .search( this.value )
                                .draw();
                        }
                    } );
                } );
            } );</script>
    </head>
<?php
echo'
    <body>
        <div class="container">
            <div class="row">
                <table id="datatable" class="table table-striped table-bordered" width="100%" cellspacing="0">
            <thead>
            <tr>
                ';foreach ($fieldnames as $fieldname){echo'
                <td>'.$fieldname.'</td>
                ';}echo'
            </tr>
            <tr>
                ';foreach ($fieldnames as $fieldname){echo'
                <th>'.$fieldname.'</th>
                ';}echo'
            </tr>
            </thead>
            <tbody>
                ';
            foreach ($rows as $row){echo'
            <tr>
                ';foreach ($fieldnames as $fieldname){echo'
                <td>';if(isset($row[$fieldname])){echo $row[$fieldname];};echo'</td>
                ';}echo'
            </tr>
                ';}

            echo'
            </tbody>
        </table>
            </div>
        </div>
        </body>
</html>
';