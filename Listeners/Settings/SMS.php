<?php

namespace packages\kavenegar_smsgateway\Listeners\Settings;

use packages\kavenegar_smsgateway\API;
use packages\sms\Events\GateWays as GateWaysEvent;

class SMS
{
    public function gatewaysList(GateWaysEvent $gateways): void
    {
        $fieldName = fn (string $name) => API::GATEWAY_NAME.'_'.$name;

        $gateway = new GateWaysEvent\GateWay(API::GATEWAY_NAME);
        $gateway->setHandler(API::class);

        $gateway->addInput([
            'name' => $fieldName('apikey'),
            'type' => 'string',
        ]);
        $gateway->addField([
            'name' => $fieldName('apikey'),
            'label' => t('settings.sms.gateways.azinsms.apikey'),
            'input-group' => [
                'right' => [
                    [
                        'type' => 'addon',
                        'text' => sprintf(
                            '<a href="https://panel.kavenegar.com/client/setting/account" class="btn btn-no-padding" target="_blank"><i class="fa fa-key"></i> %s</a>',
                            t('settings.sms.gateways.kavenegar.get_apikey')
                        ),
                    ],
                ],
            ],
            'ltr' => true,
        ]);
        $gateways->addGateway($gateway);
    }
}
