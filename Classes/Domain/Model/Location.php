<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2\Domain\Model;

use JWeiland\Events2\Domain\Traits\Typo3PropertiesTrait;
use JWeiland\Maps2\Domain\Model\PoiCollection;
use SJBR\StaticInfoTables\Domain\Model\Country;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This class contains all getter and setters for a Location.
 */
class Location extends AbstractEntity
{
    use Typo3PropertiesTrait;

    protected string $location = '';

    protected string $street = '';

    protected string $houseNumber = '';

    protected string $zip = '';

    protected string $city = '';

    protected ?Country $country = null;

    protected ?Link $link = null;

    /**
     * @var PoiCollection|null
     */
    protected $txMaps2Uid;

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(string $houseNumber): void
    {
        $this->houseNumber = $houseNumber;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): void
    {
        $this->country = $country;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link): void
    {
        $this->link = $link;
    }

    /**
     * Returns the location as string incl. the full address.
     * This is useful for export or LOCATION-part in ICS.
     */
    public function getLocationAsString(): string
    {
        $addressParts = [];
        if ($this->getLocation() !== '') {
            $addressParts[] = $this->getLocation();
        }
        if ($this->getStreet() !== '') {
            $addressParts[] = trim(sprintf(
                '%s %s',
                $this->getStreet(),
                $this->getHouseNumber(),
            ));
        }
        if ($this->getZip() !== '' || $this->getCity() !== '') {
            $addressParts[] = trim(sprintf(
                '%s %s',
                $this->getZip(),
                $this->getCity(),
            ));
        }

        return implode(', ', $addressParts);
    }

    /**
     * SF: Do not add PoiCollection as strict_type to $txMaps2Uid
     * as this will break DataMap in Extbase when maps2 is not installed
     * @link https://github.com/jweiland-net/events2/issues/114
     *
     * @param PoiCollection|null $txMaps2Uid
     */
    public function setTxMaps2Uid($txMaps2Uid): void
    {
        $this->txMaps2Uid = $txMaps2Uid;
    }

    /**
     * SF: Do not add PoiCollection as strict_type to $txMaps2Uid
     * as this will break DataMap in Extbase when maps2 is not installed
     * @link https://github.com/jweiland-net/events2/issues/114
     *
     * @return PoiCollection|null
     */
    public function getTxMaps2Uid()
    {
        return $this->txMaps2Uid;
    }
}
