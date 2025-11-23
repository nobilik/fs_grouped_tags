<?php

namespace Modules\NobilikGroupedTags\Providers;

use Illuminate\Support\ServiceProvider;
use App\Conversation;
use App\Events\CustomerCreatedConversation;
use Modules\NobilikGroupedTags\Observers\ConversationObserver; 

use Illuminate\Support\Facades\Log;

// Определяем алиас модуля
define('GT_MODULE', 'nobilikgroupedtags');

class GroupedTagsServiceProvider extends ServiceProvider
{
    /**
     * Указывает, отложена ли загрузка провайдера.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Запуск событий приложения.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();

    }

    /**
     * Хуки модуля.
     */
    public function hooks()
    {

        // Добавляем CSS и JS файлы модуля в layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(GT_MODULE).'/css/module.css';
            $styles[] = \Module::getPublicPath(GT_MODULE).'/css/tagmanager.css';
            return $styles;
        });

        // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(GT_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(GT_MODULE).'/js/module.js';
            $javascripts[] = \Module::getPublicPath(GT_MODULE).'/js/conversation.js';
            $javascripts[] = \Module::getPublicPath(GT_MODULE) .'/js/tag-delete-guard.js';
            return $javascripts;
        });


        // ---------------------------------------------------------------------
        // 1. ЛОГИКА СОБЫТИЙ (Свойства 4 и 5)
        // ---------------------------------------------------------------------
        $listenerClass = \Modules\NobilikGroupedTags\Listeners\ConversationListener::class;

        \Eventy::addAction('conversation.created_by_customer', function($conversation, $thread, $customer) use ($listenerClass) {
            $listener = new $listenerClass();
            $listener->handleMailReceived($conversation, $thread, $customer);
        }, 20, 3);


        // --- ИНТЕГРАЦИЯ В ГЛОБАЛЬНОЕ МЕНЮ НАСТРОЕК (после Tags) ---

        // 1. Добавляем пункт в меню "Управление" (Manage), после пункта "Tags".
        // Приоритет 21 гарантирует, что он будет идти сразу после Tags (у которого приоритет 20).
        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            $user = auth()->user();
            // Проверка прав Администратора
            if ($user->isAdmin() || $user->hasPermission(\App\User::PERM_EDIT_TAGS)) {
                ?>
                    <li class="<?php echo \Helper::menuSelectedHtml('grouped-tags') ?>">
                        <a href="<?php echo route('grouped-tags.settings') ?>"><?php echo __('Grouped Tags') ?></a>
                    </li>
                <?php
            }
        }, 21); // Устанавливаем приоритет 21 для размещения после Tags

        // 2. Выделяем пункт меню, если мы находимся на странице нашего модуля.
        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['manage']['grouped-tags'] = [
                'grouped-tags.settings',
                'grouped-tags.store',
                'grouped-tags.update',
                'grouped-tags.destroy',
                'grouped-tags.attach',
                'grouped-tags.detach',
            ];

            return $menu;
        });

        // Встраиваем модальное окно выбора обязательных тегов в футер
        \Eventy::addAction('layout.body_bottom', function() {
            echo view('nobilikgroupedtags::partials.required_tags_modal')->render();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Регистрация конфигурации.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('nobilikgroupedtags.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'nobilikgroupedtags'
        );
    }

    /**
     * Регистрация представлений (Views).
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/nobilikgroupedtags');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/nobilikgroupedtags';
        }, \Config::get('view.paths')), [$sourcePath]), 'nobilikgroupedtags');
    }

    /**
     * Регистрация переводов.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }
        
    // /**
    //  * https://github.com/nWidart/laravel-modules/issues/626
    //  * https://github.com/nWidart/laravel-modules/issues/418#issuecomment-342887911
    //  * @return [type] [description]
    //  */
    // public function registerCommands()
    // {
    //     $this->commands([
    //         \Modules\NobilikGroupedTags\Console\Process::class
    //     ]);
    // }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}

