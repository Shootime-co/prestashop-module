<?php

$finder = PhpCsFixer\Finder::create()->in('.');
$config = new PhpCsFixer\Config();
return $config->setFinder($finder);