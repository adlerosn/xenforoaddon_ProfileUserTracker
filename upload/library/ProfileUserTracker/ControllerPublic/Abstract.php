<?php

class ProfileUserTracker_ControllerPublic_Abstract extends XFCP_ProfileUserTracker_ControllerPublic_Abstract {
	public function responseView($viewName = '', $templateName = '', array $params = array(), array $containerParams = array()){
		$view = call_user_func_array(array('parent','responseView'),func_get_args());
		if($view instanceof XenForo_ControllerResponse_View){
			$view->params['threadRecommendationKirorRelatedUsable'] = false;
			$view->params['threadRecommendationKirorUsable'] = true;
			if( isset($view->params['thread'])&&
				isset($view->params['thread']['node_id']) &&
				isset($view->params['thread']['thread_id'])
				){
				$view->params['threadRecommendationKirorRelated'] = ProfileUserTracker_sharedstatic::getRecommendationForThread($view->params['thread']['thread_id']);
				$view->params['threadRecommendationKirorRelatedUsable'] = true;
			}
			$view->params['threadRecommendationKiror'] = ProfileUserTracker_sharedstatic::getRecommendationForThread(0);
			$view->params['threadRecommendationKirorUsable'] = true;
		}
		return $view;
	}
}
