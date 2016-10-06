"use strict";

/**
 * ajax queries wrapped by promises and CRUD methods
 * @constructor
 */
var ajax = function(url){
    this.url=url;
    
    this._beforeSend = function(){/*override by AddCBs*/};
    this._afterReceived = function(){/*override by AddCBs*/};
    this._afterReceivedError = function(){/*override by AddCBs*/};

    this.AddCBs = function(_CBs) {
        if (_CBs.beforeSend) {
            this._beforeSend         = _CBs.beforeSend;
        }
        if (_CBs.afterReceived) {
            this._afterReceived      = _CBs.afterReceived;
        }
        if (_CBs.afterReceivedError) {
            this._afterReceivedError = _CBs.afterReceivedError;
        }
    };
};

 ajax.prototype.request = function (_method, _body) {

        var mythis = this;
        var xhr = new XMLHttpRequest();
       
        var promise={};
        promise.xhr = xhr;
        
        promise.hndl = new Promise(function(_resolve, _reject){
            promise.resolve = _resolve;
            promise.reject = _reject;
        });
        
        var params={
            method: _method, 
            url: this.url, 
            body: _body,
            newHeaders: []
        };
        mythis._beforeSend(params);
        ajax.beforeSend(params);
        xhr.open(params.method, params.url/*encodeURIComponent(url)*/, true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        //xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        for (var key in params.newHeaders){
            xhr.setRequestHeader(params.newHeaders[key].name, params.newHeaders[key].value);
        }
        
        
        
        xhr.send(params.body);

        xhr.onreadystatechange = function() {
            if (this.readyState != 4) return;
        
        
            if (this.status < 200 || this.status >= 300) {
                var error={
                    status: this.status, 
                    statusText:this.statusText, 
                    url: this.responseURL, 
                    params: params
                };
                var promise2 = ajax.afterReceivedError(error);
                if (promise2 && promise2.then){
                    promise2
                    .then(function(){
                        mythis.request(params.method, params.body)
                        .then(function(response){
                          promise.resolve(response);
                        })
                        .catch(function(error){
                            throw error;
                        });
                    })
                    .catch(function(error){
                        promise2 = mythis._afterReceivedError(error);
                        if (promise2  && promise2.then){
                            promise2
                            .then(function(){
                                mythis.request(params.method, params.body)
                                .then(function(response){
                                  promise.resolve(response);
                                })
                                .catch(function(error){
                                    throw error;
                                });
                            })
                            .catch(function(error){
                                promise.reject(error);
                            });
                        } else {
                            promise.reject(error);
                        }    
                    });
                } else {
                    promise.reject(error);
                }
            } else {
                ajax.afterReceived(this.responseText);
                mythis._afterReceived(this.responseText);
                promise.resolve(this.responseText);
            }
          
        };
        
        return promise.hndl;
    };

    ajax.prototype.get = function () {
        return this.request("GET", "");   
    };
    
    ajax.prototype.add = function ( _body) {
        return this.request("PUT", _body);   
    };
    
    ajax.prototype.update = function ( _body) {
        return this.request("POST", _body);   
    };
    
    ajax.prototype.set = ajax.prototype.update;
    
    ajax.prototype.del = function () {
        return this.request("DELETE", "");   
    };
    
    ajax.prototype.child = function (_id) {
        var newUrl = this.url;
        if (_id) {
            newUrl+="/"+_id;
        }
        
        return new ajax(newUrl);
    };

    

    ajax.beforeSend = function(){/*override by AddGlobalCBs*/};
    ajax.afterReceived = function(){/*override by AddGlobalCBs*/};
    ajax.afterReceivedError = function(){/*override by AddGlobalCBs*/};

exports.new = function(url) {
    return new ajax(url);
};

/**
 * Set global callback
 * @param {Object} _CBs - Object with CB functions
 */
exports.AddGlobalCBs = function(_CBs) {
    if (_CBs.beforeSend) {
        ajax.beforeSend         = _CBs.beforeSend;
    }
    if (_CBs.afterReceived) {
        ajax.afterReceived      = _CBs.afterReceived;
    }
    if (_CBs.afterReceivedError) {
        ajax.afterReceivedError = _CBs.afterReceivedError;
    }
};



