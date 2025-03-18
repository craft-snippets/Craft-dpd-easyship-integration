<?php

namespace craftsnippets\dpdeasyship\models;

use craft\commerce\elements\Order;
use craft\base\Model;
use craftsnippets\baseshippingplugin\BaseShipmentParcel;
use craftsnippets\dpdeasyship\models\DpdEasyShipShipmentDetails;

class DpdEasyShipParcel extends BaseShipmentParcel
{

    public ?string $status;

    public function getTrackingUrl()
    {
        // if parcel status not null and not created
        if(is_null($this->status) || $this->status == DpdEasyShipShipmentDetails::STATUS_CREATED){
            return null;
        }
        $trackingUrl = 'https://www.dpdgroup.com/hr/mydpd/my-parcels/track?parcelNumber='.$this->number;
        return $trackingUrl;
    }

    public function getStatusText()
    {
        return $this->status;
    }

    public function getIsDelivered(): bool
    {
        return $this->status == DpdEasyShipShipmentDetails::STATUS_DELIVERED;
    }

}