.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

.. _faq:

===
FAQ
===

Why not using f:form in search form?
------------------------------------

We have tried it without using no_cache, but in some rare situations, when a customer starts two searches,
the second search request shows the results of the first request. Since than we do not add cHash anymore
in search requests.
If we would use POST requests, the URI of the search results is not copyable anymore. There are many people out
there copying the URIs in forums, emails, slack or other tools.
If we would use f:form VH in combination with GET, all hidden information of extbase will be appended to the URI,
which makes the URI extremely long. As there are some browsers limiting the URIs length to 1000 or 3000 we
can not us f:form, to prevent cutting the URI by Browser restrictions.


Translations
------------

Events2 is currently not fully multilingual. Our idea was to have one day record for all translations of an
event record. That way we have removed all translation columns from TCA of day table. But since TYPO3 8 and especially
TYPO3 9 the problems in events2 with translation grows.
With version 5.0.0 we have added these language columns back to day table. Now you can create translatable versions
of your event records again, but we have deactivated all day record related columns from form of translated record.
That's because events2 currently does not support multilingual records for day table. That should be OK for
most cases, but it is not possible to have different dates or date configuration for different translations.
