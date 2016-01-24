<?php

class ProfileUserTracker_setup
{
	public static function install(){
		ProfileUserTracker_sharedstatic::createDB();
	}

	public static function reinstall(){
		ProfileUserTracker_sharedstatic::clearDB();
	}

	public static function uninstall(){
		ProfileUserTracker_sharedstatic::dropDB();
	}
}
