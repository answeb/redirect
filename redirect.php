<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PrestaShop\Module\Redirect\Service\RedirectMatcher;

class Redirect extends Module
{
    public function __construct()
    {
        $this->name = 'redirect';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Answeb';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['locale']] = 'Redirections';
        }

        $this->tabs = [
            [
                'route_name' => 'admin_redirect_index',
                'class_name' => 'AdminRedirect',
                'visible' => true,
                'name' => $tabNames,
                'parent_class_name' => 'AdminAdvancedParameters',
                'wording' => 'Redirections',
                'wording_domain' => 'Modules.Redirect.Admin',
            ],
        ];

        parent::__construct();

        $this->displayName = $this->trans('Redirections HTTP', [], 'Modules.Redirect.Admin');
        $this->description = $this->trans(
            'Gestion de redirections HTTP (exactes et regex) avec ordre, activation temporaire et import CSV.',
            [],
            'Modules.Redirect.Admin'
        );
        $this->confirmUninstall = $this->trans('Êtes-vous sûr de vouloir désinstaller ?', [], 'Modules.Redirect.Admin');
    }

    public function install(): bool
    {
        return parent::install()
            && $this->installDb()
            && $this->registerHook('actionDispatcherBefore');
    }

    public function uninstall(): bool
    {
        return $this->uninstallDb() && parent::uninstall();
    }

    public function hookActionDispatcherBefore(array $params): void
    {
        if (($params['controller_type'] ?? null) !== Dispatcher::FC_FRONT) {
            return;
        }

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $matcher = new RedirectMatcher($this->getActiveRulesOrdered());
        $match = $matcher->match($requestPath);

        if (null === $match) {
            return;
        }

        header('Location: ' . $match['destination'], true, $match['http_code']);
        exit;
    }

    /**
     * Utilise le DB layer legacy (toujours disponible) plutôt que le conteneur
     * Symfony, qui n'est pas initialisé à ce stade du dispatch front.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getActiveRulesOrdered(): array
    {
        $cacheKey = 'redirect_rule_active_ordered';

        if (Cache::isStored($cacheKey)) {
            return Cache::retrieve($cacheKey);
        }

        $rules = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT source, destination, is_regex, http_code FROM `' . _DB_PREFIX_ . 'redirect_rule` WHERE active = 1 ORDER BY position ASC'
        ) ?: [];

        Cache::store($cacheKey, $rules);

        return $rules;
    }

    private function installDb(): bool
    {
        return Db::getInstance()->execute(require __DIR__ . '/sql/install.php');
    }

    private function uninstallDb(): bool
    {
        return Db::getInstance()->execute(require __DIR__ . '/sql/uninstall.php');
    }
}
