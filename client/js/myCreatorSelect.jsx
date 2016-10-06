"use strict";

var ajaxObject = require('js/ajaxObject');

var MyCreator = function(){
    
    var texts = {}; //multilang texts
    
    var ReactMyCreator = React.createClass({
        getInitialState: function(){
            this.props.getSetDataFunction(this.setData);
            return {mycreator: null, show: false, mycreators: []};
        },
        setData: function (_function_name, _addToObject) {
            this.addToObject = _addToObject;
            var mythis = this;
            ajaxObject.new("api/v1/permissions/getmycreatorsfor/"+_function_name).$get()
            .then(function(_res){
                var lMycreator = null;
                if (_res.length == 1 ) {
                    lMycreator = _res[0].data.id
                    if (mythis.addToObject) {
                        mythis.addToObject.mycreator = _res[0].data.id; 
                    }
                } 
                mythis.setState({mycreators: _res, show: true, mycreator: lMycreator});
            })
            .catch(function (_reject) {
                if (_reject.status == "404") {
                    mythis.setState({show: false});
                } else {
                    throw _reject;
                }
            });
            
            this.setState({show: true});
        },
        changed: function(_event){
            this.setState({mycreator: _event.target.value});
            if (this.addToObject) {
                this.addToObject.mycreator = _event.target.value; 
            }
        },
        render: function (){
            if (!this.state.show) return (<span></span>);
            
            if (this.state.mycreators.length) {
                var options = this.state.mycreators.map(function(_item){
                    return  (
                        <option key={_item.data.id} value={_item.data.id}>
                           {_item.data.name}
                        </option>
                    );
                });
            } else {
                options = null;
            }
            
            return (
                <div className="input-group">
                    <span className="input-group-addon">
                        {texts.TITLE || "MyCreator"}:
                    </span>
                    <select className="form-control" value={this.state.mycreator} onChange={this.changed}>
                        <option value=""></option>
                        {options}
                    </select>
                </div>    
            );
        }
    });
    
    this.init = function(_elementToDrawIn, _texts){
        if (typeof _texts == "object") {
            texts=_texts;
        }
        
        if (_elementToDrawIn) {
            ReactDOM.render(this.getReactComponent(), _elementToDrawIn);
        }
    }
    
    this.setData = function(){};//assigned by getReactComponent
    
    this.getReactComponent = function (){
        var mythis = this;
        return <ReactMyCreator getSetDataFunction={
            function(_setData){
                mythis.setData = _setData;
            }
        }/>;
    }
}

exports.new = function () {
    return new MyCreator();
}; 