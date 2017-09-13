/* eslint dot-location:1 */

'use strict';

const sock = new WebSocket('ws://localhost:3000');
var myTable = null;

sock.onopen = function () {
};

sock.onmessage = function (message) {
  const msg = JSON.parse(message.data);

  if (msg.type === 'html') {
    if (msg.div === 'content' && msg.code !== '') {
      $(`#${msg.div}`).html(msg.code);
      myTable = initTable();
    } else {
      $(`#${msg.div}`).html(msg.code);
    }
  }
  if (msg.type === 'cookie') {
    if (msg.function === 'set') {
      Cookies.set(msg.cookie, msg.value);
    }
    if (msg.function === 'rm') {
      Cookies.remove(msg.cookie);
    }
    if (msg.function === 'get') {
      const myCookie = Cookies.get(msg.cookie);

      if (myCookie) {
        sock.send(JSON.stringify({ type: 'cookie', cookie: msg.cookie, value: myCookie }));
      } else {
        sock.send(JSON.stringify({ type: 'cookie', cookie: msg.cookie, value: false }));
      }
    }
  }
  if (msg.type === 'views') {
    if (msg.function === 'addButtons') {
      if (myTable) {
        let view = msg.data;
        for (let i = 0; i < view.length; i++) {
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
        }
      }
    }
  }
};

const showLogNot = () => {
  $('#divLog').html('');
};

const login = () => {
  const username = $('#username').val();
  const password = $('#password').val();

  sock.send(JSON.stringify({ type: 'login', data: { user: username, pwd: password }}));
};
const logout = () => {
  sock.send(JSON.stringify({ type: 'logout' }));
};
