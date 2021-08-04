<?php

use \Rector\Set\ValueObject\SetList;
use \Rector\Set\ValueObject\DowngradeSetList;
use \Rector\Core\Configuration\Option;
use \Rector\Core\ValueObject\PhpVersion;

use \Rector\CodingStyle\Rector\FuncCall\ConsistentPregDelimiterRector;

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
  $parameters->set(
    Option::SKIP,
    array(
      __DIR__ . '/src/lib/list-table.php',
      __DIR__ . '/src/lib/compatibility.php',
    )
  );

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

  // Arguments
  $services->set(\Rector\Arguments\Rector\FuncCall\FunctionArgumentDefaultValueReplacerRector::class)->call(
    'configure',
    array(
      array(
        \Rector\Arguments\Rector\FuncCall\FunctionArgumentDefaultValueReplacerRector::REPLACED_ARGUMENTS => \Symplify\SymfonyPhpConfig\ValueObjectInliner::inline(
          array(
            new \Rector\Arguments\ValueObject\ReplaceFuncCallArgumentDefaultValue('version_compare', 2, 'gte', 'ge'),
          )
        ),
      ),
    )
  );

  // Code Quality
  $services->set(\Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector::class);
  $services->set(\Rector\CodeQuality\Rector\Assign\CombinedAssignRector::class);
  $services->set(\Rector\CodeQuality\Rector\Assign\SplitListAssignToSeparateLineRector::class);
  $services->set(\Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector::class);
  $services->set(\Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector::class);
  $services->set(\Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector::class);
  $services->set(\Rector\CodeQuality\Rector\ClassMethod\DateTimeToDateTimeInterfaceRector::class);
  $services->set(\Rector\CodeQuality\Rector\ClassMethod\NarrowUnionTypeDocRector::class);
  $services->set(\Rector\CodeQuality\Rector\Concat\JoinStringConcatRector::class);
  $services->set(\Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector::class);
  $services->set(\Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\ForeachItemsAssignToEmptyArrayToAssignRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToArrayFilterRector::class);
  $services->set(\Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector::class);
  $services->set(\Rector\CodeQuality\Rector\For_\ForRepeatedCountToOwnVariableRector::class);
  $services->set(\Rector\CodeQuality\Rector\For_\ForToForeachRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\AddPregQuoteDelimiterRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\ArrayKeysAndInArrayToArrayKeyExistsRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\CallUserFuncWithArrowFunctionToInlineRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\InArrayAndArrayKeysToArrayKeyExistsRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\IntvalToTypeCastRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\IsAWithStringWithThirdArgumentRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\RemoveSoleValueSprintfRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SetTypeToCastRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyFuncGetArgsCountRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyInArrayValuesRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector::class);
  $services->set(\Rector\CodeQuality\Rector\FuncCall\UnwrapSprintfOneArgumentRector::class);
  $services->set(\Rector\CodeQuality\Rector\FunctionLike\RemoveAlwaysTrueConditionSetInConstructorRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\BooleanNotIdenticalToNotIdenticalRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\GetClassToInstanceOfRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\SimplifyArraySearchRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector::class);
  $services->set(\Rector\CodeQuality\Rector\Identical\StrlenZeroToIdenticalEmptyStringRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\CombineIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\ShortenElseIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfIssetToNullCoalescingRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfNotNullReturnRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector::class);
  $services->set(\Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector::class);
  $services->set(\Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector::class);
  $services->set(\Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class);
  $services->set(\Rector\CodeQuality\Rector\LogicalAnd\AndAssignsToSeparateLinesRector::class);
  $services->set(\Rector\CodeQuality\Rector\LogicalAnd\LogicalToBooleanRector::class);
  $services->set(\Rector\CodeQuality\Rector\Name\FixClassCaseSensitivityNameRector::class);
  $services->set(\Rector\CodeQuality\Rector\New_\NewStaticToNewSelfRector::class);
  $services->set(\Rector\CodeQuality\Rector\NotEqual\CommonNotEqualRector::class);
  $services->set(\Rector\CodeQuality\Rector\Return_\SimplifyUselessVariableRector::class);
  $services->set(\Rector\CodeQuality\Rector\Switch_\SingularSwitchToIfRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\ArrayKeyExistsTernaryThenValueToCoalescingRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\SimplifyDuplicatedTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\SimplifyTautologyTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector::class);
  $services->set(\Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector::class);

  // Coding Style TODO: SORT
  $services->set(\Rector\CodingStyle\Rector\Assign\ManualJsonStringToJsonEncodeArrayRector::class);
  $services->set(\Rector\CodingStyle\Rector\Assign\PHPStormVarAnnotationRector::class);
  $services->set(\Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector::class);
  $services->set(\Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector::class);
  $services->set(\Rector\CodingStyle\Rector\Class_\AddArrayDefaultToArrayPropertyRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassConst\SplitGroupedConstantsAndPropertiesRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassConst\VarConstantCommentRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\RemoveDoubleUnderscoreInMethodNameRector::class);
  $services->set(\Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector::class);
  $services->set(\Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\CallUserFuncToMethodCallRector::class);
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
  $services->set(\Rector\CodingStyle\Rector\FuncCall\PreslashSimpleFunctionRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\StrictArraySearchRector::class);
  $services->set(\Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector::class);
  $services->set(\Rector\CodingStyle\Rector\If_\NullableCompareToNullRector::class);
  $services->set(\Rector\CodingStyle\Rector\Include_\FollowRequireByDirRector::class);
  $services->set(\Rector\CodingStyle\Rector\Plus\UseIncrementAssignRector::class);
  $services->set(\Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector::class);
  $services->set(\Rector\CodingStyle\Rector\Property\AddFalseDefaultToBoolPropertyRector::class);
  $services->set(\Rector\CodingStyle\Rector\String_\SplitStringClassConstantToClassConstFetchRector::class);
  $services->set(\Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector::class);
  $services->set(\Rector\CodingStyle\Rector\String_\UseClassKeywordForClassNameResolutionRector::class);
  $services->set(\Rector\CodingStyle\Rector\Switch_\BinarySwitchToIfElseRector::class);
  $services->set(\Rector\CodingStyle\Rector\Ternary\TernaryConditionVariableAssignmentRector::class);
  $services->set(\Rector\CodingStyle\Rector\Use_\RemoveUnusedAliasRector::class);
  $services->set(\Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector::class);

  // Dead Code
  $services->set(\Rector\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector::class);
  $services->set(\Rector\DeadCode\Rector\Assign\RemoveAssignOfVoidReturnFunctionRector::class);
  $services->set(\Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector::class);
  $services->set(\Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector::class);
  $services->set(\Rector\DeadCode\Rector\BinaryOp\RemoveDuplicatedInstanceOfRector::class);
  $services->set(\Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector::class);
  $services->set(\Rector\DeadCode\Rector\Cast\RecastingRemovalRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveDeadConstructorRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveDelegatingParentCallRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveLastReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector::class);
  $services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector::class);
  $services->set(\Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector::class);
  $services->set(\Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector::class);
  $services->set(\Rector\DeadCode\Rector\Expression\SimplifyMirrorAssignRector::class);
  $services->set(\Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector::class);
  $services->set(\Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector::class);
  $services->set(\Rector\DeadCode\Rector\For_\RemoveDeadLoopRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveCodeAfterReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveDuplicatedIfReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\FunctionLike\RemoveOverriddenValuesRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\RemoveUnusedNonEmptyArrayBeforeForeachRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector::class);
  $services->set(\Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfFunctionExistsRector::class);
  $services->set(\Rector\DeadCode\Rector\MethodCall\RemoveEmptyMethodCallRector::class);
  $services->set(\Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector::class);
  $services->set(\Rector\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector::class);
  $services->set(\Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector::class);
  $services->set(\Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector::class);
  $services->set(\Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector::class);
  $services->set(\Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector::class);
  $services->set(\Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector::class);
  $services->set(\Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector::class);
  $services->set(\Rector\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector::class);
  $services->set(\Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector::class);

  // Defluent
  $services->set(\Rector\Defluent\Rector\MethodCall\InArgFluentChainMethodCallToStandaloneMethodCallRector::class);
  $services->set(\Rector\Defluent\Rector\MethodCall\MethodCallOnSetterMethodCallToStandaloneAssignRector::class);
  $services->set(\Rector\Defluent\Rector\Return_\DefluentReturnMethodCallRector::class);
  $services->set(\Rector\Defluent\Rector\Return_\ReturnFluentChainMethodCallToNormalMethodCallRector::class);

  // Dependency Injection
  $services->set(\Rector\DependencyInjection\Rector\Variable\ReplaceVariableByPropertyFetchRector::class);

  // Early Return
  $services->set(\Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Foreach_\ReturnAfterToEarlyOnBreakRector::class);
  $services->set(\Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Return_\ReturnBinaryAndToEarlyReturnRector::class);
  $services->set(\Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector::class);

  // Type Declaration
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddArrayParamDocTypeRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\MethodCall\FormerNullableArgumentToScalarTypedRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\Property\CompleteVarDocTypePropertyRector::class);
  $services->set(\Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector::class);

  // Privatization
  $services->set(\Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector::class);
  $services->set(\Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector::class);
  $services->set(\Rector\Privatization\Rector\MethodCall\ReplaceStringWithClassConstantRector::class);
  $services->set(\Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector::class);

  // Upgrade code to PHP 5.6
  $services->set(\Rector\Php52\Rector\Property\VarToPublicPropertyRector::class);
  $services->set(\Rector\Php52\Rector\Switch_\ContinueToBreakInSwitchRector::class);
  $services->set(\Rector\Php53\Rector\AssignRef\ClearReturnNewByReferenceRector::class);
  $services->set(\Rector\Php53\Rector\FuncCall\DirNameFileConstantToDirConstantRector::class);
  $services->set(\Rector\Php53\Rector\Ternary\TernaryToElvisRector::class);
  $services->set(\Rector\Php53\Rector\Variable\ReplaceHttpServerVarsByServerRector::class);
  $services->set(\Rector\Php54\Rector\Break_\RemoveZeroBreakContinueRector::class);
  $services->set(\Rector\Php54\Rector\FuncCall\RemoveReferenceFromCallRector::class);
  $services->set(\Rector\Php55\Rector\FuncCall\PregReplaceEModifierRector::class);
  $services->set(\Rector\Php55\Rector\String_\StringClassNameToClassConstantRector::class);
  $services->set(\Rector\Php56\Rector\FuncCall\PowToExpRector::class);
  $services->set(\Rector\Php56\Rector\FunctionLike\AddDefaultValueForUndefinedVariableRector::class);

  // TODO: INVESTIGATE
  //$services->set(\Rector\DeadCode\Rector\Assign\RemoveUnusedAssignVariableRector::class);
  //$services->set(\Rector\Privatization\Rector\Class_\ChangeReadOnlyVariableWithDefaultValueToConstantRector::class);
  //$services->set(\Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 7.0 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class);
  //$services->set(\Rector\CodeQuality\Rector\Foreach_\SimplifyForeachToCoalescingRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\Closure\AddClosureReturnTypeRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\FunctionLike\ParamTypeDeclarationRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector::class);
  //$services->set(\Rector\Php70\Rector\Assign\ListSplitStringRector::class);
  //$services->set(\Rector\Php70\Rector\Assign\ListSwapArrayOrderRector::class);
  //$services->set(\Rector\Php70\Rector\Break_\BreakNotInLoopOrSwitchToReturnRector::class);
  //$services->set(\Rector\Php70\Rector\ClassMethod\Php4ConstructorRector::class);
  //$services->set(\Rector\Php70\Rector\FuncCall\CallUserMethodRector::class);
  //$services->set(\Rector\Php70\Rector\FuncCall\EregToPregMatchRector::class);
  //$services->set(\Rector\Php70\Rector\FuncCall\MultiDirnameRector::class);
  //$services->set(\Rector\Php70\Rector\FuncCall\NonVariableToVariableOnFunctionCallRector::class);
  //$services->set(\Rector\Php70\Rector\FuncCall\RandomFunctionRector::class);
  //$services->set(\Rector\Php70\Rector\FuncCall\RenameMktimeWithoutArgsToTimeRector::class);
  //$services->set(\Rector\Php70\Rector\FunctionLike\ExceptionHandlerTypehintRector::class);
  //$services->set(\Rector\Php70\Rector\If_\IfToSpaceshipRector::class);
  //$services->set(\Rector\Php70\Rector\List_\EmptyListRector::class);
  //$services->set(\Rector\Php70\Rector\MethodCall\ThisCallOnStaticMethodToStaticCallRector::class);
  //$services->set(\Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector::class);
  //$services->set(\Rector\Php70\Rector\Switch_\ReduceMultipleDefaultSwitchRector::class);
  //$services->set(\Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector::class);
  //$services->set(\Rector\Php70\Rector\Ternary\TernaryToSpaceshipRector::class);
  //$services->set(\Rector\Php70\Rector\Variable\WrapVariableVariableNameInCurlyBracesRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 7.1 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector::class);
  //$services->set(\Rector\Php71\Rector\Assign\AssignArrayToStringRector::class);
  //$services->set(\Rector\Php71\Rector\BinaryOp\BinaryOpBetweenNumberAndStringRector::class);
  //$services->set(\Rector\Php71\Rector\BooleanOr\IsIterableRector::class);
  //$services->set(\Rector\Php71\Rector\ClassConst\PublicConstantVisibilityRector::class);
  //$services->set(\Rector\Php71\Rector\FuncCall\CountOnNullRector::class);
  //$services->set(\Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class);
  //$services->set(\Rector\Php71\Rector\List_\ListToArrayDestructRector::class);
  //$services->set(\Rector\Php71\Rector\Name\ReservedObjectRector::class);
  //$services->set(\Rector\Php71\Rector\TryCatch\MultiExceptionCatchRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 7.2 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\Php72\Rector\Assign\ListEachRector::class);
  //$services->set(\Rector\Php72\Rector\Assign\ReplaceEachAssignmentWithKeyCurrentRector::class);
  //$services->set(\Rector\Php72\Rector\FuncCall\CreateFunctionToAnonymousFunctionRector::class);
  //$services->set(\Rector\Php72\Rector\FuncCall\GetClassOnNullRector::class);
  //$services->set(\Rector\Php72\Rector\FuncCall\IsObjectOnIncompleteClassRector::class);
  //$services->set(\Rector\Php72\Rector\FuncCall\ParseStrWithResultArgumentRector::class);
  //$services->set(\Rector\Php72\Rector\FuncCall\StringifyDefineRector::class);
  //$services->set(\Rector\Php72\Rector\FuncCall\StringsAssertNakedRector::class);
  //$services->set(\Rector\Php72\Rector\Unset_\UnsetCastRector::class);
  //$services->set(\Rector\Php72\Rector\While_\WhileEachToForeachRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 7.3 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\Php73\Rector\BooleanOr\IsCountableRector::class);
  //$services->set(\Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector::class);
  //$services->set(\Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector::class);
  //$services->set(\Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector::class);
  //$services->set(\Rector\Php73\Rector\FuncCall\RegexDashEscapeRector::class);
  //$services->set(\Rector\Php73\Rector\FuncCall\SensitiveDefineRector::class);
  //$services->set(\Rector\Php73\Rector\FuncCall\SetCookieRector::class);
  //$services->set(\Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector::class);
  //$services->set(\Rector\Php73\Rector\String_\SensitiveHereNowDocRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 7.4 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector::class);
  //$services->set(\Rector\Php74\Rector\Assign\NullCoalescingOperatorRector::class);
  //$services->set(\Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector::class);
  //$services->set(\Rector\Php74\Rector\Double\RealToFloatTypeCastRector::class);
  //$services->set(\Rector\Php74\Rector\FuncCall\ArrayKeyExistsOnPropertyRector::class);
  //$services->set(\Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector::class);
  //$services->set(\Rector\Php74\Rector\FuncCall\FilterVarToAddSlashesRector::class);
  //$services->set(\Rector\Php74\Rector\FuncCall\GetCalledClassToStaticClassRector::class);
  //$services->set(\Rector\Php74\Rector\FuncCall\MbStrrposEncodingArgumentPositionRector::class);
  //$services->set(\Rector\Php74\Rector\Function_\ReservedFnFunctionRector::class);
  //$services->set(\Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector::class);
  //$services->set(\Rector\Php74\Rector\MethodCall\ChangeReflectionTypeToStringToGetNameRector::class);
  //$services->set(\Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector::class);
  //$services->set(\Rector\Php74\Rector\Property\TypedPropertyRector::class);
  //$services->set(\Rector\Php74\Rector\StaticCall\ExportToReflectionFunctionRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 8.0 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector::class);
  //$services->set(\Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector::class);
  //$services->set(\Rector\Php80\Rector\Class_\AnnotationToAttributeRector::class);
  //$services->set(\Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector::class);
  //$services->set(\Rector\Php80\Rector\Class_\DoctrineAnnotationClassToAttributeRector::class);
  //$services->set(\Rector\Php80\Rector\ClassMethod\FinalPrivateToPrivateVisibilityRector::class);
  //$services->set(\Rector\Php80\Rector\ClassMethod\OptionalParametersAfterRequiredRector::class);
  //$services->set(\Rector\Php80\Rector\ClassMethod\SetStateToStaticRector::class);
  //$services->set(\Rector\Php80\Rector\Class_\StringableForToStringRector::class);
  //$services->set(\Rector\Php80\Rector\FuncCall\ClassOnObjectRector::class);
  //$services->set(\Rector\Php80\Rector\FuncCall\TokenGetAllToObjectRector::class);
  //$services->set(\Rector\Php80\Rector\FunctionLike\UnionTypesRector::class);
  //$services->set(\Rector\Php80\Rector\Identical\StrEndsWithRector::class);
  //$services->set(\Rector\Php80\Rector\Identical\StrStartsWithRector::class);
  //$services->set(\Rector\Php80\Rector\If_\NullsafeOperatorRector::class);
  //$services->set(\Rector\Php80\Rector\NotIdentical\StrContainsRector::class);
  //$services->set(\Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class);
  //$services->set(\Rector\Php80\Rector\Ternary\GetDebugTypeRector::class);

  // TODO: TEST AND MAYBE UNCOMMENT WHEN PHP 8.1 IS THE LOWEST SUPPORTED VERSION
  //$services->set(\Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector::class);
  //$services->set(\Rector\Php81\Rector\Class_\MyCLabsClassToEnumRector::class);
  //$services->set(\Rector\Php81\Rector\MethodCall\MyCLabsMethodCallToEnumConstRector::class);
  //$services->set(\Rector\Php81\Rector\Property\ReadOnlyPropertyRector::class);

  // Downgrades, uncomment these temporarily if needed
  //$services->set(\Rector\DowngradePhp53\Rector\Dir\DirConstToFileConstRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\ClassMethod\DowngradeSelfTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\Coalesce\DowngradeNullCoalesceRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\Declare_\DowngradeStrictTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\Expression\DowngradeDefineArrayConstantRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\FuncCall\DowngradeSessionStartArrayOptionsRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\FunctionLike\DowngradeScalarTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\GroupUse\SplitGroupedUseImportsRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\New_\DowngradeAnonymousClassRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\Spaceship\DowngradeSpaceshipRector::class);
  //$services->set(\Rector\DowngradePhp70\Rector\String_\DowngradeGeneratedScalarTypesRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\Array_\SymmetricArrayDestructuringToListRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\ClassConst\DowngradeClassConstantVisibilityRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\FuncCall\DowngradeIsIterableRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\FunctionLike\DowngradeIterablePseudoTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\FunctionLike\DowngradeNullableTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\FunctionLike\DowngradeVoidTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\List_\DowngradeKeysInListRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\String_\DowngradeNegativeStringOffsetToStrlenRector::class);
  //$services->set(\Rector\DowngradePhp71\Rector\TryCatch\DowngradePipeToMultiCatchExceptionRector::class);
  //$services->set(\Rector\DowngradePhp72\Rector\ClassMethod\DowngradeParameterTypeWideningRector::class);
  //$services->set(\Rector\DowngradePhp72\Rector\FuncCall\DowngradePregUnmatchedAsNullConstantRector::class);
  //$services->set(\Rector\DowngradePhp72\Rector\FuncCall\DowngradeStreamIsattyRector::class);
  //$services->set(\Rector\DowngradePhp72\Rector\FunctionLike\DowngradeObjectTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp73\Rector\FuncCall\DowngradeArrayKeyFirstLastRector::class);
  //$services->set(\Rector\DowngradePhp73\Rector\FuncCall\DowngradeIsCountableRector::class);
  //$services->set(\Rector\DowngradePhp73\Rector\FuncCall\DowngradeTrailingCommasInFunctionCallsRector::class);
  //$services->set(\Rector\DowngradePhp73\Rector\FuncCall\SetCookieOptionsArrayToArgumentsRector::class);
  //$services->set(\Rector\DowngradePhp73\Rector\List_\DowngradeListReferenceAssignmentRector::class);
  //$services->set(\Rector\DowngradePhp73\Rector\String_\DowngradeFlexibleHeredocSyntaxRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\Array_\DowngradeArraySpreadRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\ArrowFunction\ArrowFunctionToAnonymousFunctionRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\ClassMethod\DowngradeContravariantArgumentTypeRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\ClassMethod\DowngradeCovariantReturnTypeRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\Coalesce\DowngradeNullCoalescingOperatorRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\FuncCall\DowngradeArrayMergeCallWithoutArgumentsRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\FuncCall\DowngradeStripTagsCallWithArrayRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\Identical\DowngradeFreadFwriteFalsyToNegationRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\LNumber\DowngradeNumericLiteralSeparatorRector::class);
  //$services->set(\Rector\DowngradePhp74\Rector\Property\DowngradeTypedPropertyRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\Catch_\DowngradeNonCapturingCatchesRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\ClassConstFetch\DowngradeClassOnObjectToGetClassRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\Class_\DowngradeAttributeToAnnotationRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\Class_\DowngradePropertyPromotionRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\ClassMethod\DowngradeAbstractPrivateMethodInTraitRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\ClassMethod\DowngradeStaticTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\ClassMethod\DowngradeTrailingCommasInParamUseRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\Expression\DowngradeMatchToSwitchRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\Expression\DowngradeThrowExprRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrContainsRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrEndsWithRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\FuncCall\DowngradeStrStartsWithRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\FunctionLike\DowngradeMixedTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\FunctionLike\DowngradeUnionTypeDeclarationRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\MethodCall\DowngradeNamedArgumentRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\NullsafeMethodCall\DowngradeNullsafeToTernaryOperatorRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\Property\DowngradeUnionTypeTypedPropertyRector::class);
  //$services->set(\Rector\DowngradePhp80\Rector\StaticCall\DowngradePhpTokenRector::class);
  //$services->set(\Rector\DowngradePhp81\Rector\ClassConst\DowngradeFinalizePublicClassConstantRector::class);

  // NOT NEEDED, KEEP COMMENTED
  //$services->set(\Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector::class);
  //$services->set(\Rector\Arguments\Rector\ClassMethod\ArgumentAdderRector::class);
  //$services->set(\Rector\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRector::class);
  //$services->set(\Rector\Arguments\Rector\FuncCall\SwapFuncCallArgumentsRector::class);
  //$services->set(\Rector\Autodiscovery\Rector\Class_\MoveEntitiesToEntityDirectoryRector::class);
  //$services->set(\Rector\Autodiscovery\Rector\Class_\MoveServicesBySuffixToDirectoryRector::class);
  //$services->set(\Rector\Autodiscovery\Rector\Class_\MoveValueObjectsToValueObjectDirectoryRector::class);
  //$services->set(\Rector\Autodiscovery\Rector\Interface_\MoveInterfacesToContractNamespaceDirectoryRector::class);
  //$services->set(\Rector\Carbon\Rector\MethodCall\ChangeCarbonSingularMethodCallToPluralRector::class);
  //$services->set(\Rector\Carbon\Rector\MethodCall\ChangeDiffForHumansArgsRector::class);
  //$services->set(\Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector::class);
  //$services->set(\Rector\CodingStyle\Rector\ClassMethod\OrderAttributesRector::class);
  //$services->set(\Rector\CodingStyle\Rector\ClassMethod\ReturnArrayClassMethodToYieldRector::class);
  //$services->set(\Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector::class);
  //$services->set(\Rector\CodingStyle\Rector\MethodCall\PreferThisOrSelfMethodCallRector::class);
  //$services->set(\Rector\CodingStyle\Rector\MethodCall\UseMessageVariableForSprintfInSymfonyStyleRector::class);
  //$services->set(\Rector\Composer\Rector\AddPackageToRequireComposerRector::class);
  //$services->set(\Rector\Composer\Rector\AddPackageToRequireDevComposerRector::class);
  //$services->set(\Rector\Composer\Rector\ChangePackageVersionComposerRector::class);
  //$services->set(\Rector\Composer\Rector\RemovePackageComposerRector::class);
  //$services->set(\Rector\Composer\Rector\RenamePackageComposerRector::class);
  //$services->set(\Rector\Composer\Rector\ReplacePackageAndVersionComposerRector::class);
  //$services->set(\Rector\DeadCode\Rector\ClassLike\RemoveAnnotationRector::class);
  //$services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector::class);
  //$services->set(\Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector::class);
  //$services->set(\Rector\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector::class);
  //$services->set(\Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector::class);
  //$services->set(\Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector::class);
  //$services->set(\Rector\Defluent\Rector\ClassMethod\NormalToFluentRector::class);
  //$services->set(\Rector\Defluent\Rector\ClassMethod\ReturnThisRemoveRector::class);
  //$services->set(\Rector\Defluent\Rector\MethodCall\FluentChainMethodCallToNormalMethodCallRector::class);
  //$services->set(\Rector\Defluent\Rector\MethodCall\NewFluentChainMethodCallToNonFluentRector::class);
  //$services->set(\Rector\Defluent\Rector\Return_\ReturnNewFluentChainMethodCallToNonFluentRector::class);
  //$services->set(\Rector\DependencyInjection\Rector\Class_\ActionInjectionToConstructorInjectionRector::class);
  //$services->set(\Rector\DependencyInjection\Rector\ClassMethod\AddMethodParentCallRector::class);
  //$services->set(\Rector\DowngradePhp53\Rector\Dir\DirConstToFileConstRector::class);
  //$services->set(\Rector\EarlyReturn\Rector\If_\ChangeOrIfReturnToEarlyReturnRector::class);
  //$services->set(\Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector::class);
  //$services->set(\Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector::class);
  //$services->set(\Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector::class);
  //$services->set(\Rector\LeagueEvent\Rector\MethodCall\DispatchStringToObjectRector::class);
  //$services->set(\Rector\MockeryToProphecy\Rector\ClassMethod\MockeryCreateMockToProphizeRector::class);
  //$services->set(\Rector\MockeryToProphecy\Rector\StaticCall\MockeryCloseRemoveRector::class);
  //$services->set(\Rector\MysqlToMysqli\Rector\Assign\MysqlAssignToMysqliRector::class);
  //$services->set(\Rector\MysqlToMysqli\Rector\FuncCall\MysqlFuncCallToMysqliRector::class);
  //$services->set(\Rector\MysqlToMysqli\Rector\FuncCall\MysqlPConnectToMysqliConnectRector::class);
  //$services->set(\Rector\MysqlToMysqli\Rector\FuncCall\MysqlQueryMysqlErrorWithLinkRector::class);
  //$services->set(\Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector::class);
  //$services->set(\Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector::class);
  //$services->set(\Rector\Naming\Rector\Class_\RenamePropertyToMatchTypeRector::class);
  //$services->set(\Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector::class);
  //$services->set(\Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class);
  //$services->set(\Rector\Order\Rector\Class_\OrderPrivateMethodsByUseRector::class);
  //$services->set(\Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\Class_\AddMockPropertiesRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\ClassMethod\PhpSpecMethodToPHPUnitMethodRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\Class_\PhpSpecClassToPHPUnitClassRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\Class_\RenameSpecFileToTestFileRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\MethodCall\PhpSpecMocksToPHPUnitMocksRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\MethodCall\PhpSpecPromisesToPHPUnitAssertRector::class);
  //$services->set(\Rector\PhpSpecToPHPUnit\Rector\Variable\MockVariableToPropertyFetchRector::class);
  //$services->set(\Rector\PostRector\Rector\ClassRenamingPostRector::class);
  //$services->set(\Rector\PostRector\Rector\NameImportingPostRector::class);
  //$services->set(\Rector\PostRector\Rector\NodeAddingPostRector::class);
  //$services->set(\Rector\PostRector\Rector\NodeRemovingPostRector::class);
  //$services->set(\Rector\PostRector\Rector\NodeToReplacePostRector::class);
  //$services->set(\Rector\PostRector\Rector\PropertyAddingPostRector::class);
  //$services->set(\Rector\PostRector\Rector\UseAddingPostRector::class);
  //$services->set(\Rector\Privatization\Rector\Class_\ChangeLocalPropertyToVariableRector::class);
  //$services->set(\Rector\Privatization\Rector\ClassMethod\ChangeGlobalVariablesToPropertiesRector::class);
  //$services->set(\Rector\Privatization\Rector\Class_\RepeatedLiteralToClassConstantRector::class);
  //$services->set(\Rector\Removing\Rector\ClassMethod\ArgumentRemoverRector::class);
  //$services->set(\Rector\Removing\Rector\Class_\RemoveInterfacesRector::class);
  //$services->set(\Rector\Removing\Rector\Class_\RemoveParentRector::class);
  //$services->set(\Rector\Removing\Rector\Class_\RemoveTraitUseRector::class);
  //$services->set(\Rector\Removing\Rector\FuncCall\RemoveFuncCallArgRector::class);
  //$services->set(\Rector\Removing\Rector\FuncCall\RemoveFuncCallRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\Class_\DesiredClassTypeToDynamicRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\Class_\NewUniqueObjectToEntityFactoryRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\Class_\PassFactoryToUniqueObjectRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\Class_\StaticTypeToSetterInjectionRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\Property\DesiredPropertyClassMethodTypeToDynamicRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\StaticCall\DesiredStaticCallTypeToDynamicRector::class);
  //$services->set(\Rector\RemovingStatic\Rector\StaticPropertyFetch\DesiredStaticPropertyFetchTypeToDynamicRector::class);
  //$services->set(\Rector\Renaming\Rector\ClassConstFetch\RenameClassConstFetchRector::class);
  //$services->set(\Rector\Renaming\Rector\ClassMethod\RenameAnnotationRector::class);
  //$services->set(\Rector\Renaming\Rector\ConstFetch\RenameConstantRector::class);
  //$services->set(\Rector\Renaming\Rector\FileWithoutNamespace\PseudoNamespaceToNamespaceRector::class);
  //$services->set(\Rector\Renaming\Rector\FuncCall\RenameFunctionRector::class);
  //$services->set(\Rector\Renaming\Rector\MethodCall\RenameMethodRector::class);
  //$services->set(\Rector\Renaming\Rector\Name\RenameClassRector::class);
  //$services->set(\Rector\Renaming\Rector\Namespace_\RenameNamespaceRector::class);
  //$services->set(\Rector\Renaming\Rector\PropertyFetch\RenamePropertyRector::class);
  //$services->set(\Rector\Renaming\Rector\StaticCall\RenameStaticMethodRector::class);
  //$services->set(\Rector\Renaming\Rector\String_\RenameStringRector::class);
  //$services->set(\Rector\Restoration\Rector\ClassConstFetch\MissingClassConstantReferenceToStringRector::class);
  //$services->set(\Rector\Restoration\Rector\ClassLike\UpdateFileNameByClassNameFileSystemRector::class);
  //$services->set(\Rector\Restoration\Rector\ClassMethod\InferParamFromClassMethodReturnRector::class);
  //$services->set(\Rector\Restoration\Rector\Class_\RemoveFinalFromEntityRector::class);
  //$services->set(\Rector\Restoration\Rector\Namespace_\CompleteImportForPartialAnnotationRector::class);
  //$services->set(\Rector\Restoration\Rector\Property\MakeTypedPropertyNullableIfCheckedRector::class);
  //$services->set(\Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector::class);
  //$services->set(\Rector\PSR4\Rector\FileWithoutNamespace\NormalizeNamespaceByPSR4ComposerAutoloadRector::class);
  //$services->set(\Rector\PSR4\Rector\Namespace_\MultipleClassFileToPsr4ClassesRector::class);
  //$services->set(\Rector\Transform\Rector\Assign\DimFetchAssignToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\Assign\GetAndSetToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\Assign\PropertyAssignToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\Assign\PropertyFetchToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\Class_\AddInterfaceByParentRector::class);
  //$services->set(\Rector\Transform\Rector\Class_\AddInterfaceByTraitRector::class);
  //$services->set(\Rector\Transform\Rector\Class_\ChangeSingletonToServiceRector::class);
  //$services->set(\Rector\Transform\Rector\ClassConstFetch\ClassConstFetchToValueRector::class);
  //$services->set(\Rector\Transform\Rector\Class_\MergeInterfacesRector::class);
  //$services->set(\Rector\Transform\Rector\ClassMethod\SingleToManyMethodRector::class);
  //$services->set(\Rector\Transform\Rector\ClassMethod\WrapReturnRector::class);
  //$services->set(\Rector\Transform\Rector\Class_\ParentClassToTraitsRector::class);
  //$services->set(\Rector\Transform\Rector\FuncCall\ArgumentFuncCallToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\FuncCall\FuncCallToConstFetchRector::class);
  //$services->set(\Rector\Transform\Rector\FuncCall\FuncCallToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\FuncCall\FuncCallToNewRector::class);
  //$services->set(\Rector\Transform\Rector\FuncCall\FuncCallToStaticCallRector::class);
  //$services->set(\Rector\Transform\Rector\Isset_\UnsetAndIssetToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\CallableInMethodCallToVariableRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\MethodCallToAnotherMethodCallWithArgumentsRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\MethodCallToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\MethodCallToStaticCallRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\ReplaceParentCallByPropertyCallRector::class);
  //$services->set(\Rector\Transform\Rector\MethodCall\ServiceGetterToConstructorInjectionRector::class);
  //$services->set(\Rector\Transform\Rector\New_\NewArgToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\New_\NewToConstructorInjectionRector::class);
  //$services->set(\Rector\Transform\Rector\New_\NewToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\New_\NewToStaticCallRector::class);
  //$services->set(\Rector\Transform\Rector\StaticCall\StaticCallToFuncCallRector::class);
  //$services->set(\Rector\Transform\Rector\StaticCall\StaticCallToMethodCallRector::class);
  //$services->set(\Rector\Transform\Rector\StaticCall\StaticCallToNewRector::class);
  //$services->set(\Rector\Transform\Rector\String_\StringToClassConstantRector::class);
  //$services->set(\Rector\Transform\Rector\String_\ToStringToMethodCallRector::class);
  //$services->set(\Rector\Visibility\Rector\ClassConst\ChangeConstantVisibilityRector::class);
  //$services->set(\Rector\Visibility\Rector\ClassMethod\ChangeMethodVisibilityRector::class);
  //$services->set(\Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class);
};
