
All PHPUnit-based tests requiring a fully bootstrapped/functioning
Drupal/Tripal instance for effective testing should be included in this
directory. Each one should extend one of the following base classes:

- `Drupal\Tests\tripal_chado\Functional\ChadoTestBrowserBase`
    For any test which needs a chado instance either directly or indirectly.

- `Drupal\Tests\tripal\Functional\TripalTestBrowserBase`
    For any test which needs a Tripal site but will not use Chado in any way.

- `Drupal\Tests\BrowserTestBase`
    For any test which does not use Tripal in any way. If this is the case,
    you may want to think about the design of your module to ensure that
    it is effectively using all Tripal APIs available.

NOTE: The above list of classes each inherit from those listed below them in
the list. As such, you have all the functionality from the TripalTestBrowserBase
and the Drupal BrowserTestBase available to you when you extend the
ChadoTestBrowserBase.

NOTE: Javascript-focused tests should NOT BE in this directory. Instead they
should be in a FunctionalJavscript directory as they use WebDriver and a
different set of base classes.

## Directory Structure

Only tests which apply to the module as a whole should be directly in this folder.

In most cases you will create a folder to indicate a category of tests. For example,
you will create a folder labelled `ChadoFields` to contain all tests relating
to ChadoField implementations (testing type, widget and formatter).

Rule of Thumb:
- All plugin types should have a folder named the same as the plugin type that
  contains all tests for implementations, base classes, and the plugin manager
  for that plugin type.
- All services should have its own folder named the same as the service class.
  This folder can also include tests for forms, controllers, etc which provide
  a user or administrative interface for this service.
- Beyond this, create folders focused on common functionality.
    - For example, if this module creates an entity, you may create a folder
      named the same as the entity that contains tests related to the entity
      classes, forms, list builders, etc.
