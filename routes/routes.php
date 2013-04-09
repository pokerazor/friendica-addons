<?php 
/**
 * Name: Route Administration App
 * Description: Route Administration Feature
 * Version: 0.1
 * Author: Hanno - Felix Wagner <http://friendica.tdr.iem.uni-due.de/profile/sehawagn>, Philipp Bartenstein <http://friendica.tdr.iem.uni-due.de/profile/phil>
 */


function routes_install()
{
	register_hook('init_1', 'addon/routes/routes.php', 'routes_init_1');
	register_hook('post_local', 'addon/routes/routes.php', 'routes_post_local_end_hook');
	register_hook('profile_tabs', 'addon/routes/routes.php', 'routes_profile_tabs_hook');
}


function routes_uninstall()
{
	unregister_hook('init_1', 'addon/routes/routes.php', 'routes_init_1');
	unregister_hook('post_local', 'addon/routes/routes.php', 'routes_post_local_end_hook');
	unregister_hook('profile_tabs', 'addon/routes/routes.php', 'routes_profile_tabs_hook');
}

function routes_init_1(){
	require_once ('include/api.php');
	api_register_func('api/routes/list','routes_api_list', true);
	api_register_func('api/routes/active','routes_api_get_active_route', true);
	api_register_func('api/routes/activeevents','routes_api_get_active_route_events', true);
	api_register_func('api/routes/events','routes_api_get_specific_route_events', true);
	api_register_func('api/routes/setactive','routes_api_set_active_route', true);
	

}

function routes_api_get_active_route(&$a, $type){
}

function routes_api_set_active_route(&$a, $type){
	if (api_user()===false) return false;
	$user_info = api_get_user($a);
	if(isset($a->argv[3])){
		$searchedForRoute=intval($a->argv[3]);
	}
//	print_r($searchedForRoute);
	$r=setActive(&$a,$searchedForRoute,true);
	
	$data = array('$result' => array(array('result'=>'done'),array('result'=>'done')));
	
	return $data;
}

function routes_api_get_active_route_events(&$a, $type){
	$activeRoute=getActiveRoute($a);
	if($activeRoute!=null){
		if (api_user()===false) return false;
		$user_info = api_get_user($a);
		$r=getRouteEvents($a,$activeRoute);
		$ret = api_format_items($r,$user_info);

	}else{

		$ret = array();
	}
	$data = array('$statuses' => $ret);
	switch($type){
		case "atom":
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
	}
	return api_apply_template("timeline", $type, $data);

}

function routes_api_get_specific_route_events(&$a, $type){
	if (api_user()===false) return false;
	$user_info = api_get_user($a);
	if(isset($a->argv[5])){
		$searchedForRoute=intval($a->argv[5]);
	}
	$r=getRouteEvents($a,$searchedForRoute);
	$ret = api_format_items($r,$user_info);
	$data = array('$statuses' => $ret);
	switch($type){
		case "atom":
		case "rss":
			$data = api_rss_extra($a, $data, $user_info);
	}
	return api_apply_template("timeline", $type, $data);
}

function routes_api_list(&$a, $type){
	if (api_user()===false) return false;

	$r=getRoutes($a);

	$data = array('$routes' => $r);

	return $data;
}

function routes_query($user_info,$whichroute=-1){

	$whichroute=intval($whichroute);
	// params
	$count = (x($_REQUEST,'count')?$_REQUEST['count']:20);
	$page = (x($_REQUEST,'page')?$_REQUEST['page']-1:0);
	if ($page<0) $page=0;
	$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);
	$exclude_replies = (x($_REQUEST,'exclude_replies')?1:0);
	//$since_id = 0;//$since_id = (x($_REQUEST,'since_id')?$_REQUEST['since_id']:0);

	$start = $page*$count;

	$sql_extra = '';
	if ($user_info['self']==1) $sql_extra .= " AND `item`.`wall` = 1 ";
	if ($exclude_replies > 0)  $sql_extra .= ' AND `item`.`parent` = `item`.`id`';
	if ($whichroute>0){
		$sql_extra .= ' AND route_id = '.$whichroute .' ';
	}


	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`,
			`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
			`contact`.`network`, `contact`.`thumb`, `contact`.`dfrn-id`, `contact`.`self`,
			`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`, routes.name AS route_name, routes.id AS route_id
			FROM `item`, `contact`,`route_items`, `routes`

			WHERE `item`.`uid` = %d
			AND routes.name IS NOT NULL
			AND `item`.`contact-id` = %d
			AND `item`.`visible` = 1 and `item`.`moderated` = 0 AND `item`.`deleted` = 0
			AND `contact`.`id` = `item`.`contact-id`
			AND `route_items`.`item_uri`=`item`.`uri`
			AND `routes`.`id`= `route_items`.`route_id`
			AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
			$sql_extra
			AND `item`.`id`>%d
			ORDER BY `item`.`received` DESC LIMIT %d ,%d ",
			intval(api_user()),
			intval($user_info['id']),
			intval($since_id),
			intval($start),	intval($count)
	);
	/*
	 print_r($sql_extra);

	print_r(mysql_info());
	print_r ($r);
	print_r ($user_info);
	*/
	return $r;
}

function routes_profile_tabs_hook(&$a, &$b)
{
	$b["tabs"][] = array(
			"label" => t('Routes'),
			"url"   => $a->get_baseurl() . "/routes/",
			"sel"   => "",
			"title" => t('Administrate your routes'),
	);

}

function routes_post_local_end_hook(&$a, $b){
	$route=getActiveRoute($a);
	if($route){
		$r = q("INSERT INTO route_items(route_id, item_uri) VALUES ('%s','%s')",$route,$b['uri']);

	}
}

function routes_module() {
}

function routes_init(&$a) {
	ini_set("display_errors", 1);
	error_reporting(E_ALL);
	require_once (__DIR__ . "/routes.fnk.inc.php");
}

//handle post requests
function routes_post(&$a)  {
}
function routes_content(&$a) {

	if (!isset($a->user["uid"]) || $a->user["uid"] == 0) {
		return login();
	}

	$tpl = get_markup_template( 'route_management.tpl', 'addon/routes/' );

	// 	phpinfo();

	// print_r($a);

	// 	print_r($a->user['uid']);


	/*
	 $r = q("SELECT * FROM item WHERE uid=%s",$a->user['uid']);

	$tpl_macros['items']['test']=true;

	$pgcnt='';
	foreach ($r as $not) {
	$curItem['id']=$not['id'];
	$curItem['body']=$not['body'];
	$curItem['coord']=$not['coord'];

	$tpl_macros['items'][]=$curItem;
	}
	*/
	$tpl_macros['title']='Routes';
	$tpl_macros['path']='/'.$a->argv[0];

	$pgcnt = replace_macros($tpl, $tpl_macros);

	$pgcnt = routeAction($a);
	if (!$pgcnt){

		return login();
	}

	return $pgcnt;
}

function getForm(&$a,$presetValues=array()){
	$tpl = get_markup_template( 'route_management.tpl', 'addon/routes/' );
	$form['path']='/'.$a->argv[0];

	$form['form']=true;
	if(isset($presetValues['id'])){
		$form['id']=$presetValues['id'];
	}
	if( isset($presetValues['name'])){
		$form['name']=$presetValues['name'];
	}
	$form_html = replace_macros($tpl, $form);
	return $form_html;
}

function getRouteList(&$a){
	$tpl = get_markup_template( 'route_management.tpl', 'addon/routes/' );
	$tpl_macros['routes_list']=true;
	$tpl_macros['path']='/'.$a->argv[0];


	$r = getRoutes($a);

	foreach ($r as $entr) {
		$curItem['id']=$entr['id'];
		$curItem['name']=$entr['name'];
		$curItem['active']=$entr['active'];


		$tpl_macros['routes'][]=$curItem;
	}

	$html = replace_macros($tpl, $tpl_macros);
	return $html;
}

function getRoutes(&$a){
	return $r = q("SELECT id,name,active FROM routes WHERE uid=%s",$a->user['uid']);
}

function getRouteEvents(&$a,$searchedForRoute=-1){
	$user_info = api_get_user($a);
	$r=routes_query($user_info,$searchedForRoute);
	return $r;
}

function routeAction(&$a){
	$html='';
	$route=array();
	$r_id=-1;
	if(isset($_REQUEST['r_id'])){
		$r_id=intval($_REQUEST['r_id']);
	}
	if(isset($a->argv[1]) && $a->argv[1]=='submit'){
		$add['uid']=$a->user['uid'];
		$add['name']=$_REQUEST['r_name_inp'];
		if(isset($_REQUEST['r_id_inp'])){
			$add['id']=$_REQUEST['r_id_inp'];
			changeRoute($a,$add);
		}else{
			addRoute($a,$add);
		}
	}else if(isset($a->argv[1]) && $a->argv[1]=='alter'){
		$route=getRoute($a,$r_id);
	}else if(isset($a->argv[1]) && $a->argv[1]=='remove'){
		$route=removeRoute($a,$r_id);
	}else if(isset($a->argv[1]) && $a->argv[1]=='activate'){
		$route=setActive($a,$r_id);
	}else if(isset($a->argv[1]) && $a->argv[1]=='deactivate'){
		$route=setActive($a,$r_id,false);
	} else {

	}
	$html=$html.getForm($a,$route);

	$html=$html.getRouteList($a,$a);

	return $html;
}

function setActive(&$a,$routeID,$activate=true){
	if(allowed($a,$routeID)){
		$r = q("UPDATE routes SET active=0 WHERE uid='%s'",$a->user['uid']);
		if($activate){
			$r = q("UPDATE routes SET active=1 WHERE id='%s'",$routeID);
		}
	}
}

function allowed(&$a,$routeID){
	$r = q("SELECT uid FROM routes WHERE id=%s",$routeID);

	if($r[0]['uid']==$a->user['uid']){
		return true;
	} else {
		return false;
	}
}

function getRoute(&$a,$routeID){
	$routeID=intval($routeID);
	$r=false;
	if(allowed(&$a,$routeID)){
		$r = q("SELECT id,uid,name,active FROM routes WHERE id=%s",$routeID);
	}
	return $r[0];
}

function getActiveRoute(&$a){
	$r = q("SELECT routes.id FROM routes WHERE routes.uid=%s AND active=1",$a->user['uid']);

	if($r[0]['id']){
		return $r[0]['id'];
	} else {
		return false;
	}
}

function changeRoute(&$a,$add){
	if(allowed($a,$add['id'])){
		$r = q("UPDATE routes SET name='%s' WHERE id='%s'",$add['name'],$add['id']);
	}
}

function removeRoute(&$a,$r_id){
	if(allowed($a,$r_id)){
		$r = q("DELETE FROM routes WHERE id='%s'",$r_id);
	}
}

function addRoute(&$a,$add){
	$r = q("INSERT INTO routes(uid, name) VALUES ('%s','%s')",$add['uid'],$add['name']);
}
?>