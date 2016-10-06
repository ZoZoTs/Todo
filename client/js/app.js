"use strict";

var ajaxObject = require('js/ajaxObject');
ajaxObject.AddGlobalCBs({
    beforeSend: function(params){
        var SessionRegRes = document.cookie.match(/\bZoZoLand_session_id=([^;]*)/);
        if (SessionRegRes !== null) {
            params.newHeaders.push({name: "token", value: SessionRegRes[1]});
        }
    }
});

var loginDlg = require('js/loginDlg').new(document.getElementById("loginDlg"), 'api/v1/login'); //must by after globally setting token in ajaxObject 

ajaxObject.AddGlobalCBs({   //must by after loginDlg added
    afterReceivedError: function(_error){
        if (_error.status == 401 && !~_error.url.indexOf('api/v1/login')){
            return loginDlg.show();
        }
    }
});

var remoteTodoControl = require('js/remoteTodoControl').elem;
var remoteTodoStorage = ajaxObject.new('api/v1/todo', []); 

var texts = ajaxObject.new('./client/lang/en-us.json'); //static texts for multilang
var textsGet = texts.$get();


Promise.all([
        textsGet,
        remoteTodoStorage.$get(),
    ])
    .then(function(_res){
         ReactDOM.render(React.createElement(remoteTodoControl, { ajaxObject:remoteTodoStorage, texts: texts.data.remoteTodoControl}), document.getElementById("remoteTodoControl"));
    })
    .catch(function(_e) {
        if (_e.status == 404 &&  ~_e.url.indexOf('api/v1/todo')) {
            ReactDOM.render(React.createElement(remoteTodoControl, { ajaxObject:remoteTodoStorage, texts: texts.data.remoteTodoControl}), document.getElementById("remoteTodoControl"));
        } else {
            throw _e;
        }
    }).catch(function(_e) {
             if (_e.status) {
                alert("Remote Todo Error: " + _e.status + " " + _e.statusText);
             } else {
                throw _e;
             }
    });






