<?php
/**
 * @package    Joomla.Module - Lifetime counter
 * @version    __DEPLOY_VERSION__
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Date\Date;

defined('_JEXEC') or die;

JLoader::register('ProductionCalendar', JPATH_LIBRARIES . '/production-calendar/ProductionCalendar.php');

if(!class_exists('ProductionCalendar'))
{
	return false;
}

if(!function_exists('checkHolidays')){
	function checkHolidays(&$date, &$days)
	{
		if (ProductionCalendar::isWeekend($date))
		{
			$days['w']++;

			return;
		}
		else
		{
			if (ProductionCalendar::isPreHoliday($date))
			{
				$days['p']++;

				return;
			}
			else
			{
				if (ProductionCalendar::isHoliday($date))
				{
					$days['h']++;

					return;
				}
				else
				{
					$days['o']++;
				}
			}
		}

		return;
	}
}

$startDate = new Date($params->get('start_date', 'NOW'));
$nowDate   = new Date('NOW');
$difDate   = $startDate->diff($nowDate);

if ($difDate->days === 0)
{
	return false;
}

$counter = $params->get('start_counter', 0);
$plusDay = $params->get('plus', 0);
$plusPH  = $params->get('plus_pre_holiday', 0);
$plusH   = $params->get('plus_holiday', 0);
$plusW   = $params->get('plus_weekend', 0);

if (empty($plusPH) && empty($plusH) && empty($plusW))
{
	$counter += $difDate->days * $plusDay;
}
else
{
	$days = [
		'o' => 0, //working days
		'w' => 0, //weekend days
		'p' => 0, //after holiday days
		'h' => 0, //holiday days
	];

	if ($difDate->invert === 0)
	{
		while ($startDate <= $nowDate)
		{
			checkHolidays($startDate, $days);
			$startDate->modify('+1 day');
		}
	}
	else
	{
		while ($nowDate <= $startDate)
		{
			checkHolidays($nowDate, $days);
			$nowDate->modify('+1 day');
		}
	}

	$counter += $days['o'] * $plusDay;
	$counter += $days['w'] * $plusW;
	$counter += $days['h'] * $plusH;
	$counter += $days['p'] * $plusPH;
}

if ($difDate->invert === 1)
{
	$counter = $counter * -1;
}

if (empty($counter))
{
	return false;
}

$preText  = $params->get('pre_text', '');
$postText = $params->get('pos_ttext', '');
$backgroundimage = $params->get('backgroundimage', '');

$moduleclass = htmlspecialchars($params->get('moduleclass', ''));
$moduleid = htmlspecialchars($params->get('moduleid', ''));

require ModuleHelper::getLayoutPath('mod_lifetime_counter', $params->get('layout', 'default'));