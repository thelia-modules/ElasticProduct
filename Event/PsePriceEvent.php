<?php


namespace ElasticProduct\Event;


use Thelia\Core\Event\ActionEvent;

class PsePriceEvent extends ActionEvent
{
    const GET_PSE_PRICES = "elasticpse.get.pse.prices";

    protected $pseId;

    protected $countryId;

    protected $customerId;

    protected $price;

    protected $promoPrices;

    public function __construct($pseId, $countryId, $customerId = null)
    {
        $this->pseId = $pseId;
        $this->countryId = $countryId;
        $this->customerId = $customerId;
    }

    /**
     * @return mixed
     */
    public function getPseId()
    {
        return $this->pseId;
    }

    /**
     * @param mixed $pseId
     * @return PsePriceEvent
     */
    public function setPseId($pseId)
    {
        $this->pseId = $pseId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * @param mixed $countryId
     * @return PsePriceEvent
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
        return $this;
    }

    /**
     * @return null
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param null $customerId
     * @return PsePriceEvent
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     * @return PsePriceEvent
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPromoPrices()
    {
        return $this->promoPrices;
    }

    /**
     * @param mixed $promoPrices
     * @return PsePriceEvent
     */
    public function setPromoPrices($promoPrices)
    {
        $this->promoPrices = $promoPrices;
        return $this;
    }
}