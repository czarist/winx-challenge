<?php

namespace App\OpenApi;

use L5Swagger\ConfigFactory;
use L5Swagger\Generator;
use L5Swagger\GeneratorFactory as BaseGeneratorFactory;
use L5Swagger\SecurityDefinitions;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

/**
 * L5Swagger's own factory only wires AttributeAnnotationFactory, silently
 * ignoring the classic docblock-style @OA\* annotations used throughout
 * this project. The fix needs an actual analyser object, and that can't
 * live in config/l5-swagger.php - an object literal there breaks
 * `php artisan config:cache` (Laravel var_export()s the config array).
 * Overriding the factory instead - bound in AppServiceProvider, same
 * pattern as every other interface in this app - keeps the config file
 * itself cache-safe.
 */
class GeneratorFactory extends BaseGeneratorFactory
{
    public function __construct(private readonly ConfigFactory $configFactory)
    {
        parent::__construct($configFactory);
    }

    public function make(string $documentation): Generator
    {
        $config = $this->configFactory->documentationConfig($documentation);

        $scanOptions = $config['scanOptions'] ?? [];
        $scanOptions['analyser'] ??= new ReflectionAnalyser([
            new AttributeAnnotationFactory,
            new DocBlockAnnotationFactory,
        ]);

        $security = new SecurityDefinitions(
            $config['securityDefinitions']['securitySchemes'] ?? [],
            $config['securityDefinitions']['security'] ?? [],
        );

        return new Generator(
            $config['paths'],
            $config['constants'] ?? [],
            $config['generate_yaml_copy'] ?? false,
            $security,
            $scanOptions,
        );
    }
}
