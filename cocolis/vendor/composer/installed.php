<?php return array(
    'root' => array(
        'pretty_version' => '1.0.9',
        'version' => '1.0.9.0',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'cocolis/woocommerce',
        'dev' => true,
    ),
    'versions' => array(
        'cocolis/php' => array(
            'pretty_version' => 'dev-develop',
            'version' => 'dev-develop',
            'type' => 'package',
            'install_path' => __DIR__ . '/../cocolis/php',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'reference' => '94098bda9003d423ff6cb9ba68e56f383c09fbc0',
            'dev_requirement' => false,
        ),
        'cocolis/woocommerce' => array(
            'pretty_version' => '1.0.9',
            'version' => '1.0.9.0',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
