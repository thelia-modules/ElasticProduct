import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import axios from 'axios';
import InputRange from 'react-input-range';
import InfiniteScroll from 'react-infinite-scroller';

class App extends Component {
    constructor(props){
        super(props);

        this.handleClick = this.handleClick.bind(this);
        document.addEventListener('click', this.handleClick, false);

        this.state = {
            loading: true,
            search: "",
            resultsVisible: false,
            categoryFilter: null,
            brandFilter: null,
            featureFilters: [],
            attributeFilters: [],
            priceFilters: {
                min:null,
                max:null
            },
            timeoutId: null,
            categories: [],
            brands: [],
            features: [],
            attributes: [],
            prices: {
                min:null,
                max:null
            },
            products: [],
            loadedProducts: [],
            hasMore: true
        }
    }

    handleClick(e) {
        // ignore clicks on the component itself
        if (typeof this.node !== "undefined" && this.node.contains(e.target)) {
            return;
        }

        // If outside hide results
        this.setState({resultsVisible: false});
    }

    handleKeyUp = (event) => {
        const { timeoutId } = this.state;
        if (null !== timeoutId) {
            clearTimeout(timeoutId);
        }

        this.setState({search:event.target.value});
        if (event.target.value.length > 3) {
            const timeoutId = setTimeout(() => {
                this.resetFilters(this.search);
            }, 300);
            this.setState({timeoutId})
        } else {
            this.setState({
                products:[],
                loadedProducts:[],
                categories:[],
                brands:[],
                features: [],
                attributes: [],
                prices: {
                    min:null,
                    max:null
                }
            });
        }
    }

    search = (updatePriceFilter = true) => {
        const {search, categoryFilter, brandFilter, featureFilters, attributeFilters, priceFilters} = this.state;

        let query = '/esearch?q='+search;

        if (categoryFilter) {
            query += '&category='+categoryFilter
        }

        if (brandFilter) {
            query += '&brand='+brandFilter
        }

        featureFilters.forEach((featureFilter => {
            query += '&features['+featureFilter.feature+']='+featureFilter.value;
        }));

        attributeFilters.forEach((attributeFilter => {
            query += '&attributes['+attributeFilter.feature+']='+attributeFilter.value;
        }));

        if (!updatePriceFilter) {
            if (priceFilters.min) {
                query += '&min_price='+priceFilters.min;
            }

            if (priceFilters.max) {
                query += '&max_price='+priceFilters.max;
            }
        }

        this.setState({loading:true}, () => {
            axios.get(query)
                .then((response) => {
                    const { priceFilters } = this.state;
                    const {products, categories, brands, features, attributes, prices} = response.data;
                    this.setState({
                        loading:false,
                        products,
                        loadedProducts: products.slice(0, 16),
                        categories,
                        brands,
                        features,
                        attributes,
                        resultsVisible: true
                    });

                    if (updatePriceFilter) {
                        console.log("Update price");
                        console.log("Prices", prices);
                        const roundedPrices = {min:Math.floor(prices.min), max:Math.ceil(prices.max)};
                        this.setState({prices:roundedPrices, priceFilters:roundedPrices});
                    }
                })
        });
    }

    handleFeatureClick = (feature, value) => {
        this.setState(prevState => ({
            featureFilters: [...prevState.featureFilters, {feature, value}]
        }), this.search);
    };

    unsetFeatureFilter = (featureFilterIndex) => {
        this.setState(prevState => ({
            featureFilters: prevState.featureFilters.filter((featureFilter, key) => key !== featureFilterIndex)
        }), this.search);
    };

    handleAttributeClick = (attribute, value) => {
        this.setState(prevState => ({
            attributeFilters: [...prevState.attributeFilters, {attribute, value}]
        }), this.search);
    };

    unsetAttributeFilter = (attributeFilterIndex) => {
        this.setState(prevState => ({
            attributeFilters: prevState.attributeFilters.filter((attributeFilter, key) => key !== attributeFilterIndex)
        }), this.search);
    };

    resetFilters(callback) {
        this.setState({
            categoryFilter: null,
            brandFilter: null,
            featureFilters: [],
            attributeFilters: [],
            priceFilters: {
                min:null,
                max:null
            },
        }, callback);
    }

    setCategoryFilter = (categoryFilter) => {
        this.setState({categoryFilter}, this.search);
    };

    setBrandFilter = (brandFilter) => {
        this.setState({brandFilter}, this.search);
    };

    loadMoreProducts = () => {
        const { products, loadedProducts } = this.state;
        const start = loadedProducts.length;
        const newItems = products.slice(start, (start+16));

        this.setState({
            loadedProducts:[...loadedProducts, ...newItems]
        });
    }

    render() {
        const {
            loading,
            resultsVisible,
            search,
            products, loadedProducts,
            priceFilters, prices,
            categories, categoryFilter,
            brands, brandFilter,
            features, featureFilters,
            attributes, attributeFilters
        } = this.state;
        return (
            <div className="elastic_product" ref={node => this.node = node}>
                <input type="text" placeholder={elasticProductIntl.search} onClick={() => this.setState({resultsVisible: true})} onKeyUp={(event)=>this.handleKeyUp(event)}/>
                {resultsVisible && search ?
                    <div className="dropdown container col-md-8">
                        <div className="row justify-content-center">
                            <div className="results col-md-9" ref={(ref) => this.scrollParentRef = ref}>
                                {loading ? <div className="elastic_loader"></div> : null}
                                {search && !loading ? <div className="count text-center" >{products.length} {elasticProductIntl.resultsFound}</div> : null}
                                <div className="product_results">
                                    {products.length > 0 ?
                                        <InfiniteScroll
                                            pageStart={0}
                                            loadMore={this.loadMoreProducts}
                                            hasMore={loadedProducts.length < products.length}
                                            useWindow={false}
                                            threshold={100}
                                            loader={
                                                <div className="loader" key={0}>
                                                    Loading ...
                                                </div>
                                            }
                                        >
                                            {loadedProducts.map((product) => <Product key={product.id} {...product} />)}
                                        </InfiniteScroll>
                                        : null}
                                </div>
                            </div>
                            {products.length  ?
                                <div className="filters col-md-3 row">

                                    <div className="text-center">
                                        {elasticProductIntl.filters}
                                    </div>
                                    <div className="filter_list filter_price">
                                        <p className="title">{elasticProductIntl.prices}</p>
                                        {null !== priceFilters.min ?
                                            <InputRange minValue={prices.min} maxValue={prices.max} value={priceFilters} onChange={(value) => this.setState({priceFilters:value})} onChangeComplete={() => this.search(false)} />
                                            : null }
                                    </div>
                                    <FilterList title={elasticProductIntl.categories} items={categories} activeFilter={categoryFilter} setFilter={this.setCategoryFilter} unsetFilter={() => this.setCategoryFilter(null)}/>
                                    <FilterList title={elasticProductIntl.brands} items={brands} activeFilter={brandFilter} setFilter={this.setBrandFilter} unsetFilter={() => this.setBrandFilter(null)}/>
                                    {features.length > 0
                                        ? features.map((feature) =>
                                            <Feature  key={feature.name} {...feature} featureFilters={featureFilters} handleClick={this.handleFeatureClick} unsetFeatureFilter={this.unsetFeatureFilter} />
                                        )
                                        : null
                                    }
                                    {attributes.length > 0
                                        ? attributes.map((attribute) =>
                                            <Attribute key={attribute.name} {...attribute} attributeFilters={attributeFilters} handleClick={this.handleAttributeClick} unsetAttributeFilter={this.unsetAttributeFilter} />
                                        )
                                        : null
                                    }
                                </div>
                            : null}
                        </div>
                    </div>
                : null}
            </div>
        );
    }
}

class Product extends Component {
    render() {
        const {url, id, image, title, price, currency_symbol, original_price} = this.props;

        return (
            <a className="product text-center" href={"/"+url} key={id}>
                {image ? <img height="80" src={image.image_url} alt={title} /> : null}
                <h5>{title}</h5>
                <p>{price} {currency_symbol}</p>
                {original_price ? <p><del>{original_price} {currency_symbol}</del></p> : null}
            </a>
        );
    }
}

class Filter extends Component {
    render() {
        return (
            <div className="filter" onClick={(e) => {this.props.handleClick(this.props.name)}} key={this.props.name}>
                <h5>{this.props.name} {this.props.withCount !== false ? <span>({this.props.count})</span> : null}</h5>
            </div>
        );
    }
}

class FilterList extends Component {
    constructor(props){
        super(props);

        this.state = {
            size: 5
        }
    }

    render() {

        if (this.props.activeFilter) {
            return(
                <div className="filter_list col-md-12">
                    <p className="title">{this.props.title}</p>
                    <p className="filter active" onClick={this.props.unsetFilter}>{this.props.activeFilter} <span>X</span></p>
                </div>
            );
        }

        if (this.props.items.length && !this.props.activeFilter) {
            return (
                <div className="filter_list col-md-12">
                    <p className="title">{this.props.title}</p>
                    {this.renderItems()}
                    {this.props.items.length > this.state.size ? <a onClick={() => this.setState(prevState => ({size: prevState.size + 5}))}>Voir plus</a> : null}
                </div>
            );
        }

        return null;
    }

    renderItems() {
        return this.props.items.slice(0, this.state.size).map(
            (filter) =>
                <Filter key={filter.name} {...filter} handleClick={(name) => this.props.setFilter(name)} withCount={this.props.withCount} />
        )
    }
}

class Feature extends Component {
    render() {
        const featureFilterIndex = this.props.featureFilters.findIndex((element) => element.feature === this.props.name);
        const activeFilter = featureFilterIndex !== -1 ? this.props.featureFilters[featureFilterIndex].value : null;

        return <FilterList title={this.props.name} items={this.props.values} activeFilter={activeFilter} setFilter={(valueName) => this.props.handleClick(this.props.name, valueName)} unsetFilter={() => this.props.unsetFeatureFilter(featureFilterIndex)}/>
    }
}

class Attribute extends Component {
    render() {
        const attributeFilterIndex = this.props.attributeFilters.findIndex((element) => element.attribute === this.props.name);
        const activeFilter = attributeFilterIndex !== -1 ? this.props.attributeFilters[attributeFilterIndex].value : null;

        return <FilterList title={this.props.name} items={this.props.values} activeFilter={activeFilter} setFilter={(valueName) => this.props.handleClick(this.props.name, valueName)} unsetFilter={() => this.props.unsetAttributeFilter(attributeFilterIndex)} withCount={false}/>
    }
}

ReactDOM.render(
    <App/>,
    document.getElementById('esearch_container')
);