'use strict';

var ajaxObject = require('js/ajaxObject');

var LoginDlg = function(_url, _elementToDrawIn, _texts){
    
    var myPromise={}; 
    
    var setDlgData; //function, will be setted by getInitialState
    var elem; //store dialog element
    
    var loginQuery = ajaxObject.new(_url); 
    
    var ReactLoginDlg = React.createClass({
        getInitialState: function(){
            var mythis = this;
            loginQuery.$get()
            .then(function(_res){
                mythis.setState({user: _res})
            })
            .catch(function(){
                mythis.login();
            });
            return {user: {}};
        },
        handleOkClick: function(){
            var mythis=this;
            mythis.setState({error: ""})
            if (mythis.refs.i_login.value) {
    	        loginQuery.data={"login":mythis.refs.i_login.value, "pass":mythis.refs.i_password.value};
    	        mythis.refs.i_password.value = "";
    	        loginQuery.$add()
    	        .then(function(_res){
    				mythis.setState({user: _res});
    				mythis.refs.i_password.value = "";
    				if (mythis.state.promise) {
    				    mythis.state.promise.resolve(mythis.refs.i_login.value);
    				}
    				$(mythis.refs.myModalLoginDlg).modal('hide');
    				//waiting.httpBuffer.retryAll();
    			})
    			.catch(function(){
    			    mythis.setState({error: mythis.props.texts.USERNAMEORPASSWORDISINCORRECT|| "Username or password is incorrect"})
    			});
             } else {
                mythis.setState({error: mythis.props.texts.USERNAMEISEMPTY || "Username is empty"})
    	     }
        },
        setData: function (_data){
            if ( _data.defaultLogin) {
                this.refs.i_login.value = _data.defaultLogin;
            }
            _data.error = "";
            this.setState(_data);
        },
        componentDidMount: function () {
            this.props.sendBack(this.refs.myModalLoginDlg, this.setData);
        },
        handleKeyDown: function(_e){
            if (_e.keyCode == 27) { //esc
                $(this.refs.myModalLoginDlg).modal('hide');
            } else if (_e.keyCode == 13) { //enter
                this.handleOkClick();
            }
        },
        login: function(){
             $(this.refs.myModalLoginDlg).modal('show');
        },
        logout: function(){
            var mythis = this;
            loginQuery.$del()
            .then(function(){
                mythis.setState({user: {}});
            });
        },
        render: function () {
            if (this.state.user.AUTH == 'TRUE') {
                var userData = (
                    <ul className="nav navbar-nav navbar-right">
                        <li><a href="javascript:" ><span className="glyphicon glyphicon-user"></span> {this.state.user.FULL_NAME}({this.state.user.USERNAME})</a></li>
                        <li><a onClick={this.logout}><span className="glyphicon glyphicon-log-out"></span> {this.props.texts.LOGOUT || "Logout"}</a></li>
                    </ul>
                );
            } else {
                userData = (
                    <ul className="nav navbar-nav navbar-right">
                        <li><a onClick={this.login}><span className="glyphicon glyphicon-log-in"></span> {this.props.texts.LOGIN || "Login"}</a></li>    
                    </ul>
                )
            }
            return (
                <div>
                    {userData}
                    <div ref="myModalLoginDlg" className="modal fade" role="dialog" onKeyDown={this.handleKeyDown}>
                      <div className="modal-dialog">
                        <div className="modal-content">
                          <div className="modal-header">
                            <button type="button" className="close" data-dismiss="modal">
                                &times;
                            </button>
                            <h4 className="modal-title">{this.state.header||"Enter Login Data"}</h4>
                          </div>
                          <div className="modal-body">
                            <div className="alert alert-danger" role="alert" style={{display: this.state.error?'block':'none'}}>
                                {this.props.texts.ERROR || "Error"}: {this.state.error}
                            </div>
                            <div className="input-group">
                                <span className="input-group-addon">
                                    {this.props.texts.LOGIN || "Login"}:
                                </span>
                                <input className="form-control" type="text" ref="i_login" style={{width: '100%'}} data-autofocus />
                            </div>
                            <div className="input-group">
                                <span className="input-group-addon">
                                    {this.props.texts.PASSWORD || "Password"}:
                                </span>
                                <input className="form-control" type="password" ref="i_password" style={{width: '100%'}} />
                            </div>
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
                </div>
            );
        }
    });
    
    this.show = function (_defaultLogin) {
        if (!elem) {
            throw new Error("no react component added to page");
        }
        
        myPromise.hndl = new Promise(function(_resolve, _reject){
            myPromise.resolve = _resolve;
            myPromise.reject = _reject;
        });
        
        var data = {
            defaultLogin: _defaultLogin || "",
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
        return <ReactLoginDlg texts={_texts || {}} sendBack={function(_elem, _setFunction){elem=_elem; setDlgData=_setFunction;}}/>;
    }
   
    if (_elementToDrawIn) {
        ReactDOM.render(this.getReactComponent(), _elementToDrawIn);
    }
   
}

exports.new = function (_elementToDrawIn, _url, _texts) {

    return new LoginDlg(_url, _elementToDrawIn, _texts);
}; 
    
    