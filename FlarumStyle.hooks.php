<?php

// SiteDispatcher.class.php ->

class FlarumStyle
{
    public static $enable = false;

    public static function integrate_action_frontpage(&$default_action)
    {
        global $modSettings;

        self::$enable = (bool) $modSettings['flarumstyle_enabled'];
        if (!self::$enable) {
            return;
        }

		$default_action = [
			'file' => CONTROLLERDIR . '/FlarumStyle.controller.php',
			'controller' => 'FlarumStyle_Controller',
			'function' => 'action_index'
		];

        if (file_exists(SOURCEDIR . '/libs/vendor/autoload.php')) {
            require_once SOURCEDIR . '/libs/vendor/autoload.php';
        }
        require_once SUBSDIR.'/FlarumStyle.subs.php';

        loadLanguage('FlarumStyle');
    }

    public static function integrate_actions(&$actionArray, &$adminActions)
    {
        if (!self::$enable) {
            return;
        }

        $actionArray['flarumstyle'] = ['FlarumStyle.controller.php', 'FlarumStyle_Controller', 'action_index'];
        $actionArray['boardindex'] = ['BoardIndex.controller.php', 'BoardIndex_Controller', 'action_index'];
    }

    // Boards.subs.php
    public static function integrate_board_tree_query(&$query)
    {
        $query['select'] = ', b.flarum_board_color';
        $query['join'] = '';
    }

    // Boards.subs.php
    public static function integrate_board_tree($row)
    {
        global $boards;

        $boards[$row['id_board']]['flarum_board_color'] = $row['flarum_board_color'];
    }

    // ManageBoards.controller.php
    public static function integrate_save_board($board_id, &$boardOptions)
    {
        $boardOptions['flarum_board_color'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $_POST['flarum_board_color']);
    }

    // Boards.subs.php
    public static function integrate_modify_board($board_id, $boardOptions, &$boardUpdates, &$boardUpdateParameters)
    {
        if (isset($boardOptions['flarum_board_color']))
        {
            $boardUpdates[] = 'flarum_board_color = {string:flarum_board_color}';
            $boardUpdateParameters['flarum_board_color'] = $boardOptions['flarum_board_color'];
        }
    }

    // integrate_admin_areas
    // Menu.subs.php - 87
    public static function integrate_admin_areas(&$admin_areas)
    {
        global $txt;

        $admin_areas['config']['areas']['addonsettings']['subsections']['flarumstyle'] = ['FlarumStyle'];
    }

    public static function integrate_sa_modify_modifications(&$subActions)
    {
        $subActions['flarumstyle'] = [
            'file' => 'FlarumStyle.hooks.php',
            'dir' => SOURCEDIR,
            'controller' => 'FlarumStyle',
            'function' => 'addon_settings',
            'permission' => 'admin_forum',
        ];
    }

    public static function addon_settings()
    {
        global $txt, $context, $scripturl, $modSettings, $boardurl;

        // Lets build a settings form
        require_once(SUBSDIR . '/SettingsForm.class.php');

        $fsSettings = new Settings_Form();

        $txt['flarumstyle_enabled'] = 'Enable Flarum Style addon';

        // All the options, well at least some of them!
        $config_vars = [
            ['check', 'flarumstyle_enabled', 'postinput' => ''],
        ];

        // Load the settings to the form class
        $fsSettings->settings($config_vars);

        // Saving?
        if (isset($_REQUEST['save']))
        {
            checkSession();

            Settings_Form::save_db($config_vars);

            redirectexit('action=admin;area=addonsettings;sa=flarumstyle');
        }

        // Continue on to the settings template
        $context['page_title'] = 'FlarumStyle addon';
        // $context['settings_title'] = $txt['flarumstyle_title'];
        $context['post_url'] = $scripturl . '?action=admin;area=addonsettings;sa=flarumstyle;save';

        // if (!empty($modSettings['bla']))
            // updateSettings(array('bla' => 'bla'));

        Settings_Form::prepare_db($config_vars);
    }
}
