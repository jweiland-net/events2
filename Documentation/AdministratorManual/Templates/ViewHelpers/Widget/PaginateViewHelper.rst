Widget / PaginateViewHelper
---------------------------

We have very special SQL-Queries in events2, which can't be realized
with QueryInterface of extbase. In that case it is not possible to use
the original paginate Widget of extbase.
This Widget expects some placeholders in the SQL-Statement like
###LIMIT### and ###SELECT###. It will automatically transform the given SQL-Query
to a SELECT or a COUNT query.

**Type:** Basic

General properties
^^^^^^^^^^^^^^^^^^

See documentation of extbase pagebrowser.

.. t3-field-list-table::
 :header-rows: 1

 - :Name: Name:
   :Type: Type:
   :Description: Description:
   :Default value: Default value:

 - :Name: maxRecords
   :Type: integer
   :Description: Max. amount of records on each page
   :Default value:
