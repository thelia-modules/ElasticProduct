<?php


namespace ElasticProduct\Command;

use ElasticProduct\ElasticProduct;
use ElasticProduct\Model\ElasticProductIndexationQueueQuery;
use ElasticProduct\Service\IndexationService;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Action\Image;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Attribute;
use Thelia\Model\AttributeAv;
use Thelia\Model\AttributeAvI18n;
use Thelia\Model\AttributeAvQuery;
use Thelia\Model\AttributeCombination;
use Thelia\Model\AttributeI18n;
use Thelia\Model\AttributeQuery;
use Thelia\Model\Base\BrandI18n;
use Thelia\Model\Base\BrandQuery;
use Thelia\Model\Base\FeatureAvI18n;
use Thelia\Model\Base\FeatureI18n;
use Thelia\Model\Base\FeatureProduct;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Brand;
use Thelia\Model\Category;
use Thelia\Model\CategoryI18n;
use Thelia\Model\CategoryQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Feature;
use Thelia\Model\FeatureAv;
use Thelia\Model\FeatureAvQuery;
use Thelia\Model\FeatureProductQuery;
use Thelia\Model\FeatureQuery;
use Thelia\Model\Product;
use Thelia\Model\ProductCategory;
use Thelia\Model\ProductI18n;
use Thelia\Model\ProductImage;
use Thelia\Model\ProductImageQuery;
use Thelia\Model\ProductPrice;
use Thelia\Model\ProductPriceQuery;
use Thelia\Model\ProductQuery;
use Thelia\Model\ProductSaleElements;

class IndexQueueCommand extends ContainerAwareCommand
{
    /** @var IndexationService */
    protected $indexationService;

    protected function configure()
    {
        $this
            ->setName("elasticproduct:index:queue")
            ->setDescription("Index catalog");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var IndexationService $indexationService */
        $this->indexationService = $this->getContainer()->get('elastic_product.indexation.service');
        $this->initRequest();

        $indexationQueue = ElasticProductIndexationQueueQuery::create()
            ->find();

        foreach ($indexationQueue as $indexationItem) {
            switch ($indexationItem->getItemType()) {
                case Product::class:
                    $this->indexFromProductId($indexationItem->getItemId());
                    break;
                case ProductI18n::class:
                    $this->indexFromProductId($indexationItem->getItemId());
                    break;
                case ProductImage::class:
                    $this->indexFromProductImageId($indexationItem->getItemId());
                    break;
                case Feature::class:
                    $this->indexFromFeatureId($indexationItem->getItemId());
                    break;
                case FeatureI18n::class:
                    $this->indexFromFeatureId($indexationItem->getItemId());
                    break;
                case FeatureAv::class:
                    $this->indexFromFeatureAvId($indexationItem->getItemId());
                    break;
                case FeatureAvI18n::class:
                    $this->indexFromFeatureAvId($indexationItem->getItemId());
                    break;
                case FeatureProduct::class:
                    $this->indexFromFeatureAvId($indexationItem->getItemId());
                    break;
                case Attribute::class:
                    $this->indexFromAttributeId($indexationItem->getItemId());
                    break;
                case AttributeI18n::class:
                    $this->indexFromAttributeId($indexationItem->getItemId());
                    break;
                case AttributeAv::class:
                    $this->indexFromAttributeAvId($indexationItem->getItemId());
                    break;
                case AttributeAvI18n::class:
                    $this->indexFromAttributeAvId($indexationItem->getItemId());
                    break;
                case ProductSaleElements::class:
                    $this->indexFromProductSaleElementsId($indexationItem->getItemId());
                    break;
                case Brand::class:
                    $this->indexFromBrandId($indexationItem->getItemId());
                    break;
                case BrandI18n::class:
                    $this->indexFromBrandId($indexationItem->getItemId());
                    break;
                case Category::class:
                    $this->indexFromCategoryId($indexationItem->getItemId());
                    break;
                case CategoryI18n::class:
                    $this->indexFromCategoryId($indexationItem->getItemId());
                    break;
                case ProductPrice::class:
                    $this->indexFromProductSaleElementsId($indexationItem->getItemId());
                    break;
                case ProductCategory::class:
                    $this->indexFromProductId($indexationItem->getItemId());
                    break;
                case AttributeCombination::class:
                    $this->indexFromProductSaleElementsId($indexationItem->getItemId());
                    break;
            }

            $indexationItem->delete();
        }
    }

    protected function indexFromProductId($productId)
    {
        $product = ProductQuery::create()
            ->findOneById($productId);

        if (null === $product) {
            return;
        }

        $this->indexationService->indexProduct($product);
    }

    protected function indexFromProductImageId($productImageId)
    {
        $productImage = ProductImageQuery::create()
            ->findOneById($productImageId);

        if (null === $productImage) {
            return;
        }

        $this->indexFromProductId($productImage->getProductId());
    }

    protected function indexFromFeatureId($featureId)
    {
        $feature = FeatureQuery::create()
            ->findOneById($featureId);

        if (null === $feature) {
            return;
        }

        foreach ($feature->getFeatureProducts() as $featureProduct) {
            $this->indexFromProductId($featureProduct->getProductId());
        }
    }

    protected function indexFromFeatureProductId($featureProductId)
    {
        $featureProduct = FeatureProductQuery::create()
            ->findOneById($featureProductId);

        if (null === $featureProduct) {
            return;
        }

        $this->indexFromProductId($featureProduct->getProductId());
    }

    protected function indexFromFeatureAvId($featureAvId)
    {
        $featureAv = FeatureAvQuery::create()
            ->findOneById($featureAvId);

        if (null === $featureAv) {
            return;
        }

        $this->indexFromFeatureId($featureAv->getFeatureId());
    }

    protected function indexFromProductSaleElementsId($productSaleElementsId)
    {
        $productSaleElements = ProductSaleElementsQuery::create()
            ->findOneById($productSaleElementsId);

        if (null === $productSaleElements) {
            return;
        }

        $this->indexFromProductId($productSaleElements->getProductId());
    }

    protected function indexFromAttributeId($attributeId)
    {
        $attribute = AttributeQuery::create()
            ->findOneById($attributeId);

        if (null === $attribute) {
            return;
        }

        $attributeCombinations = $attribute->getAttributeCombinations();

        foreach ($attributeCombinations as $attributeCombination) {
            $this->indexFromProductSaleElementsId($attributeCombination->getProductSaleElementsId());
        }
    }

    protected function indexFromAttributeAvId($attributeAvId)
    {
        $attributeAv = AttributeAvQuery::create()
            ->findOneById($attributeAvId);

        if (null === $attributeAv) {
            return;
        }

        $this->indexFromAttributeId($attributeAv->getAttributeId());
    }

    protected function indexFromBrandId($brandId)
    {
        $brand = BrandQuery::create()
            ->findOneById($brandId);

        if (null === $brand) {
            return;
        }

        foreach ($brand->getProducts() as $product) {
            $this->indexFromProductId($product->getId());
        }
    }

    protected function indexFromCategoryId($categoryId)
    {
        $category = CategoryQuery::create()
            ->findOneById($categoryId);

        if (null === $category) {
            return;
        }

        foreach ($category->getProducts() as $product) {
            $this->indexFromProductId($product->getId());
        }
    }
}