<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\ReturnIteratorInDataProviderRector;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictConstantReturnRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

/*
 * Deliberately narrow: strict types (only where provably safe — see
 * SafeDeclareStrictTypesRector), typed class constants, #[\Override], and
 * only the type-inference rules that read a type off an existing, authoritative
 * source (a parent method's own signature, a value that is provably always the
 * same scalar) rather than guessing from usage. Nothing here is meant to
 * change behavior; Rector's aggressive "infer everything"/PHP-version-upgrade
 * sets and the Laravel attribute-conversion set are deliberately left out.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/modules',
        __DIR__.'/database',
        __DIR__.'/config',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/modules/*/lang',
    ])
    ->withRules([
        SafeDeclareStrictTypesRector::class,
        AddTypeToConstRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        AddTestsVoidReturnTypeWhereNoReturnRector::class,
        ReturnIteratorInDataProviderRector::class,
        ReturnTypeFromStrictConstantReturnRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        ParamTypeByParentCallTypeRector::class,
    ])
    ->withImportNames(removeUnusedImports: true);
