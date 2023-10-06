<?php
// .scoper.inc.php in plugin root.

use Symfony\Component\Finder\Finder;

include 'vendor/barn2/barn2-lib/.scoper.inc.php';

$config = get_lib_scoper_config( 'Barn2\\Plugin\\WC_Filters\\Dependencies' );

$finder = Finder::create()->
			files()->
			ignoreVCS( true )->
			ignoreDotFiles( true )->
			notName( '/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.(json|lock)/' )->
			exclude(
				[
					'doc',
					'test',
					'build',
					'test_old',
					'tests',
					'Tests',
					'vendor-bin',
					'wp-coding-standards',
					'squizlabs',
					'phpcompatibility',
					'dealerdirect',
					'bin',
					'vendor',
					'deprecated',
					'mu-plugins',
					'plugin-boilerplate',
					'templates'
				]
			)->
			in( [
				'vendor/doctrine',
				'vendor/laravel',
				'vendor/illuminate',
				'vendor/nesbot',
				'vendor/psr',
				'vendor/calebporzio/parental',
				'vendor/voku/portable-ascii',
                'vendor/sematico/wp-fluent-query',
			] )->
			name( [ '*.php' ] );

$patcher = function (string $filePath, string $prefix, string $contents): string {
            // Change the contents here.
            $search = [
                'tap(',
                'collect(',
                'class_uses_recursive(',
                'class_basename(',
                'last(',
                'value(',
                'blank(',
                'filled(',
                'data_get(',
                'transform(',
                'windows_os(',
                'head(',
            ];
            $find_replace = [
                'findpattern' => [],
                'replace' => [],
            ];
            foreach ($search as $searchFunction) {
                $find_replace['findpattern'][] = str_replace('REP', str_replace('(', '\(', $searchFunction), '/(?<!function |->|\w|::|\$)REP/');
                $find_replace['replace'][] = 'Barn2\\Plugin\\WC_Filters\\Helpers::' . $searchFunction;
            }

            $files_search = [
                '/vendor/illuminate/database/',
                '/vendor/illuminate/filesystem/',
                '/vendor/illuminate/http/',
                '/vendor/illuminate/session/',
                '/vendor/illuminate/support/',
				'/vendor/illuminate/collections/'
            ];

            foreach ($files_search as $file_name) {
                if (strpos($filePath, $file_name) !== false) {
                    echo "\nFound $filePath  replace \n";
                    return preg_replace(
                        $find_replace['findpattern'],
                        $find_replace['replace'],
                        $contents
                    );
                }
            }

            return $contents;
        };

$config['finders'][] = $finder;
$config['patchers'][] = $patcher;

return $config;
