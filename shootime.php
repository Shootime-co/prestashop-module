<?php
/**
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class Shootime extends Module
{
    protected $config_form = false;

    private $container;

    public function __construct()
    {
        $this->name = 'shootime';
        $this->tab = 'administration';
        $this->version = '0.0.0';
        $this->author = 'Shootime';
        $this->need_instance = 0;

        $this->module_key = '55eeacd02b0c6af12c4d1ee172938638';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Shootime');
        $this->description = $this->l('Your turnkey shoot without moving. We create the most beautiful photo content, packshots & videos for your e-commerce platforms and marketing campaigns.');

        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);

        if ($this->container === null) {
            $this->container = new \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }
    }

    public function install()
    {
        // Test if MBO is installed
        // For more information, check the readme of mbo-lib-installer
        $mboStatus = (new Prestashop\ModuleLibMboInstaller\Presenter)->present();

        if(!$mboStatus["isInstalled"])
        {
            try {
                $mboInstaller = new Prestashop\ModuleLibMboInstaller\Installer(_PS_VERSION_);
                /** @var boolean */
               $result = $mboInstaller->installModule();

               // Call the installation of PrestaShop Integration Framework components
               $this->installDependencies();
            } catch (\Exception $e) {
                // Some errors can happen, i.e during initialization or download of the module
                $this->context->controller->errors[] = $e->getMessage();
                return 'Error during MBO installation';
            }
        }
        else {
            $this->installDependencies();
        }

        return parent::install();
    }


    /**
     * Install PrestaShop Integration Framework Components
     */
    public function installDependencies()
    {
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        /* PS Account */
        if (!$moduleManager->isInstalled("ps_accounts")) {
            $moduleManager->install("ps_accounts");
        } else if (!$moduleManager->isEnabled("ps_accounts")) {
            $moduleManager->enable("ps_accounts");
            $moduleManager->upgrade('ps_accounts');
        } else {
            $moduleManager->upgrade('ps_accounts');
        }

        /* Cloud Sync - PS Eventbus */
        if (!$moduleManager->isInstalled("ps_eventbus")) {
            $moduleManager->install("ps_eventbus");
        } else if (!$moduleManager->isEnabled("ps_eventbus")) {
            $moduleManager->enable("ps_eventbus");
            $moduleManager->upgrade('ps_eventbus');
        } else {
            $moduleManager->upgrade('ps_eventbus');
        }
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Load the configuration content
     */
    public function getContent()
    {
        $this->context->smarty->assign('module_dir', $this->_path);
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        $accountsService = null;

        try {
            $accountsFacade = $this->getService('shootime.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException $e) {
            $accountsInstaller = $this->getService('shootime.ps_accounts_installer');
            $accountsInstaller->install();
            $accountsFacade = $this->getService('shootime.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        }

        try {
            Media::addJsDef([
                'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()
                    ->present($this->name),
            ]);

            // Retrieve Account CDN
            $this->context->smarty->assign('urlAccountsCdn', $accountsService->getAccountsCdn());

        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();
            return '';
        }

        // Cloud Sync
        if ($moduleManager->isInstalled("ps_eventbus")) {
            $eventbusModule =  \Module::getInstanceByName("ps_eventbus");
            if (version_compare($eventbusModule->version, '1.9.0', '>=')) {

                $eventbusPresenterService = $eventbusModule->getService('PrestaShop\Module\PsEventbus\Service\PresenterService');

                $this->context->smarty->assign('urlCloudsync', "https://assets.prestashop3.com/ext/cloudsync-merchant-sync-consent/latest/cloudsync-cdc.js");

                Media::addJsDef([
                    'contextPsEventbus' => $eventbusPresenterService->expose($this, ['info', 'modules', 'themes'])
                ]);
            }
        }

        /**********************
         * PrestaShop Billing *
         * *******************/

        // Load context for PsBilling
        $billingFacade = $this->getService('shootime.ps_billings_facade');
        $partnerLogo = $this->getLocalPath() . 'logo.png';

        // Billing
        Media::addJsDef($billingFacade->present([
            'logo' => $partnerLogo,
            'tosLink' => 'https://www.shootime.co/legal/conditions-generales-de-vente-et-dutilisation-fr',
            'privacyLink' => 'https://www.shootime.co/legal/privacy-fr',
            'emailSupport' => 'hello@shootime.co',
        ]));

        $this->context->smarty->assign('urlBilling', "https://unpkg.com/@prestashopcorp/billing-cdc/dist/bundle.js");

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output;
    }

    /**
     * Retrieve service
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->container->getService($serviceName);
    }
}
