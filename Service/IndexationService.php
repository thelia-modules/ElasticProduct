<?php

namespace ElasticProduct\Service;

use ElasticProduct\ElasticProduct;
use Propel\Runtime\Propel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Thelia\Action\Image;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ConfigQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\Product;
use Thelia\Model\ProductQuery;
use Thelia\Model\ProductSaleElements;
use Thelia\Model\TaxRule;
use Thelia\TaxEngine\Calculator;

class IndexationService
{
    protected $eventDispatcher;

    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createIndex()
    {
        $client = ElasticProduct::getElasticSearchClient();

        $indexName = ElasticProduct::getIndexName();

        $index = [
            'index' => $indexName
        ];

        if($client->indices()->exists($index)) {
            $client->indices()->delete($index);
        }

        $index['body'] = [
            'settings' => [
                'analysis' => json_decode(file_get_contents(__DIR__.DS.'../Config/analysis.json'), true),
            ],
            'mappings' => [
                $indexName => json_decode(file_get_contents(__DIR__.DS.'../Config/mapping.json'), true),
            ]
        ];

        $client->indices()->create($index);
    }

    public function indexProducts($productPerPage, $page)
    {
        $products = ProductQuery::create()->limit($productPerPage)->offset($page*$productPerPage)->find();

        /** @var Product $product */
        foreach ($products as $product) {
            gc_enable();
            Propel::disableInstancePooling();

            $this->indexProduct($product);

            $product->clearAllReferences(true);
            gc_collect_cycles();
        }
    }

    public function indexProduct(Product $product)
    {
        $client = ElasticProduct::getElasticSearchClient();
        $indexName = ElasticProduct::getIndexName();

        $params = [];

        $params['body'][] = [
            'index' => [
                '_index' => $indexName,
                '_type' => $indexName,
                '_id' => $product->getId()
            ]
        ];

        $productTaxRule = $product->getTaxRule();
        $taxRuleCountries = $productTaxRule->getTaxRuleCountries();
        $taxedCountries = [];

        foreach ($taxRuleCountries as $taxRuleCountry) {
            $taxedCountries[] = $taxRuleCountry->getCountryId();
        }

        $countries = CountryQuery::create()
            ->find();
        $untaxedCountries = [];

        foreach ($countries as $country) {
            if (!in_array($country->getId(), $taxedCountries)) {
                $untaxedCountries[] = $country->getId();
            }
        }

        $productRelatedBody = [
            'product' => $this->getProductData($product),
            'brand' => $this->getBrandData($product),
            'categories' => $this->getCategoriesData($product),
            'features' => $this->getFeaturesData($product),
        ];

        $productSaleElementsRelatedBody = [];

        foreach ($product->getProductSaleElementss() as $productSaleElements) {
            $productSaleElementsData = $this->getProductSaleElementsData($productSaleElements);

            $productSaleElementsRelatedBody[] = array_merge(
                $productSaleElementsData,
                [
                    'prices' => $this->getPricesData($productSaleElements, $product, $productTaxRule, $taxedCountries, $untaxedCountries),
                    'attributes' => $this->getAttributesData($productSaleElements)
                ]);
        }

        $params['body'][] = array_merge($productRelatedBody, ['product_sale_elements' => array_values($productSaleElementsRelatedBody)]);

        $responses = [];
        $errors = [];
        try {
            $responses = $client->bulk($params);
            if (isset($responses['errors']) && $responses['errors'] === true) {
                foreach ($responses['items'] as $item) {
                    $errors[] = $item['index']['error']['reason'];
                }
            }
        } catch (\Exception $exception) {
            $errors = $exception->getMessage();
        }

        return ['response' => $responses, 'error' => $errors];
    }

    protected function getAttributesData(ProductSaleElements $productSaleElements)
    {
        $productAttributeCombinations = $productSaleElements->getAttributeCombinations();
        $attributes = [];

        foreach ($productAttributeCombinations as $attributeCombination) {
            $attribute = $attributeCombination->getAttribute();

            if (!isset($attributes[$attribute->getId()])) {
                $attributes[$attribute->getId()] = $this->getLangData($attribute->getAttributeI18ns());
            }

            $isSearchable = !in_array($attribute->getId(), explode(',', ElasticProduct::getConfigValue('unsearchable_attributes')));
            $attributes[$attribute->getId()]['searchable'] = $isSearchable;

            $attributeValue = $attributeCombination->getAttributeAv();
            $attributes[$attribute->getId()]['values'][] = $this->getLangData($attributeValue->getAttributeAvI18ns());
        }

        return array_values($attributes);
    }

    protected function getPricesData(ProductSaleElements $productSaleElements, Product $product, TaxRule $taxRule, $taxedCountries = [], $untaxedCountries = [])
    {
        $productPrices = $productSaleElements->getProductPrices();
        $prices = [];

        foreach ($productPrices as $price) {
            $prices['without_taxes']['countries'] = $untaxedCountries;
            $prices['without_taxes'][$price->getCurrency()->getCode()] = [
                'price' => $productSaleElements->getPromo() ? doubleval($price->getPrice()) : doubleval($price->getPromoPrice()),
                'original_price' => $productSaleElements->getPromo() ? doubleval($price->getPrice()) : null
            ];

            $taxCalculator = new Calculator();
            $taxCalculator->loadTaxRuleWithoutCountry($taxRule, $product);

            $taxedPrice = $taxCalculator->getTaxedPrice($price->getPrice());
            $taxedPromoPrice = $taxCalculator->getTaxedPrice($price->getPromoPrice());

            $prices['taxed']['countries'] = $taxedCountries;
            $prices['taxed'][$price->getCurrency()->getCode()] = [
                'price' => $productSaleElements->getPromo() ? doubleval($taxedPromoPrice) : doubleval($taxedPrice),
                'original_price' => $productSaleElements->getPromo() ? doubleval($taxedPrice) : null
            ];
        }

        return array_values($prices);
    }

    protected function getProductSaleElementsData(ProductSaleElements $productSaleElements)
    {
        return [
            'id' => $productSaleElements->getId(),
            'ref' => $productSaleElements->getRef(),
            'quantity' => $productSaleElements->getQuantity(),
            'is_promo' => !!$productSaleElements->getPromo(),
            'is_new' => !!$productSaleElements->getNewness(),
            'weight' => $productSaleElements->getWeight(),
            'is_default' => !!$productSaleElements->getIsDefault(),
            'ean_code' => $productSaleElements->getEanCode(),
            'created_at' => $productSaleElements->getCreatedAt('Y-m-d H:i:s'),
            'updated_at' => $productSaleElements->getUpdatedAt('Y-m-d H:i:s')
        ];
    }

    protected function getFeaturesData(Product $product)
    {
        $productFeatures = $product->getFeatureProducts();
        $features = [];

        foreach ($productFeatures as $productFeature) {
            $feature = $productFeature->getFeature();

            if (null === $feature) {
                continue;
            }

            if (!isset($features[$feature->getId()])) {
                $features[$feature->getId()] = $this->getLangData($feature->getFeatureI18ns());
            }

            $isSearchable = !in_array($feature->getId(), explode(',', ElasticProduct::getConfigValue('unsearchable_features')));
            $features[$feature->getId()]['searchable'] = $isSearchable;

            $featureValue = $productFeature->getFeatureAv();
            $features[$feature->getId()]['values'][] = $this->getLangData($featureValue->getFeatureAvI18ns());
        }

        return array_values($features);
    }

    protected function getCategoriesData(Product $product)
    {
        $productCategories = $product->getCategories();
        $categories = [];

        foreach ($productCategories as $category) {
            $langData = $this->getLangData($category->getCategoryI18ns(), true, $category,  'category');

            $categories[] = [
                'id' => $category->getId(),
                'is_visible' => !!$category->getVisible(),
                'parent' => $category->getParent(),
                'position' => $category->getPosition(),
                'i18ns' => $langData['i18ns'],
                'urls' => $langData['urls']
            ];
        }

        return $categories;
    }

    protected function getBrandData(Product $product)
    {
        $brand = $product->getBrand();

        if (null === $brand) {
            return [];
        }

        $langData = $this->getLangData($brand->getBrandI18ns(), true, $brand, 'brand');

        return [
            'id' => $brand->getId(),
            'is_visible' => !!$brand->getVisible(),
            'position' => $brand->getPosition(),
            'i18ns' => $langData['i18ns'],
            'urls' => $langData['urls']
        ];
    }

    protected function getProductData(Product $product)
    {
        $langData = $this->getLangData($product->getProductI18ns(), true, $product, 'product');
        $imageData = $this->getImageData($product->getProductImages(), 'product');

        return [
            'id' => $product->getId(),
            'ref' => $product->getRef(),
            'is_visible' => !!$product->getVisible(),
            'i18ns' => $langData['i18ns'],
            'urls' => $langData['urls'],
            'images' => $imageData,
            'created_at' => $product->getCreatedAt('Y-m-d H:i:s'),
            'updated_at' => $product->getUpdatedAt('Y-m-d H:i:s')
        ];
    }

    protected function getLangData($i18ns, $withUrl = false, $model = null,  $viewName = '')
    {
        $data = [];
        foreach ($i18ns as $i18n) {
            $data['i18ns'][$i18n->getLocale()] = [
                'title' => $i18n->getTitle(),
                'chapo' => $i18n->getChapo(),
                'description' => $i18n->getDescription(),
                'postscriptum' => $i18n->getPostscriptum()
            ];

            if ($withUrl) {
                $url = $model->getRewrittenUrl($i18n->getLocale());

                $data['urls'][$i18n->getLocale()] = $url ? $url :
                    sprintf(
                        "/?view=".$viewName."&lang=%s&".$viewName."_id=%d",
                        $i18n->getLocale(),
                        $model->getId()
                    );
            }
        }

        return $data;
    }

    protected function getImageData($images, $type)
    {
        $data = [];

        foreach ($images as $image) {
            if (null !== $image) {
                try {
                    $imageEvent = self::createImageEvent($image->getFile(), $type);
                    $this->eventDispatcher->dispatch(TheliaEvents::IMAGE_PROCESS, $imageEvent);

                    $i18nMethod = "get".ucfirst($type).'ImageI18ns';
                    $imageI18ns = $image->$i18nMethod();
                    $langData = $this->getLangData($imageI18ns, false);

                    $data[] = [
                        'visible' => $image->getVisible(),
                        'position' => $image->getPosition(),
                        'image_url' => $imageEvent->getFileUrl(),
                        'originale_image_url' => $imageEvent->getOriginalFileUrl(),
                        'image_path' => $imageEvent->getCacheFilepath(),
                        'i18ns' => $langData
                    ];

                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }

        return $data;
    }

    protected function createImageEvent($imageFile, $type)
    {
        $imageEvent = new ImageEvent();
        $baseSourceFilePath = ConfigQuery::read('images_library_path');
        if ($baseSourceFilePath === null) {
            $baseSourceFilePath = THELIA_LOCAL_DIR . 'media' . DS . 'images';
        } else {
            $baseSourceFilePath = THELIA_ROOT . $baseSourceFilePath;
        }
        // Put source image file path
        $sourceFilePath = sprintf(
            '%s/%s/%s',
            $baseSourceFilePath,
            $type,
            $imageFile
        );
        $imageEvent->setSourceFilepath($sourceFilePath);
        $imageEvent->setCacheSubdirectory($type);
        $imageEvent->setWidth(200)
            ->setHeight(200)
            ->setResizeMode(Image::EXACT_RATIO_WITH_BORDERS);
        return $imageEvent;
    }
}