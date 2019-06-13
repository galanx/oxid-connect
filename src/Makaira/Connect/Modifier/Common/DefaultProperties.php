<?php

namespace Makaira\Connect\Modifier\Common;

use Makaira\Connect\Modifier;
use Makaira\Connect\Type;

//use Makaira\Connect\Type\Common\BaseProduct;
//use Makaira\Connect\Type\Product\Product;
//use Makaira\Connect\Type\Variant\Variant;
//use Makaira\Connect\Type\Category\Category;
//use Makaira\Connect\Type\Manufacturer\Manufacturer;

class DefaultProperties extends Modifier
{
    private $db;

    private $commonFieldMapping = [
        'id'         => 'OXID',
        'timestamp'  => 'OXTIMESTAMP',
        'url'        => '',
        'mak_active' => 'OXACTIVE',
    ];

    private $productFieldMapping = [
        'mak_title'              => 'OXTITLE',
        'mak_searchkeys'         => 'OXSEARCHKEYS',
        'mak_hidden'             => 'OXHIDDEN',
        'mak_sort'               => 'OXSORT',
        'mak_shortdesc'          => 'OXSHORTDESC',
        'mak_longdesc'           => 'OXLONGDESC',
        'mak_stock'              => 0,
        'mak_manufacturerId'     => 'OXMANUFACTURERID',
        'mak_manufacturer_title' => 'MARM_OXSEARCH_MANUFACTURERTITLE',
        'mak_price'              => 'OXPRICE',
        'mak_insert'             => 'OXINSERT',
        'mak_soldamount'         => 'OXSOLDAMOUNT',
        'mak_rating'             => 'OXRATING',

        'mak_ean'     => 'OXARTNUM',
        'mak_onstock' => true,
    ];

    private $categoryFieldMapping = [
        'mak_category_title' => 'OXTITLE',
        'mak_sort'           => 'OXSORT',
        'mak_shortdesc'      => 'OXDESC',
        'mak_longdesc'       => 'OXLONGDESC',
    ];

    private $manufacturerFieldMapping = [
        'mak_manufacturerId'     => 'OXID',
        'mak_manufacturer_title' => 'OXTITLE',
        'mak_shortdesc'          => 'OXSHORTDESC',
    ];

    public function __construct($database)
    {
        $this->db = $database;
    }

    public function apply(Type $entity)
    {
        $mappingFields = [];

        switch ($this->getDocType()) {
            case "product":
            case "variant":
                $mappingFields = $this->productFieldMapping;
                //$oxArticle = \oxRegistry::get('oxArticle');
                break;

            case "category":
                $mappingFields = $this->categoryFieldMapping;
                //$oxCategory = \oxRegistry::get('oxCategory');
                break;

            case "manufacturer":
                $mappingFields = $this->manufacturerFieldMapping;
                //$oxManufacturer = \oxRegistry::get('oxManufacturer');
                break;

            default:

                break;
        }

        $mappingFields = array_merge($mappingFields, $this->commonFieldMapping);

        foreach ($mappingFields as $target => $source) {
            if ($source && isset($entity->$source)) {
                $entity->$target = $entity->$source;
            } elseif ($target && !isset($entity->$target)) {
                $entity->$target = $source;
            }
        }

        return $entity;
    }
}
