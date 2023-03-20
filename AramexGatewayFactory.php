<?php

namespace Omnibus\Aramex;

use Omnibus\Core\GatewayFactory;

class AramexGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'aramex',
            'omnibus.factory_title' => 'Aramex',
        ]);
    }
}