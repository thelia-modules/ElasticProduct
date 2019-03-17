<?php

namespace ElasticProduct\EventListener;

use ElasticProduct\Model\ElasticProductIndexationQueueQuery;
use Propel\Runtime\Event\ActiveRecordEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Thelia;
use Thelia\Model\Event\AttributeAvEvent;
use Thelia\Model\Event\AttributeAvI18nEvent;
use Thelia\Model\Event\AttributeCombinationEvent;
use Thelia\Model\Event\AttributeEvent;
use Thelia\Model\Event\AttributeI18nEvent;
use Thelia\Model\Event\BrandEvent;
use Thelia\Model\Event\BrandI18nEvent;
use Thelia\Model\Event\CategoryEvent;
use Thelia\Model\Event\CategoryI18nEvent;
use Thelia\Model\Event\FeatureAvEvent;
use Thelia\Model\Event\FeatureAvI18nEvent;
use Thelia\Model\Event\FeatureEvent;
use Thelia\Model\Event\FeatureI18nEvent;
use Thelia\Model\Event\FeatureProductEvent;
use Thelia\Model\Event\ProductCategoryEvent;
use Thelia\Model\Event\ProductEvent;
use Thelia\Model\Event\ProductI18nEvent;
use Thelia\Model\Event\ProductImageEvent;
use Thelia\Model\Event\ProductPriceEvent;
use Thelia\Model\Event\ProductSaleElementsEvent;
use Thelia\Model\Product;
use Thelia\Model\ProductSaleElements;

class UpdateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        if (version_compare(Thelia::THELIA_VERSION, '2.4.0-alpha2', 'lt')) {
            return [];
        }

        return array(
            ProductEvent::POST_SAVE  => ['onModelSave', 128],
            ProductI18nEvent::POST_SAVE => ['onModelSave', 128],
            ProductImageEvent::POST_SAVE => ['onModelSave', 128],
            FeatureEvent::POST_SAVE => ['onModelSave', 128],
            FeatureI18nEvent::POST_SAVE => ['onModelSave', 128],
            FeatureAvEvent::POST_SAVE => ['onModelSave', 128],
            FeatureAvI18nEvent::POST_SAVE => ['onModelSave', 128],
            FeatureProductEvent::POST_SAVE => ['onModelSave', 128],
            AttributeEvent::POST_SAVE => ['onModelSave', 128],
            AttributeI18nEvent::POST_SAVE => ['onModelSave', 128],
            AttributeAvEvent::POST_SAVE => ['onModelSave', 128],
            AttributeAvI18nEvent::POST_SAVE => ['onModelSave', 128],
            ProductSaleElementsEvent::POST_SAVE  => ['onModelSave', 128],
            BrandEvent::POST_SAVE => ['onModelSave', 128],
            BrandI18nEvent::POST_SAVE => ['onModelSave', 128],
            CategoryEvent::POST_SAVE => ['onModelSave', 128],
            CategoryI18nEvent::POST_SAVE => ['onModelSave', 128],
            ProductPriceEvent::POST_SAVE  => ['onProductPriceSave', 128],
            ProductCategoryEvent::POST_SAVE  => ['onProductCategorySave', 128],
            AttributeCombinationEvent::POST_SAVE => ['onAttributeCombinationSave', 128],
        );
    }

    public function onModelSave(ActiveRecordEvent $event)
    {
        $model = $event->getModel();
        $class = get_class($model);

        $indexation = ElasticProductIndexationQueueQuery::create()
            ->filterByItemId($model->getId())
            ->filterByItemType($class)
            ->findOneOrCreate();

        $indexation->save();
    }

    public function onProductPriceSave(ProductPriceEvent $event)
    {
        $productPrice = $event->getModel();

        $indexation = ElasticProductIndexationQueueQuery::create()
            ->filterByItemId($productPrice->getProductSaleElementsId())
            ->filterByItemType(ProductSaleElements::class)
            ->findOneOrCreate();

        $indexation->save();
    }

    public function onProductCategorySave(ProductCategoryEvent $event)
    {
        $productCategory = $event->getModel();

        $indexation = ElasticProductIndexationQueueQuery::create()
            ->filterByItemId($productCategory->getProductId())
            ->filterByItemType(Product::class)
            ->findOneOrCreate();

        $indexation->save();
    }

    public function onAttributeCombinationSave(AttributeCombinationEvent $event)
    {
        $attributeCombination = $event->getModel();

        $indexation = ElasticProductIndexationQueueQuery::create()
            ->filterByItemId($attributeCombination->getProductSaleElementsId())
            ->filterByItemType(ProductSaleElements::class)
            ->findOneOrCreate();

        $indexation->save();
    }

}