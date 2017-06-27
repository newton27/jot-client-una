<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    Messenger Messenger
 * @ingroup     UnaModules
 *
 * @{
 */

bx_import('BxBaseModTextInstaller');

class BxMessengerInstaller extends BxBaseModTextInstaller
{
    function __construct($aConfig)
    {
        parent::__construct($aConfig);
    }
	
	function install($aParams, $bAutoEnable = false) {
       $aResult = parent::install($aParams, $bAutoEnable);

	   if($aResult['result'])
			BxDolService::call('bx_messenger', 'add_messenger_blocks', array());

       return $aResult;
    }	
}

/** @} */
