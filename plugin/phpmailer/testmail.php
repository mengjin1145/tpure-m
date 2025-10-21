<?php
require '../../../../../zb_system/function/c_system_base.php';
require '../../../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action = 'root';
if(!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if(!$zbp->CheckPlugin('tpure')) {$zbp->ShowError(48);die();}

if($zbp->Config('tpure')->PostLOGOON){
    $logo = '<img src="'.$zbp->Config('tpure')->PostLOGO.'" style="height:40px;line-height:0;border:none;display:block;">';
}else{
    $logo = '<span style="font-size:22px; color:#666;">'.$zbp->name.'</span>';
}
$mailto = $zbp->Config('tpure')->MAIL_TO;
$subject = '这是一封 [ '.$zbp->name.' ] 的测试邮件';
$content = '<table width="700" align="center" cellpadding="0" cellspacing="0" style="margin-top:30px; border:1px solid rgb(230,230,230);"><tbody><tr><td><table cellpadding="0" cellspacing="0" border="0"><tbody><tr><td width="30"></td><td width="640" style="padding:20px 0 10px;"><a href="'.$zbp->host.'" target="_blank" style="text-decoration:none; display:inline-block; vertical-align:top;">'.$logo.'</a></td><td width="30"></td></tr></tbody></table></td></tr><tr><td><table><tbody><tr><td width="30"></td><td width="640"><p style="margin:0; padding:30px 0 0px; font-size:14px; color:#151515; font-family:microsoft yahei; font-weight:bold; border-top:1px solid #eee;">'.$zbp->user->StaticName.'，您好！</p><p style="font-size:14px; color:#151515; font-family:microsoft yahei;">这是 [ '.$zbp->name.' ] 的一封测试邮件！</p><p><br></p></td><td width="30"></td></tr></tbody></table></td></tr><tr><td><table align="center" cellspacing="0" class="dao-content-footer" style=" background-color:rgb(245,245,245); line-height: 28px; padding: 13px 23px; color: #7d8795; font-weight:500; border-top:1px solid #e6e6e6;" width="100%" bgcolor="#e6e6e6"><tbody><tr><td style="font-family:microsoft yahei; font-size:14px; vertical-align:top; text-align:center;" valign="top">'.$zbp->name.' - '.$zbp->subname.'</td></tr></tbody></table></td></tr></tbody></table>';

if(tpure_SendEmail($mailto, $subject, $content)){
	echo '<font color="#0b0">发送成功，请到收信邮箱查收！</font></font>';
}