.. include:: ../../Includes.txt

.. _installation:

============
Installation
============

The extension needs to be installed like any other extension of TYPO3 CMS:

#. Visit |extension_key| at `Github <https://github.com/jweiland-net/events2>`_

#. You will find a Download-Button where you can select between Download as Zip or Link for cloning this project.

#. Get the extension

   #. **Get it via Zip:** Switch to the Extensionmanager and upload |extension_key|

   #. **Get it via Git:** If Git is available on your system, switch into
      the typo3conf/ext/ directory and clone it from Github:

      .. code-block:: bash

         git clone https://github.com/jweiland-net/events2.git

   #. **Get it via Composer:** If you run TYPO3 in composer mode you can add a new Repository
      into you composer.json:

      .. code-block:: bash

         {
           "repositories": [
             {
               "type": "composer",
               "url": "https://composer.typo3.org/"
             },
             {
               "type": "vcs",
               "url": "https://github.com/jweiland-net/events2"
             }
           ],
           "name": "my-vendor/my-typo3-cms-distribution",
           "require": {
             "typo3/cms": "7.6.*",
             "jweiland/events2": "2.*"
           },
           "extra": {
             "typo3/cms": {
               "cms-package-dir": "{$vendor-dir}/typo3/cms",
               "web-dir": "web"
             }
           }
         }

#. The Extension Manager offers some basic configuration which is
   explained :ref:`here <extensionManager>`.

Preparation: Include static TypoScript
--------------------------------------

The extension ships some TypoScript code which needs to be included.

#. Switch to the root page of your site.

#. Switch to the **Template module** and select *Info/Modify*.

#. Press the link **Edit the whole template record** and switch to the tab *Includes*.

#. Select **Events2 (events2)** at the field *Include static (from extensions):*
