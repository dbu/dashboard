/**
 * @jsx React.DOM
 */
var React = require('react');
var Project = require('./project.jsx');

var ProjectsContainer = React.createClass({

    renderProjects: function () {
        var projects = [];

        this.props.projects.forEach(function (key, project) {
            projects.push(<Project key={key} filters={this.props.filters} project={project} collapsed={this.props.collapsed[key]} issues={this.props.issues[key]} ></Project>);
        }.bind(this));

        if (0 === projects.length) {
            projects.push(<div className="panel panel-danger"><div className="panel-heading"><h3>No Projects imported</h3></div><div className="panel-body">did you run "app/console fos:elastica:populate"?</div></div>);
        }

        return projects;
    },

    render: function () {
        return (
            <div className="content">
                <div className="main">
                    {this.renderProjects()}
                </div>
            </div>
        );
    }
});

module.exports = ProjectsContainer;
