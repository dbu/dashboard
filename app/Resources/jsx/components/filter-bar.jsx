/**
 * @jsx React.DOM
 */

var React = require('react');
var Filter = require('./filter.jsx');

var FilterBar = React.createClass({

    filterFilterValues: function (name) {
        var values = [];
        this.props.issues.forEach(function (key, issue) {
            if (typeof issue[0] !== 'undefined' && issue[0].hasKey(name.toLowerCase())) {
                if (-1 === values.indexOf(issue[0].val()[name.toLowerCase()])) {
                    values.push(issue[0].val()[name.toLowerCase()]);
                }
            }
        }.bind(this));

        return values;
    },

    renderFilter: function (name) {
        if (!this.props.collapsedFilters.hasKey(name)) {
            this.props.collapsedFilters.add(name, true);
        }
        return <Filter key={name} collapsedFilters={this.props.collapsedFilters} values={this.filterFilterValues(name)} filters={this.props.filters} />;
    },

    handleSearch: function () {
        this.props.filters.text.set(this.refs.fulltext.getDOMNode().value);
    },

    render: function () {
        return (
            <form method="GET" className="navbar-form navbar-right" role="search">
                { this.renderFilter('State') }
                { this.renderFilter('Type') }
                { this.renderFilter('Owner') }
                { this.renderFilter('Assignee') }
                <div className="form-group nav navbar-nav">
                    <input type="text" className="form-control" ref="fulltext" onChange={this.handleSearch} placeholder="Search" />
                </div>
            </form>
        );
    }

});

module.exports = FilterBar;
