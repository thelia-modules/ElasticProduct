<?php


namespace ElasticProduct\Controller;


use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Model\ProductQuery;

class IndexationController extends BaseAdminController
{
    public function createIndex()
    {
        if (null !== $response = $this->checkAuth(array(), 'ElasticProduct', AccessManager::UPDATE)) {
            return $response;
        }

        $indexationService = $this->getContainer()->get('elastic_product.indexation.service');
        $indexationService->createIndex();

        $productCount = ProductQuery::create()->count();

        return JsonResponse::create(['productCount' => $productCount]);
    }

    public function indexProducts($productPerPage, $page)
    {
        if (null !== $response = $this->checkAuth(array(), 'ElasticProduct', AccessManager::UPDATE)) {
            return $response;
        }

        $indexationService = $this->getContainer()->get('elastic_product.indexation.service');
        $indexationService->indexProducts($productPerPage, $page);

        return JsonResponse::create();
    }
}