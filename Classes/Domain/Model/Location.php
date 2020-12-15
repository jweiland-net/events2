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

/*
 * This class contains all getter and setters for a Location.
 */
class Location extends AbstractEntity
{
    use Typo3PropertiesTrait;

    /**
     * @var string
     */
    protected $location = '';

    /**
     * @var string
     */
    protected $street = '';

    /**
     * @var string
     */
    protected $houseNumber = '';

    /**
     * @var string
     */
    protected $zip = '';

    /**
     * @var string
     */
    protected $city = '';

    /**
     * @var \SJBR\StaticInfoTables\Domain\Model\Country
     */
    protected $country;

    /**
     * @var \JWeiland\Events2\Domain\Model\Link
     */
    protected $link;

    /**
     * @var \JWeiland\Maps2\Domain\Model\PoiCollection
     */
    protected $txMaps2Uid;

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location)
    {
        $this->location = $location;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street)
    {
        $this->street = $street;
    }

    public function getHouseNumber(): string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(string $houseNumber)
    {
        $this->houseNumber = $houseNumber;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip)
    {
        $this->zip = $zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city)
    {
        $this->city = $city;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country)
    {
        $this->country = $country;
    }

    public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link = null)
    {
        $this->link = $link;
    }

    /**
     * SF: Do not add PoiCollection as strict_type to $txMaps2Uid
     * as this will break DataMap in Extbase when maps2 is not installed
     * @link https://github.com/jweiland-net/events2/issues/114
     *
     * @param PoiCollection|null $txMaps2Uid
     */
    public function setTxMaps2Uid($txMaps2Uid = null)
    {
        $this->txMaps2Uid = $txMaps2Uid;
    }

    /**
     * @return PoiCollection|null
     */
    public function getTxMaps2Uid()
    {
        return $this->txMaps2Uid;
    }
}
