<?php

class ProfileUserTracker_Listenter_LoadClass {
	public static function callback($class, array &$extend){
		self::callback1($class,$extend);
	}
	public static function callback1($class, array &$extend){
		$baseClass = 'XenForo_ControllerPublic_Forum';
		$toExtend = 'ProfileUserTracker_ControllerPublic_Abstract';
		if(($class==$baseClass || in_array($baseClass, $extend)) && !in_array($toExtend, $extend)){
			$extend[]=$toExtend;
		}
	}
}
