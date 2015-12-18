<?php

namespace modmore\Alpacka\Test;

use modmore\Alpacka\Alpacka;

class Example extends Alpacka {
    protected $namespace = 'example';
    public function __construct($instance, array $config = array())
    {
        parent::__construct($instance, $config);

        $this->setVersion(1, 2, 3, 'pl');
    }
}