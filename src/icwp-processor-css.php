<?php
require_once( dirname(__FILE__).'/icwp-base-processor.php' );

if ( !class_exists('ICWP_WPTB_CssProcessor_V1') ):

class ICWP_WPTB_CssProcessor_V1 extends ICWP_WPTB_BaseProcessor {

	const CdnjsStem					= '//cdnjs.cloudflare.com/ajax/libs/'; //All cdnjs libraries are under this path
	const CdnJqueryVersion			= '1.10.2';

	/**
	 * @param ICWP_WPTB_FeatureHandler_Css $oFeatureOptions
	 */
	public function __construct( ICWP_WPTB_FeatureHandler_Css $oFeatureOptions ) {
		parent::__construct( $oFeatureOptions );
	}

	/**
	 */
	public function run() {
		// we do it here to get it in early.
		add_action( 'wp_enqueue_scripts', array( $this, 'doEnqueueResetCss' ), 0 );
		add_action( 'admin_init', array( $this, 'onWpAdminInit' ) );
		add_action( 'wp_loaded', array( $this, 'onWpLoaded' ), 0 );
	}

	public function onWpLoaded() {
		add_action( 'wp_enqueue_scripts', array( $this, 'doEnqueueScripts' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'doPrintStyles' ) );

		// From this point on, only do things relating to reset CSS on the frontend
		if ( !$this->getIfIncludeResetCss() ) {
			return;
		}

		if ( !$this->getIsOption( 'enq_using_wordpress', 'Y' ) ) {
			ob_start( array( $this, 'onOutputBufferFlush' ) );
		}

		if ( $this->getIsUseTwitter() && $this->getIsOption( 'useshortcodes', 'Y' ) ) {
			$sBootstrapOption = $this->getOption( 'option' );
			if ( $sBootstrapOption == 'twitter' ) {
				require_once( $this->oFeatureOptions->getSrcFile( 'icwp-wptb-bootstrap-shortcodes.php' ) );
			}
			else {
				require_once( $this->oFeatureOptions->getSrcFile( 'icwp-wptb-bootstrap-shortcodes-legacy.php' ) );
			}
			$oShortCodes = new ICWP_WPTB_BootstrapShortcodes();
		}

		// if option to enable shortcodes in sidebar is on, add filter
		if ( $this->getIsOption( 'enable_shortcodes_sidebarwidgets', 'Y' ) ) {
			add_filter( 'widget_text', 'do_shortcode' );
		}
	}

	/**
	 * Used for admin-only related bootstrap CSS
	 */
	public function onWpAdminInit() {

		add_filter( 'mce_css', array( $this, 'includeBootstrapInEditor_Filter' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueBootstrapAdminCss' ), 99 );

		$oWp = $this->loadWpFunctionsProcessor();
		if ( $oWp->getIsCurrentPage( 'index.php' ) && !$this->getIsOption( 'hide_dashboard_rss_feed', 'Y' ) ) {
			require_once( dirname(__FILE__).'/icwp-rssfeed-widget.php' );
			ICWP_DashboardRssWidget::GetInstance();
		}
	}

	/**
	 * A comma-separated string is supplied. We break it up and add our bootstrap
	 *
	 * @param $sMce_Css
	 * @return string
	 */
	public function includeBootstrapInEditor_Filter( $sMce_Css ) {
		if ( is_admin() && $this->getIsOption( 'inc_bootstrap_css_in_editor', 'Y' ) ) {
			$aMceCss = array_map( 'trim', explode( ',', $sMce_Css ) );
			array_unshift( $aMceCss, $this->getBootstrapUrl( 'css/bootstrap.min.css' ) );
			$sMce_Css = implode( ',', $aMceCss );
		}
		return $sMce_Css;
	}

	public function enqueueBootstrapAdminCss() {
		//Enqueues the WP Admin Twitter Bootstrap files if the option is set or we're in a iControlWP admin page.
		if ( is_admin() && $this->getIsOption( 'inc_bootstrap_css_wpadmin', 'Y' ) ) {
			$sUnique = $this->oFeatureOptions->doPluginPrefix( 'bootstrap_wpadmin_css' );
			wp_register_style( $sUnique, $this->getCssUrl( 'bootstrap-wpadmin.css' ), false, $this->oFeatureOptions->getVersion() );
			wp_enqueue_style( $sUnique );
		}
	}

	/**
	 * @return boolean
	 */
	protected function getIfIncludeResetCss() {
		$oWpFunctions = $this->loadWpFunctionsProcessor();
		return !is_admin()
			&& !in_array( $oWpFunctions->getCurrentPage(), array('wp-login.php', 'wp-register.php') )
			&& !isset( $_GET['thesis_editor'] );
	}

	public function doEnqueueResetCss() {
		if ( is_admin()
			|| !$this->getIsOption( 'enq_using_wordpress', 'Y' )
			|| in_array( $this->loadWpFunctionsProcessor()->getCurrentPage(), array( 'wp-login.php', 'wp-register.php') )
			|| isset( $_GET['thesis_editor'] )
		) {
			return;
		}

		$aIncludesList = $this->getCssIncludeUrls();
		if ( !empty( $aIncludesList ) ) {
			foreach( $aIncludesList as $sKey => $sCssLinkUrl ) {
				wp_register_style( $sKey, $sCssLinkUrl );
				wp_enqueue_style( $sKey );
			}
		}
	}

	public function onOutputBufferFlush( $sContent ) {
		return $this->rewriteHead( $sContent );
	}

	/**
	 * Performs the actual rewrite of the <HEAD> to include the reset file(s)
	 *
	 * @param $sContents
	 * @return string
	 */
	protected function rewriteHead( $sContents ) {
		$aIncludesList = $this->getCssIncludeUrls();

		if ( empty( $aIncludesList ) ) {
			return $sContents;
		}
		//Add the CSS link
		$sReplace = '${1}';

		$bAddDebugComment = apply_filters( 'icwp_wptb_show_debug_comment', true );
		if ( $bAddDebugComment ) {
			$sReplace .= "\n<!-- Debug: Bootstrap Inserted by WordPress Twitter Bootstrap CSS plugin v".$this->oFeatureOptions->getVersion()." http://icwp.io/w/ -->";
		}

		foreach ( $aIncludesList as $sKey => $sIncludeLink ) {
			$sReplace .= "\n".'<link rel="stylesheet" type="text/css" href="'.$sIncludeLink.'" />';
		}

		$sReplace .= "\n<!-- / WordPress Twitter Bootstrap CSS Plugin from iControlWP. -->";

		$sRegExp = "/(<\bhead\b([^>]*)>)/i";
		return preg_replace( $sRegExp, $sReplace, $sContents, 1 );
	}

	/**
	 * @return array
	 */
	protected function getCssIncludeUrls() {
		/** @var ICWP_WPTB_FeatureHandler_Css $oFO */
		$oFO = $this->getFeatureOptions();

		// We've cached the inclusions list so we don't work it out every page load.
		$aIncludesList = $oFO->getIncludesCache();
		if ( !empty( $aIncludesList ) && is_array( $aIncludesList ) ) {
			return $aIncludesList;
		}
		$aIncludesList = array();

		// An unsupported option, so just return the custom CSS.
		$aPossibleIncludeOptions = array( 'twitter', 'twitter-legacy', 'twitter-less', 'yahoo-reset', 'yahoo-reset-3', 'normalize' );
		$sIncludeOption = $this->getOption( 'option' );
		if ( in_array( $sIncludeOption, $aPossibleIncludeOptions ) ) {

			// 'twitter', 'twitter-legacy', 'yahoo-reset', 'yahoo-reset-3', 'normalize'
			switch ( $sIncludeOption ) {
				case 'normalize':
					$aIncludesList = array( 'normalize' => $this->getUrl_NormalizeCss() );
					break;
				case 'yahoo-reset':
					$aIncludesList = array( 'yahoo-reset-290' => $this->getUrl_YahooCss2() );
					break;
				case 'yahoo-reset-3':
					$aIncludesList = array( 'yahoo-reset-3' => $this->getUrl_YahooCss() );
					break;
				default: //twitter
					$aIncludesList = $this->getTwitterCssUrls();
					break;
			}

			// At this point $aIncludesList should be an array of all the URLs to be included with their labels.
			// Now add Custom/Reset CSS.
		}

		// finally add the custom CSS
		$sCustomCssUrl = $this->getCustomCssLink();
		if ( !empty( $sCustomCssUrl ) ) {
			$aIncludesList[ 'custom-reset' ] = $sCustomCssUrl;
		}

		// cache it
		$oFO->updateIncludesCache( $aIncludesList );
		return $aIncludesList;
	}

	/**
	 * Depending on the configuration options set, will provide an array of the Twitter URLs to be included
	 *
	 * @return array
	 */
	protected function getTwitterCssUrls() {
		/** @var ICWP_WPTB_FeatureHandler_Css $oFO */
		$oFO = $this->getFeatureOptions();

		$oWpFs = $this->loadFileSystemProcessor();
		$sCssFileExtension = $this->getIsOption( 'use_minified_css', 'Y' )? '.min.css' : '.css';
		$aUrls = array();

		// link to the Twitter LESS-compiled CSS (only if the files exists)
		if ( $this->getIsOption( 'option', 'twitter-less' ) ) {
			$sLessStemUrl = 'bootstrap.less'.$sCssFileExtension;
			if ( $oWpFs->exists( $this->oFeatureOptions->getPathToCss( $sLessStemUrl ) ) ) {
				$aUrls[ 'twitter-bootstrap-less' ] = $this->getCssUrl( $sLessStemUrl );
				return $aUrls;
			}
		}

		$sTwitterStem = $this->getBootstrapUrl( 'css/bootstrap' ).$sCssFileExtension; // default is to serve it "local"
		// Determine the Twitter URL stem based on local or if CDNJS selected
		if ( $this->getIsOption( 'use_cdnjs', 'Y' ) ) {
			$sTwitterCdnStem = '%stwitter-bootstrap/%s/css/bootstrap%s';
			$sTwitterCdnStem = sprintf( $sTwitterCdnStem, self::CdnjsStem, $oFO->getTwitterBootstrapVersion(), $sCssFileExtension );
			if ( $oWpFs->getIsUrlValid( 'http:'.$sTwitterCdnStem ) ) {
				$sTwitterStem = $sTwitterCdnStem;
			}
		}
		$aUrls[ 'twitter-bootstrap' ] = $sTwitterStem;

		if ( $this->getIsOption( 'inc_responsive_css', 'Y' ) && $this->getIsOption( 'option', 'twitter-legacy' ) ) {

			$sTwitterStem = $this->getBootstrapUrl( 'css/bootstrap-responsive' ).$sCssFileExtension; // default is to serve it "local"
			if ( $this->getIsOption( 'use_cdnjs', 'Y' ) ) {
				$sTwitterCdnStem = '%stwitter-bootstrap/%s/css/bootstrap-responsive%s';
				$sTwitterCdnStem = sprintf( $sTwitterCdnStem, self::CdnjsStem, $oFO->getTwitterBootstrapVersion(), $sCssFileExtension );
				if ( $oWpFs->getIsUrlValid( 'http:'.$sTwitterCdnStem ) ) {
					$sTwitterStem = $sTwitterCdnStem;
				}
			}
			$aUrls[ 'twitter-bootstrap-responsive' ] = $sTwitterStem;
		}

		return $aUrls;
	}

	/**
	 * @return string|bool
	 */
	protected function getCustomCssLink() {
		$sCustomCssUrl = $this->getOption( 'customcss_url', null );
		return $this->getIsOption( 'customcss', 'Y' ) ? $sCustomCssUrl : null;
	}

	/**
	 * Enqueue Javascript scripts according to the plugin options.
	 */
	public function doEnqueueScripts() {
		/** @var ICWP_WPTB_FeatureHandler_Css $oFO */
		$oFO = $this->getFeatureOptions();

		$fJsInFooter = ($this->getIsOption( 'js_head', 'Y' ) )? false : true ;

		if ( $this->getIsUseTwitter() && $this->getIsOption( 'all_js', 'Y' ) ) {

			$sExtension = $this->getIsOption( 'use_minified_css', 'Y' )? '.min.js' : '.js';

			if ( $this->getIsOption( 'use_cdnjs', 'Y' ) ) {
				//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.2.2/bootstrap.min.js
				//Since version 2.3.0, now changed to:
				//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.1/js/bootstrap.min.js
				$sUrlBootstrapJs = self::CdnjsStem.'twitter-bootstrap/%s/js/bootstrap'.$sExtension;
				$sUrlBootstrapJs = sprintf( $sUrlBootstrapJs, $oFO->getTwitterBootstrapVersion() );
			}
			else {
				$sUrlBootstrapJs = $this->getBootstrapUrl( 'js/bootstrap'.$sExtension );
			}

			if ( $this->getIsOption( 'replace_jquery_cdn', 'Y' ) ) {
				//cdnjs.cloudflare.com/ajax/libs/jquery/1.8.3/jquery.min.js
				$sJqueryCdnUri = self::CdnjsStem.'jquery/'.self::CdnJqueryVersion.'/jquery'.$sExtension;
				wp_deregister_script('jquery');
				wp_register_script( 'jquery', $sJqueryCdnUri, '', self::CdnJqueryVersion, false );
			}

			wp_enqueue_script( 'jquery' );
			wp_register_script( 'bootstrap-all-min', $sUrlBootstrapJs, array('jquery'), $oFO->getVersion(), $fJsInFooter );
			wp_enqueue_script( 'bootstrap-all-min' );
		}

		if ( $this->getIsOption( 'prettify', 'Y' ) ) {
			$sUrl = $this->getJsUrl( 'google-code-prettify/prettify.js' );
			wp_register_script( 'prettify_script', $sUrl, false, $oFO->getVersion(), $fJsInFooter );
			wp_enqueue_script( 'prettify_script' );
		}
	}

	/**
	 * Enqueue Styles.
	 */
	public function doPrintStyles() {
		if ( $this->getIsOption( 'prettify', 'Y' ) ) {
			$sUrl = $this->getCssUrl( 'google-code-prettify/prettify.css' );
			wp_register_style( 'prettify_style', $sUrl );
			wp_enqueue_style( 'prettify_style' );
		}
	}

	/**
	 * @return boolean - true if Twitter Bootstrap (legacy or otherwise) is the include option
	 */
	protected function getIsUseTwitter() {
		$sBootstrapOption = $this->getOption( 'option' );
		return strpos( $sBootstrapOption, 'twitter') === 0;
	}

	/**
	 * @param string
	 * @return string
	 */
	protected function getBootstrapResource( $sResource = '' ) {
		return $this->getBootstrapDir(). ltrim( $sResource, ICWP_DS );
	}

	/**
	 * @return string
	 */
	protected function getBootstrapDir() {
		return $this->oFeatureOptions->getResourcesDir( 'bootstrap-'.$this->oFeatureOptions->getTwitterBootstrapVersion().ICWP_DS );
	}

	/**
	 * @param string $sResource
	 * @return string
	 */
	protected function getBootstrapUrl( $sResource = '' ) {
		return $this->oFeatureOptions->getResourceUrl( 'bootstrap-'.$this->oFeatureOptions->getTwitterBootstrapVersion().ICWP_DS.ltrim( $sResource, ICWP_DS ) );
	}

	/**
	 * @return string
	 */
	protected function getUrl_NormalizeCss() {

		$sUrl = $this->getCssUrl( 'normalize.css' );
		if ( $this->getIsOption( 'use_cdnjs', 'Y' ) ) {
			// cdnjs.cloudflare.com/ajax/libs/normalize/2.0.1/normalize.css
			$sCdnUrl = self::CdnjsStem.'normalize/'.ICWP_WPTB_FeatureHandler_Css::NormalizeVersion.'/normalize.css';
			$oWpFs = $this->loadFileSystemProcessor();
			if ( $oWpFs->getIsUrlValid( 'http:'.$sCdnUrl ) ) {
				$sUrl = $sCdnUrl;
			}
		}
		return add_query_arg(
			array( 'ver', ICWP_WPTB_FeatureHandler_Css::NormalizeVersion ),
			$sUrl
		);
	}

	/**
	 * @return string
	 */
	protected function getUrl_YahooCss() {
		return add_query_arg(
			array( 'ver', ICWP_WPTB_FeatureHandler_Css::YUI3Version ),
			$this->getCssUrl( 'yahoo-cssreset-min.css' )
		);
	}

	/**
	 * @return string
	 */
	protected function getUrl_YahooCss2() {
		$this->getCssUrl( 'yahoo-2.9.0.min.css' );
	}

	/**
	 * @param string $sCss
	 * @return string
	 */
	protected function getCssUrl( $sCss = '/' ) {
		return $this->oFeatureOptions->getResourceUrl( 'css/'. ltrim( $sCss, '/' ) );
	}

	/**
	 * @param string $sJs
	 * @return string
	 */
	protected function getJsUrl( $sJs ) {
		return $this->oFeatureOptions->getResourceUrl( 'js/'. ltrim( $sJs, '/' ) );
	}

}

endif;

if ( !class_exists('ICWP_WPTB_CssProcessor') ):
	class ICWP_WPTB_CssProcessor extends ICWP_WPTB_CssProcessor_V1 { }
endif;