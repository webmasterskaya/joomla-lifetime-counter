<?php
/**
 * @package    Joomla.Module - Lifetime counter
 * @version    1.0.0
 * @author     Artem Vasilev - webmasterskaya.xyz
 * @copyright  Copyright (c) 2018 - 2020 Webmasterskaya. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://webmasterskaya.xyz/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

class mod_Lifetime_CounterInstallerScript
{
	/**
	 * Minimum PHP version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $minimumPhp = '7.1';

	/**
	 * Minimum Joomla version required to install the extension.
	 *
	 * @var  string
	 *
	 * @since  1.0.0
	 */
	protected $minimumJoomla = '3.9.0';

	/**
	 * Method to run before an install/update/uninstall method
	 * $parent is the class calling this method
	 * $type is the type of change (install, update or discover_install)
	 *
	 * @param $type
	 * @param $parent
	 *
	 * @return bool|void
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function preflight($type, $parent)
	{
		jimport('joomla.version');
		// Check compatible Joomla version
		$jversion = new JVersion();

		if (!$jversion->isCompatible($this->minimumJoomla))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_YAMETRIKINSERT_WRONG_JOOMLA',
				$this->minimumJoomla), 'error');

			return false;
		}

		// Check PHP
		if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
		{
			JFactory::getApplication()->enqueueMessage(JText::sprintf('PKG_QUANTUMMANAGER_ERROR_COMPATIBLE_PHP',
				$this->minimumPhp), 'error');

			return false;
		}
	}

	/**
	 * Method to run after an install/update/uninstall method
	 * $parent is the class calling this method
	 * $type is the type of change (install, update or discover_install)
	 *
	 * @param $type
	 * @param $parent
	 *
	 * @return bool|void
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function postflight($type, $parent)
	{
		if (!$this->installDependencies($parent))
		{
			return false;
		}
	}

	/**
	 * @param $parent
	 *
	 * @return bool
	 * @throws Exception
	 *
	 *
	 * @since 1.0.0
	 */
	protected function installDependencies($parent)
	{
		// Load installer plugins for assistance if required:
		PluginHelper::importPlugin('installer');

		$app = Factory::getApplication();

		$package = null;

		// This event allows an input pre-treatment, a custom pre-packing or custom installation.
		// (e.g. from a JSON description).
		$results = $app->triggerEvent('onInstallerBeforeInstallation', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			return false;
		}

		$url = 'https://github.com/webmasterskaya/joomla-production-calendar/releases/latest/download/lib_production-calendar.zip';

		// Download the package at the URL given.
		$p_file = InstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

			return false;
		}

		$config   = Factory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file.
		$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		// This event allows a custom installation of the package or a customization of the package:
		$results = $app->triggerEvent('onInstallerBeforeInstaller', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			return false;
		}

		// Get an installer instance.
		$installer = new Installer();

		/*
		 * Check for a Joomla core package.
		 * To do this we need to set the source path to find the manifest (the same first step as JInstaller::install())
		 *
		 * This must be done before the unpacked check because JInstallerHelper::detectType() returns a boolean false since the manifest
		 * can't be found in the expected location.
		 */
		if (is_array($package) && isset($package['dir']) && is_dir($package['dir']))
		{
			$installer->setPath('source', $package['dir']);

			if (!$installer->findManifest())
			{
				InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
				$app->enqueueMessage(Text::sprintf('COM_INSTALLER_INSTALL_ERROR', '.'), 'warning');

				return false;
			}
		}

		// Was the package unpacked?
		if (!$package || !$package['type'])
		{
			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			$app->enqueueMessage(Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

			return false;
		}

		// Install the package.
		if (!$installer->install($package['dir']))
		{
			// There was an error installing the package.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_ERROR',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = false;
			$msgType = 'error';
		}
		else
		{
			// Package installed successfully.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = true;
			$msgType = 'message';
		}

		// This event allows a custom a post-flight:
		$app->triggerEvent('onInstallerAfterInstaller', array($parent, &$package, $installer, &$result, &$msg));

		$app->enqueueMessage($msg, $msgType);

		// Cleanup the install files.
		if (!is_file($package['packagefile']))
		{
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}

		InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		return $result;
	}
}