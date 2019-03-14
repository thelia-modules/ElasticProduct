<?php

namespace ElasticProduct\Controller;


use ElasticProduct\ElasticProduct;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\HttpFoundation\Response;

class ConfigurationController extends BaseAdminController
{
    /**
     * @param array $params
     * @return Response
     */
    public function viewAction($params = array())
    {
        if (null !== $response = $this->checkAuth(array(), 'ElasticProduct', AccessManager::VIEW)) {
            return $response;
        }

        $unsearchableFeatures = explode(',', ElasticProduct::getConfigValue("unsearchable_features"));
        $unsearchableAttributes = explode(',', ElasticProduct::getConfigValue("unsearchable_attributes"));

        $params['unsearchableFeatures'] = $unsearchableFeatures;
        $params['unsearchableAttributes'] = $unsearchableAttributes;

        return $this->render("elastic-product/configuration", $params);
    }

    public function saveAction()
    {
        if (null !== $response = $this->checkAuth(array(), 'ElasticProduct', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm('elastic_product_configuration');

        try {
            $form = $this->validateForm($configurationForm, "POST");

            // Get the form field values
            $data = $form->getData();

            ElasticProduct::setConfigValue('host', $data['host']);
            ElasticProduct::setConfigValue('port', $data['port']);
            ElasticProduct::setConfigValue('username', $data['username']);
            ElasticProduct::setConfigValue('password', $data['password']);
            ElasticProduct::setConfigValue('index_prefix', $data['index_prefix']);

            return $this->generateSuccessRedirect($configurationForm);
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans("ElasticProduct configuration"),
                $e->getMessage(),
                $configurationForm
            );

            return $this->viewAction();
        }
    }

    public function toggleSearchableAction($type, $id)
    {
        if (null !== $response = $this->checkAuth(array(), 'ElasticProduct', AccessManager::UPDATE)) {
            return $response;
        }

        $configKey = "unsearchable_".$type;
        $isSearchable = ($this->getRequest()->get('isSearchable') === "true");

        $unsearchableString = ElasticProduct::getConfigValue($configKey, "");
        $unsearchableArray = explode(',', $unsearchableString);

        if ($isSearchable && array_search($id, $unsearchableArray) !== false) {
            unset($unsearchableArray[array_search($id, $unsearchableArray)]);
        }

        if (!$isSearchable && array_search($id, $unsearchableArray) === false) {
            $unsearchableArray[] = $id;
        }

        ElasticProduct::setConfigValue($configKey, implode(',', array_filter($unsearchableArray)));

        return new JsonResponse();
    }
}