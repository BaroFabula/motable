'use strict';

let edit = false;
const hfspace = 200;
let viewheight = window.innerHeight - hfspace;
let table;

const initTable = () => {
  /**
   * Set Searchfields in Columnheaders
   */

  table = $('#motable').DataTable({
    scrollX: true,
    scrollY: viewheight,
    scroller: true,
    columnDefs: [
      { targets: '_all', visible: true },
      { targets: '_all', visible: false }
    ],
    dom: 'Brtip',
    buttons: [
      {
        extend: 'colvis',
        collectionLayout: 'fixed four-column'
      },
      {
        text: 'Save View',
        action (e, dt, node, config) {
          // CREATING AN JSON OBJECT CONTAINING THE AKTUELL FILTERS, SORTING AND VISIBILITIES
          const sname = prompt('Enter Savename to Save');

          if (sname !== null && /^\w+$/.test(sname)) {
            const setting = { columns: {}};
            setting.name = sname;
            table.columns().every(function () {
              const column = {
                id: this.index(),
                title: this.header().innerHTML,
                visibility: this.visible(),
                filter: $(`#input_${this.header().innerHTML.replace('$', '0x24').replace(' - ', '')}`).val()
              };

              setting.columns[this.index()] = column;
            });
            setting.order = table.order();
            sock.send(JSON.stringify({ type: 'saveView', data: setting }));
          }
        }
      },
      {
        extend: 'collection',
        text: 'Load View',
        buttons: []
      },
      {
        extend: 'collection',
        text: 'Delete View',
        buttons: []
      },
      {
        extend: 'collection',
        text: 'Export',
        autoclose: true,
        buttons:
          [
            {
              text: 'Clipboard',
              extend: 'copyHtml5',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              text: 'xlsx-File',
              extend: 'excelHtml5',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              text: 'csv-File',
              extend: 'csv',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              text: 'Printview',
              extend: 'print',
              exportOptions: {
                columns: ':visible'
              }
            }
          ]
      },
      {
        text: 'Editor',
        action (e, dt, node, config) {
          const focusCell = table.cell({focused: true});

          console.log(focusCell);
          if (edit) {
            node.css('background', 'white');
            if (focusCell.length === 1) {
              table.cell({focused: true}).data(focusCell.data());
            }
            edit = false;
          } else {
            node.css('background', '#ff4d4d');
            if (focusCell.length === 1) {
              $(`#cell_${focusCell.index().row}_${focusCell.index().column}`).html(`<input class="tblinput" id="tblinput_${focusCell.index().row}_${focusCell.index().column}" value="${focusCell.data()}">`);
              $(`#tblinput_${focusCell.index().row}_${focusCell.index().column}`).focus().select();
            }
            edit = true;
          }
        }
      }

    ],
    ordering: true,
    info: false,
    keys: true
  });

  table.columns().every(function () {
    const val = this.header().innerHTML;
    const that = this;
    $(`#input_${val.replace('$', '0x24').replace(' - ', '')}`).on('keyup change', function () {
      console.log(this.value);
      if (that.search() !== this.value) {
        that.search(this.value).draw();
      }
    });
  });

  table.on('key', (e, datatable, key, cell, originalEvent) => {
  }).
    on('key-focus', (e, datatable, cell, originalEvent) => {
      const row = cell.index().row;
      const col = cell.index().column;

      if (edit && table.column(col).header().innerHTML.split(' - ').length === 1) {
        $(`#cell_${row}_${col}`).html(`<input class="tblinput" id="tblinput_${row}_${col}" value="${cell.data()}">`);
        $(`#tblinput_${row}_${col}`).focus().select();
      }
    }).
    on('key-blur', (e, datatable, cell, originalEvent) => {
      const row = cell.index().row;
      const col = cell.index().column;

      if (edit && table.column(col).header().innerHTML.split(' - ').length === 1) {
        if (cell.data() !== $(`#tblinput_${row}_${col}`).val()) {
          const pre = cell.data();

          cell.data($(`#tblinput_${row}_${col}`).val());
          sock.send(JSON.stringify({
            type: 'saveChange',
            key: $(`#cell_${row}_${table.column(0).index()}`).html(),
            prop: table.column(col).header().innerHTML,
            pre,
            data: cell.data()
          }));
        } else {
          cell.data($(`#tblinput_${cell.index().row}_${cell.index().column}`).val());
        }
      }
    });

  sock.send(JSON.stringify({ type: 'getViews'}));

  return table;
};

window.addEventListener('resize', () => {
  viewheight = window.innerHeight - hfspace;
  $('div.dataTables_scrollBody').height(viewheight);
});