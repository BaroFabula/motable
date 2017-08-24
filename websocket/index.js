/**
 * Created by bp on 24.08.2017.
 */

var server = require('ws').Server;
var s = new server({port:5001});

var MongoClient = require('mongodb').MongoClient;
var url = "mongodb://localhost:27017/psdat";

s.on('connection', function (ws) {
    console.log('Client connected');
    ws.on('message', function (msg) {
        msg = JSON.parse(msg);
        if(msg.type == "saveSetting"){
            MongoClient.connect(url, function (err, db) {
                if (err) throw err;
                db.collection("saves").insertOne(msg.data, function (err, res) {
                    if (err) throw err;
                    console.log(res);
                    db.close();
                });
            });
        }
        if(msg.type == "getSavefiles"){
            MongoClient.connect(url, function (err, db) {
                if (err) throw err;
                db.collection("saves").find({}).toArray(function (err, res) {
                    if (err) throw err;
                    db.close();
                    //ERROR: ws.send({"type":"savefiles", "data":res});
                });
            });
        }
        if(msg.type == "loadSetting"){
            console.log(msg.data);
        }

    });
});