<?php
require_once( dirname(__FILE__).'/icwp-base-processor.php' );

if ( !class_exists('ICWP_WPTB_PluginProcessor') ):

class ICWP_WPTB_PluginProcessor extends ICWP_WPTB_BaseProcessor {

	/**
	 * @param ICWP_WPTB_FeatureHandler_Plugin $oFeatureOptions
	 */
	public function __construct( ICWP_WPTB_FeatureHandler_Plugin $oFeatureOptions ) {
		parent::__construct( $oFeatureOptions );
	}

	/**
	 */
	public function run() {
		add_filter( 'auto_update_plugin',		array( $this, 'onWpAutoUpdatePlugin' ), 1000, 2 );
		add_filter( $this->oFeatureOptions->doPluginPrefix( 'show_marketing' ), array( $this, 'getIsShowMarketing' ) );
	}

	/**
	 * @param boolean $fUpdate
	 * @param $mItem
	 * @return bool
	 */
	public function onWpAutoUpdatePlugin( $fUpdate, $mItem ) {
		// If not enabled, just bail
		if ( !$this->getIsOption( 'auto_update_minor_releases', 'Y' ) ) {
			return $fUpdate;
		}

		// Only supports WordPress 3.8.2+
		if ( !is_object( $mItem ) || !isset( $mItem->new_version ) || !isset( $mItem->plugin ) )  { // WP 3.8.2+
			return $fUpdate;
		}

		if ( $mItem->plugin === $this->oFeatureOptions->getPluginBaseFile() ) {
			$aCurrentParts = explode( '-', $this->oFeatureOptions->getVersion() );
			$aUpdateParts = explode( '-', $mItem->new_version );
			// We only return true (i.e. update if and when the update is a minor version
			return ( $aUpdateParts[0] === $aCurrentParts[0] );
		}
		return $fUpdate;
	}

	/**
	 * @param $fShow
	 * @return bool
	 */
	public function getIsShowMarketing( $fShow ) {
		if ( !$fShow ) {
			return $fShow;
		}

		$oWpFunctions = $this->loadWpFunctionsProcessor();
		if ( class_exists( 'Worpit_Plugin' ) ) {
			if ( method_exists( 'Worpit_Plugin', 'IsLinked' ) ) {
				$fShow = !Worpit_Plugin::IsLinked();
			}
			else if ( $oWpFunctions->getOption( Worpit_Plugin::$VariablePrefix.'assigned' ) == 'Y'
				&& $oWpFunctions->getOption( Worpit_Plugin::$VariablePrefix.'assigned_to' ) != '' ) {

				$fShow = false;
			}
		}

		if ( $this->getInstallationDays() < 1 ) {
			$fShow = false;
		}

		return $fShow;
	}

	/**
	 * @return int
	 */
	protected function getInstallationDays() {
		$nTimeInstalled = $this->oFeatureOptions->getOpt( 'installation_time' );
		if ( empty($nTimeInstalled) ) {
			return 0;
		}
		return round( ( time() - $nTimeInstalled ) / DAY_IN_SECONDS );
	}
}

endif;
