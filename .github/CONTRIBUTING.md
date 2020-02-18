# CONTRIBUTING

We are using [GitHub Actions](https://github.com/features/actions) as a continuous integration system.

For details, see [`workflows/continuous-integration.yml`](workflows/continuous-integration.yml).

## Coding Standards

We are using [`doctrine/coding-standard`](https://github.com/doctrine-coding-standard) to enforce coding standards.

Run

```
$ make coding-standards
```

to automatically fix coding standard violations.

## Dependency Analysis

We are using [`maglnet/composer-require-checker`](https://github.com/maglnet/ComposerRequireChecker) to prevent the use of unknown symbols in production code.

Run

```
$ make dependency-analysis
```

to run a dependency analysis.

## Static Code Analysis

We are using [`phpstan/phpstan`](https://github.com/phpstan/phpstan) and [`vimeo/psalm`](https://github.com/vimeo/psalm) to statically analyze the code.

Run

```
$ make static-code-analysis
```

to run a static code analysis.

We are also using the baseline features of [`phpstan/phpstan`](https://medium.com/@ondrejmirtes/phpstans-baseline-feature-lets-you-hold-new-code-to-a-higher-standard-e77d815a5dff) and [`vimeo/psalm`](https://psalm.dev/docs/running_psalm/dealing_with_code_issues/#using-a-baseline-file).

Run

```
$ make static-code-analysis-baseline
```

to regenerate the baselines in [`../phpstan-baseline.neon`](../phpstan-baseline.neon) and [`../psalm-baseline.xml`](../psalm-baseline.xml).

:exclamation: Ideally, the baselines should shrink over time.

## Extra lazy?

Run

```
$ make
```

to enforce coding standards, run a dependency analysis, and run a static code analysis!

## Help

:bulb: Run

```
$ make help
```

to display a list of available targets with corresponding descriptions.