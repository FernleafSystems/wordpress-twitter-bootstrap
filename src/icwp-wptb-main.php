<?php
require_once( dirname(__FILE__).'/icwp-pure-base.php' );

if ( !class_exists('ICWP_WPTB_BootstrapCss') ):

class ICWP_WPTB_BootstrapCss extends ICWP_WPTB_Pure_Base_V1 {

	public function __construct( ICWP_Wordpress_Twitter_Bootstrap_Plugin $oPluginVo ) {
		parent::__construct( $oPluginVo );
		$this->loadAllFeatures();
	}

	/**
	 * @param bool $fRecreate
	 * @param bool $fFullBuild
	 * @return bool
	 */
	protected function loadAllFeatures( $fRecreate = false, $fFullBuild = false ) {
		foreach( $this->oPluginVo->getFeatures() as $sFeature ) {
			$fSuccess = $this->loadFeatureHandler( $sFeature, $fRecreate, $fFullBuild );
		}
		return $fSuccess;
	}

	protected function loadFeatureHandler( $sFeatureSlug = 'plugin', $infRecreate = false, $infFullBuild = false ) {
		if ( !$this->getIsFeature( $sFeatureSlug ) ) {
			return false;
		}

		$sFeatureName = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $sFeatureSlug ) ) );
		$sOptionsVarName = 'o'.$sFeatureName.'Options'; // e.g. oPluginOptions

		if ( isset( $this->{$sOptionsVarName} ) ) {
			return $this->{$sOptionsVarName};
		}
		$sSourceFile = $this->oPluginVo->getSourceDir().'icwp-optionshandler-'.$sFeatureSlug.'.php'; // e.g. icwp-optionshandler-plugin.php
		$sClassName = 'ICWP_WPTB_FeatureHandler_'.$sFeatureName;

		require_once( $sSourceFile );
		if ( $infRecreate || !isset( $this->{$sOptionsVarName} ) ) {
			$this->{$sOptionsVarName} = new $sClassName( $this->oPluginVo );
		}
		if ( $infFullBuild ) {
			$this->{$sOptionsVarName}->buildOptions();
		}
		return $this->{$sOptionsVarName};
	}

	/**
	 * Given a certain feature 'slug' will return true if this is a particular supported feature of this plugin.
	 *
	 * @param string $sFeature
	 * @return boolean
	 */
	public function getIsFeature( $sFeature ) {
		return in_array( $sFeature, $this->oPluginVo->getFeatures() );
	}

	public function onWpAdminInit() {
		parent::onWpAdminInit();

		// If it's a plugin admin page, we do certain things we don't do anywhere else.
		if ( $this->getIsPage_PluginAdmin() ) {

			//JS color picker for the Bootstrap LESS
			if ( $_GET['page'] == $this->getSubmenuId( 'bootstrap-less' ) ) {
				wp_register_style( 'miniColors', $this->m_sPluginUrl.'inc/miniColors/jquery.miniColors.css', false, $this->m_sVersion );
				wp_enqueue_style( 'miniColors' );
	
				wp_register_script( 'miniColors', $this->m_sPluginUrl.'inc/miniColors/jquery.miniColors.min.js', false, $this->m_sVersion, true );
				wp_enqueue_script( 'miniColors' );
			}
		}
	}

	/**
	 * @param $aLinks
	 * @param $sFile
	 * @return mixed
	 */
	public function onWpPluginActionLinks( $aLinks, $sFile ) {
		if ( $sFile == $this->getPluginBaseFile() ) {
			$sSettingsLink = '<a href="'.admin_url( "admin.php" ).'?page='.$this->getSubmenuId('plugin').'">' . _wptb__( 'Settings' ) . '</a>';
			array_unshift( $aLinks, $sSettingsLink );
		}
		return $aLinks;
	}
}

endif;