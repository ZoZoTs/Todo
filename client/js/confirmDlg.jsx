'use strict';


var ConfirmDlg = function(){
    var myPromise={}; 

    var setDlgData; //function, will be setted by getInitialState
    var elem; //store dialog element


    var ReactConfirmDlg = React.createClass({
        getInitialState: function () {
            return this.props.data;
        },
        handleOkClick: function(){
            this.state.promise.resolve();
            $(this.refs.myModalConfirmDlg).modal('hide');
        },
        setData: function (_data){
            this.setState(_data);
        },
        componentDidMount: function () {
            this.props.sendBack(this.refs.myModalConfirmDlg, this.setData);
        },
        handleKeyDown: function(_e){
            if ( _e.keyCode == 27 ) { //esc
                $(this.refs.myModalConfirmDlg).modal('hide');
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
                        <p>{this.state.question||""}</p>
                      </div>
                      <div className="modal-footer">
                        <button type="button" className="btn btn-default"  
                                onClick={this.handleOkClick} data-autofocus>
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

    this.ask = function (_header, _question, _okButtonText, _cancelButtonText) {
        if (!elem) {
            throw new Error("no react component added to page");
        }
        
        myPromise.hndl = new Promise(function(_resolve, _reject){
            myPromise.resolve = _resolve;
            myPromise.reject = _reject;
        });
        
        var data = {
            header: _header,
            question: _question,
            okButtonText: _okButtonText,
            cancelButtonText: _cancelButtonText,
            promise: myPromise
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
        return <ReactConfirmDlg data={{}} sendBack={function(_elem, _setFunction){elem=_elem; setDlgData=_setFunction;}}/>;
    }    
}

exports.new = function () {
    return new ConfirmDlg();
};