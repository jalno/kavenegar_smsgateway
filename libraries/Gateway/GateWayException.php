<?php

namespace packages\kavenegar_smsgateway\GateWay;

use packages\sms\GateWay\GateWayException as ParentGateWayException;

class GateWayException extends ParentGateWayException
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
