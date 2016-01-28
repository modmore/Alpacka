# modmore/Alpacka

Alpacka by modmore is a base service class for MODX packages, and a (growing) collection of common utilities for
MODX packages. It is meant to be included as a composer package. 

[![Build Status](https://circleci.com/gh/modmore/Alpacka.svg?style=svg)](https://circleci.com/gh/modmore/Alpacka)

To use Alpacka, your base service class will need to extend the `modmore\Alpacka\Alpacka` class. There is a (very) simple 
example service implementation in tests/Example.php. 

The service class includes a Pimple dependency injection container as $class->services. 

[Documentation (work in progress) can be found in the wiki.](https://github.com/modmore/Alpacka/wiki)

## Installation

`composer require modmore/alpacka`

## Contributions are more than welcome

To contribute to Alpacka you will first need to install the local dependencies:

`composer install`

Please follow the existing coding style. In particular:

- Array properties and MODX settings are in snake_case
- Variable, property and method names are in camelCase
- Inline documentation please! At the very least PHPDoc on every method and public properties. 

One pull request per feature/improvement/bugfix. 
