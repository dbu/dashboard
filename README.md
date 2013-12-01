This Symfony2 application provides a dashboard that collects information on
various repositories and allows to search through it. This is particularly
useful if a project spans multiple repositories.

To see a running installation, head to http://cmf.davidbu.ch

Installation
------------

Install and configure [elasticsearch](http://www.elasticsearch.org/).

This application is a standard Symfony2 project. Best follow the
[Symfony2 guide](http://symfony.com/doc/2.3/book/installation.html).
In very short, this looks like:

    curl -s http://getcomposer.org/installer | php
    ./composer.phar create-project dbu/dashboard

During composer install, you will be prompted for your Github credentials,
which will be stored as plain text in `app/config/parameters.yml`. (Even if
your repositories are public, the API only allows 5000 requests per hour
without credentials. A single sync run for large organizations exceeds that
limit.)

The configuration will also ask for repositories, but you might want to leave
that unchanged and then open parameters.yml in an editor.


Usage
-----

To populate the index, run the synchronize command:

    app/console dbu:sync

This should populate elasticsearch. Now you can go to the home of your site to
see things.

Console Issue Dumper
....................

There is also a command to see open pull requests on the commandline, with date
of last change. Run

app/console dbu:dump phpcr jackalope/jackalope-jackrabbit

[![Screenshot](doc/images/dashboard_screenshot_tn.png?raw=true)](doc/images/dashboard_screenshot.png?raw=true)


Technology
----------

This application is based on [KNPLabs PHP Github API](https://github.com/KnpLabs/php-github-api)
and [elasticsearch](http://www.elasticsearch.org) (with the
[FOSElasticaBundle](https://github.com/FriendsOfSymfony/FOSElasticaBundle)).


License
-------

The code is licensed under the MIT license. See the LICENSE file.


Contributing
------------

See CONTRIBUTING.md


Authors
-------

David Buchmann <mail@davidbu.ch> and [others](https://github.com/dbu/dashboard/graphs/contributors)
