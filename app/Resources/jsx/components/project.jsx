/**
 * @jsx React.DOM
 */

var React = require('react');
var Issue = require('./issue.jsx');
var Router = Routing;
var reqwest = require('reqwest');

var Project = React.createClass({

    getDefaultProps: function () {
        return {
            collapsed: true
        };
    },

    getInitialState: function () {
        return {
            loaded: false
        };
    },

    loadIssues: function () {
        var url = Router.generate('rs_issues_issues', {'project': this.props.key});

        reqwest({
            url : url,
            type: 'json',
            success: function (result) {
                if (this.isMounted()) {
                    this.props.issues.set(result);
                }
                this.setState({loaded: true});
            }.bind(this)
        });
    },

    componentWillReceiveProps: function(nextProps) {
        if (this.props.issues.count() === 0 && nextProps.collapsed.val() === false && this.props.project.issuesCount.val() > 0) {
            this.loadIssues();
        }
    },

    handleToggle: function () {
        if (this.props.project.issuesCount.val() === 0) {
            return;
        }

        if (this.props.issues.count() === 0) {
            this.loadIssues();
        }
        this.props.collapsed.set(!this.props.collapsed.val());
    },

    renderIssue: function (issue, key) {
        return <Issue key={key} issue={issue}></Issue>;
    },

    filterIssues: function () {
        var issues = [];

        if (this.props.issues.count() > 0) {
            var checkFilter = function (key, issue, filters) {
                return filters[key].count() === 0 || filters[key].val().indexOf(issue[key].val()) != -1;
            };

            this.props.issues.map(function (issue) {
                var authorFilter = checkFilter('author', issue, this.props.filters);
                var assigneeFilter = checkFilter('assignee', issue, this.props.filters);
                var typeFilter = checkFilter('type', issue, this.props.filters);
                var textFilter = this.props.filters.description.val() === null || issue.title.val().toLowerCase().indexOf(this.props.filters.description.val().toLowerCase()) != -1;
                var stateFilter = checkFilter('state', issue, this.props.filters);

                if (authorFilter && assigneeFilter && typeFilter && textFilter && stateFilter) {
                    issues.push(issue);
                }
            }.bind(this));
        }

        return issues;
    },

    calculateContainerClasses: function(issues) {
        var classes = ['repository'];

        if (this.props.project.issuesCount.val() === 0) {
            classes.push('empty');
        }
        if (issues === 0 && this.state.loaded === true) {
            classes.push('shim');
        }

        return classes.join(' ');
    },

    calculateCollapsedState: function(issues) {
        var collapse = this.props.collapsed.val() === true ? 'collapsed' : 'collapsed in';

        if (this.props.project.issuesCount.val() === 0 || !issues) {
            collapse = 'collapsed';
        }

        return collapse;
    },

    determineIcon: function() {
        if ('github' === this.props.project.type.val()) {
            return <span className="octicon octicon-mark-github" />
        } else if ('jira' === this.props.project.type.val()) {
            return <img width="16" height="16" src="/img/jira.svg" />
        } else if ('gitlab' === this.props.project.type.val()) {
            return <img width="18" height="18" src="/img/gitlab.svg" />
        }
    },

    renderBadges: function(badge) {
        return <a href={badge.val().link} target="_blank"><img height="18" src={badge.val().img} /></a>
    },

    render: function () {
        var issues = this.filterIssues();
        var project = this.props.project;
        var containerClasses = this.calculateContainerClasses(issues.length);
        var collapse = this.calculateCollapsedState(issues.length);
        var count = this.state.loaded ? issues.length : project.issuesCount.val();
        var type = this.determineIcon();

        return (
            <div className={containerClasses}>
                <header onClick={this.handleToggle}>
                    <h3>{type} <a href={project.url.val()} target="_blank">{project.name.val()}</a> ({count})
                        <br/>
                        <small>{project.description.val()}</small>
                    </h3>
                    <span className="badges">
                        {project.badges.map(this.renderBadges)}
                    </span>
                </header>
                <article className={collapse}>
                    <div className="issues">
                        {issues.map(this.renderIssue)}
                    </div>
                </article>
            </div>
        );
    }

});

module.exports = Project;
