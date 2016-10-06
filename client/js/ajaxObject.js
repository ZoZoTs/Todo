"use strict";


var ajax = require('js/ajax');

var handleReceivedData = function(_promise, _data) {
   try {
        if (_data) {
            var data = JSON.parse(_data);
            this.data = [];
            if (data instanceof Array) {
                for (var i=0;i<data.length;i++){
                    if (data[i].id) {
                        this.data.push(new AjaxObject(this._ajax.url+"/"+data[i].id, data[i], this));
                    } else {
                        this.data.push({data: data[i]});
                    }
                }
            } else {
                this.data = data;
                if (this.data.id) {
                    this._ajax.url=this.parent._ajax.url+"/"+this.data.id;
                }
            }
        } else {
            this.data=_data;
        }
        _promise.resolve(this.data);
    } catch (_e) {
        _promise.reject("invalid data received");
    }
};

var handleSendingData = function(_data) {
   try {
        return JSON.stringify(_data);
    } catch (_e) {
        throw new Error("invalid data for send");
    }
};

var createPromise = function(){
    var promise = {};
    promise.hndl = new Promise(function(_resolve,_reject){
        promise.resolve = _resolve;
        promise.reject = _reject;
    });
    return promise;
};

var beforeSendCB = function (params){
    this._userBeforeSend(params);
     if (params.body) {
    //     if (params.body.id) {
    //         params.url = params.url + "/" + params.body.id;
    //     }
         params.body = handleSendingData(params.body);
    }
};

var afterReceivedCB = function (result){
    this._userAfterReceived(result);
};

var afterReceivedErrorCB = function (_error){
    this._userAfterReceivedError(_error);
};

var AjaxObject = function(_url, _data, _parent) {
    this._ajax = ajax.new(_url);
    this._ajax.AddCBs({beforeSend: beforeSendCB.bind(this), afterReceived: afterReceivedCB.bind(this), afterReceivedError: afterReceivedErrorCB.bind(this)});
    
    this._userBeforeSend = function(){/*override by AddCBs*/};
    this._userAfterReceived = function(){/*override by AddCBs*/};
    this._userAfterReceivedError = function(){/*override by AddCBs*/};

    this.AddCBs = function(_CBs) {
        if (_CBs.beforeSend) {
            this._userBeforeSend         = _CBs.beforeSend;
        }
        if (_CBs.afterReceived) {
            this._userAfterReceived      = _CBs.afterReceived;
        }
        if (_CBs.afterReceivedError) {
            this._userAfterReceivedError = _CBs.afterReceivedError;
        }
    };
    
    this.data = _data||{};
    this.parent = _parent;
};

AjaxObject.prototype.$get = function() {
    var promise = createPromise();
    this._ajax.get().then(handleReceivedData.bind(this, promise), promise.reject);
    return promise.hndl;

};

AjaxObject.prototype.$update = function() {
    var promise = createPromise();
    this._ajax.update(this.data).then(handleReceivedData.bind(this, promise), promise.reject);
    return promise.hndl;

};

AjaxObject.prototype.$set = function (_body) {
    if (_body) {
        this.data = _body;    
    }
    if (this.data.id) {
        return this.$update();
    } else {
        return this.$add();
    }
};

AjaxObject.prototype.$add = function() {
    var promise = createPromise();
    this._ajax.add(this.data).then(handleReceivedData.bind(this, promise), promise.reject);
    return promise.hndl;

};

AjaxObject.prototype.$del = function() {
    var promise = createPromise();
    this._ajax.del().then(handleReceivedData.bind(this, promise), promise.reject);
    return promise.hndl;

};

AjaxObject.prototype.$newchild = function(_data) {
        var newUrl = this._ajax.url;
        if (this.data.id) {
            newUrl += "/" + this.data.id;    
        }
        var item = new AjaxObject(newUrl, _data||{}, this);
        if (this.data.push) {
            this.data.push(item);
        }
        return item;
};

exports.new = function (_url, _data) {
    return new AjaxObject(_url, _data);
};

exports.AddGlobalCBs = function(_CBs) {
    ajax.AddGlobalCBs(_CBs);
};


