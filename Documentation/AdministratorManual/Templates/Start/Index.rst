..  include:: /Includes.rst.txt


..  _changeTemplates:

============================
Changing & editing templates
============================

EXT:events2 is using fluid as template engine. If you are using fluid
already, you might skip this section.

This documentation won't bring you all information about fluid but only the
most important things you need for using it. You can get
more information in books like the one of `Jochen Rau und Sebastian
Kurfürst <http://www.amazon.de/Zukunftssichere-TYPO3-Extensions-mit-
Extbase-Fluid/dp/3897219654/>`_ or online, e.g. at
`http://wiki.typo3.org/Fluid <http://wiki.tpyo3.org/Fluid>`_ or many
other sites.

Changing paths of the template
==============================

You should never edit the original templates of an extension as those changes will vanish if you upgrade the extension.
As any extbase based extension, you can find the templates in the directory ``Resources/Private/``.

If you want to change a template, copy the desired files to the directory where you store the templates.
This can be a directory in ``fileadmin`` or a custom extension. Multiple fallbacks can be defined which makes it far easier to customize the templates.

..  code-block:: typoscript

    plugin.tx_events2 {
      view {
        templateRootPaths >
        templateRootPaths {
          0 = EXT:events2/Resources/Private/Templates/
          1 = fileadmin/templates/ext/events2/Templates/
        }
        partialRootPaths >
        partialRootPaths {
          0 = EXT:events2/Resources/Private/Partials/
          1 = fileadmin/templates/ext/events2/Partials/
        }
        layoutRootPaths >
        layoutRootPaths {
          0 = EXT:events2/Resources/Private/Layouts/
          1 = fileadmin/templates/ext/events2/Layouts/
        }
      }
    }

..  _changeTemplates-site-settings:

Change the templates using site settings
========================================

..  versionchanged:: 12.0.0

    The static TypoScript template :guilabel:`Events` and its constants
    have been removed. Use the site settings of the site set
    `jweiland/events2` instead.

You can use the following site settings of the site set `jweiland/events2`
to add your own paths. They are added with the key `2` (templates and
partials) and `1` (layouts) to the paths of events2:

..  code-block:: yaml
    :caption: config/sites/<my-site>/settings.yaml

    events2:
      templateRootPath: 'EXT:my_sitepackage/Resources/Private/Events2/Templates/'
      partialRootPath: 'EXT:my_sitepackage/Resources/Private/Events2/Partials/'
      layoutRootPath: 'EXT:my_sitepackage/Resources/Private/Events2/Layouts/'
