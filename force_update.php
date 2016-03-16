<?php

/**
 * Created by PhpStorm.
 * User: Myszczyszyn Dawid - Code2Prog
 * Date: 12.03.2016
 * Time: 03:46
 */
class WW_force_update {
	private $status = 'ERR';


	/**
	 * @param $path
	 */
	function removeDirectory( $path ) {
		// Open the source directory to read in files
		$i = new DirectoryIterator( $path );
		foreach ( $i as $f ) {
			if ( $f->isFile() ) {
				unlink( $f->getRealPath() );
			} else if ( ! $f->isDot() && $f->isDir() ) {
				$this->removeDirectory( $f->getRealPath() );
			}
		}
		rmdir( $path );
	}

	function doUpdate() {
		require_once( 'pecl.zip.php' );

		$archive = new PclZip( './tmp/build.zip' );

		if ( $archive->extract( PCLZIP_OPT_PATH, './' ) == 0 ) {
			$this->status = 'ERR';
		} else {
			$this->status = 'OK';
		}

	}

	/**
	 *
	 */
	function clean( $mode = 'before' ) {
		if ( $mode == 'before' ) {
			unlink('watchtower.php');
			if ( file_exists( 'plugin-update-checker' ) ) {
				$this->removeDirectory( 'plugin-update-checker' );
			}
			if ( file_exists( 'vendor' ) ) {
				$this->removeDirectory( 'vendor' );
			}
			if ( file_exists( 'src' ) ) {
				$this->removeDirectory( 'src' );
			}
		} elseif ( $mode == 'after' ) {
			chmod( './tmp/build.zip', 0777 );
			chmod( './tmp', 0777 );
			if ( is_writable( './tmp/build.zip' ) ) {
				unlink( './tmp/build.zip' );

			} else {
				$this->status = 'ERR';
			}
			$this->removeDirectory( 'tmp' );
		}

	}

	function returnStatus() {
		return json_encode( array(
			'status' => $this->status
		) );
	}

	/**
	 * @param $url
	 */
	function downloadLatestBuild( $url ) {
		if ( ! file_exists( './tmp' ) ) {
			mkdir( './tmp', 0777, true );
		}
		file_put_contents( './tmp/build.zip', file_get_contents( $url ) );
	}


}

$updater = new WW_force_update();
$url     = 'http://watchtower.code2prog.com/package/build.zip';
$updater->clean( 'before' );
$updater->downloadLatestBuild( $url );
$updater->doUpdate();
$updater->clean( 'after' );
header( 'Content-Type: application/json' );
echo $updater->returnStatus();