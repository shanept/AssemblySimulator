<?php

$finder = (new \PhpCsFixer\Finder())
	->in(__DIR__)
	->exclude([
		'vendor'
	])
	->name('*.php');

return (new PhpCsFixer\Config())
	->setRules([
		'@PER-CS2.0' => true,
		'array_syntax' => ['syntax' => 'short'],
		'no_unused_imports' => true,
		'linebreak_after_opening_tag' => true,
		'phpdoc_order' => true,
		'visibility_required' => ['elements' => ['property','method']],		// Disabes const for support with PHP 7.0
	])->setFinder($finder);
