<?
/**
 * Zend Encoder Header

include_once( 'lib/ezutils/classes/ezextension.php' );
$include = eZExtension::baseDirectory() . '/' . nameFromPath( __FILE__ ) . '/share/zend/ezxzend.php';
include_once( $include );
ezxZend::header();
 
 */
include_once( 'lib/ezutils/classes/ezhttptool.php' );
include_once( 'lib/ezutils/classes/ezmodule.php' );
include_once( 'lib/ezutils/classes/ezexecution.php' );
class ezxZend
{
    function ezxZend()
    {
    	
    }
    function is_encoded( $file )
    {
        if ( !file_exists( $file ) )
            $file = _FILE_;
        include_once( 'lib/ezfile/classes/ezfile.php' );
        $lines = eZFile::splitLines( $file );
        if ( "<?php @Zend;" == trim( $lines[0] ) )
            return true;
        else
            return false;
    }
    
    function header()
    {

        $extension = nameFromPath( __FILE__ );
        if ( eZSys::isShellExecution() )
        {
            include_once( 'lib/ezutils/classes/ezcli.php' );
            $cli = eZCLI::instance();
            $cli->output( "Zend Optimizer not installed" );
            $cli->output( "" );
            $cli->output( "This extension '".$extension."' was encoded. In order to run it, please install the freely available Zend Optimizer, version 2.1.0 or later." );
            $cli->output( "http://www.zend.com/store/products/zend-optimizer.php" );
        }

        else
        {



            $redirectURI =& eZSys::indexDir();

            $moduleRedirectUri = 'error/view/kernel/52';
            $redirectStatus = 302;
            $translatedModuleRedirectUri = $moduleRedirectUri;
            $ini = eZINI::instance();
            if ( $ini->variable( 'URLTranslator', 'Translation' ) == 'enabled' )
            {
                include_once( 'kernel/classes/ezurlalias.php' );
                if ( eZURLAlias::translate( $translatedModuleRedirectUri, true ) )
                {
                    $moduleRedirectUri = $translatedModuleRedirectUri;
                    if ( strlen( $moduleRedirectUri ) > 0 and
                    $moduleRedirectUri[0] != '/' )
                    $moduleRedirectUri = '/' . $moduleRedirectUri;
                }
            }

            if ( preg_match( '#^(\w+:)|^//#', $moduleRedirectUri ) )
            {
                $redirectURI = $moduleRedirectUri;
            }
            else
            {
                $leftSlash = false;
                $rightSlash = false;
                if ( strlen( $redirectURI ) > 0 and
                $redirectURI[strlen( $redirectURI ) - 1] == '/' )
                $leftSlash = true;
                if ( strlen( $moduleRedirectUri ) > 0 and
                $moduleRedirectUri[0] == '/' )
                $rightSlash = true;

                if ( !$leftSlash and !$rightSlash ) // Both are without a slash, so add one
                $moduleRedirectUri = '/' . $moduleRedirectUri;
                else if ( $leftSlash and $rightSlash ) // Both are with a slash, so we remove one
                $moduleRedirectUri = substr( $moduleRedirectUri, 1 );
                $redirectURI .= $moduleRedirectUri;
            }

            eZHTTPTool::redirect( $redirectURI, array(), $redirectStatus );
        }

        eZExecution::cleanExit();
    }
    function loadLicense( $file )
    {
        if ( !file_exists( $file ) )
            $file = __FILE__;
    	zend_loader_install_license( eZExtension::baseDirectory() .'/'. eZExtension::nameFromPath( __FILE__ ) . '/'. eZExtension::nameFromPath( __FILE__ ) . ".lic" );
    }
    function getMD5Hash( $file )
    {

        if ( !file_exists( $file ) )
            $file = __FILE__;

        return md5_file( $file );
    }
}

?>