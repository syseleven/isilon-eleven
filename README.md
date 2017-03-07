SysEleven/IsilonEleven
====================

Introduction
------------

SysEleven/IsilonEleven implements a simple wrapper to Isilon time tracking &copy RESTful API written in PHP. It encapsulates methods for retrieving, creating and manipulating time entries, customers, projects, services and users.

Requirements
------------

The requirements are pretty basic:

- php >= 5.3.19 or php >= 5.4.8 there is a bug in prior version of which prevented FILTER_VALIDATE_BOOLEAN to validate "false" correctly
- GuzzleHttp/Guzzle (will be automatically installed if using composer)

and for development or if you want to run the tests:

- mockery/mockery
- phpunit >= 3.6

Installation
------------

The recommended way to install is using composer, you can obtain the latest version of composer at http://getcomposer.org.

Simply add IsilonEleven to your requirements and specify the repository as follows:

     {
        "require": {
            "syseleven/isilon-eleven": "dev-master"
        },
        "repositories": [
                {
                    "type": "vcs",
                    "url": "https://github.com/syseleven/isilon-eleven.git"
                }
        ]
     }

Then run composer to update your dependencies:

     $ php composer.phar update

If you don't want to use composer simply clone the repository to a location of your choice

     $ git clone https://github.com/syseleven/isilon-eleven.git

Usage
-----

Use the client as follows:

     $client = new RestClient('https://localhost:8080');

     $client->setUsername('username');
     $client->setPassword('password');

     $isilonClient = new IsilonClient($client);
 
     $exports = $isilonClient->listExports();
