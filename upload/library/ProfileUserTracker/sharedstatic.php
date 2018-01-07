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
		$dbc=XenForo_Application::get('db');
		$q="CREATE TABLE IF NOT EXISTS kiror_user_browsing (
				seq SERIAL PRIMARY KEY,
				uts INTEGER,
				uid INTEGER,
				href LONGTEXT,
				flag LONGBLOB			
				) CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$dbc->query($q);
	}

	public static function createDBupdate2(){
		$dbc=XenForo_Application::get('db');
		$q='create view
				kiror_user_browsing_threads as 
					select
						id,
						time,
						user_id,
						thread_id
					from
						(select 
							seq
								as id,
							uts
								as time,
							CAST(uid AS UNSIGNED)
								as user_id,
							CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(href,"/add-reply","/"),"/",-2),".",-1),"/",1) AS UNSIGNED)
								as thread_id
						from kiror_user_browsing
						where
							(href not like "%_xf%" 
								and
							href not like "%/save-draft"
								and
							href like "http%"
								and
							href like "%threads/%/%"
								and
							href not like "%threads/%/%/%"
								and
							CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(href,"/add-reply","/"),"/",-2),".",-1),"/",1) AS UNSIGNED)>0
								and
							uid>0))
					as q1;';
		$dbc->query($q);
		$q='CREATE TABLE IF NOT EXISTS kiror_user_browsing_suggestion_cached (
				thread_id INTEGER PRIMARY KEY,
				time INTEGER NOT NULL DEFAULT 0,
				recommendations LONGBLOB NOT NULL
				) CHARACTER SET utf8 COLLATE utf8_general_ci;';
		$dbc->query($q);
	}
	
	public static function dropDB(){
		$dbc=XenForo_Application::get('db');
		$q='DROP TABLE IF EXISTS kiror_user_browsing;';
		$dbc->query($q);
		$q='DROP VIEW kiror_user_browsing_threads;';
		$dbc->query($q);
		$q='DROP TABLE IF EXISTS kiror_user_browsing_suggestion_cached;';
		$dbc->query($q);
	}
	
	//cost during tests = 0.25s
	//Returns list of arrays with keys = {thread_id, related_thread_id, frequence}
	public static function getRecommendationForThread_uncached($tid){
		$tid = intval($tid);
		if($tid==0){
			return self::getRecommendationForAll_uncached();
		}
		$dbc=XenForo_Application::get('db');
		$q='select
				q3.thread_id,
				q3.related_thread_id,
				COUNT(q3.related_thread_id)
					as frequence
			from
				(
				select distinct
					q1.*,
					q2.thread_id
						as related_thread_id
				from
					(
					select
						*
					from
						kiror_user_browsing_threads
					where
						thread_id = ?
					) as q1
					inner join
						kiror_user_browsing_threads
							as q2
						on (q1.user_id=q2.user_id)
				) as q3
			group by
				q3.related_thread_id
			order by
				frequence
					desc;';
		return $dbc->fetchAll($q,$tid);
	}
	
	//cost during tests = 0.1s
	//Returns list of arrays with keys = {thread_id, related_thread_id, frequence}
	public static function getRecommendationForAll_uncached(){
		$dbc=XenForo_Application::get('db');
		$q='select
				0
					as thread_id,
				thread_id
					as related_thread_id,
				COUNT(thread_id)
					as frequence
			from
				kiror_user_browsing_threads
			group by
				thread_id
			order by
				frequence
					desc';
		return $dbc->fetchAll($q);
	}
	
	//cost during tests = irrelevant
	public static function getRecommendationFromCache_dumb($tid){
		$tid = intval($tid);
		$dbc=XenForo_Application::get('db');
		return $dbc->fetchAll('select thread_id, time, recommendations from kiror_user_browsing_suggestion_cached where thread_id = ? ;',$tid);
	}
	
	public static function cleanOldRecommendationFromCache($oldage = 1800){
		$timeTolerance = time() + $oldage;
		$dbc=XenForo_Application::get('db');
		return $dbc->fetchAll('delete from kiror_user_browsing_suggestion_cached where time <= ? ;',$timeTolerance);
	}
	
	public static function isRecommendationInCache($tid){
		$tid = intval($tid);
		$dbc=XenForo_Application::get('db');
		return $dbc->fetchRow('select ? in (select thread_id from kiror_user_browsing_suggestion_cached) as cached;',$tid)['cached'];
	}
	
	public static function setRecommendationToCache($tid){
		$tid = intval($tid);
		$dbc=XenForo_Application::get('db');
		$rec = self::getRecommendationForThread_uncached($tid);
		$dbc->fetchRow('delete from kiror_user_browsing_suggestion_cached where thread_id = ? ;',$tid);
		$dbc->fetchRow(
			'insert into kiror_user_browsing_suggestion_cached (thread_id, time, recommendations) values (?,?,?);',
			array(
				$tid,
				time(),
				serialize($rec),
			)
		);
		return $rec;
	}
	
	public static function getRecommendationForThread_lessworse($tid){
		self::cleanOldRecommendationFromCache();
		if(self::isRecommendationInCache($tid)){
			return self::getRecommendationFromCache_dumb($tid);
		}
		else{
			return self::setRecommendationToCache($tid);
		}
	}
	
	public static $cacheRecommendationForThread = array();
	public static function getRecommendationForThread_unsliced($tid){
		$tid = intval($tid);
		if(!in_array($tid,self::$cacheRecommendationForThread)){
			self::$cacheRecommendationForThread[$tid] = self::getRecommendationForThread_lessworse($tid);
		}
		return self::$cacheRecommendationForThread[$tid];
	}
	
	public static function getRecommendationForThread($tid){
		return array_slice(self::getRecommendationForThread_unsliced($tid),0,10);
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
