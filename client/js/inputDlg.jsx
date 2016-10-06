'use strict';

var InputDlg = function(_additionalComponents){
    var myPromise={}; 
    
    var setDlgData; //function, will be setted by getInitialState
    var elem; //store dialog element    
    
    this.additionalComponents=_additionalComponents;
    
    var ReactInputDlg = React.createClass({
        getInitialState: function(){
            return {};
        },
        handleOkClick: function(){
            this.state.promise.resolve(this.refs.i_result.value);
            $(this.refs.myModalConfirmDlg).modal('hide');
            this.refs.i_result.value = "";
        },
        setData: function (_data){
            this.refs.i_result.value = _data.defaultValue;
            this.setState(_data);
        },
        componentDidMount: function () {
            this.props.sendBack(this.refs.myModalConfirmDlg, this.setData);
        },
        handleKeyDown: function(_e){
            if (_e.keyCode == 27) { //esc
                $(this.refs.myModalConfirmDlg).modal('hide');
            } else if (_e.keyCode == 13) { //enter
                this.handleOkClick();
            }
        },
        render: function () {
            return (
                <div ref="myModalConfirmDlg" className="modal fade" role="dialog" onKeyDown={this.handleKeyDown}>
                  <div className="modal-dialog">
                    <div className="modal-content">
                      <div className="modal-header">
                        <button type="button" className="close" data-dismiss="modal">
                            &times;
                        </button>
                        <h4 className="modal-title">{this.state.header||""}</h4>
                      </div>
                      <div className="modal-body">
                        <input className="form-control" type="text" ref="i_result" style={{width: '100%'}} data-autofocus />
                        {this.props.additionalComponents}
                      </div>
                      <div className="modal-footer">
                        <button type="button" className="btn btn-default"  
                                onClick={this.handleOkClick}>
                            {this.state.okButtonText||"Ok"}
                        </button>
                        <button type="button" className="btn btn-default" 
                                data-dismiss="modal">
                            {this.state.cancelButtonText||"Cancel"}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                
            );
        }
    });
    

    this.ask = function (_header, _defaultValue, _okButtonText, _cancelButtonText, _additionalComponent) {
        if (!elem) {
            throw new Error("no react component added to page");
        }
        
        myPromise.hndl = new Promise(function(_resolve, _reject){
            myPromise.resolve = _resolve;
            myPromise.reject = _reject;
        });
        
        var data = {
            header: _header,
            defaultValue: _defaultValue,
            okButtonText: _okButtonText,
            cancelButtonText: _cancelButtonText,
            promise: myPromise,
        }
        setDlgData(data);
        
        $(elem).on('hidden.bs.modal', function () {
            myPromise.reject('Canceled'); 
            $(elem).off('hidden.bs.modal');
        });
        
        $(elem).on('shown.bs.modal', function () {
            elem.querySelector('[data-autofocus]').focus();
            $(elem).off('shown.bs.modal');
        });
        
        $(elem).modal('show');
        
        
        
        return myPromise.hndl;
    };
    
    this.getReactComponent = function (){
        return <ReactInputDlg  additionalComponents={this.additionalComponents} sendBack={function(_elem, _setFunction){elem=_elem; setDlgData=_setFunction;}}/>;
    }
   
}

exports.new = function (_additionalComponents) {
    return new InputDlg(_additionalComponents);
}; 
    
    