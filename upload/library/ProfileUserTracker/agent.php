<?php

class ProfileUserTracker_agent extends XFCP_ProfileUserTracker_agent
{
	public function prepareParams() //load_class_view
	{
		$resp = parent::prepareParams();
		$vis = XenForo_Visitor::getInstance();
		if($vis['user_id']){
			$rq=$this->_renderer->getRequest();
			$dh=$this->_renderer->getDependencyHandler();
			
			$rfl = new ReflectionObject($dh);
			$prp = $rfl->getProperty('_defaultTemplateParams');
			$prp->setAccessible(true);
			$out = $prp->getValue($dh);
			unset($prp);unset($rfl);
			$defaultTemplateParams = $out; unset($out);
			
			
			$uid      =$vis['user_id'];
			$url      ='data:text/html;base64,';
			if(!is_null($defaultTemplateParams) &&
			   is_array($defaultTemplateParams) &&
				array_key_exists('requestPaths',$defaultTemplateParams) &&
			   is_array($defaultTemplateParams['requestPaths']) &&
				array_key_exists('fullUri',$defaultTemplateParams['requestPaths'])){
				$url=$defaultTemplateParams['requestPaths']['fullUri'];
			}
			else{
				try{
					$url=$rq->getRequestUri();
				}
				catch(Exception $e){
					$url.=base64_encode('<!DOCTYPE html>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Error: URL parsing</title>
	</head>
	<body>
		<h1>Error</h1>
		<p>An unexpected error happened while trying to get the URL from the page.</p>
		<p>This message was stored as the URL instead.</p>
	</body>
</html>');
				}
			}
			$now      =time();
			$flags    =array();
			if($rq->isPost())				{$flags[]='POST';};
			if($rq->isGet())				{$flags[]='GET';};
			if($rq->isPut())				{$flags[]='PUT';};
			if($rq->isDelete())				{$flags[]='DELETE';};
			if($rq->isHead())				{$flags[]='HEAD';};
			if($rq->isOptions())			{$flags[]='OPTIONS';};
			if($rq->isXmlHttpRequest())		{$flags[]='XmlHttpRequest';};
			if($rq->isFlashRequest())		{$flags[]='FlashRequest';};
			if($rq->isSecure())				{$flags[]='SSL';};
			
			//die(print_r(array($dh),true));
			//die(print_r(array($rq),true));
			//die(print_r(array($flags),true));
			
			//**TESTS
			//die(print_r(array(get_class_methods($this),get_object_vars($this)),true));
			//die(print_r(array(get_class_methods($this->_renderer),get_object_vars($this->_renderer)),true));
			//die(print_r(array($rq,get_class_methods($rq),get_object_vars($rq)),true));
			//die(print_r($defaultTemplateParams,true));
			//die(print_r(array($dh,get_class_methods($dh),get_object_vars($dh)),true));
			//die(print_r(array($rq),true));
			//**/
			
			//hiding "_xfToken"
			$urlprot = preg_replace('/((_xfToken=).+&|(_xfToken=).+$)/','_xfToken=<b><i>censored</i></b>&',$url);
			
			///Throwing everything in DB!
			
			if(strpos($url,'/admin.php') == false && strpos($url,'?chat/refresh') == false){
				ProfileUserTracker_sharedstatic::putInDB($uid,$urlprot,$now,$flags);
			}
		}
		return $resp;
	}
}

