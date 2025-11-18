<?php

namespace Modules\NobilikGroupedTags\Providers;

use Illuminate\Support\ServiceProvider;

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
        // $this->registerFactories(); // Если нужны фабрики
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Хуки модуля.
     */
    public function hooks()
    {
        // ---------------------------------------------------------------------
        // 1. ЛОГИКА СОБЫТИЙ (Свойства 4 и 5)
        // ---------------------------------------------------------------------
        $listener = 'NobilikGroupedTags\Listeners\ConversationListener';
        
        // Свойство 1: copy_to_new_conversation (Клиент присылает письмо)
        \Eventy::addAction('mail.received', $listener.'@handleMailReceived', 20, 1); // Приоритет 20
        
        // Свойство 2 (часть 1): auto_apply (Беседа создана ВРУЧНУЮ сотрудником)
        \Eventy::addAction('conversation.created_by_user', $listener.'@handleConversationCreatedByUser', 20, 2); 
        
        // Свойство 2 (часть 2): auto_apply (Беседа создана АВТОМАТИЧЕСКИ, первое действие сотрудника)
        // Используем ответ или заметку.
        \Eventy::addAction('conversation.user_replied', $listener.'@handleUserAction', 20, 2);
        \Eventy::addAction('conversation.note_added', $listener.'@handleUserAction', 20, 2);
        
        // ---------------------------------------------------------------------
        // 2. ИНТЕРФЕЙС И НАСТРОЙКИ (Свойство 6)
        // ---------------------------------------------------------------------

        // Добавляем CSS и JS файлы модуля в layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(GT_MODULE).'/css/module.css';
            return $styles;
        });

        // 1. Добавление модуля в список для генерации laroute.js
        \Eventy::addFilter('laroute_generate_paths', function($paths) {
            $modulePath = \Module::getPublicPath('NobilikGroupedTags') . '/js/laroute.js';
            $routesPath = \Module::getModulePath('NobilikGroupedTags') . '/Http/routes.php';
            
            $paths[] = [
                'routes' => $routesPath,
                'path' => $modulePath,
            ];
            
            return $paths;
        });

        // ... Ваш хук для загрузки module.js ...
        \Eventy::addFilter('javascripts', function($javascripts) {
            $laroutePublicPath = '/modules/nobilikgroupedtags/js/laroute.js';
            $modulePublicPath = '/modules/nobilikgroupedtags/js/module.js';

            // Проверяем, существует ли laroute.js
            $larouteSystemPath = \Module::getPublicPath('NobilikGroupedTags') . '/js/laroute.js';
            if (file_exists($larouteSystemPath)) {
                // ... log ...
                $javascripts[] = $laroutePublicPath;
            }
            
            // Загружаем основной файл модуля
            // ... log ...
            $javascripts[] = $modulePublicPath;
            
            return $javascripts;
        });

        // // Добавляем пункт в меню настроек почтового ящика (Mailbox Menu).
        // // Это позволяет Администратору перейти к настройкам модуля.
        // \Eventy::addAction('mailboxes.settings.menu', function($mailbox) {
        //     // Проверка прав Администратора
        //     if (\Auth::user()->isAdmin()) {
        //         // Предполагаем, что у вас будет partials/settings_menu.blade.php
        //         echo \View::make('nobilikgroupedtags::partials/settings_menu', ['mailbox' => $mailbox])->render();
        //     }
        // }, 25);

        // // Определяем, разрешено ли пользователю видеть меню настроек почтового ящика.
        // // Достаточно, чтобы пользователь был Администратором.
        // \Eventy::addFilter('user.can_view_mailbox_menu', function($value, $user) {
        //     return $value || $user->isAdmin();
        // }, 20, 2);


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
    }

    /**
     * Регистрация роутов модуля. Вызывается из главного GroupedTagsModule.php
     */
    public function registerRoutes()
    {
        \Route::group([
            'middleware' => ['web', 'auth.user'],
            'namespace' => 'Modules\NobilikGroupedTags\Http\Controllers'
        ], function() {
            require __DIR__ . '/../Http/routes.php';
        });
    }

    /**
     * Регистрация Service Provider.
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
            __DIR__.'/../Config/config.php' => config_path('groupedtags.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'groupedtags'
        );
    }

    /**
     * Регистрация представлений (Views).
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/groupedtags');
        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/groupedtags';
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

        
    // ... (можно добавить registerFactories, registerCommands, provides)
}

