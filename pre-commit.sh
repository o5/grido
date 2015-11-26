#!/bin/sh

if git rev-parse --verify HEAD >/dev/null 2>&1; then
    against=HEAD
else
    against=4b825dc642cb6eb9a060e54bf8d69288fbee4904
fi

EXITCODE=0
FILES=`git diff --cached --diff-filter=ACMRTUXB --name-only $against --`

PHP_FILES=''
if [ ! -z "$FILES" ]; then
    PHP_FILES=`find $FILES -name "*.php"`
fi;

if [ ! -z "$PHP_FILES" ]; then
    echo "\nCheck coding standards using PHP_CodeSniffer...\n"
    vendor/bin/phpcs --ignore=tests/ --standard=standards.xml --colors --encoding=utf-8 --runtime-set php_path php -sp $PHP_FILES
    if [ $? != 0 ]; then
        EXITCODE=1
    fi

    if [ $EXITCODE == 0 ]; then
        echo "Run Nette Tester...\n"
        composer test
        if [ $? != 0 ]; then
            EXITCODE=1
        fi
    fi
fi

if [ $EXITCODE -gt 0 ]; then
    echo
    echo '\033[1;41;37mFix the above erros or use:\033[0m'
    echo '  git commit --no-verify'
    echo
fi

exit $EXITCODE