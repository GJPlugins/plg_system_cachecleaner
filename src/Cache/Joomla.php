<?php
/**
 * @package         Cache Cleaner
 * @version         6.0.6
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2017 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace RegularLabs\Plugin\System\CacheCleaner\Cache;

defined('_JEXEC') or die;

use JCache;
use JFactory;

class Joomla extends Cache
{
	public static function purge()
	{
		$cache_path = JFactory::getConfig()->get('cache_path', JPATH_SITE . '/cache');

		self::emptyFolder($cache_path);
		self::emptyFolder(JPATH_ADMINISTRATOR . '/cache');

		$cache = self::getCache();

		if ( ! isset($cache->options['storage']) || $cache->options['storage'] == 'file')
		{
			return;
		}

		$cache->clean(null, 'all');
	}

	public static function purgeOPcache()
	{
		if (function_exists('opcache_reset'))
		{
			opcache_reset();

			return;
		}

		if (function_exists('apc_clear_cache'))
		{
			@apc_clear_cache();

			return;
		}
	}

	public static function purgeExpired()
	{
		$cache = self::getCache();
		$cache->gc();
	}

	public static function purgeUpdates()
	{
		$db = JFactory::getDbo();
		$db->setQuery('TRUNCATE TABLE #__updates');
		if ( ! $db->execute())
		{
			return;
		}

		// Reset the last update check timestamp
		$query = $db->getQuery(true)
			->update('#__update_sites')
			->set('last_check_timestamp = ' . $db->quote(0));
		$db->setQuery($query);
		$db->execute();
	}


	private static function getCache()
	{
		$conf = JFactory::getConfig();

		$options = [
			'defaultgroup' => '',
			'storage'      => $conf->get('cache_handler', ''),
			'caching'      => true,
			'cachebase'    => $conf->get('cache_path', JPATH_SITE . '/cache'),
		];

		$cache = JCache::getInstance('', $options);

		return $cache;
	}
}
