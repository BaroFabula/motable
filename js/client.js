/* eslint dot-location:1 */

'use strict';

const sock = new WebSocket('ws://localhost:3000');
const hfspace = 200;
let viewheight = window.innerHeight - hfspace;
let edit = false;
let myTable = null;

/**
 * This function adds a button to load the view at position i of the buttoncollection 2.
 * @param view
 * @param i
 */
const addLoadButton = (view, i) => {
  myTable.button().add(`2-${i}`, {
    text: view[i].data.name,
    id: view[i]._id,
    className: 'dt-button buttons-collection',
    action (e, dt, node, config) {
      myTable.order(view[i].data.order);
      jQuery.each(view[i].data.columns, (key, set) => {
        myTable.column(set.id).visible(set.visibility);
        if (set.filter) {
          $(`#input_${myTable.column(set.id).header().innerHTML}`).val(set.filter);
          myTable.column(set.id).search(set.filter);
        }
      });
      myTable.draw();
    }
  });
};

/**
 * This function adds a button to delete the view at position i of the buttoncollection 2.
 * @param view
 * @param i
 */
const addDeleteButton = (view, i) => {
  myTable.button().add(`3-${i}`, {
    text: view[i].data.name,
    id: view[i]._id,
    className: 'dt-button buttons-collection',
    action (e, dt, node, config) {
      if (confirm('Are you sure you want to delete this view?') === true) {
        sock.send(JSON.stringify({type: 'deleteView', id: config.id}));
      }
    }
  });
};

const updateCell = (key, col, val) => {
  const index = myTable.cell(`.${col}.${key}`).index();

  $(`#cell_${index.row}_${index.column}`).html(val);
};

sock.onopen = function () {};

/**
 * Listens for incoming messages on the WebSocket-Connection from the server.
 * Acts depending on the received message.
 * @param message
 */
sock.onmessage = function (message) {
  const msg = JSON.parse(message.data);

  switch (msg.type) {
    case 'html':
      $(`#${msg.div}`).html(msg.code);
      if (msg.div === 'content' && msg.code !== '') {
        myTable = initTable();
      }
      break;
    case 'cookie':
      switch (msg.function) {
        case 'set':
          Cookies.set(msg.cookie, msg.value);
          break;
        case 'rm':
          Cookies.remove(msg.cookie);
          break;
        case 'get':
          let myCookie = Cookies.get(msg.cookie);
          if (!myCookie) {
            myCookie = false;
          }
          sock.send(JSON.stringify({ type: 'cookie', cookie: msg.cookie, value: myCookie }));
          break;
        default:break;
      }
      break;
    case 'cell':
      if (msg.function === 'update') {
        const key = msg.data.id;
        const col = msg.data.col;
        const val = msg.data.value;

        updateCell(key, col, val);
      }
      break;
    case 'views':
      if (msg.function === 'addButtons') {
        if (myTable) {
          const view = msg.data;
          for (let i = 0; i < view.length; i++) {
            addLoadButton(view, i);
            addDeleteButton(view, i);
          }
        }
      }
      break;
    default:break;
  }
};

/**
 * Reads the login-form and sends the Data on WS to the server.
 * This function is called from the Login-Button.
 */
const login = () => {
  const username = $('#username').val();
  const password = $('#password').val();

  sock.send(JSON.stringify({ type: 'login', data: { user: username, pwd: password }}));
};

/**
 * Sends the logout-information on WS to the server.
 * This function is called from the Logout-Button.
 */
const logout = () => {
  sock.send(JSON.stringify({ type: 'logout' }));
};

/**
 * EventListener reacts when resizing the window and adapts the size of the div, that shows the window.
 * This way the Website always fits into the browserwindow and only scrolls within the table.
 */
window.addEventListener('resize', () => {
  viewheight = window.innerHeight - hfspace;
  $('div.dataTables_scrollBody').height(viewheight);
});

const initTable = () => {
  /**
   * Initializes the DataTable onto the HTML-table.
   */

  const table = $('#motable').DataTable({
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
          /**
           * CREATING AN JSON OBJECT CONTAINING THE AKTUELL FILTERS, SORTING AND VISIBILITIES
           */
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
          } else {
            window.alert('Error while saving: The Savename may only contain letters and numbers!');
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
          const focusCell = table.cell({ focused: true });

          if (edit) {
            node.css('background', 'white');
            if (focusCell.length === 1) {
              table.cell({ focused: true }).data(focusCell.data());
            }
            edit = false;
          } else {
            node.css('background', '#ff4d4d');
            edit = true;
          }
        }
      }

    ],
    ordering: true,
    info: false,
    keys: {
      keys: [ 38, 40, 13, 9, 16, 17, 18 ]
    }
  });

  table.columns().every(function () {
    const val = this.header().innerHTML;
    const that = this;

    $(`#input_${val.replace('$', '0x24').replace(' - ', '')}`).on('keyup change', function () {
      if (that.search() !== this.value) {
        that.search(this.value).draw();
      }
    });
  });

  table.on('key', (e, datatable, key, cell, originalEvent) => {
    if (edit) {
      switch (key) {
        case 13:
          const CurTableIndexs = table.rows().indexes();
          const index = table.cell({ focused: true }).index();
          const CurIndexArrayKey = CurTableIndexs.indexOf(index.row);
          const nextRow = CurTableIndexs[CurIndexArrayKey + 1];

          table.cell(`#cell_${nextRow}_${index.column}`).focus();
          break;
        default: break;
      }
    }
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

  sock.send(JSON.stringify({ type: 'getViews' }));

  return table;
};
