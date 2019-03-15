<?php


namespace ElasticProduct\Controller;


use ElasticProduct\ElasticProduct;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Country;
use Thelia\Model\Currency;
use Thelia\Tools\NumberFormat;

class SearchController extends BaseFrontController
{
    public function searchAction(Request $request)
    {
        $search = $request->get('q');
        $category = $request->get('category');
        $brand = $request->get('brand');
        $features = $request->get('features', []);
        $attributes = $request->get('attributes', []);
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');

        $searchWords = preg_split('/\s+/', trim($search));

        $client = ElasticProduct::getElasticSearchClient();
        $locale = $this->getSession()->getLang()->getLocale();
        $currency = $this->getSession()->getCurrency();

        /** @var Country $country */
        $country = $this->container->get('thelia.taxEngine')->getDeliveryCountry();

        $filters = [];

        $filters[] =                         [
            "term" => [
                "product.is_visible" => true
            ]
        ];

        if ($category) {
            $filters[] = [
                "match" => [
                    "categories.i18ns.$locale.title.raw" => $category
                ]
            ];
        }

        if ($brand) {
            $filters[] = [
                "match" => [
                    "brand.i18ns.$locale.title.raw" => $brand
                ]
            ];
        }

        foreach ($features as $feature => $value) {
            $filters[] = [
                "nested" => [
                    "path" => "features",
                    "query" => [
                        "bool" => [
                            'filter' => [
                                [
                                    "match" => [
                                        "features.i18ns.$locale.title.raw" => $feature
                                    ]
                                ],
                                [
                                    "match" => [
                                        "features.values.i18ns.$locale.title.raw" => $value
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        foreach ($attributes as $attribute => $value) {
            $filters[] = [
                "nested" => [
                    "path" => "product_sale_elements.attributes",
                    "query" => [
                        "bool" => [
                            "filter" => [
                                [
                                    "match" => [
                                        "product_sale_elements.attributes.i18ns.$locale.title.raw" => $attribute
                                    ]
                                ],
                                [
                                    "match" => [
                                        "product_sale_elements.attributes.values.i18ns.$locale.title.raw" => $value
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $ranges = [];
        if ($minPrice) {
            $ranges['gte'] = $minPrice;
        }
        if ($maxPrice) {
            $ranges['lte'] = $maxPrice;
        }

        if (!empty($ranges)) {
            $filters[] = [
                "nested" => [
                    "path" => "product_sale_elements.prices",
                    "query" => [
                        "bool" => [
                            "filter" => [
                                [
                                    "term" => [
                                        "product_sale_elements.prices.countries" => $country->getId()
                                    ]
                                ],
                                [
                                    "range" => [
                                        "product_sale_elements.prices.".$currency->getCode().".price" => $ranges
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $textSearches = [];
        $refSearch = [];
        foreach ($searchWords as $searchWord) {
            $textSearches[] = [
                "multi_match" => [
                    "query" => $searchWord,
                    "fields" => [
                        "product.i18ns.".$locale.".title.analyzed^6",
                        "*.i18ns.".$locale.".*.analyzed",
                    ]
                ]
            ];

            $refSearch[] = [
                "match" => [
                    "product.ref" => $searchWord
                ]
            ];
        }

        $body = [
            "size" => 100,
            "query" => [
                "bool" => [
                    "filter" => $filters,
                    "must" => [
                        "bool" => [
                            "should" => [
                                [
                                    "bool" => [
                                        "must" => $textSearches
                                    ]
                                ],
                                [
                                    "bool" => [
                                        "should" => $refSearch
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "highlight" => [
                "boundary_scanner" => "word",
                "order" => "score",
                "pre_tags" => [""],
                "post_tags" => [""],
                "fields" => ["*" => ["force_source"=>false]]
            ],
            "aggs" => [
                "by_categories" => [
                    "terms" => [
                        "field" => "categories.i18ns.$locale.title.raw",
                        "size" => 100
                    ]
                ],
                "by_brands" => [
                    "terms" => [
                        "field" => "brand.i18ns.$locale.title.raw",
                        "size" => 100
                    ]
                ],
                "by_features_nested" => [
                    "nested" => [
                        "path" => "features"
                    ],
                    "aggs" => [
                        "by_features_searchable" => [
                            "filter" => [ "term"=> ["features.searchable" => true ]],
                            "aggs" => [
                                "by_features" => [
                                    "terms" => [
                                        "field" => "features.i18ns.$locale.title.raw",
                                        "size" => 100
                                    ],
                                    "aggs" => [
                                        "by_features_values" => [
                                            "terms" => [
                                                "field" => "features.values.i18ns.$locale.title.raw",
                                                "size" => 100
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "by_product_sale_elements_nested" => [
                    "nested" => [
                        "path" => "product_sale_elements"
                    ],
                    "aggs" => [
                        "by_attributes_nested" => [
                            "nested" => [
                                "path" => "product_sale_elements.attributes"
                            ],
                            "aggs" => [
                                "by_attributes_searchable" => [
                                    "filter" => [ "term"=> ["product_sale_elements.attributes.searchable" => true ]],
                                    "aggs" => [
                                        "by_attributes" => [
                                            "terms" => [
                                                "field" => "product_sale_elements.attributes.i18ns.$locale.title.raw",
                                                "size" => 100
                                            ],
                                            "aggs" => [
                                                "by_attributes_values" => [
                                                    "terms" => [
                                                        "field" => "product_sale_elements.attributes.values.i18ns.$locale.title.raw",
                                                        "size" => 100
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "by_prices_nested" => [
                            "nested" => [
                                "path" => "product_sale_elements.prices"
                            ],
                            "aggs" => [
                                "by_current_country" => [
                                    "filter" => ["term" => ["product_sale_elements.prices.countries" => $country->getId()]],
                                    "aggs" => [
                                        "min_price" => [
                                            "min" => [
                                                "field" => "product_sale_elements.prices.EUR.price"
                                            ]
                                        ],
                                        "max_price" => [
                                            "max" => [
                                                "field" => "product_sale_elements.prices.EUR.price"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $params = [
            'index' => ElasticProduct::getIndexName(),
            'type' => ElasticProduct::getIndexName(),
            'body' => $body
        ];

        try {
            $response = $client->search($params);

            $results = $response['hits']['hits'];

            $products = [];

            foreach ($results as $result) {
                $source = $result['_source'];
                $productId = $source['product']['id'];
                if (!isset($products[$productId])) {
                    $i18ns['title'] = $source['product']['i18ns'][$locale]['title'];
                    $productData = [
                        'id' => $productId,
                        'ref' => $source['product']['ref'],
                        'url' => $source['product']['urls'][$locale],
                        'image' => null
                    ];
                    $prices = $this->getDefaultProductPrices($source, $country->getId(), $currency);

                    if (!empty($source['product']['images'])) {
                        $productData['image'] = $source['product']['images'][0];
                    }

                    $products[$productId] = array_merge($productData, $i18ns, $prices);
                }
            }

            $products = array_values($products);

            $aggs = $response['aggregations'];

            $categories = [];

            foreach ($aggs['by_categories']['buckets'] as $category) {
                $categories[] = ['name' => $category['key'], 'count' => $category['doc_count']];
            }

            $brands = [];

            foreach ($aggs['by_brands']['buckets'] as $brand) {
                $brands[] = ['name' => $brand['key'], 'count' => $brand['doc_count']];
            }

            $features = [];
            foreach ($aggs['by_features_nested']['by_features_searchable']['by_features']['buckets'] as $featureBucket) {
                $feature = ["name" => $featureBucket['key']];
                foreach ($featureBucket['by_features_values']['buckets'] as $featureValueBucket) {
                    $feature['values'][] = ['name' => $featureValueBucket['key'], 'count' => $featureValueBucket['doc_count']];
                }
                $features[] = $feature;
            }

            $attributes = [];
            foreach ($aggs['by_product_sale_elements_nested']['by_attributes_nested']['by_attributes_searchable']['by_attributes']['buckets'] as $attributeBucket) {
                $attribute = ["name" => $attributeBucket['key']];
                foreach ($attributeBucket['by_attributes_values']['buckets'] as $attributeValueBucket) {
                    $attribute['values'][] = ['name' => $attributeValueBucket['key'], 'count' => $attributeValueBucket['doc_count']];
                }
                $attributes[] = $attribute;
            }

            $prices = [
                "min" => round($aggs['by_product_sale_elements_nested']['by_prices_nested']['by_current_country']['min_price']['value'], 2),
                "max" => round($aggs['by_product_sale_elements_nested']['by_prices_nested']['by_current_country']['max_price']['value'], 2)
            ];

            return new JsonResponse(compact('products', 'categories', 'brands', 'features', 'attributes', 'prices'));
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => $exception->getMessage(), 'products' => []]);
        }

    }

    protected function getDefaultProductPrices($source, $countryId, Currency $currency) {
        $defaultPse = $this->getDefaultPseFromSource($source);
        $countryPrice = null;

        foreach ($defaultPse['prices'] as $price) {
            if (in_array($countryId, $price['countries'])) {
                $countryPrice = $price;
                continue;
            }
        }

        if (!$countryPrice) {
            return null;
        }

        $currencyPrice = $countryPrice[$currency->getCode()];
        $numberFormat = NumberFormat::getInstance($this->getRequest());

        return [
            'price' => $numberFormat->format($currencyPrice['price']),
            'original_price' => $currencyPrice['original_price'] ? $numberFormat->format($currencyPrice['original_price']) : null,
            "currency_symbol" => $currency->getSymbol()
        ];
    }

    protected function getDefaultPseFromSource($source) {
        foreach ($source["product_sale_elements"] as $productSaleElement) {
            if ($productSaleElement['is_default']) {
                return $productSaleElement;
            }
        }

    }

}