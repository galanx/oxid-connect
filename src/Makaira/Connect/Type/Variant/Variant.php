<?php

namespace Makaira\Connect\Type\Variant;

//use Makaira\Connect\Type\Common\BaseProduct;
use Makaira\Connect\Type\Product\Product;

class Variant extends Product
{
    public $parent = '';
    public $isVariant = true;
    public $variantactive = 0;
}
