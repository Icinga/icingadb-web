# Icinga DB Web

[![PHP Support](https://img.shields.io/badge/php-%3E%3D%205.6-777BB4?logo=PHP)](https://php.net/)
![Build Status](https://github.com/icinga/icingaweb2-module-icingadb/workflows/PHP%20Tests/badge.svg?branch=master)
[![Github Tag](https://img.shields.io/github/tag/Icinga/icingaweb2-module-icingadb.svg)](https://github.com/Icinga/icingaweb2-module-icingadb)

![Icinga Logo](https://icinga.com/wp-content/uploads/2014/06/icinga_logo.png)

1. [Documentation](#documentation)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Migration](#migration)
5. [New Features](#new-features)
6. [Concepts](#concepts)

**Icinga DB Web** pushes your monitoring stack to the next level.

Based on the lessons learnt with the base monitoring module, it offers a modern
and streamlined design to provide a clear and concise overview of your monitoring
environment.

## Documentation

The documentation is located in the [doc/](doc/) directory and also available
on [icinga.com/docs](https://icinga.com/docs/icingadb-web/latest/).

## Installation

For installing Icinga DB Web please check the [installation](https://icinga.com/docs/icingadb-web/latest/doc/02-Installation/)
chapter.

## Configuration

To configure Icinga DB Web please check the [configuration](https://icinga.com/docs/icingadb-web/latest/doc/03-Configuration/)
chapter.

## Migration

To migrate from the monitoring module to Icinga DB Web check the [migration](https://icinga.com/docs/icingadb-web/latest/doc/10-Migration/)
chapter.

## New Features

### Multiple List Layouts

The new view switcher displayed in the controls of lists allows to change their layout.
The majority of lists use this to switch between various levels of detail. The service
list below for example uses it to show a check's output at different lengths.

![View Switcher Preview](doc/res/view-switcher-preview.png)

### Cleaner Detail Views

A host's or service's detail view has been restructured to show more details but also
to make more use of the available space. Important details also got moved to the top
so that they are visible right away without having to scroll down.

![Service Detail Preview](doc/res/service-detail-preview.png)

### Modal Dialogs

Acknowledging a problem, scheduling a downtime or sending a custom notification does
not take you away from where you've been. Instead a modal dialog is shown on top of
your current view.

![Modal Dialog Preview](doc/res/modal-dialog-preview.png)

### Bulk Operations

If you ever wanted to perform really big bulk acknowledgements or downtime schedules,
now is the time for it. Simply filter for the hosts or services you want to operate on
and then select *Continue with filter*. No more *shift-click* nightmares! (Which are
still possible, for the die-hard)

![Continue With Preview](doc/res/continue-with-preview.png)

## Concepts

To learn more about our widget/view designs check the [concepts](https://icinga.com/docs/icingadb-web/latest/doc/11-Concepts/)
chapter.
