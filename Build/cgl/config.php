<?php

declare(strict_types=1);

/**
 * This file represents the configuration for Code Sniffing PER-related
 * automatic checks of coding guidelines.
 * This configuration is originally provided by the official TYPO3 14.3.0 Git repository.
 * It has been copied into the extension folder and adapted where necessary
 * to be compatible with this project (PHP 8.3, TYPO3 14.3).
 * Run checks with:
 * Build/Scripts/runTests.sh -p 8.3 -s cgl
 */
if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

// 1. Official TYPO3 Rules
$typo3Rules = [
    '@DoctrineAnnotation' => true,
    '@PER-CS1x0' => true,
    'array_indentation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'cast_spaces' => ['space' => 'none'],
    'concat_space' => ['spacing' => 'one'],
    'declare_equal_normalize' => ['space' => 'none'],
    'declare_parentheses' => true,
    'dir_constant' => true,
    'function_declaration' => [
        'closure_fn_spacing' => 'none',
    ],
    'function_to_constant' => ['functions' => ['get_called_class', 'get_class', 'get_class_this', 'php_sapi_name', 'phpversion', 'pi']],
    'type_declaration_spaces' => true,
    'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
    'list_syntax' => ['syntax' => 'short'],
    'method_argument_space' => true,
    'modernize_strpos' => true,
    'modernize_types_casting' => true,
    'native_function_casing' => true,
    'no_alias_functions' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => true,
    'no_leading_namespace_whitespace' => true,
    'no_null_property_initialization' => true,
    'no_short_bool_cast' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_superfluous_elseif' => true,
    'no_trailing_comma_in_singleline' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_nullsafe_operator' => true,
    'nullable_type_declaration' => [
        'syntax' => 'question_mark',
    ],
    'nullable_type_declaration_for_default_null_value' => true,
    'ordered_class_elements' => ['order' => ['use_trait', 'case', 'constant', 'property']],
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    'php_unit_construct' => ['assertions' => ['assertEquals', 'assertSame', 'assertNotEquals', 'assertNotSame']],
    'php_unit_mock_short_will_return' => true,
    'php_unit_test_case_static_method_calls' => ['call_type' => 'self',
        'methods' => [
            'any' => 'this',
            'atLeast' => 'this',
            'atLeastOnce' => 'this',
            'atMost' => 'this',
            'exactly' => 'this',
            'never' => 'this',
            'onConsecutiveCalls' => 'this',
            'once' => 'this',
            'returnArgument' => 'this',
            'returnCallback' => 'this',
            'returnSelf' => 'this',
            'returnValue' => 'this',
            'returnValueMap' => 'this',
            'throwException' => 'this',
        ],
    ],
    'phpdoc_no_access' => true,
    'phpdoc_no_empty_return' => true,
    'phpdoc_no_package' => true,
    'phpdoc_scalar' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => true,
    'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
    'protected_to_private' => true,
    'return_type_declaration' => ['space_before' => 'none'],
    'single_quote' => true,
    'single_space_around_construct' => true,
    'single_line_comment_style' => ['comment_types' => ['hash']],
    'single_line_empty_body' => true,
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
];

// 2. Custom jweiland.net Overrides
$jweilandRules = [
    'braces_position' => [
        'allow_single_line_anonymous_functions' => false,
        'allow_single_line_empty_anonymous_classes' => true,
        'anonymous_classes_opening_brace' => 'same_line',
        'anonymous_functions_opening_brace' => 'same_line',
        'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'control_structures_opening_brace' => 'same_line',
        'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
    ],
    'control_structure_braces' => true,
    'control_structure_continuation_position' => [
        'position' => 'same_line',
    ],
    'function_declaration' => [
        'closure_fn_spacing' => 'none',
        'closure_function_spacing' => 'one',
        'trailing_comma_single_line' => false,
    ],
    'method_argument_space' => [
        'after_heredoc' => false,
        'attribute_placement' => 'same_line',
        'keep_multiple_spaces_after_comma' => false,
        'on_multiline' => 'ensure_fully_multiline',
    ],
    'method_chaining_indentation' => true,
    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line',
    ],
    'no_empty_comment' => true,
    'no_extra_blank_lines' => [
        'tokens' => [
            'attribute',
            'case',
            'continue',
            'curly_brace_block',
            'default',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'switch',
            'throw',
            'use',
        ],
    ],
    'no_multiple_statements_per_line' => true,
    'operator_linebreak' => [
        'only_booleans' => false,
        'position' => 'beginning',
    ],
    'single_line_empty_body' => true,
    'single_space_around_construct' => true,
    'statement_indentation' => [
        'stick_comment_to_next_continuous_control_statement' => false,
    ],
    'trailing_comma_in_multiline' => [
        'elements' => [
            'arrays',
            'arguments',
            'parameters',
        ],
    ],
];

// Merge rules: jweilandRules will overwrite typo3Rules in case of conflicts
$mergedRules = array_merge($typo3Rules, $jweilandRules);

return (new \PhpCsFixer\Config())
    ->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->exclude(['var', 'packages'])
            ->ignoreVCSIgnored(true)
            ->in(__DIR__ . '/../../'),
    )
    ->setRiskyAllowed(true)
    ->setRules($mergedRules);
