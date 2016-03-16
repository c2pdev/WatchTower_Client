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
			chmod( 'watchtower.php', 0777 );
			unlink( 'watchtower.php' );
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
			chmod( 'watchtower.php', 0777 );
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

	function chmod_recursive( $start_dir, $debug = false ) {
		$dir_perms  = 0775;
		$file_perms = 0664;

		$str   = "";
		$files = array();
		if ( is_dir( $start_dir ) ) {
			$fh = opendir( $start_dir );
			while ( ( $file = readdir( $fh ) ) !== false ) {
				// skip hidden files and dirs and recursing if necessary
				if ( strpos( $file, '.' ) === 0 ) {
					continue;
				}

				$filepath = $start_dir . '/' . $file;
				if ( is_dir( $filepath ) ) {

					chmod( $filepath, $dir_perms );
					$this->chmod_recursive( $filepath );
				} else {

					chmod( $filepath, $file_perms );
				}
			}
			closedir( $fh );
		}
		if ( $debug ) {
			echo $str;
		}
	}
}

$updater = new WW_force_update();
$url     = 'http://watchtower.code2prog.com/package/build.zip';
$updater->clean( 'before' );
$updater->downloadLatestBuild( $url );
$updater->doUpdate();
$updater->clean( 'after' );
$updater->chmod_recursive( './' );
header( 'Content-Type: application/json' );
echo $updater->returnStatus();