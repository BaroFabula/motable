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
        function overlayOn() {
            document.getElementById("overlay").style.display = "block";
        }
        function overlayOff() {
            document.getElementById("overlay").style.display = "none";
        }

        var edit = false;

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
                                }
                            ]
                    },
                    {
                        text:'Editor',
                        action: function (e, dt, node, config) {
                            var c = table.cell({focused:true});
                            console.log(c);
                            if(edit){
                                node.css("background","white");
                                if(c.length==1){
                                    table.cell({focused:true}).data(c.data());
                                }
                                edit = false;
                            }else{
                                node.css("background","#ff4d4d");
                                if(c.length==1){
                                    $('#cell_'+c.index().row+'_'+c.index().column).html('<input class="tblinput" id="tblinput_'+c.index().row+'_'+c.index().column+'" value="'+c.data()+'">');
                                    $('#tblinput_'+c.index().row+'_'+c.index().column).focus().select();
                                }
                                edit = true;
                            }
                        }
                    }

                ],
                "ordering": true,
                "info":     false,
                "keys": true
            });
            table.on( 'key', function ( e, datatable, key, cell, originalEvent ) {
                } )
                .on('key-focus', function(e, datatable, cell, originalEvent ){
                    if(edit){
                        $('#cell_'+cell.index().row+'_'+cell.index().column).html('<input class="tblinput" id="tblinput_'+cell.index().row+'_'+cell.index().column+'" value="'+cell.data()+'">');
                        $('#tblinput_'+cell.index().row+'_'+cell.index().column).focus().select();
                    }
                })
                .on('key-blur', function(e, datatable, cell, originalEvent ){
                    if(edit){
                        if(cell.data()!=$('#tblinput_'+cell.index().row+'_'+cell.index().column).val()){
                            var pre = cell.data();
                            cell.data($('#tblinput_'+cell.index().row+'_'+cell.index().column).val());
                            sock.send(JSON.stringify({
                                "type":"saveChange",
                                "key":$('#cell_'+cell.index().row+'_'+table.column('<?php print $config->key; ?>:name').index()).html(),
                                "prop":table.column(cell.index().column).header().innerHTML,
                                "pre":pre,
                                "data":cell.data()
                            }));
                        }else{
                            cell.data($('#tblinput_'+cell.index().row+'_'+cell.index().column).val());
                        }
                    }
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
                        table.button().add('2-1-'+i,{
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
                        table.button().add('2-0-'+i,{
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
                if(msg.type == 'error'){
                    if(msg.data == 'dbsave'){
                        window.alert('An error occured during the save of changes. The site will be reloaded.');
                        location.reload(true);
                    }
                }
                if(msg.type == 'reloadButtons'){
                    var i = 0;
                    while(table.buttons('2-1-'+i).text() != ''){
                        table.buttons('2-1-'+i).remove();
                    }
                    sock.send('{"type":"getPublicSavefiles"}');
                    sock.send('{"type":"getMySavefiles", "data":null}');
                }
            };
        });

    </script>
</head>
<?php
echo'
    <body>
        <div id="overlay" onclick="overlayOff();">
        </div>
        <div class="container">
        ';
include "tbl/display.php";
        echo'
        </div>
        </body>
</html>
';