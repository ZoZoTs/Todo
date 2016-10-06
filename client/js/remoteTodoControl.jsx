'use strict';


    var todo = require('js/Todo').elem;
    var confirmDlg = require("js/confirmDlg").new();
    var mycreator = require('js/myCreatorSelect').new();
    var inputDlg = require("js/inputDlg").new(mycreator.getReactComponent());
    
    
    exports.elem = React.createClass({
        getDefaultProps: function() {
            return {
                texts: { 
                    "INPUTDLG": {},
                    "CONFIRMDLG":{},
                    "myCreator":{}
                }
            };
        },
        getInitialState: function(){
            mycreator.init(null, this.props.texts.myCreator);
            return {items: this.props.ajaxObject.data, index: 0, showEditButtons: false, todos: []};
        },
        componentDidMount: function () {
            if (this.props.ajaxObject.data.length) {
                this.changeIndex(0);
            }
        },
        
        handleTodoChange: function(_todo){
            var item = this.state.items[this.state.index];
            var json_body = JSON.stringify(_todo)
            item.data.json_body=json_body;
            item.$set();
            
            var match = json_body.match(/"done":false/g) || [];
            var count = match.length;
            this.state.items[this.state.index].data.undone_count = count;
            this.state.todos=_todo;
            this.setState(this.state);
            
        },
        
        changeIndex: function(_index) {
            if (_index >=0 && this.props.ajaxObject.data[_index]) {
                var mythis = this;
                this.setState({index: _index});
                this.props.ajaxObject.data[_index].$get()
                .then(function(_item){
                    var todo_data = _item.json_body ? JSON.parse(_item.json_body) : [];
                    mythis.setState({todos: todo_data});
                });
            } else {
                this.setState({index: -1});
                this.setState({todos: []});
            }
        },
        handleTodoIndexChange: function(_event){
            this.changeIndex(_event.target.value);
        },
        
        handleAddButtonClick: function(){
            var mythis = this;
            var newitem={};
            mycreator.setData("todo", newitem);
            inputDlg.ask(this.props.texts.INPUTDLG.HEADER_ADD||"Enter New todo name", 
                "", 
                this.props.texts.INPUTDLG.OK||"OK", 
                this.props.texts.INPUTDLG.CANCEL||"Cancel"
                )
            .then(function(_res){
                newitem.name = _res;
                newitem.json_body = "";
                mythis.props.ajaxObject.$newchild(newitem)
                .$add().then(function(_res){
                    mythis.setState({items: mythis.props.ajaxObject.data});
                    // mythis.props.ajaxObject.$get()
                    // .then(function(){
                    //     mythis.setState({items: mythis.props.ajaxObject.data, index: 0});
                    // });
                }).catch(function(_e){throw _e;})
            }, function(){});
        },
        
        handleEditButtonClick: function(){
            var mythis = this;
            inputDlg.ask(this.props.texts.INPUTDLG.HEADER_EDIT||"Enter todo new name", 
                mythis.state.items[mythis.state.index].data.name, 
                this.props.texts.INPUTDLG.OK||"OK", 
                this.props.texts.INPUTDLG.CANCEL||"Cancel")
            .then(function(_res){
                mythis.state.items[mythis.state.index].data.name=_res;
                mythis.state.items[mythis.state.index].$set().then(function(){
                    mythis.setState({items: mythis.state.items});
                    // mythis.props.ajaxObject.$get()
                    // .then(function(){
                    //     mythis.setState({items: mythis.props.ajaxObject.data, index: 0});
                    // });
                });
            }, function(){});
        },
        
        handleDeleteButtonClick: function(){
            var mythis = this;
            var item = mythis.state.items[this.state.index];
            confirmDlg.ask(this.props.texts.CONFIRMDLG.HEADER||"Warning", 
                (this.props.texts.CONFIRMDLG.QUESTION||"Do you realy want to delete item: %i")
                    .replace("%i", item.data.name), 
                this.props.texts.CONFIRMDLG.YES||"Yes", 
                this.props.texts.CONFIRMDLG.NO||"No")
            .then(function(){
                item.$del().then(function(){
                    mythis.props.ajaxObject.data.splice(mythis.state.index,1);
                    if (mythis.state.index >= mythis.props.ajaxObject.data.length) {
                        mythis.state.index = mythis.props.ajaxObject.data.length-1;
                    }
                    mythis.setState({items: mythis.props.ajaxObject.data});
                    mythis.changeIndex(mythis.state.index);
                    // mythis.props.ajaxObject.$get()
                    // .then(function(){
                    //     mythis.setState({items: mythis.props.ajaxObject.data, index: 0});
                    // });
                });
            });
        },
        
        toggleEditButtonsShowing: function (){
          this.setState({showEditButtons: !this.state.showEditButtons});
        },
        
        handleRefreshButtonClick: function(){
            var mythis = this;
            mythis.props.ajaxObject.$get()
            .then(function(){
                mythis.setState({items: mythis.props.ajaxObject.data});
                mythis.changeIndex(mythis.state.index);
            })
            .catch(function(_e){throw _e;});
        },
        
        render: function(){
            if (this.state.items.length){
                var options = this.state.items.map(function(_item, _index){
                    if (_item.data.undone_count) {
                        var display_name = _item.data.name + ' ('+_item.data.undone_count+')';
                    } else {
                        display_name = _item.data.name;
                    }
                    return (
                        <option key={_index} value={_index}>{display_name}</option>
                    );
                });
            }

            if (this.state.showEditButtons) {
                var editButtons = (
                    <div className="input-group-btn">
                        <button className="btn btn-default" onClick={this.handleRefreshButtonClick}><span className="glyphicon glyphicon-refresh"></span></button>
                        <button className="btn btn-default" onClick={this.handleAddButtonClick}><span className="glyphicon glyphicon-plus"></span></button>
                        <button className="btn btn-default" onClick={this.handleEditButtonClick} style={this.props.ajaxObject.data.length?{}:{display: "none"}}><span className="glyphicon glyphicon-edit"></span></button>
                        <button className="btn btn-default" onClick={this.handleDeleteButtonClick} style={this.props.ajaxObject.data.length?{}:{display: "none"}}><span className="glyphicon glyphicon-remove"></span></button>
                        <button className="btn btn-default active" onClick={this.toggleEditButtonsShowing}><span className="glyphicon glyphicon-expand"></span></button>
                    </div>    
                );
            } else {
                editButtons = (
                    <div className="input-group-btn">
                        <button className="btn btn-default" onClick={this.handleRefreshButtonClick}><span className="glyphicon glyphicon-refresh"></span></button>
                        <button className="btn btn-default" onClick={this.toggleEditButtonsShowing}><span className="glyphicon glyphicon-expand"></span></button>
                    </div>   
                );
            }
     
            return (
                <div className="remoteTodoControl"> 
                    <div className="input-group" >
                        <select type="button" className="form-control" value={this.state.index} onChange={this.handleTodoIndexChange}>
                            {options}    
                        </select>
                        {editButtons}
                    </div>

                    <div style={this.props.ajaxObject.data.length?{}:{display: "none"}}>
                        {React.createElement(todo, {texts: this.props.texts.todo,  onChange: this.handleTodoChange, todos: this.state.todos} ) }
                    </div>
                    
                    {inputDlg.getReactComponent()}
                    {confirmDlg.getReactComponent()}
                </div>
            );
        }
    });
    

