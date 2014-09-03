/**
 * @jsx React.DOM
 */

var React = require('react');
var marked = require('marked');
var moment = require('moment');

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
        try {
            return marked(text);
        } catch (e) {
            return text;
        }
    },

    render: function () {
        var icon = this.props.issue.type.val() == 'pull' ? "octicon octicon-git-pull-request type" : "octicon octicon-issue-opened type";
        var collapse = this.state.collapsed ? 'collapsed' : 'collapsed in';
        var collapseMarkup = this.props.issue.text.val() ? <span className="collapser octicon octicon-chevron-down pull-right" onClick={this.handleToggle}></span> : '';

        return (
            <div className="issue">
                <h4>
                    <span className={icon}></span>
                    <a target="_blank" href={ this.props.issue.url.val() }>#{this.props.issue.number.val()} { this.props.issue.title.val() }</a>
                { collapseMarkup }
                </h4>
                <div>
                    <header>
                        <span>By:
                            <small>
                                <a href={this.props.issue.owner_url.val()} target="_blank">{this.props.issue.owner.val()}</a>
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
                    <div dangerouslySetInnerHTML={ {__html: this.convertText(this.props.issue.text.val())} } className={collapse}>
                    </div>
                </div>
            </div>
        );
    }

});

module.exports = Issue;
