# modmore/Alpacka

Alpacka by modmore is a base service class for MODX packages, and a (growing) collection of common utilities for
MODX packages. It is meant to be included as a composer package. 

To use Alpacka, your base service class will need to extend the `modmore\Alpacka\Alpacka` class. There is a (very) simple 
example service implementation in tets/Example.php. 

The service class includes a Pimple dependency injection container as $class->services. 

Documentation to follow :-)

## Installation

`composer require modmore/alpacka`

## Dev installation

`composer install`
