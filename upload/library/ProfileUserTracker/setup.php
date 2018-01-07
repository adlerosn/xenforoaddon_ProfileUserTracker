<?php

class ProfileUserTracker_setup
{
	public static function install($installedAddon){
		$version = is_array($installedAddon) ? $installedAddon['version_id'] : 0;
		if($version <= 2){
			ProfileUserTracker_sharedstatic::createDB();
		}
		if($version <= 3){
			ProfileUserTracker_sharedstatic::createDBupdate2();
		}
	}

	public static function uninstall(){
		ProfileUserTracker_sharedstatic::dropDB();
	}
}
