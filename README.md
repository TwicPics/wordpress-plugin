# TwicPics' WordPress Plugin

[TwicPics](https://www.twicpics.com/?ref=wordpress) is a real-time image processing service that enables individuals and businesses of all sizes to deliver high performing and rich visual content with easy setup.

It reduces image file sizes with adaptive compression and automatic Next-Gen format to boost your website performance and SEO by delivering pixel perfect images on the fly.

TwicPics ensures your images are perfect. Neither too big nor too small.

You can download and install this plugin from your WordPress Installation or on [WordPress website](https://wordpress.org/plugins/twicpics/).

# Prepare for deployment

## Pre-requisites

You need to install:
- [Composer 2.5.4+](https://getcomposer.org/)
- [Yarn 1.x](https://classic.yarnpkg.com/lang/en/)

## Commands

```bash
composer install
yarn
```

This should create a `vendor` directory and an `admin.js` file at the root of the project.

# Development

## Packaging a development version

To generate a pre-release/development version as a zip file to be installed on a test site or distributed to external testers, type the following command:

```bash
node pack.js
```

This will generate a zip file at the root of the project called `twicpics.<version>.<datetime>.zip` that can be distributed safely.

`<version>` is extracted from the main `twicpics.php` file plugin description. For example: `Version: 0.3.0-beta` would result in `<version>` being `0.3.0-beta`

`<datetime>` is of the format `yymmddHHMM`. The higher the value, the more recent the archive.

Here is a complete example: `twicpics.0.3.0-beta.2306161602.zip`.

`pack.js` will amend the version number in both `twicpics.php` and `readme.txt` by appending `<datetime>`.

`pack.js` does parse and follow `.distignore` which means the zip file should be as close to an actual plugin distribution as possible.

__Please note you must follow the same steps as when preparing for deployment prior to packaging that way or crucial files will be missing from the archive.__

## Installing a development version

- Go to the WordPress install `Plugins` admin panel,
- click on the `Add New` button at the top of the page,
- on the next page, click on the `Upload Plugin` button at the top,
- select the zip file to upload from your local hard-drive.

This will create a folder on your server that is separate from the officially released TwicPics plugin and makes it possible to compare behaviour easily.

__Make sure only one version of the plugin is activated at the same time or unexpected behaviours may ensue.__

# Deploy

Everything is automated using Github Actions. As soon as a version is tagged, the plugin will be deployed to the WordPress repository with no manual operation necessary.
