This Symfony2 application provides a dashboard that collects information on
various repositories and allows to search through it. This is particularly
useful if a project spans multiple repositories.

Installation
------------

Install and configure [elasticsearch](http://www.elasticsearch.org/).

This application is a standard Symfony2 project. Best follow the
[Symfony2 guide](http://symfony.com/doc/2.3/book/installation.html).

Proceed to configure the repositories you want to include in the
`parameters.yml` file and provide your github credentials. (Even if your
repositories are public, the API only allows a few requests per day when
accessing it anonymous.)


Usage
-----

To populate the index, run the synchronize command:

    app/console dashboard:synchronize

This should populate elasticsearch. Now you can go to the home of your site to
see things.


Technology
----------

This application is based on the knplabs github-api and elasticsearch (with the
FOSElasticaBundle).


License
-------

The code is licensed under the MIT license. See the LICENSE file.


Contributing
------------

See CONTRIBUTING.md


Authors
-------

David Buchmann <mail@davidbu.ch> and [others](https://github.com/dbu/dashboard/graphs/contributors)
