<?php

class ProfileUserTracker_agent extends XFCP_ProfileUserTracker_agent
{	
	public function prepareParams()
	{
		parent::prepareParams();
		if(XenForo_Visitor::getInstance()['user_id']){
			$rq=$this->_renderer->getRequest();
			$dh=$this->_renderer->getDependencyHandler();
			
			$rfl = new ReflectionObject($dh);
			$prp = $rfl->getProperty('_defaultTemplateParams');
			$prp->setAccessible(true);
			$out = $prp->getValue($dh);
			unset($prp);unset($rfl);
			$defaultTemplateParams = $out; unset($out);
			
			$uid      =$defaultTemplateParams['visitor']['user_id'];
			$url      =$defaultTemplateParams['requestPaths']['fullUri'];
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
			
			
			
			///Throwing everything in DB!
			
			if(strpos($url,'/admin.php') == false && strpos($url,'?chat/refresh') == false){
				ProfileUserTracker_sharedstatic::putInDB($uid,$url,$now,$flags);
			}
		}
	}
}

