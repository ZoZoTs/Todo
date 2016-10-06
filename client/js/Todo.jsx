'use strict';


    
    var confirmDlg = require("js/confirmDlg").new();
    
    var CONST={};
    CONST.FILTER={};
    CONST.FILTER.ALL=2;
    CONST.FILTER.DONE=true;
    CONST.FILTER.INWORK=false;
    
    
    
    var NewTodoItem = React.createClass({
        getInitialState: function() {
                return {newTodoText: ""};
        },
        
        handleTextChange: function(_e) {
            this.setState({newTodoText: _e.target.value});
        },
        
        handleSubmit: function(_e){
            _e.preventDefault();
            this.props.onChange("add", this.state.newTodoText);
            this.setState({newTodoText: ""});
        },
        
        render: function(){
            return (
                <form onSubmit={this.handleSubmit}>
                    <div className="new_todo">
                        <div className="input-group">
                            <input  className="form-control"  type="text" ref="i_new_todo" placeholder={this.props.texts.ENTERNEWTODO||"Enter New Todo"} value={this.state.newTodoText} onChange={this.handleTextChange} style={{width: '100%'}}/>
                            <span className="input-group-btn">
                                <input type="submit" className="btn btn-default" disabled={!this.state.newTodoText} value={this.props.texts.ADD||"ADD"} />
                            </span>
                        </div>
                    </div>
                </form>
            );
        }
    });
    
    var TodoItem = React.createClass({
        getInitialState: function() {
            return {
                editing: false, 
                todoNewText: this.props.data.text,
            };
        },
        
        handleTextChange: function(_e) {
            this.setState({todoNewText: _e.target.value});
        },
        
        handleCheckedChange: function(){
            var data  = this.props.data;
            data.done = !data.done;
            this.props.onChange("change", data);
        },
        
        handleEditClick: function(){
            if (this.state.editing) {
                var data  = this.props.data;
                data.text = this.state.todoNewText;
                this.props.onChange("change", data);
            }
            this.setState({editing: !this.state.editing});
        },
        handleCancelClick: function(){
            this.setState({editing: !this.state.editing, todoNewText: this.props.data.text});
        },
        handleDeleteClick: function(){
            var data  = this.props.data;
            var mythis = this;
            confirmDlg.ask(this.props.texts.CONFIRMDLG.HEADER||"Warning", 
                (this.props.texts.CONFIRMDLG.QUESTION||"Do you realy want to delete item: %i")
                    .replace("%i", data.text), 
                this.props.texts.CONFIRMDLG.YES||"Yes", 
                this.props.texts.CONFIRMDLG.NO||"No")
            .then(function(){
                mythis.props.onChange("delete", data);
            }, function(){});
        
        },
        handleKeyDown: function(_e){
            if ( _e.keyCode == 27 && this.state.editing) { //esc
                this.handleCancelClick();
            } else if (_e.keyCode == 13 && this.state.editing) { //enter
                this.handleEditClick();
            }
        },
        render: function(){
            if (this.state.editing) {
                var body = ( 
                    <div className="input-group">
                        <span className="input-group-addon">
                                <span className={this.props.data.done?"glyphicon glyphicon-check":"glyphicon glyphicon-unchecked"}></span>
                        </span>
                        <input className="form-control"  type="text" placeholder={this.props.texts.ENTERTODOTEXT||"Enter Todo Text"} value={this.state.todoNewText} onChange={this.handleTextChange} />
                        <span className="input-group-btn">
                            <button className="btn btn-default" onClick={this.handleEditClick} disabled={!this.state.todoNewText}>
                                <span className="glyphicon glyphicon-floppy-disk"></span>
                            </button>
                            <button className="btn btn-default" onClick={this.handleCancelClick}>
                                <span className="glyphicon glyphicon-floppy-remove"></span>
                            </button>
                        </span> 
                    </div>
                );
            } else {
                body = (
                    <div className="input-group">
                        <span className="input-group-addon">
                                <span className={this.props.data.done?"glyphicon glyphicon-check":"glyphicon glyphicon-unchecked"} onClick={this.handleCheckedChange}></span>
                        </span>
                        <span className="form-control">
                                {this.props.data.text}
                        </span>
                        <span className="input-group-btn">
                            <button className="btn btn-default" onClick={this.handleEditClick} disabled={!this.state.todoNewText}>
                                <span className="glyphicon glyphicon-edit"></span>
                            </button>
                            <button className="btn btn-default" onClick={this.handleDeleteClick}>
                                <span className="glyphicon glyphicon-remove"></span>
                            </button>
                        </span>
                    </div>
                );
            }
            return (
                <li className="list-group-item" onKeyDown={this.handleKeyDown}>
                    {body}
                </li>
            );
        },
    });
    
    var TodoList = React.createClass({
        getInitialState: function() {
                return {filterText: "", filterDone: 0};
            },
            
        handleFilterTextChange: function(_e) {
            var state = this.state;
            state.filterText=_e.target.value;
            this.setState(state);
        },
        
        handleItemChange: function(_action, _item){
            this.props.onChange(_action, _item);
        },
        
        handleFilterDoneChange: function(_value) {
            var state = this.state;
            state.filterDone=_value;
            this.setState(state);
        }, 
        
        render: function(){
            
            var data = [];
            var mythis=this;
            if (this.props.data.length) {
                this.props.data.map(function(_item, _index){
                    if (~_item.text.indexOf(mythis.state.filterText) && (mythis.state.filterDone == CONST.FILTER.ALL || mythis.state.filterDone == _item.done)) {
                        _item.index=_index;
                        data.push(_item);
                    }
                });
            }
    
            var todo_maps = data.map(function(_item, _index) {
                return (
                    <TodoItem key={_item.text} data={_item} onChange={mythis.handleItemChange} texts={mythis.props.texts}/>
                );
            });
            
            
            return (
                <div className="todos panel panel-info">
                   <div className="input-group panel-heading">
                        <span className="input-group-btn">
                            <button className={"btn btn-default " + (this.state.filterDone == CONST.FILTER.ALL ? 'active':'')} onClick={this.handleFilterDoneChange.bind(this, CONST.FILTER.ALL)}>{this.props.texts.ALL||"All"}</button>
                            <button className={"btn btn-default " + (this.state.filterDone == CONST.FILTER.DONE ? 'active':'')} onClick={this.handleFilterDoneChange.bind(this, CONST.FILTER.DONE)}><span className="glyphicon glyphicon-ok"></span></button>
                            <button className={"btn btn-default " + (this.state.filterDone == CONST.FILTER.INWORK ? 'active':'')} onClick={this.handleFilterDoneChange.bind(this, CONST.FILTER.INWORK)}><span className="glyphicon glyphicon-unchecked"></span></button>
                        </span>
                        <input className="form-control" type="text" onChange={this.handleFilterTextChange} placeholder={this.props.texts.FILTER||"Filter"} value={this.state.filterText} />
                    </div>
                    <div className="panel-body">
                        <ul className="list-group">
                            {todo_maps}
                            <li className="list-group-item">
                                <NewTodoItem onChange={this.props.onChange} texts={this.props.texts}/>
                            </li>
                        </ul>
                        
                    </div>
                    {confirmDlg.getReactComponent()}
                </div>
            );
        }
    });
    
    
    exports.elem = React.createClass({
        getDefaultProps: function() {
            return {
                texts: { "CONFIRMDLG":{
                            "HEADER":"Warning",
                            "QUESTION":"Do you realy want to delete item: %i",
                            "YES":"Yes",
                            "NO":"No"
                        }
                },
                todos: []
            };
        },
        
        getTodosData: function(){
           return this.props.todos.map(function(_item){
                return jQuery.extend(true, {}, _item);
            });;
        },
        
        handleChange: function(_action, _item){
            var todos = this.getTodosData();  //this.getTodosData() used to make a clone 
            if (_action == "add") {
               var NewTodo = function (todoText){
                    this.text=todoText;
                    this.done=false;
                }
                var item = new NewTodo(_item);
                todos.push(item);
            } else if (_action == "change") {
                todos[_item.index]=_item;
                delete todos[_item.index].index;//clear returned index of item don't needed to save
            } else if (_action == "delete") {
                todos.splice(_item.index,1);
            }
            
            
            
            this.props.onChange(todos);
        },
    
        render: function(){
            return (
                <div> 
                    <TodoList data={this.getTodosData()} onChange={this.handleChange} texts={this.props.texts}/> {/*this.getTodosData() used to make a clone*/ }
                </div>
            );
        }
    });
    
   