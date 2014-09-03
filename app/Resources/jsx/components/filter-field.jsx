/**
 * @jsx React.DOM
 */

var React = require('react');

var FilterField = React.createClass({

    getDefaultProps: function () {
        return {
            name: null,
            value: null
        };
    },

    handleChange: function (e) {
        var add = this.refs.field.getDOMNode().checked;
        var name = this.props.name.toLowerCase();
        if (add) {
            this.props.filters[name].push(this.props.value);
        } else {
            var index = this.props.filters[name].findIndex(function (v, k) {
                return v.val() === this.props.value;
            }.bind(this));

            this.props.filters[name].removeAt(index);
        }
    },

    render: function () {
        var checked = this.props.filters[this.props.name.toLowerCase()] && -1 < this.props.filters[this.props.name.toLowerCase()].val().indexOf(this.props.value);

        return (
            <a href="#" className="checkbox">
                <label>
                    <input type="checkbox" ref="field" checked={checked} onChange={this.handleChange} />{this.props.value}
                </label>
            </a>
        );
    }

});

module.exports = FilterField;
