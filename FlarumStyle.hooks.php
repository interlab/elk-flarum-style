<?php

// SiteDispatcher.class.php ->

class FlarumStyle
{
    public static function integrate_action_frontpage(&$default_action)
    {
		$default_action = array(
			'file' => CONTROLLERDIR . '/FlarumStyle.controller.php',
			'controller' => 'FlarumStyle_Controller',
			'function' => 'action_index'
		);

        if (file_exists(SOURCEDIR . '/libs/vendor/autoload.php')) {
            require_once SOURCEDIR . '/libs/vendor/autoload.php';
        }
        require_once SUBSDIR.'/FlarumStyle.subs.php';
    }

    public static function integrate_actions(&$actionArray, &$adminActions)
    {
        $actionArray['flarumstyle'] = ['FlarumStyle.controller.php', 'FlarumStyle_Controller', 'action_index'];
        //loadLanguage('FlarumStyle'); 
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
}
