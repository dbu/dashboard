/**
 * @jsx React.DOM
 */
var React = require('react');
var FilterBar = require('./filter-bar.jsx');
var NavControls = require('./nav-controls.jsx');

var Navigation = React.createClass({
//TODO port toggling
    render: function () {
        return (
            <div className="top-navigation" role="navigation">
                <div className="container-fluid">
                    <div className="navbar-header">
                        <button type="button" className="navbar-toggle collapsed">
                            <span className="sr-only">Toggle navigation</span>
                            <span className="icon-bar"></span>
                            <span className="icon-bar"></span>
                            <span className="icon-bar"></span>
                        </button>
                        <a className="navbar-brand" href="#">Issue Dashboard</a>
                    </div>

                    <NavControls collapsedFilters={this.props.collapsedFilters} collapsed={this.props.collapsed} filters={this.props.filters} />

                    <div className="navbar-collapse collapse">
                        <FilterBar collapsedFilters={this.props.collapsedFilters} issues={this.props.issues} filters={this.props.filters} />
                    </div>
                </div>
            </div>
        );
    }
});

module.exports = Navigation;
