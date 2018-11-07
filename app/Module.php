<?php
/**
 * 'active' parameter in module.json is not taken in account.
 * Module 'active' flag is taken from DB.
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    const IMG_DEFAULT = '/img/default-module.png';

    public $timestamps = false;

    /**
     * Modules list cached in memory.
     */
    public static $modules;

    public static function getCached()
    {
    	if (!self::$modules) {
    		self::$modules = Module::all();
    	}
    	return self::$modules;
    }

    public static function isActive($alias)
    {
        $module = self::getByAlias($alias);
        if ($module) {
            return $module->active;
        } else {
            return false;
        }
    }

    public static function setActive($alias, $active, $save = true)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->active = $active;
        if ($save) {
            $module->save();
        }
        return true;
    }

    /**
     * Is module license activated.
     */
    public static function isLicenseActivated($alias, $author_url)
    {
        // If module is from modules directory, license activation is required
        if ($author_url && self::isOfficial($author_url)) {
            $module = self::getByAlias($alias);
            if ($module) {
                return $module->activated;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public static function isOfficial($author_url)
    {
        return parse_url($author_url, PHP_URL_HOST) == parse_url(\Config::get('app.freescout_url'), PHP_URL_HOST);
    }

    /**
     * Activate module license.
     * @param  [type]  $alias       [description]
     * @param  [type]  $details_url [description]
     * @return boolean              [description]
     */
    public static function activateLicense($alias, $license)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->license = $license;
        $module->activated = true;
        $module->save();
    }

    public static function deactivateLicense($alias, $license)
    {
        $module = self::getByAliasOrCreate($alias);
        $module->license = $license;
        $module->activated = false;
        $module->save();
    }

    public static function getByAliasOrCreate($alias)
    {
        $module = self::getByAlias($alias);
        if (!$module) {
            $module = new Module();
            $module->alias = $alias;
        }
        return $module;
    }

    /**
     * Get module license.
     */
    public static function getLicense($alias)
    {
        $module = self::getByAlias($alias);
        if ($module) {
            return $module->license;
        } else {
            return '';
        }
    }

    public static function normalizeAlias($alias)
    {
        return trim(strtolower($alias));
    }

    public static function getByAlias($alias)
    {
    	return self::getCached()->where('alias', $alias)->first();
    }

    /**
     * Deactivate module and update modules cache.
     */
    public static function deactiveModule($alias)
    {
        self::setActive($alias, false);
        // Update modules cache
        \Module::clearCache();
    }
}