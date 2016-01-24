<?php
class ProfileUserTracker_actions extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex(){
		ProfileUserTracker_sharedstatic::pruneOlders();
		$imp2=$this->_input->getInput()['_matchedRoutePath'];
		$imp=explode('/',$imp2);
		$exp=array();
		foreach($imp as $v){
			if(strlen($v)>0){
				$exp[]=$v;
			}
		}
		unset($imp);
		//
		$viewParams=array('page'=>'');
		//
		$userModel = XenForo_Model::create('XenForo_Model_User');
		$user = array('user_id' => 0);
		$page=0;
		if(array_key_exists(1,$exp)){
			if($exp[1]=='nm'){
				if(array_key_exists(2,$exp)){
					$user = $userModel->getUserByName($exp[2]);
				}
			}
			if($exp[1]=='id'){
				if(array_key_exists(2,$exp)){
					$user = $userModel->getUserById($exp[2]);
				}
			}
			if(ProfileUserTracker_sharedstatic::startsWith(end($exp),'page-')){
				$page=intval(substr(end($exp),5));
			}
		}
		$SearchUid=$user['user_id'];
		if($SearchUid==null){
			$SearchUid=0;
		}
		if($page==null || $page<0){
			$page=0;
		}
		//die(print_r(array($exp,$page,$SearchUid),true));
		$res=ProfileUserTracker_sharedstatic::getFromDB($SearchUid,$page,50);
		$html='';
		foreach($res as $k => $v){
			$html.=ProfileUserTracker_sharedstatic::resultToHtml($v);
		}
		$html='<style type="text/css">
td
{
    padding:0 5px 0 5px;
}
</style>
<div class="section sectionMain searchResults InlineModForm">
<ol class="searchResultsList">'.$html."\n".'</ol></div>'."\n\n\n";
		$viewParams['htmlpage']=$html;
		//
		$last=0;
		$add='';
		if(ProfileUserTracker_sharedstatic::startsWith(end($exp),'page-')){
			$last=strlen(substr(end($exp),5))+((substr($imp2, -1)=='/')?1:0);
			$last=strlen($imp2)-$last;
		}else{
			$last=strlen($imp2);
			$add='page-';
		}
		$viewParams['prevnxt']=substr($imp2,13,$last-13).$add;
		$viewParams['page']=$page;
		return $this->responseView(
            'XenForo_ViewAdmin_Base',
            'kiror_user_tracking_page',
            $viewParams
        );
	}
}
