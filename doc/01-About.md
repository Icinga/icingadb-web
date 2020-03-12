# About Icinga DB Web

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Migration](#migration)
4. [New Features](#new-features)
5. [Concepts](#concepts)

**Icinga DB Web** pushes your monitoring stack to the next level.

Based on the lessons learnt with the base monitoring module, it offers a modern
and streamlined design to provide a clear and concise overview of your monitoring
environment.

## Installation

For installing Icinga DB Web please check the [installation](02-Installation.md) chapter.

## Configuration

To configure Icinga DB Web please check the [configuration](03-Configuration.md) chapter.

## Migration

To migrate from the monitoring module to Icinga DB Web check the [migration](10-Migration.md)
chapter.

## New Features

### Multiple List Layouts

The new view switcher displayed in the controls of lists allows to change their layout.
The majority of lists use this to switch between various levels of detail. The service
list below for example uses it to show a check's output at different lengths.

![View Switcher Preview](res/view-switcher-preview.png)

### Cleaner Detail Views

A host's or service's detail view has been restructured to show more details but also
to make more use of the available space. Important details also got moved to the top
so that they are visible right away without having to scroll down.

![Service Detail Preview](res/service-detail-preview.png)

### Modal Dialogs

Acknowledging a problem, scheduling a downtime or sending a custom notification does
not take you away from where you've been. Instead a modal dialog is shown on top of
your current view.

![Modal Dialog Preview](res/modal-dialog-preview.png)

### Bulk Operations

If you ever wanted to perform really big bulk acknowledgements or downtime schedules,
now is the time for it. Simply filter for the hosts or services you want to operate on
and then select *Continue with filter*. No more *shift-click* nightmares! (Which are
still possible, for the die-hard)

![Continue With Preview](res/continue-with-preview.png)

## Concepts

To learn more about our widget/view designs check the [concepts](11-Concepts.md) chapter.
