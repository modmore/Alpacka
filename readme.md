# modmore/Alpacka

Alpacka by modmore is a collection of common utilities for MODX packages. It is meant to be included as a composer
package. 

To use Alpacka, your base service class will need to extend the `modmore\Alpacka\Alpacka` class. It features a Pimple
dependency injection container under $class->services. 

## Installation

`composer require modmore/alpacka`

## Dev installation

`composer install`