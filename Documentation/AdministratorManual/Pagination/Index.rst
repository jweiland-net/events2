..  include:: /Includes.rst.txt


==========
Pagination
==========

`events2` uses the `SimplePagination` from TYPO3 Core to navigate through
your records with `first`, `previous`, `next` and `last` buttons. If
you need something more complex like `1, 2 ... 56, 57, 58 ... 123, 124` you
should use another pagination library or build your own one. In the next steps
I explain you how to implement the numbered_pagination solution
of Georg Ringers.

..  rst-class:: bignums

    1.  Install `numbered_pagination`

        Install and activate `numbered_pagination` extension from Georg
        Ringer. Please check in your SitePackage extension that
        `numbered_pagination` is set as a dependency and will be loaded
        before `events2` and your SitePackage.

    2.  Change pagination class in TypoScript

        ..  code-block:: typoscript

            plugin.tx_events2.pageBrowser.class = GeorgRinger\NumberedPagination\NumberedPagination

    3.  Change path to events2 partials

        Set constant `partialRootPath` to a location within your SitePackage:

        ..  code-block:: typoscript

            plugin.tx_events2.view.partialRootPath = EXT:site_package/Resources/Private/Extensions/Events2/Partials/

    4.  Create Pagination template

        Create file `Resources/Private/Extensions/Events2/Partials/Component/Pagination.html`
        with example content from numbered_pagination
        https://github.com/georgringer/numbered_pagination/blob/master/Resources/Private/Partials/Pagination.html

        ..  code-block:: html

            <html lang="en"
                  xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
                  data-namespace-typo3-fluid="true">

            <ul class="f3-widget-paginator">
                <f:if condition="{pagination.previousPageNumber} && {pagination.previousPageNumber} >= {pagination.firstPageNumber}">
                    <li class="previous">
                        <a href="{f:uri.action(action:actionName, arguments:{currentPage: pagination.previousPageNumber})}" title="{f:translate(key:'pagination.previous')}">
                            {f:translate(key:'widget.pagination.previous', extensionName: 'fluid')}
                        </a>
                    </li>
                </f:if>
                <f:if condition="{pagination.hasLessPages}">
                    <li>…</li>
                </f:if>
                <f:for each="{pagination.allPageNumbers}" as="page">
                    <li class="{f:if(condition: '{page} == {paginator.currentPageNumber}', then:'current')}">
                        <a href="{f:uri.action(action:actionName, arguments:{currentPage: page})}">{page}</a>
                    </li>
                </f:for>
                <f:if condition="{pagination.hasMorePages}">
                    <li>…</li>
                </f:if>
                <f:if condition="{pagination.nextPageNumber} && {pagination.nextPageNumber} <= {pagination.lastPageNumber}">
                    <li class="next">
                        <a href="{f:uri.action(action:actionName, arguments:{currentPage: pagination.nextPageNumber})}" title="{f:translate(key:'pagination.next')}">
                            {f:translate(key:'widget.pagination.next', extensionName: 'fluid')}
                        </a>
                    </li>
                </f:if>
            </ul>
            </html>

    5.  Clear Cache

        Needed to reload the fluid templates.
