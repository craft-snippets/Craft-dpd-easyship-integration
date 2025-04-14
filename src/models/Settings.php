<?php

namespace craftsnippets\dpdeasyship\models;

use Craft;
use craft\base\Model;
use craft\commerce\Plugin as CommercePlugin;
use DataLinx\DPD\ParcelCODType;
use craftsnippets\dpdeasyship\DpdEasyShip;

/**
 * DPD EasySHip Shipping Toolbox settings
 */
class Settings extends Model
{
    const COUNTRY_CROATIA = 'HR';
    const COUNTRY_SLOVENIA = 'SI';

    public $apiLogin;
    public $apiPassword;
    public $apiCountry = self::COUNTRY_CROATIA;
//    public $instructionsFieldId;
    public $enabledShippingMethods = [];
    public $codType = ParcelCODType::AVERAGE;
    public $parcelShopOptionsUrl;

    public function attributeLabels()
    {
        return [
            'apiLogin' => Craft::t('dpd-easy-ship', 'API Login'),
            'apiPassword' => Craft::t('dpd-easy-ship', 'API Password'),
            'apiCountry' => Craft::t('dpd-easy-ship', 'API Country'),
            'enabledShippingMethods' => Craft::t('dpd-easy-ship', 'Shipping methods with Dpd EasyShip integration enabled'),
            'codType' => Craft::t('dpd-easy-ship', 'Cash On Delivery (COD) type'),
            'parcelShopOptionsUrl' => Craft::t('dpd-easy-ship', 'Url to the JSON file containing parcel shop options'),
        ];
    }

    public function getShippingMethodsColumns()
    {
        $shippingMethods = CommercePlugin::getInstance()->getShippingMethods()->getAllShippingMethods();
        $shippingMethodsOptions = $shippingMethods->map(function ($shippingMethod) {
            return [
                'label' => $shippingMethod->name,
                'value' => $shippingMethod->id,
            ];
        });
        $parcelTypeOptions = DpdEasyShip::getInstance()->getPluginService()->getParcelTypeOptions();
        $columns = [
            'shippingMethodId' => [
                'heading' => Craft::t('dpd-easy-ship', 'Shipping method'),
                'type' => 'select',
                'options' => $shippingMethodsOptions,

            ],
            'parcelType' => [
                'heading' => Craft::t('dpd-easy-ship', 'Parcel type'),
                'type' => 'select',
                'options' => $parcelTypeOptions,
            ],
        ];
        return $columns;
    }

    public function getCountryOptions()
    {
        return [
            [
                'label' => Craft::t('dpd-easy-ship', 'Croatia'),
                'value' => self::COUNTRY_CROATIA,
            ],
            [
                'label' => Craft::t('dpd-easy-ship', 'Slovenia'),
                'value' => self::COUNTRY_SLOVENIA,
            ],
        ];
    }

    public function getCodTypeOptions()
    {
        return DpdEasyShip::getInstance()->getPluginService()->getCodTypeOptions();
    }



}
