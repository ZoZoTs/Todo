'use strict';

var Storage = function(_id){

    if(typeof localStorage === "undefined") {
        throw new Error("local storage not supported");
    }
    var id=_id;
   
    
    this.get = function (){
           
        if (!id) {
            return Promise.reject(new Error("storage not initializated"));
        }
        
        try {
            return Promise.resolve(JSON.parse(localStorage[id]||"{}"));
        } catch (_e) {
            return Promise.reject(new Error("local storage has invalid data. key: " + id));
        }
           
    };
    
    this.set = function (_data){
        if (!id) {
            return Promise.reject(new Error("storage not initializated"));
        }
        
        try {
            localStorage[id]=JSON.stringify(_data);
            return Promise.resolve("saved");
        } catch (_e) {
            return Promise.reject(new Error("object has invalid data. key: " + id));
        }
        

    
    };
    
};

exports.new = function(_id){
    return new Storage(_id);
};