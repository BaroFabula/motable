'use strict';

const config = require('./config.json');

const path = require('path');
const http = config.secure === 1 ? require('https') : require('http');
const https = require('https');
const ws = config.secure === 1 ? require('wss') : require('ws');
const fs = require('fs');

const express = require('express');
const winston = require('winston');

const app = express();
const server = http.createServer(app);
const wsServer = new ws.Server({ server });

const htmlLogin = fs.readFileSync(path.join(__dirname, 'client', 'login.html'), 'utf8');
const htmlLogout = fs.readFileSync(path.join(__dirname, 'client', 'logout.html'), 'utf8');

const broadcast = msg => {
  wsServer.clients.forEach(client => {
    client.send(JSON.stringify(msg));
  });
};

wsServer.on('connection', socket => {
  winston.log('info', `Websocket connection created by client`);
  var session = null;

  const sendHtml = (socket, area, html) => {
    winston.log('info', `Send HTML to: ${area}`);
    socket.send(JSON.stringify({ type: 'html', div: area, code: html }));
  };

  const logout = socket => {
    winston.log('info', 'Logout');
    socket.send(JSON.stringify({ type: 'cookie', function: 'rm', cookie: 'motable' }));
    session = null;
    sendHtml(socket, 'divLog', htmlLogin);
    sendHtml(socket, 'content', '');
  };

  const getColumnNames = (myObject, prefix) => {
    let headers = [];

    if (Array.isArray(myObject)){
      myObject.forEach(item => {
        let tempHeaders = getColumnNames(item, prefix);
        tempHeaders.forEach(th => {
          if (!headers.includes(`${th}`)) {
            headers.push(`${th}`);
          }
        });
      });
    } else if (typeof myObject === 'object') {
      Object.keys(myObject).forEach((key) => {
        if (typeof myObject[key] === 'object' || Array.isArray(myObject[key])) {
          headers.push(getColumnNames(myObject[key], `${key} - `));
        } else {
          if (!headers.includes(`${prefix}${key}`)) {
            headers.push(`${prefix}${key}`);
          }
        }
      });
    }

    return headers;
  };

  const getTable = data => {
    const headers = getColumnNames(data, '');
    let html = '<table id="motable" class="table table-striped table-bordered" width="100%" cellspacing="0"><thead>';

    for (let header in headers) {
      html = `${html}<td><input id="input_${headers[header].replace('$', '0x24').replace(' - ', '')}"class="form-control input-sm" placeholder="Filter ${headers[header]}" type="text"></td>`;
    }
    html = `${html}<tr>`;
    for (let header in headers) {
      html = `${html}<th data-name="${headers[header]}">${headers[header]}</th>`;
    }
    html = `${html}</tr></thead><tbody>`;
    let row = 0;
    let col = 0;
    data.forEach(dat => {
      html = `${html}<tr>`;
      for (let header in headers) {
        let val = dat;
        const hd = headers[header].split(' - ');
        for (let i = 0; i < hd.length; i++){
          if (val[hd[i]]) {
            val = val[hd[i]];
          } else {
            val = '';
          }
        }
        html = `${html}<td id="cell_${row}_${col}" class="${headers[header]} ${dat['_id']['$oid']}">${val}</td>`;
        col++;
      }
      html = `${html}</tr>`;
      col = 0;
      row++;
    });
    html = `${html}</tbody></table>`;

    return html;
  };

  const setCookie = rawcookie => {
    const myCookie = `${rawcookie}`.replace(`[`, ``).replace(`'`, ``).replace(`]`, ``).replace(` `, ``).split(';');

    for (let i = 0; i < myCookie.length; i++) {
      if (myCookie[i].indexOf('JSESSIONID=') !== -1) {
        socket.send(JSON.stringify({ type: 'cookie', function: 'set', cookie: 'motable', value: `${myCookie[i]};` }));
        session = `${myCookie[i]};`;
      }
    }
  };

  const getData = (socket, options) => {
    https.get(options, res => {
      res.setEncoding('utf8');
      if (res.statusCode != 200) {
        sendHtml(socket, 'content', 'The connection to the Datenbase isn\'t working properly.');
      }else{
        if (res.headers['set-cookie']) {
          setCookie(res.headers['set-cookie']);
        }
        res.on('data', function(chunk) {
          console.log(chunk);
          const result = JSON.parse(chunk);


          if (result.status === 200) {
            winston.log('info', `${result.status}`);
            const tbl = getTable(JSON.parse(chunk).results);

            sendHtml(socket, 'divLog', htmlLogout);
            sendHtml(socket, 'content', tbl);
          } else {
            winston.log('warn', `${result.status} ${result.error}`);
            logout(socket);
          }
        });
      }
    }).on('error', function(e) {
      winston.log('error: ', e.message);
    });
  };

  const getDataUser = (socket, username, password) => {
    getData(socket,
      {
        host: 'trapdb.kskserver.de',
        path: '/api/patients',
        auth: `${username}:${password}`
      }
    );
  };

  const getDataCookie = (socket, myCookie) => {
    getData(socket,
      {
        host: 'trapdb.kskserver.de',
        path: '/api/patients',
        headers: {
          Cookie: myCookie
        }
      }
    );
  };

  const updateDatafield = (socket, session, msg) => {
    const header = {
      host: 'trapdb.kskserver.de',
      path: `/api/patients/${msg.key}`,
      headers: {
        Cookie: session
      }
    };
    https.get(header, res => {
      if (res.headers['set-cookie']) {
        setCookie(res.headers['set-cookie']);
      }
      res.on('data', function(chunk) {
        const result = JSON.parse(chunk);

        if (result.status === 200) {
          winston.log('info', `Status: ${result.status}`);

          broadcast({
            type: 'cell',
            function: 'update',
            data: {
              id: result.results[0]['_id']['$oid'],
              col: msg.prop,
              value: result.results[0][msg.prop]
            }
          });
        } else {
          winston.log('warn', `${result.status} ${result.error}`);
          logout(socket);
        }
      });
    }).on('error', function(e) {
      winston.log('error: ', e.message);
    });
  };



  const filterExtras = (data, type) => {
    const ret = [];

    data.forEach((extra) => {
      if (extra.type === type) {
        ret.push(extra);
      }
    });

    return ret;
  };

  const getViewDocuments = (socket, myCookie) => {
    const header = {
      host: 'trapdb.kskserver.de',
      path: '/api/extras',
      headers: {
        Cookie: myCookie
      }
    };

    https.get(header, res => {
      if (res.headers['set-cookie']) {
        setCookie(res.headers['set-cookie']);
      }
      res.on('data', function(chunk) {
        const result = JSON.parse(chunk);

        if (result.status === 200) {
          winston.log('info', `${result.status}`);
          const views = filterExtras(result.results, 'saveView');
          socket.send(JSON.stringify({ type: 'views', function: 'addButtons', data: views }));
        } else {
          winston.log('warn', `${result.status} ${result.error}`);
          logout(socket);
        }
      });
    }).on('error', function(e) {
      winston.log('error: ', e.message);
    });
  };

  const createViewDocument = (socket, myCookie, myObject) => {
    const options = {
      host: 'trapdb.kskserver.de',
      path: '/api/extras',
      method: 'POST',
      headers: {
        Cookie: myCookie
      }
    };
    const postData = JSON.stringify(myObject);
    const req = https.request(options, (res) => {
      res.setEncoding('utf8');
      res.on('data', (chunk) => {
        winston.log('info', `BODY: ${chunk}`);
      });
      res.on('end', () => {
        winston.log('info', 'No more data in response.');
      });
    });

    req.on('error', (e) => {
      winston.log('error', `problem with request: ${e.message}`);
    });

    req.write(postData);
    req.end();
  };

  const deleteViewDocument = (socket, myCookie, oid) => {
    const options = {
      host: 'trapdb.kskserver.de',
      path: `/api/extras/${oid}`,
      method: 'DELETE',
      headers: {
        Cookie: myCookie
      }
    };
    const req = https.request(options, (res) => {
      res.setEncoding('utf8');
      res.on('data', (chunk) => {
      });
      res.on('end', () => {
      });
    });

    req.on('error', (e) => {
      console.error(`problem with request: ${e.message}`);
    });
    req.end();
  };

  const changeValue = (socket, session, msg) => {
    const options = {
      host: 'trapdb.kskserver.de',
      path: `/api/patients/${msg.key}`,
      method: 'PUT',
      headers: {
        Cookie: session
      }
    };
    const prop = msg.prop.split(' - ');

    const createData = (array) => {
      if(array.length > 1){
        console.log('Editor for Objects is not supported yet');
      } else {
        return `{"${array[0]}":"${msg.data}"}`;
      }
    };

    const postData = createData(prop);

    const req = https.request(options, (res) => {
      res.setEncoding('utf8');
      res.on('data', (chunk) => {
      });
      res.on('end', () => {
        updateDatafield(socket, session, msg);
      });
    });

    req.on('error', (e) => {
      console.error(`problem with request: ${e.message}`);
    });

    req.write(postData);
    req.end();
  };

  socket.send(JSON.stringify({ type: 'cookie', function: 'get', cookie: 'motable' }));
  socket.on('message', message => {
    winston.log('info', `WS Message Received: ${message}`);
    const msg = JSON.parse(message);

    switch (msg.type) {
      case 'cookie':
        session = msg.value;
        winston.log('warn', session);
        if (msg.value) {
          sendHtml(socket, 'divLog', htmlLogout);
          getDataCookie(socket, msg.value);
        } else {
          sendHtml(socket, 'divLog', htmlLogin);
        }
        break;
      case 'login':
        getDataUser(socket, msg.data.user, msg.data.pwd);
        break;
      case 'logout':
        logout(socket);
        break;
      case 'saveView':
        createViewDocument(socket, session, msg);
        break;
      case 'deleteView':
        deleteViewDocument(socket, session, msg.id.$oid);
        break;
      case 'getViews':
        getViewDocuments(socket, session, msg);
        break;
      case 'saveChange':
        changeValue(socket, session, msg);
        break;
      default:break;
    }
  });
});

app.use((req, res, next) => {
  winston.log('info', `${req.method} ${req.path}`);
  next();
});

// app.use('/js', express.static(path.join(__dirname, 'datatables')));
app.use('/js', express.static(path.join(__dirname, 'node_modules', 'jquery', 'dist')));
app.use('/js', express.static(path.join(__dirname, 'node_modules', 'bootstrap', 'dist', 'js')));
app.use('/js', express.static(path.join(__dirname, 'js')));

// app.use('/css', express.static(path.join(__dirname, 'datatables')));
app.use('/css', express.static(path.join(__dirname, 'node_modules', 'bootstrap', 'dist', 'css')));
app.use('/css', express.static(path.join(__dirname, 'sass')));

app.use('/fonts', express.static(path.join(__dirname, 'fonts')));

app.set('view engine', 'html');

app.get('/', (req, res) => {
  res.status(200).sendFile(path.join(__dirname, 'client', 'index.html'));
});

server.listen(3000, () => {
  winston.log('info', `Server is listening on port 3000`);
});