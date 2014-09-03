This Symfony2 application provides a dashboard that collects information on
various repositories and allows to search through it. This is particularly
useful if a project spans multiple repositories.

To see a running installation, head to http://cmf.davidbu.ch

Installation
------------

Install and configure [elasticsearch](http://www.elasticsearch.org/).

This application is a standard Symfony2 project. Best follow the
[Symfony2 guide](http://symfony.com/doc/2.5/book/installation.html).
In very short, this looks like:

    curl -s http://getcomposer.org/installer | php
    
    php ./composer.phar create-project dbu/dashboard

During composer install, you will be prompted for your Github credentials,
which will be stored as plain text in `app/config/parameters.yml`. (Even if
your repositories are public, the API only allows 5000 requests per hour
without credentials. A single sync run for large organizations exceeds that
limit.)

*Generate an Github [API Key](https://help.github.com/articles/creating-an-access-token-for-command-line-use) for that*

The configuration will also ask for projects, but you might want to leave
that unchanged and then open parameters.yml in an editor.

Install Frontend Dependencies and build the CSS and JS Files (**already done through composer scripts**)

    bower install
    npm install
    gulp build

Usage
-----

This Application could handle various Issue-Trackers (github/jira atm).

###Configuration

configure this section in your `app/config/parameters.yml`

    rs_issues:
        github:
            - dbu/dashboard                             # explicit repo
            - phpcr/*                                   # all user/org repos
            - symfony/[Console|Debug]+$                 # only symfony/Console or symfony/Debug
            - doctrine/(?!common|lexer)([a-z0-9\.-]+)$  # all but doctrine/common or doctrine/lexer
        jira:
            # jira is a bit special, create this string with app/console issues:jira-crypt
            - ENCRYPTEDSTRING https://jira.domain.com PROJKEY

### Jira Generator

simply run the following Command, you will be asked a few credentials, and the result is a *copy-paste-line* for your config.

    app/console issues:jira-crypt

*note that this encrypting is only done for hiding the obvious jira credentials, its not a secure storage!*
 
### Synchronizing

To populate the index, run the synchronize command:

    app/console fos:elastica:populate #only once in the very first beginning
    app/console issues:sync #from the second run on

This should populate elasticsearch. Now you can go to the home of your site to
see things.

### Console

There is also a command to see issues on the commandlineRun

    app/console issues:search github jackalope/jackalope-jackrabbit


Technology
----------

This application is based on [KNPLabs PHP Github API](https://github.com/KnpLabs/php-github-api)
and [elasticsearch](http://www.elasticsearch.org) (with the
[FOSElasticaBundle](https://github.com/FriendsOfSymfony/FOSElasticaBundle)).

For the Frontend Stack we use [ReactJs](http://facebook.github.io/react/) with [cortexjs](http://mquan.github.io/cortex/).
The Build-Tool we use is [gulp](http://gulpjs.com/).

The Styles are built with [Sass](http://sass-lang.com/)

License
-------

The code is licensed under the MIT license. See the LICENSE file.


Contributing
------------

See CONTRIBUTING.md


Authors
-------

* [David Buchmann](https://github.com/dbu) 
* [Robert Schönthal](https://github.com/digitalkaoz)
* [Others](https://github.com/dbu/dashboard/graphs/contributors)
