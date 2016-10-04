..  Content substitution
	...................................................
	Hint: following expression |my_substition_value| will be replaced when rendering doc.

.. |author| replace:: Stefan Froemken <projects@jweiland.net>
.. |extension_key| replace:: events2
.. |extension_name| replace:: Events 2
.. |typo3| image:: Images/Typo3.png
.. |time| date:: %m-%d-%Y %H:%M

..  Custom roles
	...................................................
	After declaring a role like this: ".. role:: custom", the document may use the new role like :custom:`interpreted text`.
	Basically, this will wrap the content with a CSS class to be styled in a special way when document get rendered.
	More information: http://docutils.sourceforge.net/docs/ref/rst/roles.html

.. role:: code
.. role:: typoscript
.. role:: typoscript(code)
.. role:: ts(typoscript)
.. role:: php(code)

.. |img-config-em|      image:: /Images/AdministratorManual/events2-configure-ExtensionManager.png
.. :border: 0
.. :align: left
.. :name: ConfigureEvents2InExtensionManager
