.. include:: ../../Includes.txt

.. _typoScriptSearchPlugin:

========================
TypoScript Search Plugin
========================

In Extbase a link will be created by its Plugin Namespace like *tx_events2_search* or *tx_events2_events*. As we have
designed Search Plugin as its own Plugin it will only react and build links based on Plugin
Namespace *tx_events2_search*. But this is a problem. To build correct links within the Search Results which should be
compatible with our Event Plugin we have to change its Plugin Namespace to *tx_events2_events*.

That's why we set the Plugin Namespace of Search Plugin back to Events Plugin Namespace:

plugin.tx_events2_search.view.pluginNamespace = tx_events2_events

We prefer to not change that value.
