<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace ElasticProduct;

use Elasticsearch\ClientBuilder;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Module\BaseModule;

class ElasticProduct extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'elasticproduct';

    const SEARCHABLE_FEATURE_CONFIG_KEY = "searchable_features";
    const SEARCHABLE_ATTRIBUTE_CONFIG_KEY = "searchable_attributes";

    public static function getElasticSearchClient()
    {
        $hosts = [
            [
                'host' => self::getConfigValue('host', 'localhost'),
                'port' => self::getConfigValue('port', '9200'),
                'user' => self::getConfigValue('username'),
                'pass' => self::getConfigValue('password')
            ]
        ];

        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();

        return $client;
    }

    public function getHooks()
    {
        return [
            [
                "type" => TemplateDefinition::FRONT_OFFICE,
                "code" => "elastic_product.container",
                "title" => [
                    "en_US" => "ElasticProduct container hook",
                    "fr_FR" => "ElasticProduct container hook
                    ",
                ],
                "block" => false,
                "active" => true
            ],
        ];
    }

    public static function getIndexName()
    {
        return ElasticProduct::getConfigValue('index_prefix').'products';
    }
}
