/**
 * Created by bp on 24.08.2017.
 */
var config = require('../config.json');

var server = require('ws').Server;
var s = new server({port:5001});

if(config.dbtype == 'mongodb'){
    var mongo = require('mongodb');
    var MongoClient = mongo.MongoClient;
    var url = config.dburl+'/'+config.dbname;
}

s.on('connection', function (ws) {
    console.log('Client connected');
    ws.on('message', function (msg) {
        msg = JSON.parse(msg);
        if (msg.type == "saveSetting") {
            if(config.dbtype == 'mongodb') {
                MongoClient.connect(url, function (err, db) {
                    if (err) throw err;
                    db.collection(config.saves).insertOne(msg.data, function (err, res) {
                        if (err) throw err;
                        console.log(res);
                        db.close();
                        ws.send(JSON.stringify({"type":"reloadButtons"}));
                    });
                });
            }
        }
        if(msg.type == "getPublicSavefiles") {
            if (config.dbtype == 'mongodb') {
                MongoClient.connect(url, function (err, db) {
                    if (err) throw err;
                    db.collection(config.saves).find({}, {"user":1, "name":1}).toArray(function (err, res) {
                        if (err) throw err;
                        db.close();
                        ws.send(JSON.stringify({"type":"publicsavefiles", "data":res}));
                    });
                });
            }
        }
        if(msg.type == "getMySavefiles") {
            if (config.dbtype == 'mongodb') {
                MongoClient.connect(url, function (err, db) {
                    if (err) throw err;
                    db.collection(config.saves).find({"user":msg.data}, {"user":1, "name":1}).toArray(function (err, res) {
                        if (err) throw err;
                        db.close();
                        ws.send(JSON.stringify({"type":"mysavefiles", "data":res}));
                    });
                });
            }
        }
        if(msg.type == "loadSetting"){
            if (config.dbtype == 'mongodb') {
                if (msg.id != '' && msg.id != null) {
                    MongoClient.connect(url, function (err, db) {
                        if (err) throw err;
                        var o_id = new mongo.ObjectID(msg.id);
                        db.collection(config.saves).find({"_id": o_id}).toArray(function (err, res) {
                            if (err) throw err;
                            db.close();
                            ws.send(JSON.stringify({"type": "savefile", "data": res}));
                        });
                    });
                }
            }
        }
        if(msg.type == "saveChange"){
            console.log(msg);
            if (config.dbtype == 'mongodb') {
                    MongoClient.connect(url, function (err, db) {
                        if (err) throw err;
                        var o_id = new mongo.ObjectID(msg.key);
                        var set = {}; set[msg.prop] = msg.data;
                        db.collection(config.collection).update({"_id":o_id},{$set:set},{},function (err, obj) {
                            if(err){
                                ws.send(JSON.stringify({"type":"error", "data":"dbsave"}));
                            }
                        });
                    });
                }
        }
    });
});