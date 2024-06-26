<?php
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new \PhpCsFixer\Finder())
	->in(__DIR__)
	->exclude([
		'vendor'
	])
	->name('*.php');

return (new PhpCsFixer\Config())
        ->setParallelConfig(ParallelConfigFactory::detect())
	->setRules([
		'@PER-CS2.0' => true,
		'array_syntax' => ['syntax' => 'short'],
		'no_unused_imports' => true,
		'linebreak_after_opening_tag' => true,
		'phpdoc_order' => true,
		'visibility_required' => ['elements' => ['property','method']],		// Disabes const for support with PHP 7.0
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match']]
	])->setFinder($finder);
