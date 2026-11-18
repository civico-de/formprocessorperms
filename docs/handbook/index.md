---
title: Form Processor Permissions
---

# Form Processor Permissions

Form Processor lets each processor require its own permission for API calls. It checks that permission but never registers it with CiviCRM. On Standalone, saving a role then silently drops the permission, and every API call to the processor fails with "Authorization failed". On Drupal, the permission is missing from the permissions screen.

This extension registers the permission of every form processor that has one. Each appears in the permission list as "Form Processor: <processor title>" and can be assigned to roles like any other permission.

## Requirements

Form Processor and Action Provider must be installed. The supported CiviCRM and PHP versions are listed on the extension's page. There is nothing to configure.

## Assigning a processor's permission

1. Set the permission on the form processor.
2. Clear the caches (Administer → System Settings → Clear Caches, or `cv flush`). On Drupal, also clear the Drupal cache.
3. Assign "Form Processor: <processor title>" to the role that calls the processor.

An API user also needs "authenticate with api key" and "access CiviCRM backend and API". Those are independent of the processor's own permission.

## Uninstalling

Uninstalling the extension unregisters the permissions. On Standalone they are removed from a role the next time the role is saved, and API calls to those processors fail again.
