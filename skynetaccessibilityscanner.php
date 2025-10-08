<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Data\Data;
use Grav\Common\File\CompiledYamlFile;
use RocketTheme\Toolbox\Event\Event;

class SkynetAccessibilityScannerPlugin extends Plugin
{
    // Admin route name
    protected $routes = [
        'skynetaccessibilityscanner-manager'
    ];
    /**
     * admin controller
     * @type string
     */
    private $adminController;
    private $dataStorageDefault = 'user://data';

    public static function getSubscribedEvents(): array
    {
        return [
            'onPluginsInitialized'  => ['onPluginsInitialized', 0],
            'onTwigTemplatePaths'   => ['onTwigTemplatePaths', 0],
            'onPageInitialized'     => ['onPageInitialized', 0],
            'onAdminMenu'           => ['onAdminMenu', 0],
            'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
            'onTwigSiteVariables'   => ['onTwigSiteVariables', 0],
            'onGetPageTemplates'    => ['onGetPageTemplates', 0],
            'onAdminControllerInit' => ['onAdminControllerInit', 0],
        ];
    }

    /**
     * Initialize plugin
     */
    public function onPluginsInitialized()
    {
        $this->grav['locator']->addPath('blueprints', '', __DIR__ . '/blueprints');
        if ($this->isAdmin()) {

            // Enable admin events
            $this->enable([
                'onAdminMenu' => ['onAdminMenu', 0],
                'onAdminTwigTemplatePaths' => ['onAdminTwigTemplatePaths', 0],
                'onGetPageTemplates' => ['onGetPageTemplates', 0],
                'onAdminControllerInit' => ['onAdminControllerInit', 0],
                'onAdminData' => ['onAdminData', 0],
            ]);
        }
    }

    /**
     * Admin sidebar menu
     */
    public function onAdminMenu()
    {
        if ($this->isAdmin()) {
            $this->grav['twig']->plugins_hooked_nav['PLUGIN_SKYNETACCESSIBILITYSCANNER.SKYNETACCESSIBILITY_SCANNER'] = [
                'route' => $this->routes[0],
                'icon' => 'fa-universal-access'
            ];
        }
    }

    /**
     * Add admin twig templates
     */
    public function onAdminTwigTemplatePaths(Event $event)
    {
        $paths = $event['paths'];
        $paths[] = __DIR__ . '/admin/templates';
        $event['paths'] = $paths;
    }

    /**
     * Add blueprint directory
     */
    public function onGetPageTemplates(Event $event)
    {
        if (isset($event['types']) && $event['types']) {
            $types = $event['types'];
            $types->scanBlueprints('plugin://' . $this->name . '/blueprints');
        }
    }


    /**
     * Admin controller init
     */
    public function onAdminControllerInit(Event $event)
    {
        $this->adminController = $event['controller'];
    }

    /**
     * Frontend twig template paths
     */
    public function onTwigTemplatePaths(): void
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/admin/templates';
    }

    /**
     * Called for both site and admin to add twig variables / assets.
     */
    public function onTwigSiteVariables()
    {
        $this->grav['assets']->addCss('plugin://skynetaccessibilityscanner/assets/css/scanning-and-monitoring-app.css');
        if ($this->isAdmin()) {
            $path = $this->grav['uri']->path();
            if (strpos($path, $this->routes[0]) !== false) {
                $this->grav['log']->info('[Skynet] Admin route matched. Fetching data...');
                $data = $this->onAdminData();
                $this->grav['twig']->twig_vars = array_merge($this->grav['twig']->twig_vars, ['data' => $data]);
            }
        }
    }

    /**
     * Page initialization (fetch API data)
     */
    public function onPageInitialized(): void
    {
        $page = $this->grav['page'];
        $route = $page->route();
        $this->grav['log']->info("Skynet plugin route: " . $route);
        if (strpos($route, 'skynetaccessibilityscanner-manager') !== false) {
            $data = $this->onAdminData();
            $this->grav['twig']->twig_vars['data'] = $data;
        }
    }

    /**
     * Fetch API data
     */
    public function onAdminData(): array
    {
        $data = [];
        $domain_name = $_SERVER['HTTP_HOST'];//'http://getgrav.skynettechnologies.us/';;

        // --- Register Domain ---
        $arrDetails = [
            'website' => base64_encode($domain_name),
            'platform' => 'Grav CMS',
            'is_trial_period' => 1,
            'name' => $domain_name,
            'email' => 'noreply@' . $domain_name,
            'comapany_name' => $domain_name,
            'package_type' => '10-pages'
        ];

        $ch = curl_init('https://skynetaccessibilityscan.com/api/register-domain-platform');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arrDetails);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            $this->grav['log']->error("Skynet Register API error: $error");
            return [];
        }
        curl_close($ch);

        // --- Scan Detail API ---
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://skynetaccessibilityscan.com/api/get-scan-detail',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => ['website' => base64_encode($domain_name)],
        ]);
        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            $this->grav['log']->error("Skynet Scan Detail API error: $error");
            return [];
        }
        curl_close($curl);
        $result = json_decode($response, true) ?? [];
        if (isset($result['data'][0])) {
            $row = $result['data'][0];
            $data['domain'] = $row['domain'] ?? '';
            $data['fav_icon'] = $row['fav_icon'] ?? 'https://silverstripe.skynettechnologies.us/themes/simple/images/favicon.ico';
            $data['url_scan_status'] = $row['url_scan_status'] ?? 0;
            $data['scan_status']= $row['scan_status'] ?? 0;
            $data['total_selected_pages'] = $row['total_selected_pages'];
            $data['total_last_scan_pages'] = $row['total_last_scan_pages'];
            $data['total_pages'] = $row['total_pages'] ?? 0;
            $data['last_url_scan'] = $row['last_url_scan'] ?? 0;
            $data['total_scan_pages'] = $row['total_scan_pages'] ?? 0;
            $data['last_scan'] = $row['last_scan'] ?? null;
            $data['next_scan_date'] = $row['next_scan_date'] ?? null;
            $data['success_percentage'] = $row['success_percentage'] ?? '0';
            $data['scan_violation_total'] = $row['scan_violation_total'] ?? '0';
            $data['total_violations'] = $row['total_violations'] ?? 0;
            $data['package_name'] = $row['name'] ?? '';
            $data['package_id'] = $row['package_id'] ?? '';
            $data['page_views'] = $row['page_views'] ?? '';
            $data['package_price'] = $row['package_price'] ?? '';
            $data['subscr_interval'] = $row['subscr_interval'] ?? '';
            $data['end_date'] = $row['end_date'] ?? '';
            $data['website_id'] = $row['website_id'] ?? '';
            $data['is_trial_period'] = $row['is_trial_period'] ?? '';
            $data['dashboard_link'] = $result['dashboard_link'] ?? '';
            $data['total_fail_sum'] = $row['total_fail_sum'] ?? '';
            $data['is_expired'] = $row['is_expired'] ?? '';
        }

        // --- Scan Count API ---
        $curl1 = curl_init();
        curl_setopt_array($curl1, [
            CURLOPT_URL => 'https://skynetaccessibilityscan.com/api/get-scan-count',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => ['website' => base64_encode($domain_name)],
        ]);
        $response1 = curl_exec($curl1);
        curl_close($curl1);
        $result1 = json_decode($response1, true);

        $with_rem     = $result1['scan_details']['with_remediation'] ?? [];
        $without_rem  = $result1['scan_details']['without_remediation'] ?? [];
        $widgetPurchased = $result1['widget_purchased'] ?? false;

        // Always assign to with_remediation (since your template uses it)
        if ($widgetPurchased === false || $widgetPurchased === "false" || $widgetPurchased == 0) {
            // Use with_remediation data
            $data['scan_details'] = [
                'with_remediation' => $without_rem,
            ];
        } else {
            // Use without_remediation data
            $data['scan_details'] = [
                'with_remediation' => $with_rem,
            ];
        }


        // --- Packages API ---
        $payload = json_encode(['website' => base64_encode($domain_name)]);
        $curl2 = curl_init();
        curl_setopt_array($curl2, [
            CURLOPT_URL => 'https://skynetaccessibilityscan.com/api/packages-list',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
        ]);
        $response2 = curl_exec($curl2);
        $decoded = json_decode($response2, true);
        $activePackageId = $data['package_id'] ?? '';
        $activeInterval  = $data['subscr_interval'] ?? ''; // 'M' or 'Y'
        $allowedNames = ['Small Site', 'Medium Site', 'Large Site', 'Extra Large Site'];
        $plans = [];
        $websiteId = (string)($data['website_id'] ?? '');
        $today = new \DateTime('now', new \DateTimeZone('UTC'));
        $todayStr = $today->format('Y-m-d');

        // ----------------- Check active or expired -----------------
        $today = new \DateTime('now', new \DateTimeZone('UTC'));
        $todayStr = $today->format('Y-m-d');

        if (!empty($decoded['current_active_package'])) {
            foreach ($decoded['current_active_package'] as $key => $package) {
                if ((string)$key === $websiteId) {
                    $endDate = !empty($package['end_date']) ? new \DateTime($package['end_date'], new \DateTimeZone('UTC')) : null;

                    if ($endDate && $endDate->format('Y-m-d') === $todayStr) {
                        if (!empty($decoded['expired_package_detail'][$websiteId])) {
                            $expiredPackage = $decoded['expired_package_detail'][$websiteId];
                            $data['expired_package'] = $expiredPackage;

                            $activePackageId = $expiredPackage['package_id'] ?? '';
                            $activeInterval  = $expiredPackage['subscr_interval'] ?? '';
                        }
                    } else {
                        $activePackageId = $package['package_id'] ?? '';
                        $activeInterval  = $package['subscr_interval'] ?? '';
                    }
                    break;
                }
            }


        } elseif (!empty($decoded['expired_package_detail'])) {
            // No active, fallback to expired package
            if (!empty($decoded['expired_package_detail'][$websiteId])) {
                $expiredPackage = $decoded['expired_package_detail'][$websiteId];
                $data['expired_package'] = $expiredPackage;

                $activePackageId = $expiredPackage['package_id'] ?? '';
                $activeInterval  = $expiredPackage['subscr_interval'] ?? '';
            }
        }

        // ----------------- Final price handling -----------------
        if (!empty($decoded['current_active_package'])) {
            $data1 = $decoded['current_active_package'];
            $firstKey = array_key_first($data1);
            if ($firstKey !== null) {
                $finalPrice = $data1[$firstKey]['final_price'] ?? 0;
                $data['final_price'] = $finalPrice;
            }
        } elseif (!empty($decoded['expired_package_detail'])) {
            $firstKey = array_key_first($decoded['expired_package_detail']);
            $finalPrice = $decoded['expired_package_detail'][$firstKey]['final_price'] ?? 0;
            $data['final_price'] = $finalPrice;
        }

        // ----------------- Plans loop -----------------
        foreach ($decoded['Data'] as $plan) {
            if (isset($plan['name']) && in_array($plan['name'], $allowedNames)) {
                $packageId = $plan['id'] ?? null;
                if (!$packageId) {
                    continue; // skip if no ID
                }
                $action = 'upgrade'; // default action
                // Check if current active package matches this plan
                if ($packageId == $activePackageId) {
                    $plan['interval'] = $activeInterval; // 'M' or 'Y'

                    // Check if subscription has expired
                    $endDateStr = $data['end_date'] ?? ''; // end date from backend
                    if ($endDateStr) {
                        $endDate = new \DateTime($endDateStr, new \DateTimeZone('UTC'));
                        if ($today <= $endDate) {
                            // Still active → allow cancel
                            $action = 'cancel';
                        } else {
                            // Expired → force upgrade
                            $action = 'upgrade';
                        }
                    } else {
                        // If no end_date, default to cancel for active package
                        $action = 'cancel';
                    }
                }
                $plan['action'] = $action; // attach action to plan
                $data['activePackageId'] = $activePackageId;
                $data['packageId'] = $packageId;
                $data['websiteId'] = $websiteId;

                // Later: Generate autologin link for upgrade/cancel button
                $plans[] = $plan;
                // Generate violation autologin link
                $curl4 = curl_init();
                curl_setopt_array($curl4, [   // should be $curl4, not $curl
                    CURLOPT_URL => 'https://skynetaccessibilityscan.com/api/generate-plan-action-link',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => [
                        'website_id' => $websiteId,
                        'current_package_id' => $activePackageId,
                        'action' => 'violation'
                    ],
                ]);
                $response4 = curl_exec($curl4);
                curl_close($curl4);
                // Decode JSON
                $decodedLink1 = json_decode($response4, true);
                // Get the action_link or fallback to #
                $violationLink = $decodedLink1['action_link'] ?? '#';
                // Send to Twig
                $data['violation_link'] = $violationLink;
            }
        }
        $data['plans'] = $plans;
        return $data;
    }
}
