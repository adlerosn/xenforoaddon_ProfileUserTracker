<?php
//ProfileUserTracker_sharedstatic class contains only "public static function" methods
class ProfileUserTracker_sharedstatic
{
	public static function mysql_escape_mimic_fromPhpDoc($inp)
	{//http://php.net/manual/pt_BR/function.mysql-real-escape-string.php
		return str_replace(array('\\',    "\0",  "\n",  "\r",   "'",   '"', "\x1a"),
						   array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'),
						   $inp);
	}

	public static function startsWith($haystack, $needle)
	{//http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
		 $length = strlen($needle);
		 return (substr($haystack, 0, $length) === $needle);
	}

	public static function createDB(){
		$q="CREATE TABLE IF NOT EXISTS kiror_user_browsing (
				seq SERIAL PRIMARY KEY,
				uts INTEGER,
				uid INTEGER,
				href LONGTEXT,
				flag LONGBLOB			
				) CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$dbc=XenForo_Application::get('db');
		$dbc->query($q);
	}
	
	public static function dropDB(){
		$q='DROP TABLE IF EXISTS kiror_user_browsing;';
		$dbc=XenForo_Application::get('db');
		$dbc->query($q);
	}
	
	public static function clearDB(){
		ProfileUserTracker_sharedstatic::dropDB();
		ProfileUserTracker_sharedstatic::createDB();
	}
	
	public static function putInDB($uid,$url,$now,$flags){
		$url = ProfileUserTracker_sharedstatic::mysql_escape_mimic_fromPhpDoc($url);
		$flags=ProfileUserTracker_sharedstatic::mysql_escape_mimic_fromPhpDoc(serialize($flags));
		$q='INSERT INTO kiror_user_browsing (uid,href,uts,flag) VALUES
			(\''.$uid.'\', \''.$url.'\', \''.$now.'\', \''.$flags.'\');';
		//die($q);
		$dbc=XenForo_Application::get('db');
		$dbc->query($q);
	}
	
	public static function getFromDB($uid,$page,$filter,$resPerPage){
		$uid = intval($uid);
		$page = intval($page);
		$filter = self::mysql_escape_mimic_fromPhpDoc($filter);
		$resPerPage = intval($resPerPage);
		$q='SELECT seq,uid,uts,href,flag FROM `kiror_user_browsing`';
		if($uid==0){
			$q.=' WHERE href LIKE \'%'.$filter.'%\'';
		}else{
			$q.=' WHERE uid='.$uid.' AND href LIKE \'%'.$filter.'%\'';
		}
		$q.=' ORDER BY uts desc LIMIT '.$page*$resPerPage.' , '.$resPerPage;' ;';
		//die($q);
		$dbc=XenForo_Application::get('db');
		$r=$dbc->fetchAll($q);
		unset($dbc);
		foreach($r as $k=>$v){
			$r[$k]['flag']=unserialize($r[$k]['flag']);
		}
		//die(print_r($r,true));
		return $r;
	}
	
	public static function getFromDBLimitless($uid,$page,$filter){
		$uid = intval($uid);
		$page = intval($page);
		$filter = self::mysql_escape_mimic_fromPhpDoc($filter);
		$q='SELECT COUNT(seq) AS res FROM `kiror_user_browsing`';
		if($uid==0){
			$q.=' WHERE href LIKE \'%'.$filter.'%\'';
		}else{
			$q.=' WHERE uid='.$uid.' AND href LIKE \'%'.$filter.'%\'';
		}
		//die($q);
		$dbc=XenForo_Application::get('db');
		$r=$dbc->fetchRow($q)['res'];
		unset($dbc);
		return $r;
	}
	
	public static function getAllFromDB($uid,$filter){
		$uid = intval($uid);
		$filter = self::mysql_escape_mimic_fromPhpDoc($filter);
		$q='SELECT seq,uid,uts,href,flag FROM `kiror_user_browsing`';
		if($uid==0){
			$q.=' WHERE href LIKE \'%'.$filter.'%\'';
		}else{
			$q.=' WHERE uid='.$uid.' AND href LIKE \'%'.$filter.'%\'';
		}
		$q.=' ORDER BY uts ASC;';
		//die($q);
		$dbc=XenForo_Application::get('db');
		$r=$dbc->fetchAll($q);
		unset($dbc);
		foreach($r as $k=>$v){
			$r[$k]['flag']=unserialize($r[$k]['flag']);
		}
		//die(print_r($r,true));
		return $r;
	}
	
	public static function resultToHtml($res){
		$resultFactory='
<li class="searchResult post primaryContent" data-author="<!--USERNUM-->">
<table><tr><td>
	<div class="listBlock posterAvatar"><a href="<!--PROFILELINK-->" class="avatar" data-avatarhtml="true"><img src="<!--AVATAR-->" alt="<!--USERNAME-->" height="48" width="48"></a></div>
</td><td>
	<div class="listBlock main">
		<div class="titleText">
			<span class="contentType"><b><a href="<!--PROFILELINK-->">@<!--USERNAME--></a></b>: Request #<!--REQNUM--></span>
			<h3 class="title"><!--GENTXT--></h3>
		</div>
		<blockquote class="snippet">
			<!--OCURRENCES-->
		</blockquote>
	</div>
</td></tr>
</table>
</li>';
		$prfLnk='index.php?members/'.$res['uid'].'/';
		$prfNfoLnk=$prfLnk.'#info';
		$userModel = XenForo_Model::create('XenForo_Model_User');
		$user = $userModel->getUserById($res['uid']);
		$profImg=XenForo_Template_Helper_Core::callHelper('avatar',array($user,'s'));
			
		//die(print_r($user['username'],true));
		
		$gentxt ='<a href="'.$res['href'].'">'.$res['href'].'</a>';
		if(self::startsWith($res['href'],'data:text/html;base64,')){
			$gentxt ='<a href="'.$res['href'].'">Stored HTML message</a>';
		}
		$gentxt.="\n<br />\n";
		$gentxt.=date('Y\/m\/d, G\:i\:s \(e\)',$res['uts']);
		$resultFactory=str_replace('<!--GENTXT-->'         ,$gentxt,$resultFactory);
		$resultFactory=str_replace('<!--USERNUM-->'        ,$res['uid'],$resultFactory);
		$resultFactory=str_replace('<!--PROFILEINFOLINK-->',$prfNfoLnk,$resultFactory);
		$resultFactory=str_replace('<!--AVATAR-->'         ,$profImg,$resultFactory);
		$resultFactory=str_replace('<!--USERNAME-->'       ,$user['username'],$resultFactory);
		$resultFactory=str_replace('<!--PROFILELINK-->'    ,$prfLnk,$resultFactory);
		$resultFactory=str_replace('<!--REQNUM-->'         ,$res['seq'],$resultFactory);
		$octbl='<div>Flags: ';
		foreach($res['flag'] as $k=>$v){
			$octbl.=$v.', ';
		}
		$octbl=substr($octbl,0,strlen($octbl)-2);
		$octbl.='</div>';
		$resultFactory=str_replace('<!--OCURRENCES-->',$octbl,$resultFactory);
		return $resultFactory;
	}
	
	public static function pruneOlders(){
		$xfopt = XenForo_Application::get('options');
		$lifetime = $xfopt->useracttrackeroldage;
		$oldage = time()-(24*3600*$lifetime);
		$q='DELETE FROM `kiror_user_browsing` WHERE uts<'.$oldage.' ;';
		$dbc=XenForo_Application::get('db');
		$dbc->query($q);
	}
}
