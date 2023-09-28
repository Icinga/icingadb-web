# Contributing

Icinga is an open source project and lives from your ideas and contributions.

There are many ways to contribute, from improving the documentation, submitting
bug reports and features requests or writing code to add enhancements or fix bugs.

#### Table of Contents

1. [Introduction](#introduction)
2. [Fork the Project](#fork-the-project)
3. [Branches](#branches)
4. [Commits](#commits)
5. [Pull Requests](#pull-requests)
6. [Testing](#testing)
7. [Source Code Patches](#source-code-patches)
8. [Documentation Patches](#documentation-patches)

## Introduction

Please consider our [roadmap](https://github.com/Icinga/icingadb-web/milestones) and
[open issues](https://github.com/icinga/icingadb-web/issues) when you start contributing
to the project.

Before starting your work on Icinga DB Web, you should [fork the project](https://help.github.com/articles/fork-a-repo/)
to your GitHub account. This allows you to freely experiment with your changes.
When your changes are complete, submit a [pull request](https://help.github.com/articles/using-pull-requests/).
All pull requests will be reviewed and merged if they suit some general guidelines:

* Changes are located in a topic branch
* For new functionality, proper tests are written
* Changes should follow the existing coding style and standards

Please continue reading in the following sections for a step by step guide.

## Fork the Project

[Fork the project](https://help.github.com/articles/fork-a-repo/) to your GitHub account
and clone the repository:

```
git clone git@github.com:jdoe/icingadb-web.git
cd icingadb-web
```

Add a new remote `upstream` with this repository as value.

```
git remote add upstream https://github.com/icinga/icingadb-web.git
```

You can pull updates to your fork's default branch:

```
git fetch --all
git pull upstream HEAD
```

Please continue to learn about [branches](#branches).

## Branches

Choosing a proper name for a branch helps us identify its purpose and possibly
find an associated bug or feature.
Generally a branch name should include a topic such as `fix` or `feature` followed
by a description and an issue number if applicable. Branches should have only changes
relevant to a specific issue.

```
git checkout -b fix/service-template-typo-1234
git checkout -b feature/config-handling-1235
```

Continue to apply your changes and test them. More details on specific changes:

* [Source Code Patches](#source-code-patches)
* [Documentation Patches](#documentation-patches)

## Commits

Once you've finished your work in a branch, please ensure to commit
your changes. A good commit message includes a short topic, additional body
and a reference to the issue you wish to solve (if existing).

Fixes:

```
Fix missing style in detail view

refs #4567
```

Features:

```
Add DateTime picker

refs #1234
```

You can add multiple commits during your journey to finish your patch.
Don't worry, you can squash those changes into a single commit later on.

## Pull Requests

Once you've committed your changes, please update your local default
branch and rebase your fix/feature branch against it before submitting a PR.

```
git checkout main
git pull upstream HEAD

git checkout fix/style-detail-view-5678
git rebase main
```

Once you've resolved any conflicts, push the branch to your remote repository.
It might be necessary to force push after rebasing - use with care!

New branch:
```
git push --set-upstream origin fix/style-detail-view-5678
```

Existing branch:
```
git push -f origin fix/style-detail-view-5678
```

You can now either use the [hub](https://hub.github.com) CLI tool to create a PR, or navigate
to your GitHub repository and create a PR there.

The pull request should again contain a telling subject and a reference
with `fixes` to an existing issue id if any. That allows developers
to automatically resolve the issues once your PR gets merged.

```
hub pull-request

<a telling subject>

fixes #1234
```

Thanks a lot for your contribution!


### Rebase a Branch

If you accidentally sent in a PR which was not rebased against the upstream default branch,
developers might ask you to rebase your PR.

First off, fetch and pull the default branch.

```
git checkout main
git fetch --all
git pull upstream HEAD
```

Then change to your working branch and start rebasing it against the default:

```
git checkout fix/style-detail-view-5678
git rebase main
```

If you are running into a conflict, rebase will stop and ask you to fix the problems.

```
git status

  both modified: path/to/conflict.php
```

Edit the file and search for `>>>`. Fix, build, test and save as needed.

Add the modified file(s) and continue rebasing.

```
git add path/to/conflict.php
git rebase --continue
```

Once succeeded ensure to push your changed history remotely.

```
git push -f origin fix/style-detail-view-5678
```


If you fear to break things, do the rebase in a backup branch first and later replace your current branch.

```
git checkout fix/style-detail-view-5678
git checkout -b fix/style-detail-view-5678-rebase

git rebase main

git branch -D fix/style-detail-view-5678
git checkout -b fix/style-detail-view-5678

git push -f origin fix/style-detail-view-5678
```

### Squash Commits

> **Note:**
>
> Be careful with squashing. This might lead to non-recoverable mistakes.
>
> This is for advanced Git users.

Say you want to squash the last 3 commits in your branch into a single one.

Start an interactive (`-i`)  rebase from current HEAD minus three commits (`HEAD~3`).

```
git rebase -i HEAD~3
```

Git opens your preferred editor. `pick` the commit in the first line, change `pick` to `squash` on the other lines.

```
pick e4bf04e47 Fix style detail view
squash d7b939d99 Tests
squash b37fd5377 Doc updates
```

Save and let rebase to its job. Then force push the changes to the remote origin.

```
git push -f origin fix/style-detail-view-5678
```


## Testing

TBD

## Source Code Patches

Icinga DB Web is written in PHP, LESS and JavaScript.

## Documentation Patches

The documentation is written in GitHub flavored [Markdown](https://guides.github.com/features/mastering-markdown/).
It is located in the `doc/` directory and can be edited with your preferred editor. You can also
edit it online on GitHub.

```
vim doc/02-Installation.md
```
