<?php
    use App\Services\AppServices\AppConfigs;

    require __DIR__ . '/../vendor/autoload.php';
    require __DIR__ . '/../../appConfigs/lib/AppServices.php';
    $env = new AppConfigs();
    $app = new \Slim\App(['settings' => ['displayErrorDetails'  => true,
                                         'db'                   => $env->db_config['mysql_cloud']
                                        ],
                          'portal_mode' => 1,
                        ]);

                                        
    $container  =   $app->getContainer();
    $capsule = new \Illuminate\Database\Capsule\Manager;
    
    $capsule->addConnection($container['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    $container['db'] = function($container) use ($capsule){
        return $capsule;
    };
    
    $container['MasterConfigsController'] = function($container){
        return new \App\Controllers\MasterConfigsController($container);
    };

    $container['LiveDBController'] = function($container){
        return new \App\Controllers\LiveDBController($container);
    };

    $container['MacDBReportController'] = function($container){
        return new \App\Controllers\MacDBReportController($container);
    };

    $container['MgmtDBController'] = function($container){
        return new \App\Controllers\MgmtDBController($container);
    };

    require __DIR__ . '/../app/routes.php';