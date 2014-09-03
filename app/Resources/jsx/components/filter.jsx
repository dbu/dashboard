/**
 * @jsx React.DOM
 */

var React = require('react');
var FilterField = require('./filter-field.jsx');

var Filter = React.createClass({

    getDefaultProps: function () {
        return {
            key: null,
            values: []
        };
    },

    handleToggle: function () {
        if (this.props.values.length === 0) {
            return;
        }

        var state = this.props.collapsedFilters[this.props.key].val();

        this.props.collapsedFilters.forEach(function(key, collapse) {
                if (key === this.props.key) {
                    collapse.set(!state);
                } else {
                    collapse.set(true);
                }
        }.bind(this));
    },

    renderFilterItem: function (item, key) {
        return <li>
            <FilterField key={key} filters={this.props.filters} name={this.props.key} value={item} />
        </li>;
    },

    render: function () {
        var collapse = !this.props.collapsedFilters[this.props.key] || this.props.collapsedFilters[this.props.key].val() === true ? 'dropdown-menu' : 'dropdown-menu open';
        var caret = this.props.values.length > 0 ? <span className="octicon octicon-chevron-down"></span> : '';

        return (
            <div className="input-group nav navbar-nav">
                <div className="input-group-btn">
                    <a className="btn btn-default" onClick={this.handleToggle} disabled={caret === '' ? 'disabled' : ''} >
                        {this.props.key}
                        {caret}
                    </a>

                    <ul role="menu" className={collapse}>
                        { this.props.values.map(this.renderFilterItem) }
                    </ul>
                </div>
            </div>
        );
    }

});

module.exports = Filter;
