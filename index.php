<?php
require 'vendor/autoload.php';

/**
 * Create Database connection
 */


$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->psdat;

/** Get Data Collection */
$data = $db->data;
$fieldnames = getFieldnamesOfCollection($data);
$rows = $data->find();

function getFieldnamesOfCollection($collection)
{
    $titles = Array();
    $dataset = $collection->find();
    foreach ($dataset as $ds) {
        $dstitles = array_keys(get_object_vars($ds));
        foreach ($dstitles as $dstitle) {
            if (!in_array($dstitle, $titles)) {
                array_push($titles, $dstitle);
            }
        }
    }
    return $titles;
}


//foreach ($result as $entry) {
//    echo $entry['policyID'], "<br/>";
//}
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
                    $(this).html( '<input type="text" placeholder="Filter '+title+'" />' );
                } );

                var table = $('#datatable').DataTable({
                    scrollX:     true,
                    scrollY:     h,
                    scroller:    true,
                    columnDefs: [
                        { targets: [], visible: true},
                        { targets: '_all', visible: true }
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