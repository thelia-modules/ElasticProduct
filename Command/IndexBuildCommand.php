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

        $client = ElasticProduct::getElasticSearchClient();

        $indexName = ElasticProduct::getIndexName();

        $index = [
            'index' => $indexName
        ];

        if($client->indices()->exists($index)) {
            $client->indices()->delete($index);
        }

        $output->writeln($indexName);

        $index['body'] = [
            'settings' => [
                'analysis' => json_decode(file_get_contents(__DIR__.DS.'../Config/analysis.json'), true),
            ],
            'mappings' => [
                $indexName => json_decode(file_get_contents(__DIR__.DS.'../Config/mapping.json'), true),
            ]
        ];

        $client->indices()->create($index);

        $maxPerPage = 2000;
        $productCount = ProductQuery::create()->count();
        $nbPage = ceil($productCount / $maxPerPage);
        $i = 0;
        for ($c = 0; $c < $nbPage; $c++) {

            $products = ProductQuery::create()->limit($maxPerPage)->offset($c*$maxPerPage)->find();

            /** @var Product $product */
            foreach ($products as $product) {
                $i++;

                $output->writeln($i);
                $output->writeln($product->getTitle());

                gc_enable();
                Propel::disableInstancePooling();

                $indexationService->indexProduct($product);

                $product->clearAllReferences(true);
                gc_collect_cycles();
                $output->writeln(memory_get_usage());
            }
        }
    }
}