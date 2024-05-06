<?php
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 */

$aConfig = array(
    /**
     * Main Section.
     */
    'title' => 'Messenger',
    'version_from' => '13.0.4',
	'version_to' => '13.0.5',
    'vendor' => 'BoonEx',

    'compatible_with' => array(
        '13.0.0'
    ),

    /**
     * 'home_dir' and 'home_uri' - should be unique. Don't use spaces in 'home_uri' and the other special chars.
     */
    'home_dir' => 'boonex/messenger/updates/update_13.0.4_13.0.5/',
	'home_uri' => 'messenger_update_1304_1305',

	'module_dir' => 'boonex/messenger/',
	'module_uri' => 'messenger',

    'db_prefix' => 'bx_messenger_',
    'class_prefix' => 'BxMessenger',
		
	/**
     * Installation/Uninstallation Section.
     */
    'install' => [
        'execute_sql' => 1,
        'update_files' => 1,
        'update_languages' => 1,
        'clear_db_cache' => 1
    ],
	
	/**
     * Category for language keys.
     */

    'language_category' => 'Messenger',

    /**
     *  Files Section
     */

    'delete_files' => [
      'template/giphy-form.html',
      'template/video_record_form.html',
    ],
);
