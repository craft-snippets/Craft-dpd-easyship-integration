<?php

namespace craftsnippets\dpdeasyship\responses;

use DataLinx\DPD\Responses\AbstractResponse;
class ParcelPrintResponse extends AbstractResponse
{
    public function isSuccessful(): bool
    {
        return true;
    }

    public function getPdfContent()
    {
        return $this->data['content'];
    }
}