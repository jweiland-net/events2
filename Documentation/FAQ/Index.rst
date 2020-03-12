.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../_IncludedDirectives.rst

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
