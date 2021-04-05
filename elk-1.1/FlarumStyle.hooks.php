<?php

class FlarumStyle
{
    public function pre_dispatch() {}

    // public static function integrate_actions(&$actionArray, &$adminActions)
    public static function integrate_actions(&$actionArray)
    {
        $actionArray['boardindex'] = ['BoardIndex_Controller', 'action_boardindex'];
        $actionArray['flarumstyle'] = ['FlarumStyle_Controller', 'action_index'];
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

    // /.../sources/subs/Menu.subs.php - 83
    public static function integrate_admin_areas(&$menuData, &$menuOptions)
    {
        // global $txt;

        loadLanguage('FlarumStyleAdmin');
        $menuData['config']['areas']['addonsettings']['subsections']['flarumstyle'] = ['FlarumStyle'];
    }

    // /.../sources/admin/AddonSettings.controller.php - 68
    // /.../sources/subs/Action.class.php - 99
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

    // self - 61
    public static function addon_settings()
    {
        global $txt, $context, $scripturl;

        // Lets build a settings form
        require_once(SUBSDIR . '/SettingsForm.class.php');

        $fsSettings = new Settings_Form();

        // All the options, well at least some of them!
        $config_vars = [
            ['check', 'flarumstyle_enabled', 'postinput' => ''],
            ['check', 'flarumstyle_show_search', 'postinput' => ''],
            ['check', 'flarumstyle_show_who', 'postinput' => ''],
            ['check', 'flarumstyle_show_num_likes', 'postinput' => ''],
            ['int', 'flarumstyle_num_topics', 'postinput' => ''],
            ['select', 'flarumstyle_message_icon', [
                'flarumstyle_first_icon' => $txt['flarumstyle_first_message'],
                'flarumstyle_last_icon' => $txt['flarumstyle_last_message']
            ]],
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
