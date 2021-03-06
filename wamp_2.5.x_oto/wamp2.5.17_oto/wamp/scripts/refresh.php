<?php

require ('config.inc.php');
require ('wampserver.lib.php');

// ************************
//   gestion de la langue


// on recupere la langue courante
if (isset($wampConf['language']))
	$lang = $wampConf['language'];
else
  $lang = $wampConf['defaultLanguage'];

// on inclus le fichier correspondant si existant
require($langDir.$wampConf['defaultLanguage'].'.lang');
if (is_file($langDir.$lang.'.lang'))
	require($langDir.$lang.'.lang');


// on inclus les fichiers de langue de modules par defaut
if ($handle = opendir($langDir.$modulesDir))
{
	while (false !== ($file = readdir($handle)))
	{
		if ($file != "." && $file != ".." && preg_match('|_'.$wampConf['defaultLanguage'].'|',$file))
			include($langDir.$modulesDir.$file);
	}
	closedir($handle);
}

// on inclus les fichiers de langue de modules correspondant � la langue courante
if ($handle = opendir($langDir.$modulesDir))
{
	while (false !== ($file = readdir($handle)))
	{
		if ($file != "." && $file != ".." && preg_match('|_'.$lang.'|',$file))
			include($langDir.$modulesDir.$file);
	}
	closedir($handle);
}

//Update string to use alternate port.
$w_AlternatePort = sprintf($w_UseAlternatePort, $c_UsedPort);
if($c_UsedPort == $c_DefaultPort) {
	$UrlPort = '';
	$w_newPort = "8080";
}
else {
	$UrlPort = ':'.$c_UsedPort;
	$w_newPort = "80";
}

//Update string to use alternate MySQL port.
$w_AlternateMysqlPort = sprintf($w_UseAlternatePort, $c_UsedMysqlPort);
if($c_UsedMysqlPort == $c_DefaultMysqlPort) {
	$w_newMysqlPort = "3307";
}
else {
	$w_newMysqlPort = "3306";
}

// ************************
//Before to require wampmanager.tpl ($templateFile)
// we need to change some options, otherwise the variables are replaced by their content.
// Option to launch Homepage at startup
if(!empty($wampConf['HomepageAtStartup']))
	update_wampmanager_file("Action: run; FileName: \"\${c_navigator}\"; Parameters: \"http://localhost\${UrlPort}/\"; ShowCmd: normal; Flags: ignoreerrors",
		$wampConf['HomepageAtStartup'],
		"on", "off",
		$templateFile);

// Item menu Online / Offline
if(!empty($wampConf['MenuItemOnline']))
	update_wampmanager_file("Type: item; Caption: \"\${w_putOnline}\"; Action: multi; Actions: onlineoffline",
		$wampConf['MenuItemOnline'],
		"on", "off",
		$templateFile);

// Item submenu Apache Check port used (if not 80)
if(!empty($wampConf['apacheUseOtherPort']))
	update_wampmanager_file("Type: item; Caption: \"\${w_testPortUsed}",
		$wampConf['apacheUseOtherPort'],
		"on", "off",
		$templateFile);

// Item Tools submenu Check MySQL port used (if not 3306)
if(!empty($wampConf['mysqlUseOtherPort']))
	update_wampmanager_file("Type: item; Caption: \"\${w_testPortMysqlUsed}",
		$wampConf['mysqlUseOtherPort'],
		"on", "off",
		$templateFile);

// Item Tools submenu Change the names of the services
if(!empty($wampConf['ItemServicesNames'])) {
	update_wampmanager_file("Type: separator; Caption: \"Apache: \${c_apacheService} - MySQL: \${c_mysqlService}\"",
		$wampConf['ItemServicesNames'],
		"on", "off",
		$templateFile);
	update_wampmanager_file("Type: item; Caption: \"\${w_changeServices}\"; Action: multi; Actions: changeservicesnames; Glyph: 9",
		$wampConf['ItemServicesNames'],
		"on", "off",
		$templateFile);
}

// on inclus le fichier de template
require($templateFile);

// ************************
// on gere le mode online /offline
$c_OnOffLine = 'off';
if ($wampConf['status'] == 'online')
{
	$tpl = str_replace('images_off.bmp', 'images_on.bmp',$tpl);
  $tpl = str_replace($w_serverOffline, $w_serverOnline,$tpl);
  $tpl = str_replace('onlineOffline.php on', 'onlineOffline.php off', $tpl);
  $tpl = str_replace($w_putOnline,$w_putOffline,$tpl);
  $c_OnOffLine = 'on';
}

// ************************
// chargement du menu des langues disponibles
if ($handle = opendir($langDir))
{
	while (false !== ($file = readdir($handle)))
	{
		if ($file != "." && $file != ".." && preg_match('|\.lang|',$file))
		{
			if ($file == $lang.'.lang')
				$langList[$file] = 1;
			else
				$langList[$file] = 0;
		}
	}
	closedir($handle);
}

$langText = ";WAMPLANGUAGESTART
Type: separator; Caption: \"".$w_language."\";
";
ksort($langList);
foreach ($langList as $langname=>$langstatus)
{
  $cleanLangName = str_replace('.lang','',$langname);
  if ($langList[$langname] == 1)
    $langText .= 'Type: item; Caption: "'.$cleanLangName.'"; Glyph: 13; Action: multi; Actions: lang_'.$cleanLangName.'
';
  else
    $langText .= 'Type: item; Caption: "'.$cleanLangName.'"; Action: multi; Actions: lang_'.$cleanLangName.'
';

}

foreach ($langList as $langname=>$langstatus)
{
  $cleanLangName = str_replace('.lang','',$langname);
  $langText .= '[lang_'.$cleanLangName.']
Action: run; FileName: "'.$c_phpCli.'";Parameters: "changeLanguage.php '.$cleanLangName.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: resetservices
Action: readconfig;

';

}

$tpl = str_replace(';WAMPLANGUAGESTART',$langText,$tpl);

// ************************
// chargement du menu d'extensions de PHP
$myphpini = @file($c_phpConfFile) or die ("php.ini file not found");

//on recupere la conf courante
foreach($myphpini as $line) {
  $extMatch = array();
  if(preg_match('/^(;)?extension\s*=\s*"?([a-z0-9_]+)\.dll"?/i', $line, $extMatch)) {
    $ext_name = $extMatch[2];
    if($extMatch[1] == ';') {
      $ext[$ext_name] = '0';
    } else {
      $ext[$ext_name] = '1';
    }
  }
}

// on recupere la liste d'extensions presentes dans le r�pertoire ext
if ($handle = opendir($phpExtDir))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != ".." && strstr($file,'.dll'))
      $extDirContents[] = str_replace('.dll','',$file);
  }
  closedir($handle);
}

// on croise les deux tableaux
foreach ($extDirContents as $extname)
{
  if(in_array($extname, $phpNotLoadExt)) {
  	$ext[$extname] = -3; //dll not to be loaded by extension = in php.ini
  	continue;
  }
  if (!array_key_exists($extname,$ext))
    $ext[$extname] = -1; //dll file exists but not extension line in php.ini
}
foreach ($ext as $extname=>$value)
{
  if (!in_array($extname,$extDirContents))
    $ext[$extname] = -2; //extension line in php.ini but not dll file
}

ksort($ext);

//on construit le menu correspondant
$extText = ';WAMPPHP_EXTSTART
';
foreach ($ext as $extname=>$extstatus)
{
  if ($ext[$extname] == 1)
    $extText .= 'Type: item; Caption: "'.$extname.'"; Glyph: 13; Action: multi; Actions: php_ext_'.$extname.'
';
  elseif($ext[$extname] == -1)
  {
   	//[modif oto] - Warning icon to indicate problem with this extension: No extension line in php.ini
    $extText .= 'Type: item; Caption: "'.$extname.'"; Action: multi; Actions: php_ext_'.$extname.' ; Glyph: 19;
';
	}
  elseif($ext[$extname] == -2)
  {
   	//[modif oto] - Square red icon to indicate problem with this extension: no dll file in ext directory
    $extText .= 'Type: item; Caption: "'.$extname.'"; Action: multi; Actions: php_ext_'.$extname.' ; Glyph: 11;
';
	}
  elseif($ext[$extname] == -3)
  {
   	//[modif oto] - blue || icon to indicate that the dll must not be loaded by extension = in php.ini
    $extText .= 'Type: item; Caption: "'.$extname.'"; Action: multi; Actions: php_ext_'.$extname.' ; Glyph: 22;
';
	}
  else
  {
    $extText .= 'Type: item; Caption: "'.$extname.'"; Action: multi; Actions: php_ext_'.$extname.'
';
	}
}

foreach ($ext as $extname=>$extstatus)
{
  if ($ext[$extname] == 1)
    $extText .= '[php_ext_'.$extname.']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchPhpExt.php '.$extname.' off";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices;
Action: readconfig;
';
  elseif ($ext[$extname] == 0)
    $extText .= '[php_ext_'.$extname.']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchPhpExt.php '.$extname.' on";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  elseif ($ext[$extname] == -1)
    $extText .= '[php_ext_'.$extname.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 3 '.base64_encode($extname).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
  elseif ($ext[$extname] == -2)
    $extText .= '[php_ext_'.$extname.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 4 '.base64_encode($extname).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
  elseif ($ext[$extname] == -3)
    $extText .= '[php_ext_'.$extname.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 5 '.base64_encode($extname).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
}

$tpl = str_replace(';WAMPPHP_EXTSTART',$extText,$tpl);

// ************************
// menu de configuration de PHP
$myphpini = parse_ini_file($c_phpConfFile);

// on recupere les valeurs dans le php.ini
foreach($phpParams as $next_param_name=>$next_param_text)
{
  if (isset($myphpini[$next_param_text]))
  {
  	if(empty($myphpini[$next_param_text]))
  		$params_for_wampini[$next_param_name] = '0';
  	elseif(is_string($myphpini[$next_param_text]) && $myphpini[$next_param_text] !== 'On' && $myphpini[$next_param_text] !== 'Off' && $myphpini[$next_param_text] !== '1' && $myphpini[$next_param_text] !== '0') {
  	  $params_for_wampini[$next_param_name] = -2;
  	  $phpErrorMsg = "\nThe value of this PHP parameter must be modified in the file:\n".$c_phpConfFile."\nNot to change the wrong file, the best way to access this file is:\nWampmanager icon->PHP->php.ini\n";
  	}
  	elseif($myphpini[$next_param_text] == "Off")
  		$params_for_wampini[$next_param_name] = '0';
  	elseif($myphpini[$next_param_text] == 0)
  		$params_for_wampini[$next_param_name] = '0';
  	elseif($myphpini[$next_param_text] == "On")
  		$params_for_wampini[$next_param_name] = '1';
  	elseif($myphpini[$next_param_text] == 1)
  		$params_for_wampini[$next_param_name] = '1';
  	else
  	  $params_for_wampini[$next_param_name] = -2;
  }
  else //[modif oto] - Parameter in $phpParams (config.inc.php) does not exist in php.ini
    $params_for_wampini[$next_param_name] = -1;
}

$phpConfText = ";WAMPPHP_PARAMSSTART
";
foreach ($params_for_wampini as $paramname=>$paramstatus)
{
  if ($params_for_wampini[$paramname] == 1)
    $phpConfText .= 'Type: item; Caption: "'.$paramname.'"; Glyph: 13; Action: multi; Actions: '.$phpParams[$paramname].'
';
  elseif ($params_for_wampini[$paramname] == 0) //[modif oto] - It does not display non-existent settings in php.ini
    $phpConfText .= 'Type: item; Caption: "'.$paramname.'"; Action: multi; Actions: '.$phpParams[$paramname].'
';
  elseif ($params_for_wampini[$paramname] == -2) //[modif oto] - || blue to indicate different from 0 or 1 or On or Off
     $phpConfText .= 'Type: item; Caption: "'.$paramname.' = '.$myphpini[$paramname].'"; Action: multi; Actions: '.$phpParams[$paramname].' ; Glyph: 22;
';
}

foreach ($params_for_wampini as $paramname=>$paramstatus)
{
  if ($params_for_wampini[$paramname] == 1)
  $phpConfText .= '['.$phpParams[$paramname].']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchPhpParam.php '.$phpParams[$paramname].' off";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  elseif ($params_for_wampini[$paramname] == 0)  //[modif oto] - It does not act for non-existent settings in php.ini
  	$phpConfText .= '['.$phpParams[$paramname].']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchPhpParam.php '.$phpParams[$paramname].' on";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  elseif ($params_for_wampini[$paramname] == -2)  //[modif oto] - Parameter is neither 'on' nor 'off'
  	$phpConfText .= '['.$phpParams[$paramname].']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 6 '.base64_encode($paramname).' '.base64_encode($phpErrorMsg).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';

}

$tpl = str_replace(';WAMPPHP_PARAMSSTART',$phpConfText,$tpl);

// ************************
// modules Apache
$myhttpd = @file($c_apacheConfFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) or die ("httpd.conf file not found");

$recherche = array("modules/");
$mod_load = array();
foreach($myhttpd as $line)
{
  if (preg_match('|^#LoadModule|',$line))
  {
    $mod_table = explode(' ', $line);
    $mod_name = $mod_table[1];
    $mod[$mod_name] = '0';
		$load_module = str_replace($recherche,'',$mod_table[2]);
		$mod_load[$mod_name] = $load_module;
  }
  elseif (preg_match('|^LoadModule|',$line))
  {
    $mod_table = explode(' ', $line);
    $mod_name = $mod_table[1];
    $mod[$mod_name] = '1';
		$load_module = str_replace($recherche,'',$mod_table[2]);
		$mod_load[$mod_name] = $load_module;
  }
}

// on recup�re la liste des modules pr�sents dans le r�pertoire /modules/
$modDirContents = array();
if ($handle = opendir($c_apacheConfFile = $c_apacheVersionDir.'/apache'.$wampConf['apacheVersion'].'/modules/'))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != ".." && strstr($file,'.so'))
			$modDirContents[] = $file;
  }
  closedir($handle);
}
//[modif oto] - On croise les tableaux
//D�tection pr�sence du module xxxxxx.so demand� par Loadmodule
foreach ($mod as $modname=>$value)
{
	if(in_array($modname, $apacheModNotDisable)) {
		$mod[$modname] = -3 ; //not to be switched in Apache Modules sub-menu
		continue;
	}
	if(!in_array($mod_load[$modname], $modDirContents))
		$mod[$modname] = -1 ;
}
//D�tection de Loadmodule dans httpd.conf pour chaque module dans /modules/
foreach($modDirContents as $module)
{
	if(!in_array($module, $mod_load))
	{
		$modname = str_replace(array("mod_",".so"),array("","_module"),$module);
		$mod[$modname] = -2 ;
	}
}
ksort($mod);

$httpdText = ";WAMPAPACHE_MODSTART
";

foreach ($mod as $modname=>$modstatus)
{
  if ($modstatus == 1)
    $httpdText .= 'Type: item; Caption: "'.$modname.'"; Glyph: 13; Action: multi; Actions: apache_mod_'.$modname.'
';
	elseif ($modstatus == -1)
		$httpdText .= 'Type: item; Caption: "'.$modname.'"; Action: multi; Actions: apache_mod_'.$modname.' ; Glyph: 11;
';
	elseif ($modstatus == -2)
		$httpdText .= 'Type: item; Caption: "'.$modname.'"; Action: multi; Actions: apache_mod_'.$modname.' ; Glyph: 19;
';
	elseif ($modstatus == -3)
		$httpdText .= 'Type: item; Caption: "'.$modname.'"; Action: multi; Actions: apache_mod_'.$modname.' ; Glyph: 22;
';
  else
    $httpdText .= 'Type: item; Caption: "'.$modname.'"; Action: multi; Actions: apache_mod_'.$modname.'
';

}

foreach ($mod as $modname=>$modstatus)
{
  if ($mod[$modname] == 1)
    $httpdText .= '[apache_mod_'.$modname.']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchApacheMod.php '.$modname.' on";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  elseif ($mod[$modname] == 0)
    $httpdText .= '[apache_mod_'.$modname.']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchApacheMod.php '.$modname.' off";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  elseif ($mod[$modname] == -1)
    $httpdText .= '[apache_mod_'.$modname.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 7 '.base64_encode($modname).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
  elseif ($mod[$modname] == -2)
    $httpdText .= '[apache_mod_'.$modname.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 8 '.base64_encode($modname).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
  elseif ($mod[$modname] == -3)
    $httpdText .= '[apache_mod_'.$modname.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 12 '.base64_encode($modname).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';

}

$tpl = str_replace(';WAMPAPACHE_MODSTART',$httpdText,$tpl);

// ************************
// alias Apache
if ($handle = opendir($aliasDir))
{
  while (false !== ($file = readdir($handle)))
  {
    if ($file != "." && $file != ".." && strstr($file,'.conf'))
      $aliasDirContents[] = $file;
  }
  closedir($handle);
}

$myreplace = $myreplacemenu = $mydeletemenu = '';
foreach ($aliasDirContents as $one_alias)
{
  $mypattern = ';WAMPADDALIAS';
  $newalias_dir = str_replace('.conf','',$one_alias);
  $alias_contents = @file_get_contents ($aliasDir.$one_alias);
  preg_match('|^Alias /'.$newalias_dir.'/ "(.+)"|',$alias_contents,$match);
  if (isset($match[1]))
    $newalias_dest = $match[1];
  else
    $newalias_dest = NULL;

    $myreplace .= 'Type: submenu; Caption: "http://localhost/'.$newalias_dir.'/"; SubMenu: alias_'.str_replace(' ','_',$newalias_dir).'; Glyph: 3
';

  $myreplacemenu .= '
[alias_'.str_replace(' ','_',$newalias_dir).']
Type: separator; Caption: "'.$newalias_dir.'"
Type: item; Caption: "'.$w_editAlias.'"; Glyph: 6; Action: multi; Actions: edit_'.str_replace(' ','_',$newalias_dir).'
Type: item; Caption: "'.$w_editHtaccess.'"; Glyph: 6; Action: run; FileName: "'.$c_editor.'"; parameters: "'.$newalias_dest.'.htaccess"
Type: item; Caption: "'.$w_deleteAlias.'"; Glyph: 6; Action: multi; Actions: delete_'.str_replace(' ','_',$newalias_dir).'
';

  $mydeletemenu .= '
[delete_'.str_replace(' ','_',$newalias_dir).']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpExe.'";Parameters: "-c . deleteAlias.php '.str_replace(' ','-whitespace-',$newalias_dir).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
[edit_'.str_replace(' ','_',$newalias_dir).']
Action: run; FileName: "'.$c_editor.'"; parameters:"'.$c_installDir.'/alias/'.$newalias_dir.'.conf"; Flags: waituntilterminated
Action: service; Service: '.$c_apacheService.'; ServiceAction: restart;
';

}

$tpl = str_replace($mypattern,$myreplace.$myreplacemenu.$mydeletemenu,$tpl);

// ************************
// versions de PHP
$phpVersionList = listDir($c_phpVersionDir,'checkPhpConf');

$myPattern = ';WAMPPHPVERSIONSTART';
$myreplace = $myPattern."
";
$myreplacemenu = '';
foreach ($phpVersionList as $onePhp)
{
  $phpGlyph = '';
  $onePhpVersion = str_ireplace('php','',$onePhp);
  //on verifie si le PHP est compatible avec la version d'apache courante
  unset($phpConf);
  include $c_phpVersionDir.'/php'.$onePhpVersion.'/'.$wampBinConfFiles;

  $apacheVersionTemp = $wampConf['apacheVersion'];
  while (!isset($phpConf['apache'][$apacheVersionTemp]) && $apacheVersionTemp != '')
  {
    $pos = strrpos($apacheVersionTemp,'.');
    $apacheVersionTemp = substr($apacheVersionTemp,0,$pos);
  }

  // PHP incompatible avec la version courante d'apache
  $incompatiblePhp = 0;
  if (empty($apacheVersionTemp))
  {
    $incompatiblePhp = -1;
    $phpGlyph = '; Glyph: 19';
		$phpErrorMsg = "apacheVersion = empty in wampmanager.conf file";
  }
  elseif (empty($phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']))
  {
    $incompatiblePhp = -2;
    $phpGlyph = '; Glyph: 19';
		$phpErrorMsg = "\$phpConf['apache']['".$apacheVersionTemp."']['LoadModuleFile'] does not exists or is empty in ".$c_phpVersionDir.'/php'.$onePhpVersion.'/'.$wampBinConfFiles;
  }
  elseif (!file_exists($c_phpVersionDir.'/php'.$onePhpVersion.'/'.$phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']))
  {
    $incompatiblePhp = -3;
    $phpGlyph = '; Glyph: 19';
		$phpErrorMsg = $c_phpVersionDir.'/php'.$onePhpVersion.'/'.$phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']." does not exists.";
  }

  if ($onePhpVersion === $wampConf['phpVersion'])
    $phpGlyph = '; Glyph: 13';

    $myreplace .= 'Type: item; Caption: "'.$onePhpVersion.'"; Action: multi; Actions:switchPhp'.$onePhpVersion.$phpGlyph.'
';
  if ($incompatiblePhp == 0)
  {
  $myreplacemenu .= '[switchPhp'.$onePhpVersion.']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: ignoreerrors waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchPhpVersion.php '.$onePhpVersion.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpExe.'";Parameters: "-c . switchMysqlPort.php '.$c_UsedMysqlPort.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  }
  else
  {
  $myreplacemenu .= '[switchPhp'.$onePhpVersion.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 1 '.base64_encode($onePhpVersion).' '.base64_encode($phpErrorMsg).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
  }

}
/*$myreplace .= 'Type: separator;
Type: item; Caption: "Get more..."; Action: run; FileName: "'.$c_navigator.'"; Parameters: "http://www.wampserver.com/addons_php.php";
';*/

$tpl = str_replace($myPattern,$myreplace.$myreplacemenu,$tpl);

// ************************
// versions de Apache

$apacheVersionList = listDir($c_apacheVersionDir,'checkApacheConf');

$myPattern = ';WAMPAPACHEVERSIONSTART';
$myreplace = $myPattern."
";
$myreplacemenu = '';

foreach ($apacheVersionList as $oneApache)
{
  $apacheGlyph = '';
  $oneApacheVersion = str_ireplace('apache','',$oneApache);

	//on verifie si le apache est compatible avec la version d'apache courante
  unset($phpConf);
  include $c_phpVersionDir.'/php'.$wampConf['phpVersion'].'/'.$wampBinConfFiles;
  $apacheVersionTemp = $oneApacheVersion;
  while (!isset($phpConf['apache'][$apacheVersionTemp]) && $apacheVersionTemp != '')
  {
    $pos = strrpos($apacheVersionTemp,'.');
    $apacheVersionTemp = substr($apacheVersionTemp,0,$pos);
  }

  // apache incompatible avec la version courante de PHP
  $incompatibleApache = 0;
  if (empty($apacheVersionTemp))
  {
    $incompatibleApache = -1;
    $apacheGlyph = '; Glyph: 19';
		$apacheErrorMsg = "apacheVersion = empty in wampmanager.conf file";
  }
  elseif (!isset($phpConf['apache'][$apacheVersionTemp]['LoadModuleFile'])
      || empty($phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']))
  {
    $incompatibleApache = -2;
    $apacheGlyph = '; Glyph: 19';
		$apacheErrorMsg = "\$phpConf['apache']['".$apacheVersionTemp."']['LoadModuleFile'] does not exists or is empty in ".$c_phpVersionDir.'/php'.$wampConf['phpVersion'].'/'.$wampBinConfFiles;
  }
  elseif (!file_exists($c_phpVersionDir.'/php'.$wampConf['phpVersion'].'/'.$phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']))
  {
    $incompatibleApache = -3;
    $apacheGlyph = '; Glyph: 23';
		$apacheErrorMsg = $c_phpVersionDir.'/php'.$wampConf['phpVersion'].'/'.$phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']." does not exists.".PHP_EOL.PHP_EOL."First switch on a version of PHP that contains ".$phpConf['apache'][$apacheVersionTemp]['LoadModuleFile']." file before you change to Apache version ".$oneApacheVersion.".";
  }

  if (isset($apacheConf))
    $apacheConf = NULL;
  include $c_apacheVersionDir.'/apache'.$oneApacheVersion.'/'.$wampBinConfFiles;

  if ($oneApacheVersion === $wampConf['apacheVersion'])
    $apacheGlyph = '; Glyph: 13';

  $myreplace .= 'Type: item; Caption: "'.$oneApacheVersion.'"; Action: multi; Actions:switchApache'.$oneApacheVersion.$apacheGlyph.'
';

  if ($incompatibleApache == 0)
  {
    $myreplacemenu .= '[switchApache'.$oneApacheVersion.']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: ignoreerrors waituntilterminated
Action: run; FileName: "'.$c_apacheExe.'"; Parameters: "'.$c_apacheServiceRemoveParams.'"; ShowCmd: hidden; Flags: ignoreerrors waituntilterminated
Action: closeservices; Flags: ignoreerrors
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchApacheVersion.php '.$oneApacheVersion.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchPhpVersion.php '.$wampConf['phpVersion'].'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_apacheVersionDir.'/apache'.$oneApacheVersion.'/'.$apacheConf['apacheExeDir'].'/'.$apacheConf['apacheExeFile'].'"; Parameters: "'.$apacheConf['apacheServiceInstallParams'].'"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; Filename: "sc"; Parameters: "\\\\. config '.$c_apacheService.' start= demand"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpExe.'";Parameters: "-c . switchWampPort.php '.$c_UsedPort.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpExe.'";Parameters: "-c . onlineOffline.php '.$c_OnOffLine.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
  }
  else
  {
    $myreplacemenu .= '[switchApache'.$oneApacheVersion.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 2 '.base64_encode($oneApacheVersion).' '.base64_encode($apacheErrorMsg).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
  }
}
/*$myreplace .= 'Type: separator;
Type: item; Caption: "Get more..."; Action: run; FileName: "'.$c_navigator.'"; Parameters: "http://www.wampserver.com/addons_apache.php";
';*/

$tpl = str_replace($myPattern,$myreplace.$myreplacemenu,$tpl);

// ************************
// versions de MySQL
$mysqlVersionList = listDir($c_mysqlVersionDir,'checkMysqlConf');

$myPattern = ';WAMPMYSQLVERSIONSTART';
$myreplace = $myPattern."
";
$myreplacemenu = '';
foreach ($mysqlVersionList as $oneMysql)
{
  $oneMysqlVersion = str_ireplace('mysql','',$oneMysql);
  unset($mysqlConf);
  include $c_mysqlVersionDir.'/mysql'.$oneMysqlVersion.'/'.$wampBinConfFiles;

	//[modif oto] - Check name of the group [wamp...] under '# The MySQL server' in my.ini file
	//    must be the name of the mysql service.
	$myIniFile = $c_mysqlVersionDir.'/mysql'.$oneMysqlVersion.'/'.$mysqlConf['mysqlConfFile'];
	$myIniContents = file_get_contents($myIniFile);

	if(strpos($myIniContents, "[".$c_mysqlService."]") === false) {
		$myIniContents = preg_replace("/^\[wamp.*\]$/m", "[".$c_mysqlService."]", $myIniContents, 1, $count);
		if(!is_null($myIniContents) && $count == 1) {
			$fp = fopen($myIniFile,'w');
			fwrite($fp,$myIniContents);
			fclose($fp);
			$mysqlServer[$oneMysqlVersion] = 0;
		}
		else { //The MySQL server has not the same name as mysql service
			$mysqlServer[$oneMysqlVersion] = -1;
		}
	}
	else
		$mysqlServer[$oneMysqlVersion] = 0;
	unset($myIniContents);

	if ($oneMysqlVersion === $wampConf['mysqlVersion'] && $mysqlServer[$oneMysqlVersion] == 0)
  	$mysqlServer[$oneMysqlVersion] = 1;

	if ($mysqlServer[$oneMysqlVersion] == 1) {
    $myreplace .= 'Type: item; Caption: "'.$oneMysqlVersion.'"; Action: multi; Actions:switchMysql'.$oneMysqlVersion.'; Glyph: 13
';
	}
  elseif($mysqlServer[$oneMysqlVersion] == 0) {
    $myreplace .= 'Type: item; Caption: "'.$oneMysqlVersion.'"; Action: multi; Actions:switchMysql'.$oneMysqlVersion.'
';
  	$myreplacemenu .= '[switchMysql'.$oneMysqlVersion.']
Action: service; Service: '.$c_mysqlService.'; ServiceAction: stop; Flags: ignoreerrors waituntilterminated
Action: run; FileName: "'.$c_mysqlExe.'"; Parameters: "'.$c_mysqlServiceRemoveParams.'"; ShowCmd: hidden; Flags: ignoreerrors waituntilterminated
Action: closeservices;
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchMysqlVersion.php '.$oneMysqlVersion.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_mysqlVersionDir.'/mysql'.$oneMysqlVersion.'/'.$mysqlConf['mysqlExeDir'].'/'.$mysqlConf['mysqlExeFile'].'"; Parameters: "'.$mysqlConf['mysqlServiceInstallParams'].'"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpExe.'";Parameters: "-c . switchMysqlPort.php '.$c_UsedMysqlPort.'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_mysqlService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: resetservices;
Action: readconfig;

';
	}
  elseif($mysqlServer[$oneMysqlVersion] == -1) {
    $myreplace .= 'Type: item; Caption: "'.$oneMysqlVersion.'"; Action: multi; Actions:switchMysql'.$oneMysqlVersion.'; Glyph: 19
';
  	$myreplacemenu .= '[switchMysql'.$oneMysqlVersion.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 13 '.base64_encode($myIniFile).' '.base64_encode($c_mysqlService).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
	}

}
/*$myreplace .= 'Type: separator;
Type: item; Caption: "Get more..."; Action: run; FileName: "'.$c_navigator.'"; Parameters: "http://www.wampserver.com/addons_mysql.php";
';
*/
$tpl = str_replace($myPattern,$myreplace.$myreplacemenu,$tpl);

//[modif oto] - Submenu Projects
if(strpos($tpl,";WAMPPROJECTSUBMENU") !== false && isset($wampConf['ProjectSubMenu']) && $wampConf['ProjectSubMenu'] == "on")
{
	//Add item for submenu
	$myPattern = ';WAMPPROJECTSUBMENU';
	$myreplace = $myPattern."
";
	$myreplacesubmenu = 'Type: submenu; Caption: "'.$w_projectsSubMenu.'"; Submenu: myProjectsMenu; Glyph: 3
';
	$tpl = str_replace($myPattern,$myreplace.$myreplacesubmenu,$tpl);

	//Add submenu
	$myPattern = ';WAMPMENULEFTEND';
	$myreplace = $myPattern."
";
	$myreplacesubmenu = '

[myProjectsMenu]
;WAMPPROJECTMENUSTART
;WAMPPROJECTMENUEND

';
	$tpl = str_replace($myPattern,$myreplace.$myreplacesubmenu,$tpl);

	//Construct submenu
	$myPattern = ';WAMPPROJECTMENUSTART';
	$myreplace = $myPattern."
";
	// Place projects into submenu Hosts
	// Folder to ignore in projects
	$projectsListIgnore = array ('.','..','wampthemes');
	// List projects
	$myDir = $wwwDir;
	if(substr($myDir,-1) != "/")
		$myDir .= "/";
	$handle=opendir($myDir);
	$projectContents = array();
	while (($file = readdir($handle))!==false)
	{
		if (is_dir($myDir.$file) && !in_array($file,$projectsListIgnore))
			$projectContents[] = $file;
	}
	closedir($handle);

	$myreplacesubmenuProjects = '';
	if (count($projectContents) > 0)
	{
		for($i = 0 ; $i < count($projectContents) ; $i++)
		{ //[modif oto] Support de suppressLocalhost dans wampmanager.conf
			$myreplacesubmenuProjects .= 'Type: item; Caption: "'.$projectContents[$i].'"; Action: run; FileName: "'.$c_navigator.'"; Parameters: "';
			if($c_suppressLocalhost)
				 $myreplacesubmenuProjects .= 'http://'.$projectContents[$i].$UrlPort.'/"';
			else
				$myreplacesubmenuProjects .= 'http://localhost'.$UrlPort.'/'.$projectContents[$i].'/"';
			$myreplacesubmenuProjects .= '; Glyph: 5
';
		}
	}
	$tpl = str_replace($myPattern,$myreplace.$myreplacesubmenuProjects,$tpl);
}

//[modif oto] - Submenu Virtual Hosts
if(strpos($tpl,";WAMPVHOSTSUBMENU") !== false && isset($wampConf['VirtualHostSubMenu']) && $wampConf['VirtualHostSubMenu'] == "on")
{
	//Add item for submenu
	$myPattern = ';WAMPVHOSTSUBMENU';
	$myreplace = $myPattern."
";
	$myreplacesubmenu = 'Type: submenu; Caption: "'.$w_virtualHostsSubMenu.'"; Submenu: myVhostsMenu; Glyph: 3
';
	$tpl = str_replace($myPattern,$myreplace.$myreplacesubmenu,$tpl);
	//Add submenu
	$myPattern = ';WAMPMENULEFTEND';
	$myreplace = $myPattern."
";
	$myreplacesubmenu = '

[myVhostsMenu]
;WAMPVHOSTMENUSTART
;WAMPVHOSTMENUEND

';
	$tpl = str_replace($myPattern,$myreplace.$myreplacesubmenu,$tpl);
	$myPattern = ';WAMPVHOSTMENUSTART';
	$myreplace = $myPattern."
Type: separator; Caption: \"".$w_virtualHostsSubMenu."\"
";
	$myreplacesubmenuVhosts = '';

	$virtualHost = check_virtualhost();

	//is Include conf/extra/httpd-vhosts.conf uncommented?
	if($virtualHost['include_vhosts'] === false) {
		$myreplacesubmenuVhosts .= 'Type: item; Caption: "Virtual Host ERROR"; Action: multi; Actions: server_not_included; Glyph: 21
';
    $myreplacesubmenuVhosts .= '[server_not_included]
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 14";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
	}
	else
	{
		if($virtualHost['vhosts_exist'] === false) {
			$myreplacesubmenuVhosts .= 'Type: item; Caption: "Virtual Host ERROR"; Action: multi; Actions: server_not_exists; Glyph: 21
';
    	$myreplacesubmenuVhosts .= '[server_not_exists]
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 15 '.base64_encode($virtualHost['vhosts_file']).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
		}
		else
		{
			$server_name = array();

			if($virtualHost['nb_Server'] > 0)
			{
				$nb_Server = $virtualHost['nb_Server'];
				$nb_Virtual = $virtualHost['nb_Virtual'];
				$nb_Document = $virtualHost['nb_Document'];
				$nb_Directory = $virtualHost['nb_Directory'];
				$nb_End_Directory = $virtualHost['nb_End_Directory'];

				$port_number = true;
				//Check number of <Directory and </Directory equals to number of ServerName
				if($nb_Directory < $nb_Server || $nb_End_Directory != $nb_Directory) {
					$value = "ServerName_Directory";
					$server_name[$value] = -2;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}
				//Check number of DocumentRoot equals to number of ServerName
				if($nb_Document != $nb_Server) {
					$value = "ServerName_Document";
					$server_name[$value] = -7;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}
				//Check validity of DocumentRoot
				if($virtualHost['document'] === false) {
					foreach($virtualHost['documentPath'] as $value) {
						if($virtualHost['documentPathValid'][$value] === false) {
							$documentPathError = $value;
							break;
						}
					}
					$value = "DocumentRoot error";
					$server_name[$value] = -8;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}
				//Check validity of Directory Path
				if($virtualHost['directory'] === false) {
					foreach($virtualHost['directoryPath'] as $value) {
						if($virtualHost['directoryPathValid'][$value] === false) {
							$directoryPathError = $value;
							break;
						}
					}
					$value = "Directory Path error";
					$server_name[$value] = -9;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}

				//Check number of <VirtualHost equals or > to number of ServerName
				if($nb_Server != $nb_Virtual) {
					$value = "ServerName_Virtual";
					$server_name[$value] = -3;
					$port_number = false;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}

				//Check number of port definition of <VirtualHost *:xx> equals to number of ServerName
				if($virtualHost['nb_Virtual_Port'] != $nb_Virtual) {
					$value = "VirtualHost_Port";
					$server_name[$value] = -4;
					$port_number = false;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}
				//Check validity of port number
				if($port_number && $virtualHost['port_number'] === false) {
					$value = "VirtualHost_PortValue";
					$server_name[$value] = -5;
					$port_number = false;
					$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 23
';
				}

				foreach($virtualHost['ServerName'] as $key => $value) {
					if($virtualHost['ServerNameValid'][$value] === false) {
						$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 20
';
						$server_name[$value] = -1;
					}
					elseif($virtualHost['ServerNameValid'][$value] === true) {
						$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: run; FileName: "'.$c_navigator.'"; Parameters: "http://'.$value.$UrlPort.'/"; Glyph: 5
';
						$server_name[$value] = 1;
					}
					else {
						$myreplacesubmenuVhosts .= 'Type: item; Caption: "'.$value.'"; Action: multi; Actions: server_'.$value.'; Glyph: 20
';
						$server_name[$value] = -6;
					}
				} //End foreach


				foreach($server_name as $name=>$value) {
					if($server_name[$name] != 1) {
						if($server_name[$name] == -1) {
    					$myreplacesubmenuVhosts .= '[server_'.$name.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 9 '.base64_encode($name).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
						}
						else {
							if($server_name[$name] == -2)
								$message = "In the httpd-vhosts.conf file:\r\n\r\n\tThe number of\r\n\r\n\t\t<Directory ...>\r\n\t\t</Directory>\r\n\r\n\tis not equal to the number of\r\n\r\n\t\tServerName\r\n\r\nThey should be identical.";
							elseif($server_name[$name] == -3)
								$message = "In the httpd-vhosts.conf file:\r\n\r\n\tThe number of\r\n\r\n\t\t<VirtualHost ...>\r\n\tis not equal to the number of\r\n\r\n\t\tServerName\r\n\r\nThey should be identical.\r\n\r\n\tCorrect syntax is: <VirtualHost *:80>\r\n";
							elseif($server_name[$name] == -4)
								$message = "In the httpd-vhosts.conf file:\r\n\r\n\tPort number into <VirtualHost *:port>\r\n\tis not defined for all\r\n\r\n\t\t<VirtualHost...>\r\n\r\n\tCorrect syntax is: <VirtualHost *:xx>\r\n\r\n\t\twith xx = port number [80 by default]\r\n";
							elseif($server_name[$name] == -5)
								$message = "In the httpd-vhosts.conf file:\r\n\r\n\tPort number into <VirtualHost *:port>\r\n\thas not correct value or is not the same for each <VirtualHost *:xx>\r\n\r\nValue are:".print_r($virtualHost['virtual_port'],true)."\r\n";
							elseif($server_name[$name] == -6)
								$message = "The httpd-vhosts.conf file has not been cleaned.\r\nThere remain VirtualHost examples like: dummy-host.example.com\r\n";
							elseif($server_name[$name] == -7)
								$message = "In the httpd-vhosts.conf file:\r\n\r\n\tThe number of\r\n\r\n\t\tDocumentRoot\r\n\tis not equal to the number of\r\n\r\n\t\tServerName\r\n\r\nThey should be identical.\r\n";
							elseif($server_name[$name] == -8)
								$message = "In the httpd-vhosts.conf file:\r\n\r\nThe DocumentRoot path\r\n\r\n\t".$documentPathError."\r\n\r\ndoes not exits\r\n";
							elseif($server_name[$name] == -9)
								$message = "In the httpd-vhosts.conf file:\r\n\r\nThe Directory path\r\n\r\n\t".$directoryPathError."\r\n\r\ndoes not exits\r\n";

    					$myreplacesubmenuVhosts .= '[server_'.$name.']
Action: run; FileName: "'.$c_phpExe.'";Parameters: "msg.php 11 '.base64_encode($message).'";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
';
						}
					}
				}
			}
		}
	}
	$tpl = str_replace($myPattern,$myreplace.$myreplacesubmenuVhosts,$tpl);
}

//[modif oto] Right submenu Wampmanager settings
if(strpos($tpl,";WAMPSETTINGSSTART") !== false) {
	// on recupere les valeurs dans wampConf
	foreach($wamp_Param as $value)
	{
	  if (isset($wampConf[$value]))
	  {
	    $wampConfParams[$value] = $value;
	    if ($wampConf[$value] == 'on')
	      $params_for_wampconf[$value] = '1';
	    elseif ($wampConf[$value] == 'off')
	      $params_for_wampconf[$value] = '0';
	    else
	      $params_for_wampconf[$value] = '-1';
	  }
	  else {//Param�tre n'existe pas dans wampserver.conf
	    $params_for_wampconf[$value] = -1;
	    $wampConfParams[$value] = $value;
	  }
	}

	$wampConfText = ";WAMPSETTINGSSTART
Type: Separator; Caption: \"".$w_wampSettings."\"
";
	foreach ($params_for_wampconf as $paramname=>$paramstatus)
	{
	  if ($params_for_wampconf[$paramname] == 1)
	    $wampConfText .= 'Type: item; Caption: "'.$w_settings[$paramname].'"; Glyph: 13; Action: multi; Actions: '.$wampConfParams[$paramname].'
';
	  elseif ($params_for_wampconf[$paramname] == 0)
	    $wampConfText .= 'Type: item; Caption: "'.$w_settings[$paramname].'"; Action: multi; Actions: '.$wampConfParams[$paramname].'
';
	  elseif ($params_for_wampconf[$paramname] == -1)
	    $wampConfText .= 'Type: item; Caption: "'.$w_settings[$paramname].'"; Action: multi; Actions: '.$wampConfParams[$paramname].' ;Glyph: 11;
';}

	foreach ($params_for_wampconf as $paramname=>$paramstatus)
	{
	  if ($params_for_wampconf[$paramname] == 1)
	  	$wampConfText .= '['.$wampConfParams[$paramname].']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchWampParam.php '.$wampConfParams[$paramname].' off";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
	  elseif ($params_for_wampconf[$paramname] == 0)
	  	$wampConfText .= '['.$wampConfParams[$paramname].']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchWampParam.php '.$wampConfParams[$paramname].' on";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';
	  elseif ($params_for_wampconf[$paramname] == -1)
	  	$wampConfText .= '['.$wampConfParams[$paramname].']
Action: service; Service: '.$c_apacheService.'; ServiceAction: stop; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "switchWampParam.php '.$wampConfParams[$paramname].' create off [options]";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "'.$c_phpCli.'";Parameters: "refresh.php";WorkingDir: "'.$c_installDir.'/scripts"; Flags: waituntilterminated
Action: run; FileName: "net"; Parameters: "start '.$c_apacheService.'"; ShowCmd: hidden; Flags: waituntilterminated
Action: resetservices
Action: readconfig;
';

	}

	$tpl = str_replace(';WAMPSETTINGSSTART',$wampConfText,$tpl);
}

// ************************
//on enregistre le fichier wampmanager.ini

$fp = fopen($wampserverIniFile,'w');
fwrite($fp,$tpl);
fclose($fp);

//Checking symbolic links from Apache/bin
//  on the dll files and phpForApache.ini in the active version of php
linkPhpDllToApacheBin($wampConf['phpVersion']);

?>