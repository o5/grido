# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/o5/grido).


## Pull Requests

- **Coding Standard** - The easiest way to apply the conventions just run `composer syntax`. You can also try `composer syntax-fix` for fixing errors automatically.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

## Running Syntax Checker

``` bash
$ composer syntax
```

## Running Tests

``` bash
$ composer test
```

## Working with assets
- You should never modify files in */assets/dist/* folder
- Just install build dependencies over [npm](https://docs.npmjs.com/getting-started/what-is-npm)

``` bash
$ npm install
```

- Run watcher and edit any of **.scss* or **.js* file.

``` bash
$ npm run watch
```

## Tip: For nerds only!
- You should set pre-commit hook which will trigger `composer syntax` and `composer test` before each commit.
- How to install it? Just run **exactly** command bellow in root of repository:
``` bash
$ ln -vs ../../pre-commit.sh .git/hooks/pre-commit
```


## Happy coding!
