<?php return array(
    'root' => array(
        'name' => 'cocolis/woocommerce',
        'pretty_version' => '1.0.13',
        'version' => '1.0.13.0',
        'reference' => NULL,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'cocolis/php' => array(
            'pretty_version' => 'dev-develop',
            'version' => 'dev-develop',
            'reference' => 'b761cf189d420d8242850755781c3d7ac959ff77',
            'type' => 'package',
            'install_path' => __DIR__ . '/../cocolis/php',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'cocolis/woocommerce' => array(
            'pretty_version' => '1.0.13',
            'version' => '1.0.13.0',
            'reference' => NULL,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
