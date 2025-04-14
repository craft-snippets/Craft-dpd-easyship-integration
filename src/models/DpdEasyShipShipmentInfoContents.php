<?php

namespace craftsnippets\dpdeasyship\models;
use craftsnippets\baseshippingplugin\BaseShipmentInfoContents;
use craftsnippets\shippingtoolbox\ShippingToolbox;
use craftsnippets\dpdeasyship\DpdEasyShip;

class DpdEasyShipShipmentInfoContents extends BaseShipmentInfoContents
{

    public $parcelShopCode;
    public $parcelShopName;
    public $test;

    public static function getJsonProperties(): array
    {
        return [
            [
                'value' => 'parcelShopCode',
                'label' => \Craft::t('shipping-toolbox', 'Parcel shop code'),
            ],
            [
                'value' => 'parcelShopName',
                'label' => \Craft::t('shipping-toolbox', 'Parcel shop name'),
            ],
        ];
    }

    public static function render($order, $property)
    {
        if($property == 'parcelShopCode'){
            $html = ShippingToolbox::getInstance()->plugins->renderParcelShopSelect($order, DpdEasyShip::getInstance()->handle);
            return $html;
        }
        return null;
    }
}