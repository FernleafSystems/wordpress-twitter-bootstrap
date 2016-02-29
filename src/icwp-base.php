<?php
if ( !defined('ICWP_DS') ) {
	define( 'ICWP_DS', DIRECTORY_SEPARATOR );
}

if ( !class_exists('ICWP_Wordpress_Plugin') ):

class ICWP_Wordpress_Plugin {

	/**
	 * @const string
	 */
	const ViewDir				= 'views';

	/**
	 * @const string
	 */
	const SrcDir				= 'src';

	/**
	 * @var string
	 */
	protected static $fLoggingEnabled;

	/**
	 * @var string
	 */
	protected static $sParentSlug	= 'icwp';

	/**
	 * @var string
	 */
	protected static $sPluginSlug;

	/**
	 * @var string
	 */
	protected static $sVersion;

	/**
	 * @var string
	 */
	protected static $sHumanName;

	/**
	 * @var string
	 */
	protected static $sMenuTitleName;

	/**
	 * @var string
	 */
	protected static $sTextDomain;

	/**
	 * @var string
	 */
	protected static $sBasePermissions = 'manage_options';

	/**
	 * @var string
	 */
	protected static $sWpmsNetworkAdminOnly = true;

	/**
	 * @var string
	 */
	protected static $sRootFile;

	/**
	 * @var string
	 */
	protected static $fAutoUpgrade = false;

	/**
	 * @var string
	 */
	protected static $aFeatures;

	/**
	 */
	protected function __construct() {
		if ( empty( self::$sRootFile ) ) {
			self::$sRootFile = __FILE__;
		}
	}

	/**
	 * @return string
	 */
	public function getAdminMenuTitle() {
		return self::$sMenuTitleName;
	}

	/**
	 * @return string
	 */
	public function getBasePermissions() {
		return self::$sBasePermissions;
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getFullPluginPrefix( $sGlue = '-' ) {
		return sprintf( '%s%s%s', self::$sParentSlug, $sGlue, self::$sPluginSlug );
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getFeatures() {
		return self::$aFeatures;
	}

	/**
	 * @param string
	 * @return string
	 */
	public function getOptionStoragePrefix() {
		return $this->getFullPluginPrefix( '_' ).'_';
	}

	/**
	 * @return string
	 */
	public function getHumanName() {
		return self::$sHumanName;
	}

	/**
	 * @return string
	 */
	public function getIsLoggingEnabled() {
		return self::$fLoggingEnabled;
	}

	/**
	 * @return string
	 */
	public function getIsWpmsNetworkAdminOnly() {
		return self::$sWpmsNetworkAdminOnly;
	}

	/**
	 * @return string
	 */
	public function getParentSlug() {
		return self::$sParentSlug;
	}

	/**
	 * @return string
	 */
	public function getPluginSlug() {
		return self::$sPluginSlug;
	}

	/**
	 * get the root directory for the plugin with the trailing slash
	 *
	 * @return string
	 */
	public function getRootDir() {
		return dirname( $this->getRootFile() ).ICWP_DS;
	}

	/**
	 * @return string
	 */
	public function getRootFile() {
		return self::$sRootFile;
	}

	/**
	 * get the directory for the plugin view with the trailing slash
	 *
	 * @return string
	 */
	public function getSourceDir() {
		return $this->getRootDir().self::SrcDir.ICWP_DS;
	}

	/**
	 * @return string
	 */
	public static function GetTextDomain() {
		return self::$sTextDomain;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return self::$sVersion;
	}

	/**
	 * get the directory for the plugin view with the trailing slash
	 *
	 * @return string
	 */
	public function getViewDir() {
		return $this->getRootDir().self::ViewDir.ICWP_DS;
	}
}
endif;