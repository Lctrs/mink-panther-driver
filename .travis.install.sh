set -x

DEFAULTS="--prefer-dist --no-progress --no-suggest"
IGNORE_PLATFORM_REQUIREMENTS=""

if [ "$TRAVIS_PHP_VERSION" = 'nightly' ] || [ "$TRAVIS_PHP_VERSION" = '7.4snapshot' ]; then
    IGNORE_PLATFORM_REQUIREMENTS="--ignore-platform-reqs"
fi

composer install $DEFAULTS $IGNORE_PLATFORM_REQUIREMENTS

if [ "$SYMFONY_VERSION" != "" ]; then
    jq "(.require, .\"require-dev\")|=(with_entries(if .key|test(\"^symfony/(?!panther)\") then .value|=\"${SYMFONY_VERSION}\" else . end))" composer.json|ex -sc 'wq!composer.json' /dev/stdin
fi;

if [ "$DEPENDENCIES" = 'high' ]; then
    composer update $DEFAULTS $IGNORE_PLATFORM_REQUIREMENTS
fi

if [ "$DEPENDENCIES" = 'low' ]; then
    composer update $DEFAULTS --prefer-lowest --prefer-stable $IGNORE_PLATFORM_REQUIREMENTS
fi
