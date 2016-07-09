<?php
class siteEvents_actions extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex(){
		$visitor = XenForo_Visitor::getInstance();
		$vl = siteEvents_sharedStatic::getViewLevel($visitor);
		$uid = $visitor['user_id'];
		$action = $this->_input->filterSingle('action',XenForo_Input::STRING);
		if($action=='create')          return $this->actionCreate();
		if($action=='submitNew')       return $this->actionSubmit();
		if($action=='submitedToCheck') return $this->actionSubmitToCheck();
		if($action=='submitChecked')   return $this->actionSubmitChecked();
		if($action=='delete')          return $this->actionDelete();
		if($action=='confirmDel')      return $this->actionConfirmDeletion();
		$viewParams=array('hidingOldies'=>true);
		$limitless = $this->_input->filterSingle('old',XenForo_Input::INT);
		if($limitless==1) $viewParams['hidingOldies'] = false;
		if($limitless==1)
			if($visitor->hasPermission('siteEventGrp','viewOldies'))
				$viewParams['hidingOldies'] = false;
			else if($vl>6)
				$viewParams['hidingOldies'] = false;
			else
				return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		$toDisplay = array();
		if($limitless==1) $toDisplay = siteEvents_sharedStatic::getAllViewableEvents($vl);
		else              $toDisplay = siteEvents_sharedStatic::getNotOldViewableEvents($vl);
		if($vl>6) $viewParams['canDelete'] = true;
		else      $viewParams['canDelete'] = false;
		$viewParams['putinloop'] = $toDisplay;
		return $this->responseView(
            'XenForo_ViewPublic_Base',
            'kiror_site_events_view',
            $viewParams
        );
	}
	
	public function actionCreate(){
		$visitor = XenForo_Visitor::getInstance();
		$vl = siteEvents_sharedStatic::getViewLevel($visitor);
		if($vl<=6) return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		$hours = array();
		$minutes = array();
		$days = array();
		$years = array();
		$months = array();
		$timezones = array();
		for($i = 1 ; $i <= 12 ; $i++){
			$months[$i] = new XenForo_Phrase('month_'.$i);
		}
		for($i = 1970 ; $i <= 2032 ; $i++){
			$years[$i] = $i;
		}
		for($i = 1 ; $i <= 32 ; $i++){
			$days[$i] = $i;
		}
		for($i = -13 ; $i <= 14 ; $i++){
			$signal = ($i>=0)?'+':'';
			$inc = ''.$i;
			if($inc[0]=='-'){
				if(strlen(substr($inc,1))<2) $inc='-0'.substr($inc,1);
			}else{
				if(strlen($inc)<2) $inc='0'.$inc;
			}
			$timezones[$i] = $signal.$inc.':00';
		}
		//asort($timezones);
		for($i = 0 ; $i <= 59 ; $i++){
			if(strlen(''.($i))<2) $inc='0'.($i);
			else $inc = ''.($i);
			$minutes[$i] = $inc;
		}
		for($i = 0 ; $i <= 23 ; $i++){
			if(strlen(''.($i%12))<2) $inc='0'.($i%12);
			else $inc = ''.($i%12);
			$hours[$i] = $inc.' '.array('AM','PM')[floor($i/12)];
		}
		$hours[0]='00 AM (midnight)';
		$hours[12]='00 PM (noon)';
		$viewParams=array(
			'hours'=>$hours,
			'minutes'=>$minutes,
			'days'=>$days,
			'years'=>$years,
			'months'=>$months,
			'timezones'=>$timezones);
		return $this->responseView(
            'XenForo_ViewPublic_Base',
            'kiror_site_events_add',
            $viewParams
        );
	}
	
	public function actionSubmit(){
		$vl=siteEvents_sharedStatic::$viewLabels;
		$title    = $this->_input->filterSingle('title',XenForo_Input::STRING);
		$url      = $this->_input->filterSingle('url',XenForo_Input::STRING);
		$start_yr = $this->_input->filterSingle('start_yr',XenForo_Input::INT);
		$start_mo = $this->_input->filterSingle('start_mo',XenForo_Input::INT);
		$start_dy = $this->_input->filterSingle('start_dy',XenForo_Input::INT);
		$start_hr = $this->_input->filterSingle('start_hr',XenForo_Input::INT);
		$start_mn = $this->_input->filterSingle('start_mn',XenForo_Input::INT);
		$end_yr   = $this->_input->filterSingle('end_yr',XenForo_Input::INT);
		$end_mo   = $this->_input->filterSingle('end_mo',XenForo_Input::INT);
		$end_dy   = $this->_input->filterSingle('end_dy',XenForo_Input::INT);
		$end_hr   = $this->_input->filterSingle('end_hr',XenForo_Input::INT);
		$end_mn   = $this->_input->filterSingle('end_mn',XenForo_Input::INT);
		$end_mn   = $this->_input->filterSingle('end_mn',XenForo_Input::INT);
		$start_tz = $this->_input->filterSingle('start_tz',XenForo_Input::INT);
		$end_tz   = $this->_input->filterSingle('end_tz',XenForo_Input::INT);
		$start = siteEvents_sharedStatic::totime($start_yr,$start_mo,$start_dy,$start_hr,$start_mn) - 3600*$start_tz;
		$end = siteEvents_sharedStatic::totime($end_yr,$end_mo,$end_dy,$end_hr,$end_mn) - 3600*$end_tz;
		if($start<0) $start = 0;
		if($end<0) $end = 0;
		$vis      = $this->_input->filterSingle('vis',XenForo_Input::INT);
		$visstr = $vl[1];
		if(array_key_exists($vis,$vl))
			$visstr = $vl[$vis];
		else
			$vis = 1;
		$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('siteevents','',array(
				'action'=>'submitedToCheck',
				'title'=>$title,
				'url'=>$url,
				'start'=>$start,
				'end'=>$end,
				'vis'=>$vis,
				'visstr'=>$visstr
				)));
		return $redir;
	}
	
	public function actionSubmitToCheck(){
		$visitor = XenForo_Visitor::getInstance();
		if(siteEvents_sharedStatic::getViewLevel($visitor)<7)
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		$vl=siteEvents_sharedStatic::$viewLabels;
		$title    = $this->_input->filterSingle('title',XenForo_Input::STRING);
		$url      = $this->_input->filterSingle('url',XenForo_Input::STRING);
		$start_   = $this->_input->filterSingle('start',XenForo_Input::INT);
		$end_     = $this->_input->filterSingle('end',XenForo_Input::INT);
		$vis      = $this->_input->filterSingle('vis',XenForo_Input::INT);
		$visstr = $vl[1];
		if(array_key_exists($vis,$vl))
			$visstr = $vl[$vis];
		else
			$vis = 1;
		$start    = min($start_,$end_);
		$end      = max($start_,$end_);
		$startstr = date('r',$start);
		$endstr   = date('r',$end);
		$viewParams=array(
				'title'=>$title,
				'url'=>$url,
				'start'=>$start,
				'end'=>$end,
				'startstr'=>$startstr.' (Timezone: UTC)',
				'endstr'=>$endstr.' (Timezone: UTC)',
				'vis'=>$vis,
				'visstr'=>$visstr);
		return $this->responseView(
            'XenForo_ViewPublic_Base',
            'kiror_site_events_add_review',
            $viewParams
        );
	}
	
	public function actionSubmitChecked(){
		$vl=siteEvents_sharedStatic::$viewLabels;
		$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('siteevents'));
		$visitor = XenForo_Visitor::getInstance();
		$uid = $visitor['user_id'];
		if(siteEvents_sharedStatic::getViewLevel($visitor)<7) return $redir;
		$title    = $this->_input->filterSingle('title',XenForo_Input::STRING);
		$url      = $this->_input->filterSingle('url',XenForo_Input::STRING);
		$start_   = $this->_input->filterSingle('start',XenForo_Input::INT);
		$end_     = $this->_input->filterSingle('end',XenForo_Input::INT);
		$vis      = $this->_input->filterSingle('vis',XenForo_Input::INT);
		if(!array_key_exists($vis,$vl)) $vis = 1;
		$start    = min($start_,$end_);
		$end      = max($start_,$end_);
		siteEvents_sharedStatic::insertEvent($title,$url,$uid,$start,$end,$vis);
		return $redir;
		
	}
	
	public function actionDelete(){
		$eid = $this->_input->filterSingle('eid',XenForo_Input::INT);
		$ev = siteEvents_sharedStatic::getEvent($eid);
		if(count($ev)<=0){
			return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
											XenForo_Link::buildPublicLink('siteevents'));
		}
		$visitor = XenForo_Visitor::getInstance();
		if(siteEvents_sharedStatic::getViewLevel($visitor)<7)
			return $this->responseError(new XenForo_Phrase('do_not_have_permission'));
		$viewParams = array('event'=>$ev,'eid'=>$eid,
							'startstr1'=>siteEvents_sharedStatic::renderDateFromInt_($ev['starttime']),
							'endstr1'=>siteEvents_sharedStatic::renderDateFromInt_($ev['endtime']),
							'startstr2'=>date('r',$ev['starttime']),
							'endstr2'=>date('r',$ev['endtime'])
						   );
		return $this->responseView(
            'XenForo_ViewPublic_Base',
            'kiror_site_events_confirm_delete',
            $viewParams
        );
	}
	
	public function actionConfirmDeletion(){
		$vl=siteEvents_sharedStatic::$viewLabels;
		$redir = $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('siteevents'));
		$visitor = XenForo_Visitor::getInstance();
		$uid = $visitor['user_id'];
		if(siteEvents_sharedStatic::getViewLevel($visitor)<7) return $redir;
		$eid = $this->_input->filterSingle('eid',XenForo_Input::INT);
		siteEvents_sharedStatic::deleteEvent($eid);
		return $redir;
	}
}
