<?php
namespace craftsnippets\dpdeasyship\elements\actions;
use craftsnippets\shippingtoolbox\elements\actions\BaseCreateParcelsAction;
use craftsnippets\dpdeasyship\DpdEasyShip;

class CreateParcelsAction extends BaseCreateParcelsAction
{
    public static function getPlugin()
    {
        return DpdEasyShip::getInstance();
    }

}