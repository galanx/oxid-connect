<?php
/**
 * This file is part of a marmalade GmbH project
 * It is not Open Source and may not be redistributed.
 * For contact information please visit http://www.marmalade.de
 * Version:    1.0
 * Author:     Jens Richter <richter@marmalade.de>
 * Author URI: http://www.marmalade.de
 */

use Makaira\Connect\SearchHandler;
use Makaira\Constraints;
use Makaira\Query;

/**
 * Class makaira_connect_autosuggester
 */
class makaira_connect_autosuggester
{
    /**
     * @var oxLang
     */
    private $oxLang;

    public function __construct(oxLang $oxLang)
    {
        $this->oxLang = $oxLang;
    }

    /**
     * Search for search term and build json response
     *
     * @param string $searchPhrase
     *
     * @return array
     */
    public function search($searchPhrase = "")
    {
        $query                     = new Query();
        $query->enableAggregations = false;
        $query->isSearch           = true;
        $query->searchPhrase       = $searchPhrase;
        $query->count              = 7;
        $query->fields             = ['OXID', 'OXTITLE', 'OXVARSELECT'];

        $oxConfig = oxRegistry::getConfig();

        $query->constraints = array_filter(
            [
                Constraints::SHOP      => $oxConfig->getShopId(),
                Constraints::LANGUAGE  => oxRegistry::getLang()->getLanguageAbbr(),
                Constraints::USE_STOCK => $oxConfig->getShopConfVar('blUseStock'),
            ]
        );

        $dic = oxRegistry::get('yamm_dic');
        /** @var SearchHandler $searchHandler */
        $searchHandler = $dic['makaira.connect.searchhandler'];

        $result = $searchHandler->search($query);

        // get product results
        $aProducts = [];
        foreach ($result['product']->items as $document) {
            $aProducts[] = $this->prepareProductItem($document);
        }
        // filter out empty values
        $aProducts = array_filter($aProducts);

        // get category results
        $aCategories = [];
        if ($result['category']) {
            foreach ($result['category']->items as $document) {
                $aCategories[] = $this->prepareCategoryItem($document);
            }
        }
        // filter out empty values
        $aCategories = array_filter($aCategories);

        // get manufacturer results
        $aManufacturers = [];
        if ($result['manufacturer']) {
            foreach ($result['manufacturer']->items as $document) {
                $aManufacturers[] = $this->prepareManufacturerItem($document);
            }
        }
        // filter out empty values
        $aManufacturers = array_filter($aManufacturers);

        // get searchable links results
        $aLinks = [];
        if ($result['links']) {
            foreach ($result['links']->items as $document) {
                $aLinks[] = $this->prepareLinkItem($document);
            }
        }
        // filter out empty values
        $aLinks = array_filter($aLinks);

        return [
            'count'         => count($aProducts),
            'products'      => $aProducts,
            'productCount'  => $result['product']->total,
            'categories'    => $aCategories,
            'manufacturers' => $aManufacturers,
            'links'         => $aLinks,
        ];
    }

    /**
     * Prepare the data based on an oxArticleObject
     *
     * @param object $doc
     *
     * @return array
     */
    protected function prepareProductItem($doc)
    {
        if (empty($doc->fields['oxtitle'])) {
            return [];
        }

        $product = oxNew('oxarticle');

        if (!$product->load($doc->id)) {
            return [];
        }

        $title = $doc->fields['oxtitle'];
        if (!empty($doc->fields['oxvarselect'])) {
            $title .= ' | ' . $doc->fields['oxvarselect'];
        }
        $aItem['label']     = $title;
        $aItem['value']     = $title;
        $aItem['link']      = $product->getMainLink();
        $aItem['image']     = $product->getIconUrl(1);
        $aItem['thumbnail'] = $product->getThumbnailUrl();
        $aItem['price']     = $this->preparePrice($product->getPrice());
        $aItem['uvp']       = $this->preparePrice($product->getTPrice());
        $aItem['type']      = 'product';
        $aItem['category']  = $this->translate("MAKAIRA_CONNECT_AUTOSUGGEST_CATEGORY_PRODUCTS");

        return $aItem;
    }

    protected function prepareCategoryItem($doc)
    {
        if (empty($doc->fields['oxtitle'])) {
            return [];
        }

        $category = oxNew('oxcategory');

        if (!$category->load($doc->id)) {
            return [];
        }

        $aItem['label'] = $doc->fields['oxtitle'];
        $aItem['link']  = $category->getLink();

        return $aItem;
    }

    protected function prepareManufacturerItem($doc)
    {
        if (empty($doc->fields['oxtitle'])) {
            return [];
        }

        $manufacturer = oxNew('oxmanufacturer');

        if (!$manufacturer->load($doc->id)) {
            return [];
        }

        $aItem['label'] = $doc->fields['oxtitle'];
        $aItem['link']  = $manufacturer->getLink();
        $aItem['image'] = $manufacturer->getIconUrl();

        return $aItem;
    }

    protected function prepareLinkItem($doc)
    {
        if (empty($doc->fields['title'])) {
            return [];
        }

        $aItem['label'] = $doc->fields['title'];
        $aItem['link']  = $doc->fields['url'];

        return $aItem;
    }

    /**
     * Helper method to format prices for auto-suggest
     *
     * @param $price
     *
     * @return array
     */
    protected function preparePrice($price)
    {
        if (!$price) {
            return array('brutto' => 0, 'netto' => 0);
        } else {
            return array(
                'brutto' => number_format($price->getBruttoPrice(), 2, ',', ''),
                'netto'  => number_format($price->getNettoPrice(), 2, ',', '')
            );
        }
    }

    /**
     * Getter method for shop translations
     *
     * @param string $string
     *
     * @return string
     */
    protected function translate($string)
    {
        return $this->oxLang->translateString($string);
    }
}
