# TYPO3 Extension `events2`

[![Latest Stable Version](https://poser.pugx.org/jweiland/events2/v/stable.svg)](https://packagist.org/packages/jweiland/events2)
[![TYPO3 12.4](https://img.shields.io/badge/TYPO3-12.4-green.svg)](https://get.typo3.org/version/12)
[![License](http://poser.pugx.org/jweiland/events2/license)](https://packagist.org/packages/jweiland/events2)
[![Total Downloads](https://poser.pugx.org/jweiland/events2/downloads.svg)](https://packagist.org/packages/jweiland/events2)
[![Monthly Downloads](https://poser.pugx.org/jweiland/events2/d/monthly)](https://packagist.org/packages/jweiland/events2)
![Build Status](https://github.com/jweiland-net/events2/actions/workflows/ci.yml/badge.svg)

Events2 is an extension for TYPO3 CMS. It shows you a list of event entries incl.
detail view.

## 1 Features

* Create events of different types
    * Single: An event for just one day
    * Duration: Useful for holiday/travel like 21.09 - 29.09.2020
    * Recurring: Create events like 1st and 3rd Monday and Wednesday a month
* You can add/remove days from calculated recurring events
* Create multiple time records for one day
* Create different time records for individual weekdays
* Supports EXT:maps2 to show event location on Google Maps

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require jweiland/events2
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `events2` with the extension manager module.

### 2.2 Minimal setup

1) Include the static TypoScript of the extension.
2) Create event2 records on a sysfolder.
3) Create a plugin on a page and select at least the sysfolder as startingpoint.
