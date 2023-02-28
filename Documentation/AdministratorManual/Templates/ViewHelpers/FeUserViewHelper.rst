..  include:: /Includes.rst.txt


..  _feUserViewHelper:

================
FeUserViewHelper
================

It searches in stored fe_user record (current logged in user) of TSFE for given property and
returns its value. We have removed the *password* property for security reasons.

Example
=======

..  code-block:: html

    {e2:feUser(field: 'username')}
