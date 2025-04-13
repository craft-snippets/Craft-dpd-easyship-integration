<?php

namespace craftsnippets\dpdeasyship\services;

use Craft;
use craft\commerce\elements\Order;
use craft\elements\Address;
use craftsnippets\baseshippingplugin\ShippingServiceBase;
use craftsnippets\dpdeasyship\api\ApiPdf;
use craftsnippets\dpdeasyship\models\DpdEasyShipParcel;
use craftsnippets\shippingtoolbox\ShippingToolbox;
use DataLinx\DPD\API;
use DataLinx\DPD\ParcelCODType;
use DataLinx\DPD\ParcelType;
use craftsnippets\dpdeasyship\DpdEasyShip;

use DataLinx\DPD\Requests\ParcelImport as ParcelImportRequest;
use craftsnippets\dpdeasyship\requests\ParcelPrintRequest;
use craftsnippets\dpdeasyship\requests\ParcelStatusRequest;
use craftsnippets\dpdeasyship\requests\ParcelCancelRequest;
use craftsnippets\dpdeasyship\requests\ParcelDeleteRequest;

class DpdEasyShipService extends ShippingServiceBase
{
    public static function getPlugin()
    {
        return DpdEasyShip::getInstance();
    }

    public function getApiObject($class = null): API
    {
        $login = DpdEasyShip::getInstance()->getSettings()->apiLogin;
        $password = DpdEasyShip::getInstance()->getSettings()->apiPassword;
        $country = DpdEasyShip::getInstance()->getSettings()->apiCountry;

        // class
        if(is_null($class)){
            $class = API::class;
        }

        // Set up the API
        $dpd = new $class($login, $password, $country);
        return $dpd;
    }

    public function getParcelTypeOptions()
    {
        $options = [
            [
                'label' => Craft::t('dpd-easy-ship', 'DPD Classic'),
                'value' => ParcelType::CLASSIC,
            ],
            [
                'label' => Craft::t('dpd-easy-ship', 'DPD Classic COD'),
                'value' => ParcelType::CLASSIC_COD,
            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'DPD Classic Document return'),
//                'value' => ParcelType::CLASSIC_DOCUMENT_RETURN,
//            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'DPD Home (B2C)'),
//                'value' => ParcelType::HOME_B2C,
//            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'DPD Home COD'),
//                'value' => ParcelType::HOME_COD,
//            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'Exchange'),
//                'value' => ParcelType::EXCHANGE,
//            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'Tyre'),
//                'value' => ParcelType::TYRE,
//            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'Tyre (B2C)'),
//                'value' => ParcelType::TYRE_B2C,
//            ],
            [
                'label' => Craft::t('dpd-easy-ship', 'Parcel shop'),
                'value' => ParcelType::PARCEL_SHOP,
            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'Pallet'),
//                'value' => ParcelType::PALLET,
//            ],
//            [
//                'label' => Craft::t('dpd-easy-ship', 'DPD Home COD with return label'),
//                'value' => ParcelType::HOME_COD_RETURN,
//            ],
        ];

        return $options;
    }

    public function getCodTypeOptions()
    {
        $options = [
            [
                'label' => Craft::t('dpd-easy-ship', 'Average - the amount of each parcel will be the average amount of the total COD amount'),
                'value' => ParcelCODType::AVERAGE,
            ],
            [
                'label' => Craft::t('dpd-easy-ship', 'All - all parcels have the same amount which is the total COD amount'),
                'value' => ParcelCODType::ALL,
            ],
            [
                'label' => Craft::t('dpd-easy-ship', 'First only - only the first parcel will have the COD amount and the other parcels will be DPD Classic parcels'),
                'value' => ParcelCODType::FIRST_ONLY,
            ],
        ];
        return $options;
    }

    public function validateAddress(?Address $address, bool $isDelivery = true)
    {
        if(is_null($address)){
            throw new \Exception(Craft::t('dpd-easy-ship', 'address is not set for the order'));
        }

        if(is_null($address->organization) && is_null($address->fullName)){
            throw new \Exception(Craft::t('mygls-shipping', 'organisation and full name are both empty'));
        }

        // delivery address validation
        if($isDelivery){
            if(
                is_null($address->addressLine1) ||
                is_null($address->addressLine2) ||
                is_null($address->locality) ||
                is_null($address->postalCode) ||
                is_null($address->countryCode)
            ){
                throw new \Exception(Craft::t('dpd-easy-ship', 'Shipping address does not have all required values entered. Make sure that street (address line 1), street and home number (address line 2), locality, postal code and country are not empty.'));
            }
        }

        // only croatia and slovenia are allowed
        if($address->countryCode != 'HR' && $address->countryCode != 'SI'){
            throw new \Exception(Craft::t('dpd-easy-ship', 'Only Croatia or Slovenia are allowed for delivery address.'));
        }

    }

    public function createShipmentDetails(Order $order, $requestSettings = [])
    {
        $class = DpdEasyShip::getShipmentDetailsClass();
        $shippingData = new $class([
            'order' => $order,
        ]);

        // request settings
        $defaultSettings = [
            'parcelCount' => 1,
            'codAmount' => null,
            'parcelShopCode' => null,
            'weight' => null,
        ];
        $requestSettings = array_merge($defaultSettings, $requestSettings);

        // request obj
        $request = new ParcelImportRequest($this->getApiObject());
        $request->num_of_parcel = $requestSettings['parcelCount'];

        // sender remark
        $sender_remark = $requestSettings['sender_remark'] ?? null;
        if(!is_null($sender_remark)){
            $request->sender_remark = $sender_remark;
        }

        // delivery address
        $deliveryAddress = $order->shippingAddress;

        $request->name1 = $this->getNameForAddress($deliveryAddress);
        $request->street = $deliveryAddress->addressLine1;
        $request->rPropNum = $deliveryAddress->addressLine2;
        $request->city = $deliveryAddress->locality;
        $request->pcode = $deliveryAddress->postalCode;
        $request->country = $deliveryAddress->countryCode;

        // other request data
        $request->email = $order->email;
        $request->order_number = $order->getShortNumber();

        $request->parcel_type = $shippingData->getDefaultParcelType();

        // cod
        $request->cod_amount = $requestSettings['codAmount'];
        $request->cod_purpose = $order->getShortNumber();
        $request->parcel_cod_type = DpdEasyShip::getInstance()->getSettings()->codType;

        // parcel shop
        if(!is_null($requestSettings['parcelShopCode'])){
            $request->pudo_id = $requestSettings['parcelShopCode'];
        }
        if(!is_null($requestSettings['weight'])){
            $request->weight = (float)$requestSettings['weight'];
        }

        $phoneField = ShippingToolbox::getInstance()->plugins->getPhoneField();
        if(!is_null($phoneField)){
            $request->phone = $deliveryAddress->getFieldValue($phoneField->handle);
        }

        // sender address
        $senderAddress = ShippingToolbox::getInstance()->plugins->getSenderAddress($order, $requestSettings);
        if(!is_null($senderAddress)){

            $request->sender_name = $senderAddress->organization;
            $request->sender_city = $senderAddress->locality;
            $request->sender_pcode = $senderAddress->postalCode;
            $request->sender_country = $senderAddress->countryCode;
            $request->sender_street = $senderAddress->addressLine1;

            $phoneField = ShippingToolbox::getInstance()->plugins->getPhoneField();
            if(!is_null($phoneField)){
                $request->sender_phone = $senderAddress->getFieldValue($phoneField->handle);
            }

        }

        // send
        try {
            $response = $request->send();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage(),
                'errorType' => 'api',
            ];
        }

        // not needed ???
        if(!$response->isSuccessful()){
            return [
                'success' => false,
                'error' => 'API Error: ' . $response->getError(),
                'errorType' => 'api',
            ];
        }

        $parcelNumbers = $response->getParcelNumbers();
        $parcels = array_map(function($single) use($order){
            return new DpdEasyShipParcel([
                'number' => $single,
                'status' => null,
                'order' => $order,
            ]);
        }, $parcelNumbers);
        $shippingData->parcels = $parcels;

        $shippingData->assignRequestData($request);

        // insert data
        // SAVE DATA
        $propertiesJson = $shippingData->encodeData();
        $plugin = DpdEasyShip::getInstance();

        // get pdf
        $request = new ParcelPrintRequest($this->getApiObject(ApiPdf::class));
        $request->parcels = $parcelNumbers;

        // send
        try {
            $response = $request->send();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Label request - API Error: ' . $e->getMessage(),
                'errorType' => 'api',
            ];
        }

        $pdfContent = $response->getPdfContent();
        $shipmentElement = ShippingToolbox::getInstance()->plugins->saveShipmentData($propertiesJson, $order, $plugin, $pdfContent);

        return [
            'success' => true,
            'shipment' => $shipmentElement,
        ];

    }

    public function removeShipmentDetails(Order $order, $shipmentDetails)
    {
        $printedParcels = array_filter($shipmentDetails->parcels, function($single) use($order){
            $statusPrinted = $this->getPlugin()->getShipmentDetailsClass()::STATUS_PRINTED;
            return $single->status == $statusPrinted;
        });
        $parcelsString = implode(',', array_column($printedParcels, 'number'));

        // cancel request for already printed parcels. if they were not printed, ParcelDeleteRequest would be used
        $request = new ParcelCancelRequest($this->getApiObject());
        $request->parcels = $parcelsString;

        // send
        try {
            $response = $request->send();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage(),
                'errorType' => 'api',
            ];
        }

        return [
            'status' => $response->getStatus(),
            'success' => true,
        ];
    }

    public function updateParcelsStatus($order, $shipmentDetails)
    {
        $parcels = [];
        foreach ($shipmentDetails->parcels as $parcel){
            $request = new ParcelStatusRequest($this->getApiObject());
            $request->parcelNumber = $parcel->number;

            try {
                $response = $request->send();
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'API Error: ' . $e->getMessage(),
                    'errorType' => 'api exception',
                ];
            }
            $parcel->status = $response->getParcelStatus();
            $parcels[] = $parcel;
        }

        $shipmentDetails->parcels = $parcels;
        $json = $shipmentDetails->encodeData();
        $result = [
            'success' => true,
            'json' => $json,
            'parcels' => $parcels,
        ];
        return $result;
    }

}