/**
 * @jsx React.DOM
 */

var React = require('react');

var NavControls = React.createClass({

    handleReset: function () {
        this.props.filters.set({
            owner: [],
            assignee: [],
            type: [],
            state: [],
            text: null
        });
        this.props.collapsedFilters.set({
            State: true,
            Owner: true,
            Assignee: true,
            Type: true
        });
    },

    toggleProjects: function (state) {
        this.props.collapsed.forEach(function (k, p) {
            p.set(state);
        });
    },

    handleOpen: function () {
        this.toggleProjects(false);
    },

    handleClose: function () {
        this.toggleProjects(true);
    },

    render: function () {
        return (
            <ul className="nav navbar-nav">
                <li>
                    <a className="btn octicon octicon-chevron-up" title="Collapse All" onClick={this.handleClose}></a>
                </li>
                <li>
                    <a className="btn octicon octicon-chevron-down" title="Expand All" onClick={this.handleOpen}></a>
                </li>
                <li>
                    <a className="btn octicon octicon-sync" onClick={this.handleReset}></a>
                </li>
            </ul>
        );
    }

});

module.exports = NavControls;
