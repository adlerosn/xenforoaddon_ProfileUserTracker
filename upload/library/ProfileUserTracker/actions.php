<?php
class ProfileUserTracker_actions extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex(){
		ProfileUserTracker_sharedstatic::pruneOlders();
		$unm = $this->_input->filterSingle('unm',XenForo_Input::STRING);
		$uid = $this->_input->filterSingle('uid',XenForo_Input::STRING);
		$ftr = $this->_input->filterSingle('ftr',XenForo_Input::STRING);
		$page= $this->_input->filterSingle('pag',XenForo_Input::STRING);
		if($page=='downloadjson'){
			$downloadmode = 'json';
		}
		else{
			$downloadmode = false;
		}
		$page= $this->_input->filterSingle('pag',XenForo_Input::INT);
		//
		$viewParams=array('page'=>$page);
		//
		$userModel = XenForo_Model::create('XenForo_Model_User');
		$user = array('user_id' => intval($uid));
		if(intval($uid)>0){
			$user = $userModel->getUserById(intval($uid));
		}
		else{
			$user = $userModel->getUserByName($unm);
		}
		$SearchUid=$user['user_id'];
		if($SearchUid==null){
			$SearchUid=0;
		}
		if($page==null || $page<0){
			$page=0;
		}
		if($downloadmode){
			$visitor = XenForo_Visitor::getInstance();
			if($downloadmode == 'json'){
				$ips = array();
				try{
					$ips = $this->getModelFromCache('XenForo_Model_Ip')->getIpsByUserId($user['user_id']);
				}catch(Exception $e){};
				ksort($ips);
				$nips = array();
				foreach($ips as $time => $ip){
					$nips[] = array('timestamp'=>$time,
									'timestring'=>date('r',$time),
									'ipaddress'=>$ip
						);
				}
				$results = ProfileUserTracker_sharedstatic::getAllFromDB($SearchUid,$ftr);
				$nresults = array();
				foreach($results as $result){
					$result['timestring'] = date('r',$result['uts']);
					$nresults[]=$result;
				}
				$t=time();
				$return = array(
					'now'=>array('unixTimeStamp' => $t,
								 'textTimeStamp' => date('r',$t),
								 'requestUserID'=>$visitor['user_id'],
								 'requestUserName'=>$visitor['username']
					),
					'search' => array(
						'user_id'=>$user['user_id'],
						'username'=>$user['username'],
						'URLfilter'=>$ftr,
						'IPs' => $nips
					),
					'results' => $nresults
				);
				$downloadable = json_encode($return);
				$fsize = strlen($downloadable);
				$identif = ($user == null)?'allUsers':('ID_'.$user['user_id']);
				$fname = ''.$identif.'_-_'.((strlen($ftr)<=0)?'unfiltered':'filtered').'_-_'.date('Y-m-d--G-i-s--e',$t).'.json';
				$mime = 'application/json';
				header('Content-Type: '.$mime);
				header('Content-Disposition: attachment; filename="'.$fname.'"');
				header('Content-Length: ' . $fsize);
				header('Connection: close');
				die($downloadable);
			}
		}
		$resultsPerPage = 50;
		$res=ProfileUserTracker_sharedstatic::getFromDB($SearchUid,$page,$ftr,$resultsPerPage);
		$rescount=ProfileUserTracker_sharedstatic::getFromDBLimitless($SearchUid,$page,$ftr);
		$lastpage = ceil($rescount/$resultsPerPage);
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
		$viewParams['prevpg']=($page-1<0)?0:$page-1;
		$viewParams['nextpg']=($page+2>$lastpage)?$lastpage-1:$page+1;
		$viewParams['page']=$page;
		$viewParams['pageinc']=$page+1;
		$viewParams['uid']=$user['user_id'];
		$viewParams['unm']=$user['username'];
		$viewParams['ftr']=$ftr;
		$viewParams['totalpages']=$lastpage;
		$viewParams['totalres']=$rescount;
		$viewParams['startres']=$page*$resultsPerPage+1;
		$viewParams['endres']=($page+1)*$resultsPerPage;
		$viewParams['downloadjsonlink']=XenForo_Link::buildAdminLink('usertracking','',array(
			'unm'=>$user['username'],
			'uid'=>$user['user_id'],
			'ftr'=>$ftr,
			'pag'=>'downloadjson'
		));
		return $this->responseView(
            'XenForo_ViewAdmin_Base',
            'kiror_user_tracking_page',
            $viewParams
        );
	}
}
