<?php
 
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
    $app->add(function ($req, $res, $next) {
        $response = $next($req, $res);
        return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    }); 

    $app->get('/mastersConfigs/{customerKey}','MasterConfigsController:mastersConfigs');
    $app->post('/mastersPlantConfigs','MasterConfigsController:mastersPlantConfigs');
    
    
    $app->group('/machineDetailedDB', function () use ($app){
        $app->post('/acs_consolidated',    'LiveDBController:machineData');        
        $app->post('/acs_trend',           'LiveDBController:macAccData');     
        $app->post('/actual_idle_dur',     'LiveDBController:machineData');
        $app->post('/actual_cycl_dur',     'LiveDBController:machineData');
        $app->post('/pro_consolidated',    'LiveDBController:prodData');
        $app->post('/pro_trend',           'LiveDBController:proStatus');
        $app->post('/alarmDetails',       'LiveDBController:alarmDetails');
        $app->post('/productivity_count',  'LiveDBController:productivity_count');
        $app->post('/cy_idle_status',      'LiveDBController:cy_idle_status');
        $app->post('/consumable_details',  'LiveDBController:consumable_details');
        $app->post('/ovr_details',         'LiveDBController:ovr_details');
        $app->post('/operator_details',     'LiveDBController:operator_details');

    });

    $app->group('/MachineReport', function () use ($app) {
        $app->get('/get/{key}/{fdate}',                             'MacDBReportController:getCount');
        $app->get('/detailsonmacwise/{machine_serial}/{start_date}','MacDBReportController:detailsonmacwise');
        $app->get('/getCountAcs/{machine_serial}/{start_date}',     'MacDBReportController:getCountAcs');
        $app->get('/ovrsCountDayMachhinetWise/{machine_serial}/{start_date}','MacDBReportController:ovrsCountDayMachhinetWise');
        $app->get('/oeeCountDayMachineWise/{machine_serial}/{start_date}','MacDBReportController:oeeCountDayMachineWise');
        $app->get('/getIdleCount/{key}/{fdate}',                     'MacDBReportController:getIdleCount');
        $app->get('/getCyclingTotalCount/{key}/{fdate}',             'MacDBReportController:getCyclingTotalCount');
        $app->get('/getAllCountPid/{key}/{fdate}',                    'MacDBReportController:getAllCountpid');
        $app->get('/getPausePid/{key}/{fdate}',                       'MacDBReportController:getPauseCountPid');
        $app->get('/getDetails/{machine_serial}/{date}/{v}','MacDBReportController:getDetails');
        $app->get('/getAlramDetails/{machine_serial}/{date}','MacDBReportController:getAlramDetails');
        $app->get('/getAcsDetails/{machine_serial}/{date}','MacDBReportController:getAcsDetails');
        $app->get('/getOvrDetails/{machine_serial}/{date}','MacDBReportController:getOvrDetails');
        $app->post('/cellProduction',    'MacDBReportController:cellProduction');
        $app->post('/cellAlarm',    'MacDBReportController:cellAlarm');
        $app->post('/cellAcs',    'MacDBReportController:cellAcs');
        $app->post('/cellOvr',    'MacDBReportController:cellOvr');
        $app->post('/cellOEE',    'MacDBReportController:cellOEE');
    });

    $app->group('/MgmtDBReport', function () use ($app)
    {
        $app->get('/getMachines/{plant_key}', 'MgmtDBController:getMachines');
        $app->post('/getPowerReport', 'MgmtDBController:getPowerReport');
        $app->post('/getOperatorReport', 'MgmtDBController:operatorUtilization');
        $app->post('/getmachinesReport', 'MgmtDBController:machineUtilization');
        $app->post('/getpartsReport', 'MgmtDBController:partsUtilization');

    });
    