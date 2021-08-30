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
use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;

class ElasticProduct extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'elasticproduct';

    /**
     * @param ConnectionInterface $con
     */
    public function postActivation(ConnectionInterface $con = null)
    {
        if (!$this->getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);
        }
    }

    public static function getElasticSearchClient()
    {
        $client = ClientBuilder::create()
            ->setHosts([self::getConfigValue('host', 'http://localhost:9200')])
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
