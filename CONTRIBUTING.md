# Contributing to Yusr

First off, thank you for considering contributing to Yusr! It's people like you that make Yusr such a great tool.

## Where do I go from here?

If you've noticed a bug or have a feature request, make sure to check our [Issues](https://github.com/tal7aouy/yusr/issues) page to see if someone else in the community has already created a ticket. If not, go ahead and [make one](https://github.com/tal7aouy/yusr/issues/new)!

## Fork & create a branch

If this is something you think you can fix, then [fork Yusr](https://help.github.com/articles/fork-a-repo) and create a branch with a descriptive name.

## Get the test suite running

Make sure you're using the latest version of PHP and have Composer installed. Then, install the dependencies:

```bash
composer install
```

Now you should be able to run the entire test suite using:

```bash
composer test
```

## Implement your fix or feature

At this point, you're ready to make your changes! Feel free to ask for help; everyone is a beginner at first.

## Make a Pull Request

At this point, you should switch back to your master branch and make sure it's up to date with Yusr's main branch:

```bash
git remote add upstream git@github.com:tal7aouy/yusr.git
git checkout main
git pull upstream main
```

## Code review

A team member will review your pull request and provide feedback. Please be patient as pull requests are often reviewed in batches.

## Thank you!

Thank you in advance for contributing to Yusr! We appreciate your time and effort to help make this project better.
