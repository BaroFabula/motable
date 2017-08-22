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
                    $(this).html( '<input type="text" id="input_'+title+'" placeholder="Filter '+title+'"/>' );
                } );

                var table = $('#datatable').DataTable({
                    scrollX:     true,
                    scrollY:     h,
                    scroller:    true,
                    columnDefs: [
                        { targets: [3], visible: true},
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

                        'colvis'
                    ],
                    "ordering": true,
                    "info":     false

                });
                table.columns().every( function () {
                    var that = this;

                    $( 'input', this.header() ).on( 'keyup change', function () {
                        if ( that.search() !== this.value ) {
                            that
                                .search( this.value )
                                .draw();
                        }
                    } );
                } );

// CREATING AN JSON OBJECT CONTAINING THE AKTUELL FILTERS, SORTING AND VISIBILITIES

                var setting = {"columns":{}};
                table.columns().every(function () {
                    var column = {
                        "id":this.index() ,
                        "title":this.header().innerHTML ,
                        "visibility":this.visible(),
                        "filter":$("#input_"+this.header().innerHTML).val()
                    };
                    setting.columns[this.index()]= column;
                });
                setting.order = table.order();
                window.alert(JSON.stringify(setting));
            } );</script>
        <script src="displaysettings.js"></script>
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