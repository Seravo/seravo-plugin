<?php

use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\CodingStyle\Rector\FuncCall\ConsistentPregDelimiterRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function ( ContainerConfigurator $container_configurator ) {
  $parameters = $container_configurator->parameters();
  $services = $container_configurator->services();

  // Set files to run Rector on
  $parameters->set(Option::FILE_EXTENSIONS, array( 'php' ));
  $parameters->set(
    Option::PATHS,
    array(
      __DIR__ . '/src/lib',
      __DIR__ . '/src/modules',
      __DIR__ . '/rector.php',
      __DIR__ . '/seravo-plugin.php',
    )
  );
  $parameters->set(Option::SKIP, array( __DIR__ . '/src/lib/list-table.php' ));

  // Run Rector on changed files only
  $parameters->set(Option::ENABLE_CACHE, false);
  $parameters->set(Option::CACHE_DIR, __DIR__ . '/.rector');

  // Set target PHP version (Keep compatibile with PHP 5.6)
  $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_56);

  // Set PHPStan config
  $parameters->set(Option::PHPSTAN_FOR_RECTOR_PATH, __DIR__ . '/phpstan.neon');

  // Skip root namespace classes
  $parameters->set(Option::IMPORT_SHORT_CLASSES, false);

  // ------------------------------------------------------------------------------------- //
  // RULES TO BE APPLIED                                                                   //
  // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md           //
  // ------------------------------------------------------------------------------------- //

  // Code Quality
  $services->set(\Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\AddPregQuoteDelimiterRector::class);
  $services->set(\Rector\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\ArrayKeysAndInArrayToArrayKeyExistsRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\CallUserFuncWithArrowFunctionToInlineRector::class);
  $services->set(\Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector::class);
  $services->set(\Rector\CodeQuality\Rector\Assign\CombinedAssignRector::class);
  $services->set(\Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector::class);
  $services->set(\Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class);
  $services->set(\Rector\CodeQuality\Rector\ClassMethod\DateTimeToDateTimeInterfaceRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class);
  $services->set(\Rector\CodeQuality\Rector\Name\FixClassCaseSensitivityNameRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class);
  $services->set(\Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector::class);
  $services->set(\Rector\CodeQuality\Rector\For_\ForToForeachRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\GetClassToInstanceOfRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\InArrayAndArrayKeysToArrayKeyExistsRector::class);
  $services->set(\Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\IntvalToTypeCastRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\IsAWithStringWithThirdArgumentRector::class);
  $services->set(\Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class);
  $services->set(\Rector\CodeQuality\Rector\Concat\JoinStringConcatRector::class);
  $services->set(\Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector::class);
  $services->set(\Rector\CodeQuality\Rector\ClassMethod\NarrowUnionTypeDocRector::class);
  $services->set(\Rector\CodeQuality\Rector\New_\NewStaticToNewSelfRector::class);
  $services->set(\Rector\CodeQuality\Rector\FunctionLike\RemoveAlwaysTrueConditionSetInConstructorRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\RemoveSoleValueSprintfRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SetTypeToCastRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\ShortenElseIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector::class);
  $services->set(\Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\SimplifyDuplicatedTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToArrayFilterRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToCoalescingRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyFuncGetArgsCountRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfIssetToNullCoalescingRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Return_\SimplifyUselessVariableRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector::class);
  $services->set(\Rector\CodeQuality\Rector\Switch_\SingularSwitchToIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\Assign\SplitListAssignToSeparateLineRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector::class);
  $services->set(\Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector::class);

  // Code Quality Strict
  $services->set(\Rector\CodeQualityStrict\Rector\If_\MoveOutMethodCallInsideIfConditionRector::class);
  $services->set(\Rector\CodeQualityStrict\Rector\Stmt\VarInlineAnnotationToAssertRector::class);

  // Coding Style
  $services->set(\Rector\CodingStyle\Rector\Class_\AddArrayDefaultToArrayPropertyRector::class);
  $services->set(\Rector\CodingStyle\Rector\Property\AddFalseDefaultToBoolPropertyRector::class);
  $services->set(\Rector\CodingStyle\Rector\Switch_\BinarySwitchToIfElseRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\CallUserFuncToMethodCallRector::class);
  $services->set(\Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\ConsistentImplodeRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\ConsistentPregDelimiterRector::class)
    ->call(
      'configure',
      array(
        array(
          ConsistentPregDelimiterRector::DELIMITER => '/',
        ),
      )
    );
  $services->set(\Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector::class);
  $services->set(\Rector\CodingStyle\Rector\Include_\FollowRequireByDirRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector::class);
  $services->set(\Rector\CodingStyle\Rector\Assign\ManualJsonStringToJsonEncodeArrayRector::class);
  $services->set(\Rector\CodingStyle\Rector\If_\NullableCompareToNullRector::class);
  $services->set(\Rector\CodingStyle\Rector\Assign\PHPStormVarAnnotationRector::class);
  $services->set(\Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector::class);
  //$services->set(\Rector\CodingStyle\Rector\FuncCall\PreslashSimpleFunctionRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\RemoveDoubleUnderscoreInMethodNameRector::class);
  $services->set(\Rector\CodingStyle\Rector\Use_\RemoveUnusedAliasRector::class);
  $services->set(\Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector::class);
  $services->set(\Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassConst\SplitGroupedConstantsAndPropertiesRector::class);
  $services->set(\Rector\CodingStyle\Rector\String_\SplitStringClassConstantToClassConstFetchRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector::class);
  $services->set(\Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector::class);
  $services->set(\Rector\CodingStyle\Rector\Ternary\TernaryConditionVariableAssignmentRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector::class);
  $services->set(\Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector::class);
  $services->set(\Rector\CodingStyle\Rector\Plus\UseIncrementAssignRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassConst\VarConstantCommentRector::class);
  $services->set(\Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector::class);

  // Dead Code
  $services->set(\Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class);
  $services->set(\Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector::class);
  $services->set(\Rector\DeadCode\Rector\Assign\RemoveAssignOfVoidReturnFunctionRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveCodeAfterReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class);
  $services->set(\Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveDeadConstructorRector::class);
  $services->set(\Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class);
  $services->set(\Rector\DeadCode\Rector\For_\RemoveDeadLoopRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector::class);
  $services->set(\Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector::class);
  $services->set(\Rector\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveDelegatingParentCallRector::class);
  $services->set(\Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector::class);
  $services->set(\Rector\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector::class);
  $services->set(\Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveDuplicatedIfReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\BinaryOp\RemoveDuplicatedInstanceOfRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class);
  $services->set(\Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class);
  $services->set(\Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector::class);
  $services->set(\Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector::class);
  $services->set(\Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector::class);
  $services->set(\Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\RemoveUnusedNonEmptyArrayBeforeForeachRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector::class);
  $services->set(\Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector::class);
  $services->set(\Rector\DeadCode\Rector\Expression\SimplifyMirrorAssignRector::class);
  $services->set(\Rector\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfFunctionExistsRector::class);

  // Defluent
  $services->set(\Rector\Defluent\Rector\Return_\DefluentReturnMethodCallRector::class);
  $services->set(\Rector\Defluent\Rector\MethodCall\InArgFluentChainMethodCallToStandaloneMethodCallRector::class);
  $services->set(\Rector\Defluent\Rector\MethodCall\MethodCallOnSetterMethodCallToStandaloneAssignRector::class);

  // Dependency Injection
  $services->set(\Rector\DependencyInjection\Rector\Variable\ReplaceVariableByPropertyFetchRector::class);

  // Early return
  $services->set(\Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector::class);
  $services->set(\Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Foreach_\ReturnAfterToEarlyOnBreakRector::class);

  // Type Declaration
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\Property\CompleteVarDocTypePropertyRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector::class);

  // Downgrades
  $services->set(\Rector\DowngradePhp70\Rector\FunctionLike\DowngradeScalarTypeDeclarationRector::class);
};

