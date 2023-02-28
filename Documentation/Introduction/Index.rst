..  include:: /Includes.rst.txt


..  _introduction:

============
Introduction
============

What does it do?
================

Our events2 is a timeframe based event management system. That means, that only events within this ongoing timeframe
are visible in Frontend. All newer/older events are still accessible, but there is no date calculated for them anymore.

Other than EXT:cal we have only one event record with a huge amount of settings to dynamically calculate the day
records. If you change a description, one event record will be updated only.

You can create different types of events:

*   Single: An event for just one day
*   Duration: An event like 17.07.2020-20.07.2020
*   Recurring: An event like 1st and 3rd monday and friday a month, except 23.07.2020 and different time on friday

Screenshots
===========

See events2 in action.

Output Frontend
---------------

..  figure:: ../Images/Introduction/events2-list.jpg
    :width: 500px
    :align: left
    :alt: Output of list in frontend
