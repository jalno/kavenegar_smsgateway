<?php

namespace packages\kavenegar_smsgateway\Gateway;

use packages\sms\Gateway\GatewayException as ParentGatewayException;

class GatewayException extends ParentGatewayException
{
    protected ?\Throwable $previous = null;

    /**
     * @param mixed $data
     */
    public function __construct($data, ?\Throwable $previous = null)
    {
        parent::__construct($data);
        if ($previous) {
            $this->previous = $previous;
        }
    }
}
