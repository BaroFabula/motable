<?php
require 'vendor/autoload.php';
/**
 * Create Database connection
 */

$config = json_decode(file_get_contents('./config.json'));
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
        <link rel="stylesheet" href="main.css">
        <script src="jquery/jquery-3.2.1.min.js"></script>
        <script src="bootstrap/js/bootstrap.min.js"></script>
        <script src="datatables/datatables.min.js"></script>
        <script src="jszip/jszip.min.js"></script>
        <script>
            function cellEditable(cell){
                console.log('works');
            }

            var hfspace = 200;

            var h = window.innerHeight-hfspace;
            $(document).ready(function() {
                $.fn.dataTable.ext.buttons.saveSetting = {
                    action: function ( e, dt, node, config ) {
                        alert( this.value() );
                    }
                };
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
                        { targets: '_all', visible: true},
                        { targets: '_all', visible: false }
                    ],
                    dom: 'Brtip',
                    buttons: [
                        {
                            extend:'colvis',
                            collectionLayout: 'fixed four-column'
                        },
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
                                    sock.send(JSON.stringify({"type":"saveSetting", "data":setting}));
                                }
                            }
                        },
                        {
                            extend: 'collection',
                            text: 'Load Settings',
                            autoclose:true,
                            buttons: [
                                {
                                    extend: 'collection',
                                    text: 'My Settings',
                                    buttons:[]
                                },
                                {
                                    extend: 'collection',
                                    text: 'Public Settings',
                                    buttons: []
                                }
                            ]
                        },
                        {
                            extend:'collection',
                            text:'Export',
                            autoclose:true,
                            buttons:
                            [
                                {
                                    text:'Clipboard',
                                    extend: 'copyHtml5',
                                    exportOptions: {
                                        columns: ':visible'
                                    }
                                },
                                {
                                    text:'xlsx-File',
                                    extend: 'excelHtml5',
                                    exportOptions: {
                                        columns: ':visible'
                                    }
                                },
                                {
                                    text:'csv-File',
                                    extend: 'csv',
                                    exportOptions: {
                                        columns: ':visible'
                                    }
                                },
                                {
                                    text:'Printview',
                                    extend: 'print',
                                    exportOptions: {
                                        columns: ':visible'
                                    }
                                },
                            ]
                        }

                    ],
                    "ordering": true,
                    "info":     false,
                    "keys": true
                });
                table.on( 'key', function ( e, datatable, key, cell, originalEvent ) {
                    if(key == 13){
                        cellEditable(cell);
                    }
                    //console.log(cell.index());
                    //console.log(cell.data());
                    console.log(key);
                } )
                    .on('dblclick','tr', function (cell) {
                        cellEditable(cell);
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


                var sock = new WebSocket("ws://localhost:5001");
                var savedsettings = [];
                var publicsettings = [];
                var mysettings = [];



                sock.onopen = function (event) {
                    console.log("Socket connected successfully");
                    sock.send('{"type":"getPublicSavefiles"}');
                    sock.send('{"type":"getMySavefiles", "data":null}');
                };
                sock.onmessage = function (msg) {
                    msg = JSON.parse(msg.data);
                    if(msg.type == 'publicsavefiles'){
                        var publicsavedsettings = msg.data;
                        for(var i = 0; i < publicsavedsettings.length; i++){
                            table.button().add('5-1-'+i,{
                                text: publicsavedsettings[i].name,
                                id: publicsavedsettings[i]._id,
                                className:"dt-button buttons-collection",
                                action: function (e, dt, node, config) {
                                    sock.send(JSON.stringify({"type":"loadSetting","id":config.id}));
                                }
                            });
                        }
                    }
                    if(msg.type == 'mysavefiles'){
                        var mysavedsettings = msg.data;
                        for(var i = 0; i < mysavedsettings.length; i++){
                            table.button().add('5-0-'+i,{
                                text: mysavedsettings[i].name,
                                id: mysavedsettings[i]._id,
                                className:"dt-button buttons-collection",
                                action: function (e, dt, node, config) {
                                    sock.send(JSON.stringify({"type":"loadSetting", "id":config.id}));
                                }
                            });
                        }
                    }
                    if(msg.type == 'savefile'){
                        var setting = msg.data[0];
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
                };
            } );
        </script>
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
                        ';
                        foreach ($fieldnames as $fieldname){echo'
                        <td>';if(isset($row->{$fieldname})){echo $row->{$fieldname};};echo'</td>
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