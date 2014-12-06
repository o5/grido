#!/bin/sh

# Check vendor folder exists
if [ ! -d "vendor/" ]; then
    echo "Error: DEV dependencies are missing. You can install them using Composer:" >&2
    echo "php composer.phar update --dev." >&2
    exit 2
fi

git stash -q --keep-index

echo "Running Code Sniffer..."
vendor/bin/phpcs --standard=standards.xml --extensions=php --encoding=utf-8 -sp src
if [ $? != 0 ]
then
        echo "\033[1;41;37mFix coding standards before commit!\033[0m\n"
        exit 1
fi

echo "Running Nette Tester..."
vendor/bin/tester tests -s

RESULT=$?
git stash pop -q
[ $RESULT -ne 0 ] && exit 1
exit 0