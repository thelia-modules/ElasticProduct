<?php


namespace ElasticProduct\Command;


use ElasticProduct\ElasticProduct;
use ElasticProduct\Service\IndexationService;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Action\Image;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Product;
use Thelia\Model\ProductImage;
use Thelia\Model\ProductQuery;

class IndexBuildCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("elasticproduct:index:build")
            ->setDescription("Index catalog");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var IndexationService $indexationService */
        $indexationService = $this->getContainer()->get('elastic_product.indexation.service');
        $this->initRequest();

        $indexationService->createIndex();

        $productPerPage = 2000;
        $productCount = ProductQuery::create()->count();
        $nbPage = ceil($productCount / $productPerPage);
        for ($c = 0; $c < $nbPage; $c++) {
            $indexationService->indexProducts($productPerPage, $c);
            $output->writeln($c);
        }
    }
}