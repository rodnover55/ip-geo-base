<?php
/**
 * @author Sergei Melnikov <me@rnr.name>
 */

return [
    'migrations' => 'migrations',
    'default' => 'sqlite',

    'connections' => array(
        'sqlite' => array(
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        )
    )
];