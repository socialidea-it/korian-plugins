<?php

use LeadpagesWP\Leadpages\LeadpagesPages;
use LeadpagesMetrics\LeadpagesErrorEvent;
use LeadpagesWP\Front\ShortCodes\LeadboxShortCodes;
use LeadpagesWP\models\LeadboxesModel;
use LeadpagesWP\Lib\HttpClient\Client;
use LeadpagesWP\models\LeadbarsModel;
use LeadpagesWP\Bootstrap\AdminBootstrap;
use LeadpagesWP\Bootstrap\FrontBootstrap;
use LeadpagesWP\Helpers\PasswordProtected;
use LeadpagesWP\Admin\TinyMCE\LeadboxTinyMCE;
use LeadpagesWP\ServiceProviders\LeadboxesApi;
use LeadpagesWP\ServiceProviders\LeadbarsApi;
use LeadpagesWP\ServiceProviders\SplitTestApi;
use TheLoop\ServiceContainer\ServiceContainer;
use LeadpagesWP\models\LeadPagesPostTypeModel;
use LeadpagesWP\Front\Controllers\LeadboxController;
use LeadpagesWP\Front\Controllers\LeadbarController;
use LeadpagesWP\Front\Controllers\LeadpageController;
use LeadpagesWP\Front\Controllers\NotFoundController;
use LeadpagesWP\Front\Controllers\WelcomeGateController;
use LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType;
use LeadpagesWP\ServiceProviders\WordPressLeadpagesAuth;
use LeadpagesWP\Config\LpConfig;

/*
|--------------------------------------------------------------------------
| Instantiate Service Container
|--------------------------------------------------------------------------
|
|
*/

$leadpagesContainer = new ServiceContainer();
$leadpagesApp       = $leadpagesContainer->getContainer();

/*
|--------------------------------------------------------------------------
| Base Providers
|--------------------------------------------------------------------------
|
| Leadpages Base Service providers
|
*/

$leadpagesApp['config'] = function () {
    $env = isset($_ENV['ENV_TARGET']) ? $_ENV['ENV_TARGET'] : 'production';
    return new LpConfig($env);
};

/**
 * HttpClient
 *
 * @param $leadpagesApp
 *
 * @return \Leadpages\Providers\WordPressHttpClient
 */

$leadpagesApp['httpClient'] = function ($leadpagesApp) {
    return new Client();
};

$leadpagesApp['adminBootstrap'] = function ($leadpagesApp) {
    return new AdminBootstrap(
        $leadpagesApp['leadpagesLogin'],
        $leadpagesApp['lpPostTypeModel'],
        $leadpagesApp['leadboxesApi'],
        $leadpagesApp['leadbarsApi'],
        $leadpagesApp['leadboxesModel'],
        $leadpagesApp['leadbars'],
        $leadpagesApp['leadboxTinyMce'],
        $leadpagesApp['config']
    );
};

$leadpagesApp['frontBootstrap'] = function ($leadpagesApp) {
    return new FrontBootstrap(
        $leadpagesApp['leadpagesLogin'],
        $leadpagesApp['leadpageController'],
        $leadpagesApp['pagesApi'],
        $leadpagesApp['leadboxController'],
        $leadpagesApp['leadbarController'],
        $leadpagesApp['leadboxShortCode']
    );
};


$leadpagesApp['lpPostType'] = function ($leadpagesApp) {
    return new LeadpagesPostType();
};


$leadpagesApp['lpPostTypeModel'] = function ($leadpagesApp) {
    return new LeadPagesPostTypeModel($leadpagesApp['pagesApi'], $leadpagesApp['lpPostType']);
};

$leadpagesApp['leadboxesModel'] = function ($leadpagesApp) {
    return new LeadboxesModel();
};

$leadpagesApp['leadbars'] = function ($leadpagesApp) {
    return new LeadbarsModel();
};


$leadpagesApp['passwordProtected'] = function ($leadpagesApp) {
    global $wpdb;
    return new PasswordProtected($wpdb);
};


$leadpagesApp['leadpageController'] = function ($leadpagesApp) {
    return new LeadpageController(
        $leadpagesApp['notfound'],
        $leadpagesApp['WelcomeGateController'],
        $leadpagesApp['lpPostTypeModel'],
        $leadpagesApp['pagesApi'],
        $leadpagesApp['passwordProtected']
    );
};

$leadpagesApp['notfound']           = function ($leadpagesApp) {
    return new NotFoundController($leadpagesApp['lpPostTypeModel'], $leadpagesApp['pagesApi']);
};

$leadpagesApp['WelcomeGateController'] = function ($leadpagesApp) {
    return new WelcomeGateController();
};

$leadpagesApp['leadboxController'] = function ($leadpagesApp) {
    return new LeadboxController($leadpagesApp['leadboxesApi'], $leadpagesApp['leadboxesModel']);
};

$leadpagesApp['leadbarController'] = function ($leadpagesApp) {
    return new LeadbarController($leadpagesApp['leadbarsApi'], $leadpagesApp['leadbars']);
};

$leadpagesApp['leadboxTinyMce'] = function ($leadpagesApp) {
    return new LeadboxTinyMCE();
};

$leadpagesApp['leadboxShortCode'] = function ($leadpagesApp) {
    return new LeadboxShortCodes();
};

$leadpagesApp['errorEventsHandler'] = function () {
    return new LeadpagesErrorEvent();
};
/*
|--------------------------------------------------------------------------
| API Providers
|--------------------------------------------------------------------------
|
| Leadpages API Service providers
|
*/


/**
 * Response object for handling leadpages api calls
 *
 * @param mixed $leadpagesApp
 *
 * @return \LeadpagesWP\Lib\ApiResponseHandler
 */
$leadpagesApp['apiResponseHandler'] = function ($leadpagesApp) {
    return new ApiResponseHandler();
};

/**
 * Leadpages login api object
 *
 * @param $leadpagesApp
 *
 * @return \LeadpagesWP\ServiceProviders\LeadpagesLogin
 */
$leadpagesApp['leadpagesLogin'] = function ($leadpagesApp) {
    return new WordPressLeadpagesAuth($leadpagesApp['httpClient'], $leadpagesApp['config']);
};

/**
 * Leadpages pages api object
 *
 * @param mixed $leadpagesApp
 *
 * @return LeadpagesWP\Leadpages\LeadpagesPages
 */
$leadpagesApp['pagesApi'] = function ($leadpagesApp) {
    return new LeadpagesPages($leadpagesApp['httpClient'], $leadpagesApp['leadpagesLogin'], $leadpagesApp['config']);
};
/**
 * Leadpages login api object
 *
 * @param $leadpagesApp
 *
 * @return \LeadpagesWP\ServiceProviders\LeadboxesApi
 */
$leadpagesApp['leadboxesApi'] = function ($leadpagesApp) {
    return new LeadboxesApi($leadpagesApp['httpClient'], $leadpagesApp['leadpagesLogin'], $leadpagesApp['config']);
};

$leadpagesApp['leadbarsApi'] = function ($leadpagesApp) {
    return new LeadbarsApi($leadpagesApp['httpClient'], $leadpagesApp['leadpagesLogin']);
};

$leadpagesApp['splitTestApi'] = function ($leadpagesApp) {
    return new SplitTestApi($leadpagesApp['leadpagesLogin'], $leadpagesApp['config']);
};
