/**
 * @jsx React.DOM
 */
var React = require('react');
var Dashboard = require('./components/dashboard.jsx');
var Cortex = require('cortexjs');
var Router = Routing;

React.initializeTouchEvents(true);

var cortex = new Cortex({
    projects: {},
    issues: {},
    collapsed: {},
    collapsedFilters: {},
    filters: {
        state: [],
        author: [],
        assignee: [],
        description: null,
        type: []
    }
});

var dashboardComponent = React.renderComponent(<Dashboard cortex={cortex} />, document.getElementById('container'));

cortex.on('update', function (cortex) {
    dashboardComponent.setProps({cortex: cortex});
});
