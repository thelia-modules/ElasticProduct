<?php

namespace ElasticProduct\Event\SearchEvent;

use Thelia\Core\Event\ActionEvent;
use Thelia\Core\HttpFoundation\Request;

class SearchFilterEvent extends ActionEvent
{
    CONST GET_SEARCH_FILTER_EVENT = 'get_search_filter_event';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $filters;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     *
     * @return SearchFilterEvent
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     *
     * @return SearchFilterEvent
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function addFilter($value)
    {
        $this->filters[] = $value;
        return $this;
    }
}