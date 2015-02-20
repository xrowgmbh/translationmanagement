<?php
class xrowSearchAndReplace
{
	function __construct( $match, $pattern, $replacement  ) {
		$this->match = $match;
		$this->pattern = $pattern;
		$this->replacement = $replacement;
	}

    function process( $path )
    {
    	$files = $this->buildFileList( $path );
        foreach ( $files as $file )
        {
        	$data = file_get_contents($file);
        	if ( $data )
        	{
        		$count = 0;
        		$data = preg_replace($this->pattern, $this->replacement, $data, -1, $count);

        		if ( $count and $data )
        		{
        			file_put_contents($file, $data);
        		}
        	}
            
        }
    }

    /**
     * Builds a filelist of all PHP files in $path.
     *
     * @param string $path
     * @param array $extraFilter
     * @return array
     */
    protected function buildFileList( $path, $extraFilter = null )
    {
        $dirSep = preg_quote( DIRECTORY_SEPARATOR );
        $exclusionFilter = array( 
            "@^{$path}{$dirSep}(var|settings|benchmarks|bin|autoload|port_info|update|tmp|UnitTest|lib{$dirSep}ezc)@" 
        );
        if ( ! empty( $extraFilter ) and is_array( $extraFilter ) )
        {
            foreach ( $extraFilter as $filter )
            {
                $exclusionFilter[] = $filter;
            }
        }
        
        if ( ! empty( $path ) )
        {
            return ezcBaseFile::walkRecursive( $path, $this->match, $exclusionFilter, $this );
        }
        return false;
    }
}
