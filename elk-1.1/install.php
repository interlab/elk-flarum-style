<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK')) {
    require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('ELK')) {
    die('<b>Error:</b> Cannot install - please verify you put this in the same place as ELK\'s index.php.');
}

// global $db_prefix, $db_package_log;

// $db = database();
$db_table = db_table();

$boards = $db_table->db_list_columns('{db_prefix}boards', false);

// ALTER TABLE `{db_prefix}boards` ADD `flarum_board_color` VARCHAR(100) NOT NULL DEFAULT '' ;
if (!in_array('flarum_board_color', $boards)) {
    $db_table->db_add_column('{db_prefix}boards', [
        'name' => 'flarum_board_color', 'type' => 'varchar', 'size' => '100', 'null' => false, 'default' => '',
    ]);
}


