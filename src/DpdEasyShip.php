<?php

namespace craftsnippets\dpdeasyship;

use Craft;
use craft\commerce\elements\Order;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use craftsnippets\baseshippingplugin\ShippingPlugin;
use craftsnippets\baseshippingplugin\ShippingServiceBase;

use craftsnippets\dpdeasyship\models\Settings;
use craftsnippets\dpdeasyship\models\DpdEasyShipShipmentDetails;
use craftsnippets\dpdeasyship\services\DpdEasyShipService;
use craftsnippets\dpdeasyship\elements\actions\CreateParcelsAction;
use craftsnippets\dpdeasyship\models\DpdEasyShipShipmentInfoContents;
use yii\base\Event;

class DpdEasyShip extends ShippingPlugin
{
    public string $schemaVersion = '1.0.0';

    public function init(): void
    {
        parent::init();

        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots[$this->handle] = __DIR__ . '/templates';
            }
        );
    }

    public static function config(): array
    {
        return [
            'components' => ['easyship' => DpdEasyShipService::class],
        ];
    }

    ///////////////////////

    public static function getSettingsTemplate(): string
    {
        return 'dpd-easy-ship/settings.twig';
    }

    public static function getSettingsClass(): string
    {
        return Settings::class;
    }

    public static function getShipmentDetailsClass(): string
    {
        return DpdEasyShipShipmentDetails::class;
    }

    public static function getShippingName(): string
    {
        return 'DPD EasyShip';
    }

    public function isAllowedForOrder(Order $order): bool
    {
        $enabledTable = $this->getSettings()->enabledShippingMethods;
        $enabledIds = array_column($enabledTable, 'shippingMethodId');

        $orderShippingMethod = $order->shippingMethod;
        if(is_null($orderShippingMethod)){
            return false;
        }

        if(in_array($orderShippingMethod->id, $enabledIds)){
            return true;
        }
        return false;
    }

    public function getSettingsErrors()
    {
        $errors = [];

        $required = [
            'apiLogin',
            'apiPassword',
            'apiCountry',
        ];

        foreach ($required as $single){
            if(empty($this->getSettings()->{$single})){
                $label = $this->getSettings()->attributeLabels()[$single] ?? null;
                $errors[] = $label . ' is required.';
            }
        }

        return $errors;
    }

    public function canUseCod(Order $order): bool
    {
        return false;
    }

    public static function getLabelFolderName(): string
    {
        return 'dpd-easy-ship';
    }

    public function getPluginService(): ShippingServiceBase
    {
        return $this->easyship;
    }

    public function getCreateParcelsActionClass()
    {
        return CreateParcelsAction::class;
    }

    public function useInputParcelInfo()
    {
        return false;
    }

    public function supportsCod()
    {
        return true;
    }

    public function supportsParcelShops()
    {
        return true;
    }

    public function useInputWeight()
    {
        return true;
    }

    public function getWeightInputInstructions(): ?string
    {
        return Craft::t('dpd-easy-ship', 'Weight value is required when using "Parcel Shop" type parcel.');
    }

    public function getParcelShopSelectWidgetTemplate(): ?string
    {
        return 'dpd-easy-ship/parcel-shop-select.twig';
    }
    public static function getShipmentInfContentsClass()
    {
        return DpdEasyShipShipmentInfoContents::class;
    }
}
