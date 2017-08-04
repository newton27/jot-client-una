<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup	Messenger Messenger
 * @ingroup		UnaModules
 *
 * @{
 */

/**
 * Module  representation
 */
class BxMessengerTemplate extends BxBaseModNotificationsTemplate
{
	function __construct(&$oConfig, &$oDb)
	{
		parent::__construct($oConfig, $oDb);
	}
	
	/**
	* Attach js and css files for messenger depends on page with messenger block
	*@param string $sMode 
	*/
	public function loadCssJs($sMode = 'all'){
		$aCss = array('main.css', 'emoji.css');
		$aJs = array('primus.js', 'connect.js', 'messenger.js', 'config.js', 'util.js', 'jquery.emojiarea.js', 'emoji-picker.js', 'status.js'); 
		
		if ($this->_oConfig-> CNF['IS_PUSH_ENABLED'])
			array_push($aJs, 'https://cdn.onesignal.com/sdks/OneSignalSDK.js');
		
		if ($sMode == 'all'){			
			array_push($aCss, 'admin.css', 'messenger.css');
			array_push($aJs, 'columns.js');
		}	
	
		$this->addCss($aCss);
		$this->addJs($aJs); 
	}
	
	/**
	* Main function to build post messsage area with messages history
	*@param int $iProfileId logget member id
	*@param int $iLotId id of conversation. It can be empty if new talk
	*@param int $iType type of talk (Private, Public and etc..)
	*@param string $sEmptyContent  html content which may be edded to the cented of the talk when there is no messages yet
	*@return string html code 
	*/
	public function getPostBoxWithHistory($iProfileId, $iLotId = BX_IM_EMPTY, $iType = BX_IM_TYPE_PUBLIC, $sEmptyContent = ''){
		$aVars = $aJots = $aLotInfo = array();		
		$aParams = array(
			'content' => $sEmptyContent,
			'id'	  => 0,
			'name'	  => '',
			'user_id' => $iProfileId, 
			'type' => $iType,
		);
		
		$oProfile = $this -> getObjectUser($aParams['user_id']);
	    if($oProfile)	        
			$aParams['name'] = bx_js_string($oProfile -> getDisplayName());
		
		if ($aParams['id'] = $iLotId)			
			$aParams['content'] = $this -> getJotsOfLot($iProfileId, $iLotId, '', 0, '', $this -> _oConfig -> CNF['MAX_JOTS_BY_DEFAULT'], true);
		
		$aParams['url'] = '';
		if ($iType != BX_IM_TYPE_PRIVATE)			
			$aParams['url'] = isset($aLotInfo[$this -> _oConfig -> CNF['FIELD_URL']]) ? $aLotInfo[$this -> _oConfig -> CNF['FIELD_URL']] : '';	
	
		$aParams['place_holder'] =  $this->_oConfig-> CNF['SERVER_URL'] ? _t('_bx_messenger_post_area_message') : _t('_bx_messenger_server_is_not_installed');
		
		BxDolSession::getInstance()-> exists($iProfileId);			
		return $this -> parseHtmlByName('chat_window.html', $aParams);			
	}
  
  	/**
	* Main function to build post messsage block for any page
	*@param int $iProfileId logget member id
	*@param int $iLotId id of conversation. It can be empty if new talk
	*@param int $iType type of talk (Private, Public and etc..)
	*@param boolean $bShowMessanger show empty chat window if there is no history
	*@return string html code 
	*/
	public function getTalkBlock($iProfileId, $iLotId = BX_IM_EMPTY, $iType = BX_IM_TYPE_PUBLIC, $bShowMessanger = false){
		$sTitle = '';
		$aLotInfo = array();
 
		if ($iLotId){
			$aLotInfo = $this -> _oDb -> getLotInfoById($iLotId); 
				if ($this -> _oDb -> isAuthor($iLotId, $iProfileId) || isAdmin()){
					$aMenu[] = array('name' => _t("_bx_messenger_lots_menu_add_part"), 'title' => '', 'action' => "oMessenger.createLot({lot:{$iLotId}});");
					$aMenu[] = array('name' => _t("_bx_messenger_lots_menu_delete"), 'title' => '', 'action' => "if (confirm('" . bx_js_string(_t('_bx_messenger_delete_lot')) . "')) oMessenger.onDeleteLot($iLotId);");
				}		
		}
			  
		if (!empty($aLotInfo)){
			$iType = $aLotInfo[$this -> _oConfig -> CNF['FIELD_TYPE']];
			$sTitle = isset($aLotInfo[$this -> _oConfig -> CNF['FIELD_TITLE']]) && $aLotInfo[$this -> _oConfig -> CNF['FIELD_TITLE']] ? $aLotInfo[$this -> _oConfig -> CNF['FIELD_TITLE']] : $this -> getParticipantsNames($iProfileId, $iLotId);
			$sTitle = $this -> _oDb -> isLinkedTitle($iType) ? _t('_bx_messenger_linked_title', '<a href ="'. $aLotInfo[$this -> _oConfig -> CNF['FIELD_URL']] .'">' . $sTitle . '</a>') : _t($sTitle);
		}		  


		$aMenu[] = array('name' => _t("_bx_messenger_lots_menu_leave"), 'title' => '', 'action' => "if (confirm('" . bx_js_string(_t('_bx_messenger_leave_chat_confirm')) . "')) oMessenger.onLeaveLot($iLotId);");
		$aMenu[] = array('name' => _t("_bx_messenger_lots_menu_mute"), 'title' => _t('_bx_messenger_lots_menu_mute_info'), 'action' => "oMessenger.onMuteLot({$iLotId})");
		
		$iUnreadLotsJots = $this -> _oDb -> getUnreadJotsMessagesCount($iProfileId, $iLotId);		
		return $this -> parseHtmlByName('talk.html', array(
				'bx_repeat:settings' => $aMenu,
				'back_count' => $iUnreadLotsJots ? $iUnreadLotsJots : _t('_bx_messenger_menu_home'),
				'title' => $sTitle,
				'post_area' => !$bShowMessanger && empty($aLotInfo) ?
								MsgBox(_t('_bx_messenger_txt_msg_no_results')) : 
								$this-> getPostBoxWithHistory($iProfileId, $iLotId, $iType, MsgBox(_t('_bx_messenger_what_do_think')))
		));
	}
	
	/**
	* Create top of the block with participants names and statuses
	*@param int $iProfileId logget member id
	*@param int $iLotId id of conversation. It can be empty if new talk
	*/
	private function getParticipantsNames($iProfileId, $iLotId){
		$aNickNames = array();
		$sCode = '';
		
		$aParticipantsList = $this -> _oDb -> getParticipantsList($iLotId, true, $iProfileId);
		if (empty($aParticipantsList))
			return '';
		
		$iCount = count($aParticipantsList);
		$aParticipantsList = array_slice($aParticipantsList, 0, $this -> _oConfig -> CNF['PARAM_ICONS_NUMBER']); 
	
		if (count($aParticipantsList) == 1){
			$oProfile = $this -> getObjectUser($aParticipantsList[0]);
			
			if ($oProfile){ 
				$bOnline = method_exists($oProfile, 'isOnline') ? $oProfile -> isOnline(): false;
				$aNickNames['bx_repeat:users'][] = array(
										'profile_username' => $oProfile -> getUrl(),
										'username' =>  $oProfile -> getDisplayName(),
										'status' => ($bOnline ? 
													$this -> getOnlineStatus($oProfile-> id(), 1) : 
													$this -> getOnlineStatus($oProfile-> id(), 0)),
										'time_desc' => ($bOnline ? _t('_bx_messenger_online') : bx_time_js($this -> _oDb -> lastOnline($oProfile-> id()), BX_FORMAT_DATE))
									  );	
			}
			$sCode = $this -> parseHtmlByName('status_usernames.html', $aNickNames);
		}
		else
		{
			foreach($aParticipantsList as $iParticipant){				
				$oProfile = $this -> getObjectUser($iParticipant);			
				if ($oProfile) 
					$aNickNames[] = '<a href="' . $oProfile -> getUrl() . '">' . $oProfile->getDisplayName() . '</a>';			
			}
			
			$sCode = $this -> parseHtmlByName('simple_usernames.html', array('usernames' => implode(', ', $aNickNames), 'number' => _t('_bx_messenger_lot_title_participants_number', $iCount)));
		}
		
		return $sCode;
	}

	/**
	* New Conversation area with top and send area (right side block of the main window)
	*@param int $iProfileId logget member id
	*@param int $iLotId id of conversation. It can be empty if new talk
	*@param boolean $bFirstTime create private talk window with participants selector at the top
	*@return string html code
	*/
	public function getLotWindow($iProfileId = BX_IM_EMPTY, $iLotId = BX_IM_EMPTY, $bFirstTime = false){
		$aProfiles = array();
		$aParticipants = array();
		$iViewer = bx_get_logged_profile_id();
		
		if ($iProfileId){
			$aLot = $this -> _oDb -> getLotByUrlAndPariticipantsList(BX_IM_EMPTY_URL, array($iViewer, $iProfileId));
			$iLotId = empty($aLot) ? BX_IM_EMPTY : $aLot[$this -> _oConfig -> CNF['FIELD_ID']];
			
			$oProfile = $this -> getObjectUser($iProfileId);
			if ($oProfile)
				$aProfiles[] = array(
									'name' => $oProfile -> getDisplayName(),
									'title' => $oProfile -> getDisplayName(),
									'thumb' => $oProfile -> getThumb(),
									'user_id' => $iProfileId
								);
		} 
		else if ($iLotId)
		{
			$aParticipantsList = $this -> _oDb -> getParticipantsList($iLotId);
			foreach($aParticipantsList as $iParticipant){				
				if ($iViewer == $iParticipant) continue;
				if ($oProfile = $this -> getObjectUser($iParticipant))
					$aParticipants[] = array(
						'thumb' => $oProfile -> getThumb(),
						'name'	=> $oProfile->getDisplayName(),
						'id'	=> $oProfile-> id()
					);
			}
		}

		$aVars = array('bx_if:find_participants' =>	array(
															'condition' => !$bFirstTime && $iProfileId == BX_IM_EMPTY,
															'content' => array(
																'bx_repeat:participants_list' => $aParticipants,
																'bx_if:edit_mode' => 
																					array(
																						'condition' => $iLotId && $this -> _oDb -> isAuthor($iLotId, $iViewer),
																							'content' => array(
																												'lot' => $iLotId,																									
																												)
																					),																							
															)								
														),
						'bx_if:user_info' => array(
													'condition' => $iProfileId != BX_IM_EMPTY && !empty($aProfiles),
													'content' => array(
																		'bx_repeat:users' => $aProfiles,
																		'profile_id' => $iProfileId
																		)
													),			
						'chat_area' => !$bFirstTime ? $this -> getPostBoxWithHistory($iProfileId, $iLotId, BX_IM_TYPE_PRIVATE) : '' 
					 );
		
		return $this -> parseHtmlByName('private_chat_window.html', $aVars);	
	}
	
	/**
	* Search friends function which shows fiends only if member have no any talks yet
	*@param string $sParam keywords
	*@return string html code
	*/
	function getFriendsList($sParam = ''){
		$iLimit = (int)$this->_oConfig->CNF['PARAM_FRIENDS_NUM_BY_DEFAULT'] ? (int)$this->_oConfig->CNF['PARAM_FRIENDS_NUM_BY_DEFAULT'] : 5;
        
		
		if (!$sParam){
			bx_import('BxDolConnection');		
			$oConnection = BxDolConnection::getObjectInstance('sys_profiles_friends');
			if (!$oConnection)
				return '';
			
			$aFriends = $oConnection -> getConnectionsAsArray ('content', bx_get_logged_profile_id(), 0, false, 0, $iLimit + 1, BX_CONNECTIONS_ORDER_ADDED_DESC);
		} else{
			$aUsers = BxDolService::call('system', 'profiles_search', array($sParam, $iLimit), 'TemplServiceProfiles');
			if (empty($aUsers)) return array();
			
			foreach($aUsers as $iKey => $aValue)
					$aFriends[] = $aValue['value'];
		}			
		
		$aItems['bx_repeat:friends'] = array();
		foreach($aFriends as $iKey => $iValue){
			$oProfile = $this -> getObjectUser($iValue);
			$aItems['bx_repeat:friends'][] = array(	
							'title' => $oProfile -> getDisplayName(),
							'name' => $oProfile -> getDisplayName(),
							'thumb' => $oProfile -> getThumb(),
							'id' => $oProfile -> id(),
					);
		}
 
		return $this -> parseHtmlByName('friends_list.html', $aItems);
	}
	
	/**
	*  List of Lots (left side block content)
	*@param int $iProfileId logget member id
	*@param array $aLots list of lost to show
	*@param boolean $bShowTime display time(last message) in the right side of the lot
	*@return string html code
	*/
	function getLotsPreview($iProfileId, &$aLots, $bShowTime = false){
		$sContent = '';
		$iSymbolsMax = $this->_oConfig-> CNF['MAX_PREV_JOTS_SYMBOLS'];
			
		foreach($aLots as $iKey => $aLot){
			$aParticipantsList = $this -> _oDb -> getParticipantsList($aLot[$this -> _oConfig -> CNF['FIELD_ID']]);
			$aParticipantsList = array_slice($aParticipantsList, 0, $this -> _oConfig -> CNF['PARAM_ICONS_NUMBER']); 
				
			$aVars['bx_repeat:avatars'] = array();
			$aNickNames = array();
			$iParticipantsCount = count($aParticipantsList);
			foreach($aParticipantsList as $iParticipant){				
				if ($iProfileId == $iParticipant && $iParticipantsCount != 1) continue;
				
				$oProfile = $this -> getObjectUser($iParticipant);
				if ($oProfile) {
					$aVars['bx_repeat:avatars'][] = array(
						'title' => $oProfile->getDisplayName(),						
						'thumb' => $oProfile->getThumb(),
					);
				 
					$aNickNames[] = $oProfile-> getDisplayName();
			      }
			}
			
			if (empty($aNickNames)) continue;

			$iParticipantsCount = count($aNickNames);			
			if (!empty($aLot[$this -> _oConfig -> CNF['FIELD_TITLE']]))
			{
				$sTitle = _t($aLot[$this -> _oConfig -> CNF['FIELD_TITLE']]);	
				$sTitle = strmaxtextlen($sTitle, $bShowTime ? 15 : 30);
			}
			else
			{ 
				if ($iParticipantsCount > 3)
					$sTitle = implode(', ', array_slice($aNickNames, 0, 3)) . '...';
				else
					$sTitle = implode(', ', $aNickNames);
			}	
			
			$sStatus = '';				
			if ($iParticipantsCount == 1 && $oProfile && empty($aLot[$this -> _oConfig -> CNF['FIELD_TITLE']]))
				$sStatus = (method_exists($oProfile, 'isOnline') ? $oProfile -> isOnline() : false) ? 
					$this -> getOnlineStatus($oProfile-> id(), 1) : 
					$this -> getOnlineStatus($oProfile-> id(), 0) ;
			else	
				$sStatus = '<div class="bx-messenger-status count">' . $iParticipantsCount . '</div>';
	
			$sTitle = $sStatus . $sTitle;
			
			$aLatestJots = $this -> _oDb -> getLatestJot($aLot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']]);			
			
			$sMessage = '';
			if (isset($aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE']]))
					$sMessage = $aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE']];			
			
			$aVars[$this -> _oConfig -> CNF['FIELD_MESSAGE']] = strmaxtextlen($sMessage, $iSymbolsMax);
			$aVars[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']] = $aLot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']];			
			$aVars[$this -> _oConfig -> CNF['FIELD_TITLE']] = $sTitle;
			
			$aVars['sender_username'] = '';
			if ($oSender = $this -> getObjectUser($aLatestJots[$this -> _oConfig -> CNF['FIELD_MESSAGE_AUTHOR']]))
				$aVars['sender_username'] = $oSender -> id() == $iProfileId? _t('_bx_messenger_you_username_title') : $oSender -> getDisplayName();
			
			$aVars['class'] = (int)$aLot['unread_num'] ? 'unread-lot' : '';
			$aVars['bubble_class'] = (int)$aLot['unread_num'] ? '' : 'hidden';
			$aVars['count'] = (int)$aLot['unread_num'] ? (int)$aLot['unread_num'] : 0;		
			
			$aVars['bx_if:show_time'] = array(
												'condition' => $bShowTime,
												'content' => array(
														'time' => bx_time_js($aLot[$this -> _oConfig -> CNF['FIELD_ADDED']], BX_FORMAT_DATE)
													)
												);			
			
			$sContent .= $this -> parseHtmlByName('lots_briefs.html',  $aVars);
		}
		
		return $sContent;
	}
  
  	/**
	* Builds top talk area with Profiles names and Statuses
	*@param int $iProfileId logget member id
	*@param int $iStatus member status
	*@return string html code
	*/
	private function getOnlineStatus($iProfileId, $iStatus){
		switch($iStatus){
			case 0:
					$sTitle = _t('_bx_messenger_offline');
					$sClass = 'offline';
				break;
			case 2:
					$sTitle = _t('_bx_messenger_away');
					$sClass = 'away';
				break;
			default:
					$sTitle = _t('_bx_messenger_online');
					$sClass = 'online';
		}
	
		return $this -> parseHtmlByName('online_status.html', array(
			'id' => (int)$iProfileId,
			'title' => $sTitle,
			'class' => $sClass
		));
	}
	
	/**
	* Get jots list by specified criteria
	*@param int $iProfileId logget member id
	*@param int $iLotId 
	*@param string $sUrl of the lot block
	*@param int $iStart jot's id from which to load the messsages
	*@param string $sLoad type of the load (new jots or history) 
	*@param int $iLimit number of jots
	*@param boolean $bDisplay make jots visible before loading
	*@return string html code
	*/
	public function getJotsOfLot($iProfileId, $iLotId = 0, $sUrl = '', $iStart = 0, $sLoad = '', $iLimit = 0, $bDisplay = false){
		$aLotInfo = $this -> _oDb -> getLotByIdOrUrl($iLotId, $sUrl, $iProfileId);
		if (empty($aLotInfo))
						return '';
				
		$aJots = $this -> _oDb -> getJotsByLotId($aLotInfo[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']], $iStart, $sLoad, $iLimit); 
		if (empty($aJots))
						return '';
					
		$aVars['bx_repeat:jots'] = array(); 
		foreach($aJots as $iKey => $aJot){
			$oProfile = $this -> getObjectUser($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AUTHOR']]);
				if ($oProfile) {
					$aVars['bx_repeat:jots'][] = array(
						'title' => $oProfile->getDisplayName(),
						'time' => bx_time_js($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ADDED']], BX_FORMAT_TIME),
						'url' => $oProfile->getUrl(),
						'thumb' => $oProfile->getThumb(),
						'id' => $aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_ID']],
						'message' => nl2br($this -> _oConfig -> bx_linkify($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE']], 'class="bx-link"')),
						'attachment' => !empty($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT']]) ? 
												$this -> getJotAsAttachment($this -> _oConfig -> isJotLink($aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AT']])['id']) 
												: '',
						'display' => !$bDisplay ? 'style="display:none;"' : '',
						'bx_if:delete' => array(
												'condition' => isAdmin() || $aJot[$this -> _oConfig -> CNF['FIELD_MESSAGE_AUTHOR']] == $iProfileId,
												'content'	=> array()
												)
					);				
				  }
		}	
		return $this -> parseHtmlByName('jots.html',  $aVars);
	}

	/**
	* Builds left column with content 
	*@param int $iProfileId logget member id
	*@param int $iTalkPerson id of profile to talk with 
	*@return string html code
	*/
	public function getLotsColumn($iProfileId, $iTalkPerson = 0){
		$sContent = '';
		
		$aMyLots = $this -> _oDb -> getMyLots($iProfileId);	
		if (!empty($aMyLots))
				$sContent = $this -> getLotsPreview($iProfileId, $aMyLots);
		else 
				$sContent = $this -> getFriendsList();

		$aMyLotsTypes = $this -> _oDb -> getMemberLotsTypes($iProfileId);		
		$aVars = array(
			'items' => $sContent,
			'bx_repeat:menu' => array(
										array('menu_title' => _t("_bx_messenger_lots_type_all"), 'type' => 0, 'count' => '')
									 ),
			'profile' => (int)$iTalkPerson,
		);
		
		$aMenu = $this -> _oDb -> getAllLotsTypes();
		foreach($aMenu as $iKey => $aValue){
			$sName	= $aValue[$this -> _oConfig -> CNF['FIELD_TYPE_NAME']];
			$iCount	= isset($aMyLotsTypes[$aValue[$this -> _oConfig -> CNF['FIELD_TYPE_ID']]]) ? $aMyLotsTypes[$aValue[$this -> _oConfig -> CNF['FIELD_TYPE_ID']]] : 0;
			$aVars['bx_repeat:menu'][] = array('menu_title' => _t("_bx_messenger_lots_type_{$sName}"), 'type' => $aValue[$this -> _oConfig -> CNF['FIELD_TYPE_ID']], 'count' => $iCount ? "($iCount)" : '');
		}	
		
		return $this -> parseHtmlByName('lots_list.html', $aVars);
	}

	/**
	* Init js files depends on administratin settings
	*@param int $iProfileId logget member id
	*@return string html code
	*/
	public function loadConfig($iProfileId){
		$aUrlInfo = parse_url(BX_DOL_URL_ROOT); 
		$aVars = array(				
			'profile_id' => (int)$iProfileId,
			'server_url' => $this->_oConfig-> CNF['SERVER_URL'],
			'online' => bx_js_string(_t('_bx_messenger_online')),
			'offline' => bx_js_string(_t('_bx_messenger_offline')),
			'away' => bx_js_string(_t('_bx_messenger_away')),
			'jot_remove_confirm' => bx_js_string(_t('_bx_messenger_remove_jot_confirm')),			
			'message_length' => (int)$this->_oConfig-> CNF['MAX_SEND_SYMBOLS'] ? (int)$this->_oConfig-> CNF['MAX_SEND_SYMBOLS'] : 0,
			'ip' => gethostbyname($aUrlInfo['host']),
			'smiles' => (int)$this->_oConfig-> CNF['CONVERT_SMILES'],
			'bx_if:onsignal' => array(
										'condition'	=> $this->_oConfig-> CNF['IS_PUSH_ENABLED'],
										'content' => array(
											'one_signal_api' => $this->_oConfig-> CNF['PUSH_APP_ID'],
											'short_name' => $this->_oConfig-> CNF['PUSH_SHORT_NAME'],
											'safari_key' => $this->_oConfig-> CNF['PUSH_SAFARI_WEB_ID'],
											'jot_chat_page_url' => $this->_oConfig-> CNF['URL_HOME'],
											'notification_request' => bx_js_string(_t('_bx_messenger_notification_request')),
											'notification_request_yes' => bx_js_string(_t('_bx_messenger_notification_request_yes')),
											'notification_request_no' => bx_js_string(_t('_bx_messenger_notification_request_no')),
											'profile_id' => (int)$iProfileId,											
										)
									)
		);

		return $this -> parseHtmlByName('config.html', $aVars);
	}
	
	/**
	* Crate profile html template for jot and is used when member posts a message
	*@param int $iProfileId logget member id
	*@return string html code
	*/
	function getMembersJotTemplate($iProfileId){
		if (!$iProfileId) return '';
		
		$oProfile = $this -> getObjectUser($iProfileId);
		if ($oProfile) {
				$aVars['bx_repeat:jots'][] = array(
							'title' => $oProfile->getDisplayName(),
							'time' => bx_time_js(time(), BX_FORMAT_TIME),
							'url' => $oProfile->getUrl(),
							'thumb' => $oProfile->getThumb(),
							'display' => 'style="display:table-row;"',
							'bx_if:delete' => array(
													'condition' => true,
													'content'	=> array()
													),
							'id' => 0,
							'message' => '',
							'attachment' => '',
					);						
					
			 return $this -> parseHtmlByName('jots.html',  $aVars);
		}
		
		return '';
	}
	
	/**
	* Returns Jot content as attachment()repost for a message
	*@param int $iJotId jot id
	*@return string html code
	*/
	function getJotAsAttachment($iJotId){
		$sHTML = '';
		$aJot = $this -> _oDb -> getJotById($iJotId);
		if (!empty($aJot)){
			$aLotsTypes = $this -> _oDb -> getLotsTypesPairs();
			$oProfile = $this -> getObjectUser($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_AUTHOR']]);	
			$aLotInfo =  $this -> _oDb -> getLotByJotId($iJotId, false);
			$sHTML = $this -> parseHtmlByName('repost.html', array(
					'icon' => $oProfile -> getIcon(),
					'message' => $aJot[$this->_oConfig->CNF['FIELD_MESSAGE']],
					'username' => $oProfile -> getDisplayName(),
					'message_type' => !empty($aLotInfo) && isset($aLotInfo[$this->_oConfig->CNF['FIELD_TYPE']])? _t('_bx_messenger_lots_message_type_' . $aLotsTypes[$aLotInfo[$this->_oConfig->CNF['FIELD_TYPE']]]) : '',
					'date' => bx_process_output($aJot[$this->_oConfig->CNF['FIELD_MESSAGE_ADDED']], BX_DATA_DATETIME_TS),
				));
		}
		
		return $sHTML;
	}
	
	/**
	* Returns user profile even if it was removed from the site 
	*@param int $iProfileId profile id 
	*@return object instance of Profile
	*/
	private function getObjectUser($iProfileId = 0)
    {
    	bx_import('BxDolProfile');
        $oProfile = BxDolProfile::getInstance($iProfileId);
        if (!$oProfile) {
            bx_import('BxDolProfileUndefined');
            $oProfile = BxDolProfileUndefined::getInstance();
        }

        return $oProfile;
    }
	

}

/** @} */
