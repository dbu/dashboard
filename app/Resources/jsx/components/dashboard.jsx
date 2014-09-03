/**
 * @jsx React.DOM
 */
var React = require('react');
var Navigation = require('./navigation.jsx');
var ProjectsContainer = require('./projects-container.jsx');
var reqwest = require('reqwest');
var Router = Routing;

var Dashboard = React.createClass({

    getDefaultProps: function () {
        return {
            projectsUrl: Router.generate('rs_issues_projects')
        };
    },

    componentDidMount: function () {
        this.loadProjects();
    },

    initializeCortex: function (result) {
        this.props.cortex.projects.set(result);

        for (var key in result) {
            if (result.hasOwnProperty(key)) {
                this.props.cortex.issues.add(key, []);
                this.props.cortex.collapsed.add(key, true);
            }
        }
    },

    loadProjects: function () {
        reqwest({
            url: this.props.projectsUrl,
            type: 'json',
            success: function (result) {
                if (this.isMounted()) {
                    this.initializeCortex(result);
                }
            }.bind(this)
        });
    },

    render: function () {
        return (
            <div className="dashboard">
                <Navigation
                issues={this.props.cortex.issues}
                filters={this.props.cortex.filters}
                collapsed={this.props.cortex.collapsed}
                collapsedFilters={this.props.cortex.collapsedFilters}
                />

                <ProjectsContainer
                issues={this.props.cortex.issues}
                projects={this.props.cortex.projects}
                collapsed={this.props.cortex.collapsed}
                filters={this.props.cortex.filters}
                />
            </div>
        );
    }
});

module.exports = Dashboard;
