set -x

DEFAULTS="--prefer-dist --no-progress --no-suggest"
IGNORE_PLATFORM_REQUIREMENTS=""

if [ "$TRAVIS_PHP_VERSION" = 'nightly' ] || [ "$TRAVIS_PHP_VERSION" = '7.4snapshot' ]; then
    IGNORE_PLATFORM_REQUIREMENTS="--ignore-platform-reqs"
fi

composer update $DEFAULTS $IGNORE_PLATFORM_REQUIREMENTS

if [ "$DEPENDENCIES" = 'low' ]; then
    composer update $DEFAULTS --prefer-lowest --prefer-stable $IGNORE_PLATFORM_REQUIREMENTS
fi
