<?
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$handle = fopen("php://input", "rb");
while (!feof($handle)) {
	$http_raw_post_data .= fread($handle, 8192);
}

fclose($handlle);

$arData=json_decode($http_raw_post_data, true);

// Clean id from whitespaces. You don't need this.
$arData['user_id'] = $arData['user_id'] ? preg_replace('/\s+/', '', $arData['user_id']) : null;
if (!isset($arData["user_id"]) || strlen($arData['user_id']) <= 0) {
	return;
}

$rsUser=CUser::GetList(($by="ID"), ($order="DESC"), array("UF_GITLAB_ID"=>$arData["user_id"]));
$arUser=$rsUser->Fetch();
if(!$arUser)
	return;

$GLOBALS["USER"]->Authorize($arUser["ID"]);

$TASK_ID=false;
if(preg_match("/task([0-9]+)/i", $arData["repository"]["name"], $r))
	$TASK_ID=$r[1];
elseif(preg_match("/task([0-9]+)/i", $arData["repository"]["description"], $r))
	$TASK_ID=$r[1];
elseif(preg_match("/task([0-9]+)/i", $arData["ref"], $r))
	$TASK_ID=$r[1];

CModule::IncludeModule("tasks");
CModule::IncludeModule("forum");

$branch=$arData["ref"];
$branch=str_replace("refs/heads/", "", $branch);

foreach($arData["commits"] as $arCommit)
{
	$message=$arCommit["message"];
	$message=utf8win1251($message);
    if(preg_match("/task([0-9]+)/i", $message, $r))
		$TASK_ID=$r[1];
	if(!$TASK_ID)
		continue;
	$message=str_replace($r[0], "", $message);
	
	$rsTask=CTasks::GetList(array(), array("ID"=>$TASK_ID));
	$arTask=$rsTask->Fetch();
	if(!$arTask)
		continue;
	
	CTaskComments::add($arTask["ID"], $arUser["ID"],
        "<b>Commit:</b> <a href=".$arCommit["url"].">".substr($arCommit["id"], 0, 9)."</a>\nBranch: ".$branch."\n".$message);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
