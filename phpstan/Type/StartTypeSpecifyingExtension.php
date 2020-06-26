<?php

declare(strict_types=1);

namespace Lctrs\MinkPantherDriver\PHPStan\Type;

use Lctrs\MinkPantherDriver\PantherDriver;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class StartTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    /** @var TypeSpecifier */
    private $typeSpecifier;

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function getClass(): string
    {
        return PantherDriver::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        return $methodReflection->getName() === 'start' && $context->null();
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $expr = new StaticPropertyFetch(new Name('self'), new VarLikeIdentifier('pantherClient'));

        return $this->typeSpecifier->create(
            $expr,
            TypeCombinator::removeNull($scope->getType($expr)),
            TypeSpecifierContext::createTruthy()
        );
    }
}
