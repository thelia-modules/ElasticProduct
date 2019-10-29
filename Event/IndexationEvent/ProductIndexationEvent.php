<?php

namespace ElasticProduct\Event;

use Thelia\Core\Event\ActionEvent;
use Thelia\Model\Product;

class ProductIndexationEvent extends ActionEvent
{
    CONST GET_PRODUCT_DATA_EVENT = 'get_product_data_event';

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var array
     */
    protected $productData;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return ProductIndexationEvent
     */
    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductData()
    {
        return $this->productData;
    }

    /**
     * @param array $productData
     *
     * @return ProductIndexationEvent
     */
    public function setProductData(array $productData)
    {
        $this->productData = $productData;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function addProductData($name, $value)
    {
        $this->productData[$name] = $value;
        return $this;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function removeProductData($name)
    {
        unset($this->productData[$name]);
        return $this;
    }
}