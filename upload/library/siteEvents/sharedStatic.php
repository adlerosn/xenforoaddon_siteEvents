<?php

class siteEvents_sharedStatic {
	public static function mysql_escape_mimic_fromPhpDoc($inp)
	{//http://php.net/manual/pt_BR/function.mysql-real-escape-string.php
		return str_replace(array('\\',    "\0",  "\n",  "\r",   "'",   '"', "\x1a"),
						   array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'),
						   $inp);
	}
	
	public static $singletonPermissionSet = array();
	public static function getUserPermissions($uid){
		$uid = intval($uid);
		if(array_key_exists($uid,self::$singletonPermissionSet)) return self::$singletonPermissionSet[$uid];
		$userModel = XenForo_Model::create('XenForo_Model_User');
		$user = $userModel->getUserById($uid);
		$pci = array_key_exists('permission_combination_id',$user) ? $user['permission_combination_id'] : null;
		$gpc = array_key_exists('global_permission_cache',$user) ? $user['global_permission_cache'] : null;
		$permarr = array();
		if (!$gpc){
			$permarr = XenForo_Model::create('XenForo_Model_Permission')->rebuildPermissionCombinationById($pci);
			if(!$permarr){$permarr = array();};
		}else{
			if($gpc){
				$permarr = XenForo_Permission::unserializePermissions($gpc);
				if(!$permarr){$permarr = array();};
			}else{
				$permarr = array();
			}
		}
		self::$singletonPermissionSet[$uid] = $permarr;
		return $permarr;
	}
	
	public static function userHasPermission($uid,$permGroupId,$permId){
		$permissions = self::getUserPermissions($uid);
		return XenForo_Permission::hasPermission($permissions,$permGroupId,$permId);
	}
	
	public static function createTableDB(){
		$dbc=XenForo_Application::get('db');
		$q='CREATE TABLE IF NOT EXISTS kiror_site_events (
		eventid SERIAL,
		descr VARCHAR(50),
		infourl TEXT,
		author INT,
		addedtime INT,
		starttime INT,
		endtime INT,
		visibility INT,
		PRIMARY KEY (eventid)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;';
		$dbc->query($q);
	}

	public static function dropTableDB(){
		$dbc=XenForo_Application::get('db');
		$q='DROP TABLE IF EXISTS kiror_site_events;';
		$dbc->query($q);
	}
	
	public static function deleteEvent($eid){
		$eid = intval($eid);
		$dbc=XenForo_Application::get('db');
		$q='DELETE FROM `kiror_site_events` WHERE eventid='.$eid.';';
		$dbc->query($q);
	}
	
	public static function insertEvent($name,$url,$uid,$start,$end,$visibility){
		$uid = intval($uid);
		$start = intval($start);
		$end = intval($end);
		$visibility = intval($visibility);
		$name = self::mysql_escape_mimic_fromPhpDoc($name);
		$url = self::mysql_escape_mimic_fromPhpDoc($url);
		$dbc=XenForo_Application::get('db');
		$q='INSERT INTO `kiror_site_events` (descr,infourl,author,addedtime,starttime,endtime,visibility) VALUES
		(\''.$name.'\',\''.$url.'\','.$uid.','.time().','.$start.','.$end.','.$visibility.');';
		$dbc->query($q);
	}
	
	public static function getAllEvents(){
		$dbc=XenForo_Application::get('db');
		$q='SELECT eventid,descr,infourl,author,addedtime,starttime,endtime,visibility
			FROM `kiror_site_events`
			ORDER BY starttime ASC;';
		return $dbc->fetchAll($q);
	}
	
	public static function getAllViewableEvents($viewLevel){
		$viewLevel = intval($viewLevel);
		$dbc=XenForo_Application::get('db');
		$q='SELECT eventid,descr,infourl,author,addedtime,starttime,endtime,visibility,(starttime<'.time().' AND endtime>'.time().') AS happening
			FROM `kiror_site_events`
			WHERE visibility<='.$viewLevel.'
			ORDER BY starttime ASC;';
		return $dbc->fetchAll($q);
	}
	
	public static function getNotOldViewableEvents($viewLevel){
		$viewLevel = intval($viewLevel);
		$dbc=XenForo_Application::get('db');
		$q='SELECT eventid,descr,infourl,author,addedtime,starttime,endtime,visibility,(starttime<'.time().' AND endtime>'.time().') AS happening
			FROM `kiror_site_events`
			WHERE visibility<='.$viewLevel.' AND endtime>='.time().'
			ORDER BY starttime ASC;';
		return $dbc->fetchAll($q);
	}
	
	public static function getRecentViewableEvents($viewLevel,$limit=5){
		$viewLevel = intval($viewLevel);
		$limit = intval($limit);
		$dbc=XenForo_Application::get('db');
		$q='SELECT eventid,descr,infourl,author,addedtime,starttime,endtime,visibility,(starttime<'.time().' AND endtime>'.time().') AS happening
			FROM `kiror_site_events`
			WHERE visibility<='.$viewLevel.' AND endtime>='.time().'
			ORDER BY starttime ASC
			LIMIT '.$limit.';';
		return $dbc->fetchAll($q);
	}
	
	public static function getEvent($eid){
		$eid = intval($eid);
		$dbc=XenForo_Application::get('db');
		$q='SELECT eventid,descr,infourl,author,addedtime,starttime,endtime,visibility,(starttime<'.time().' AND endtime>'.time().') AS happening
			FROM `kiror_site_events`
			WHERE eventid='.$eid.';';
		return $dbc->fetchRow($q);
	}
	
	public static function getViewLevel($visitor){
		$canView = 0;
		if($visitor->hasPermission('siteEventGrp','viewanyone'     )) $canView = 1;
		if($visitor->hasPermission('siteEventGrp','viewregistered' )) $canView = 2;
		if($visitor->hasPermission('siteEventGrp','viewvip'        )) $canView = 3;
		if($visitor->hasPermission('siteEventGrp','viewlowstaff'   )) $canView = 4;
		if($visitor->hasPermission('siteEventGrp','viewmediumstaff')) $canView = 5;
		if($visitor->hasPermission('siteEventGrp','viewhighstaff'  )) $canView = 6;
		if($visitor->hasPermission('siteEventGrp','manageEvents'   )) $canView = 7;
		return $canView;
	}
	
	public static function pairJustified($l,$r){
		return '<dl><dt>'.$l.'</dt><dd>'.$r.'</dd></dl>';
	}
	
	public static $viewLabels = array( 1 => 'Anyone',
									   2 => 'Registered',
									   3 => 'VIP',
									   4 => 'Low staff',
									   5 => 'Medium staff',
									   6 => 'High staff'
	);
	
	public static function totime($yr,$mo,$dy,$hr=0,$mn=0,$sc=0,$tz='+0000'){
		return strtotime(''.$yr.'-'.$mo.'-'.$dy.'T'.$hr.':'.$mn.':'.$sc.$tz);
	}
	
	public static function renderDateFromInt_($date){
		$date = intval($date);
		$difftime = time() - $date;
		$uts_min =          60;
		$uts_hor =        3600;
		$uts_day =   1*24*3600;
		$uts_wek =   7*24*3600;
		$uts_mon =  30*24*3600;
		$uts_yer = 365*24*3600;
		$diff_min = abs($difftime)/$uts_min;
		$diff_hor = abs($difftime)/$uts_hor;
		$diff_day = abs($difftime)/$uts_day;
		$diff_wek = abs($difftime)/$uts_wek;
		$diff_mon = abs($difftime)/$uts_mon;
		$diff_yer = abs($difftime)/$uts_yer;
		$label = 'minutes';
		$num = $diff_min;
		if($difftime==0){
			$label = 'now';
			$num = '';
		}
		if((abs($difftime))>=1){
			$label = 'seconds';
			$num = abs($difftime);
		}
		if($diff_min>=2){
			$label = 'minutes';
			$num = $diff_min;
		}
		if($diff_hor>=2){
			$label = 'hours';
			$num = $diff_hor;
		}
		if($diff_day>=2){
			$label = 'days';
			$num = $diff_day;
		}
		if($diff_wek>=2){
			$label = 'weeks';
			$num = $diff_wek;
		}
		if($diff_mon>=2){
			$label = 'months';
			$num = $diff_mon;
		}
		if($diff_yer>=2){
			$label = 'years';
			$num = $diff_yer;
		}
		if($difftime>0){
			return '<span title="'.date('r',$date).'">'.round($num).' '.$label.' ago</span>';
		}
		else if($difftime<0){
			return '<span title="'.date('r',$date).'">in '.round($num).' '.$label.'</span>';
		}
		else{
			return '<span title="'.date('r',$date).'"> '.round($num).' '.$label.'</span>';
		}
	}
	
	public static function renderDateFromInt($contents, array $params, XenForo_Template_Abstract $template){
		$date = intval($params['date']);
		return self::renderDateFromInt_($date);
	}
	
	public static function renderSideBarEvents($contents, array $params, XenForo_Template_Abstract $template){
		$xfopt = XenForo_Application::get('options');
		$max = $xfopt->numRecentViewableEvents;
		$canView = self::getViewLevel(XenForo_Visitor::getInstance());
		$torender = self::getRecentViewableEvents($canView,$max);
		if(count($torender)<=0){
			return '<div class="pairsJustified">'.self::pairJustified($xfopt->noDisplayableEvents,'').'</div>';
		}
		$html='';
		$html.='<div class="clickerColumns oneColumns">';
		foreach($torender as $event){
			$html.='<li class="'.(($event['happening'])?'active':'').'">';
			$html.='<a href="'.$event['infourl'].'" target="_blank">';
			$html.='<span style="margin-left: 0px;" class="title">';
			$html.=htmlspecialchars($event['descr']);
			$html.='</span>';
			$html.='<span style="margin-left: 0px;" class="description">';
			$html.='Start: ';
			$html.=siteEvents_sharedStatic::renderDateFromInt_($event['starttime']);
			$html.='.';
			$html.='<br />';
			$html.='End: ';
			$html.=siteEvents_sharedStatic::renderDateFromInt_($event['endtime']);
			$html.='.';
			$html.='</span>';
			$html.='</a>';
			$html.='</li>';
		}
		$html.='</div>';
		return $html;
	}
}
