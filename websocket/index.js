/**
 * Created by bp on 24.08.2017.
 */

var server = require('ws').Server;
var s = new server({port:5001});

s.on('connection', function (ws) {
    console.log('Client connected');
    ws.on('message', function (msg) {
        console.log(msg);
    });
});