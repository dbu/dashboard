/**
 * @jsx React.DOM
 */
var React = require('react');
var FilterBar = require('./filter-bar.jsx');
var NavControls = require('./nav-controls.jsx');

var Navigation = React.createClass({

    getInitialState: function () {
        return {
            collapsed: true
        };
    },

    toggle: function() {
        this.setState({collapsed: !this.state.collapsed});
    },

    render: function () {
        var classes = this.state.collapsed ? 'navbar-collapse collapse' : 'navbar-collapse collapse in';

        return (
            <div className="top-navigation" role="navigation">
                <div className="container-fluid">
                    <div className="navbar-header">
                        <button type="button" ref="toggler" className="navbar-toggle" onClick={this.toggle}>
                            <span className="sr-only">Toggle navigation</span>
                            <span className="icon-bar"></span>
                            <span className="icon-bar"></span>
                            <span className="icon-bar"></span>
                        </button>
                        <a className="navbar-brand" href="#">Issues</a>

                        <NavControls collapsedFilters={this.props.collapsedFilters} collapsed={this.props.collapsed} filters={this.props.filters} />
                    </div>

                    <div className={classes}>
                        <FilterBar collapsedFilters={this.props.collapsedFilters} issues={this.props.issues} filters={this.props.filters} />
                    </div>
                </div>
            </div>
        );
    }
});

module.exports = Navigation;
