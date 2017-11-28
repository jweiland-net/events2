<?php
// Update the category registry
$result = \JWeiland\Maps2\Tca\Maps2Registry::getInstance()->add(
    'events2',
    'tx_events2_domain_model_location'
);
