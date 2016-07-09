<?php

class siteEvents_setup {
	public static function install(){
		siteEvents_sharedStatic::createTableDB();
	}
	public static function reinstall(){
		siteEvents_sharedStatic::dropTableDB();
		siteEvents_sharedStatic::createTableDB();
	}
	public static function uninstall(){
		siteEvents_sharedStatic::dropTableDB();
	}
}
