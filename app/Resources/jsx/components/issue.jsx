/**
 * @jsx React.DOM
 */

var React = require('react');
var marked = require('marked');
var moment = require('moment');
var xss = require('xss');

var Issue = React.createClass({

    getDefaultProps: function () {
        return {
            key: null,
            onIssueLoad: null
        };
    },


    getInitialState: function () {
        return {
            collapsed: true
        };
    },

    handleToggle: function (event) {
        this.setState({collapsed: !this.state.collapsed});
    },

    convertText: function(text) {
        if (!text) {
            return null;
        }
        text = xss(text);
        try {
            return marked(text, {sanitize: true});
        } catch (e) {
            return text;
        }
    },

    render: function () {
        var icon = this.props.issue.type.val() == 'pull' ? "octicon octicon-git-pull-request type" : "octicon octicon-issue-opened type";
        var collapse = this.state.collapsed ? 'collapsed' : 'collapsed in';
        var collapseMarkup = this.props.issue.description.val() ? <span className="collapser octicon octicon-chevron-down pull-right" onClick={this.handleToggle}></span> : '';

        return (
            <div className="issue">
                <h4>
                    <span className={icon}></span>
                    <a target="_blank" href={ this.props.issue.url.val() }>#{this.props.issue.id.val()} { this.props.issue.title.val() }</a>
                { collapseMarkup }
                </h4>
                <div>
                    <header>
                        <span>By:
                            <small>
                                <a href={this.props.issue.author_url.val()} target="_blank">{this.props.issue.author.val()}</a>
                            </small>
                        </span>
                        <span>At:
                            <small>{ moment(new Date(1000 * this.props.issue.created_at.val())).format('DD.MM.GGGG HH:mm') }</small>
                        </span>
                        <span>Updated at:
                            <small>{ moment(new Date(1000 * this.props.issue.updated_at.val())).format('DD.MM.GGGG HH:mm') }</small>
                        </span>
                        <span>Comments:
                            <small>{this.props.issue.comment_count.val()}</small>
                        </span>
                        <span>Assignee:
                            <small>{this.props.issue.assignee.val()}</small>
                        </span>
                    </header>
                    <div dangerouslySetInnerHTML={ {__html: this.convertText(this.props.issue.description.val())} } className={collapse}>
                    </div>
                </div>
            </div>
        );
    }

});

module.exports = Issue;
