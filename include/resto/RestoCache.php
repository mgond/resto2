<?php

/*
 * RESTo
 * 
 * RESTo - REstful Semantic search Tool for geOspatial 
 * 
 * Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
 * 
 * jerome[dot]gasperi[at]gmail[dot]com
 * 
 * 
 * This software is governed by the CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 * 
 */

/**
 * RESTo cache module is used by database driver to store queries in cache
 */
class RestoCache {
    
    /*
     * Cache directory - if not set, no cache !
     */
    private $directory;

    /**
     * Constructor
     * 
     * @param string $directory : cache directory (must be readable+writable for Webserver user)
     */
    public function __construct($directory) {
        if (isset($directory) && is_writable($directory)) {
            $this->directory = $directory;
        }
    }

    /**
     * Return true if fileName is in cache
     * 
     * @param type $fileName
     */
    public function isInCache($fileName) {
        if (!isset($fileName) || !isset($this->directory)) {
            return false;
        }
        if (file_exists($this->directory . DIRECTORY_SEPARATOR . $fileName)) {
            return true;
        }
        return false;
    }
    
    /**
     * Read from cached file
     * 
     * @param $fileName - name of the cached file
     */
    public function read($fileName) {
        if (!$this->isInCache($fileName)) {
            return null;
        }
        $fileName = $this->directory . DIRECTORY_SEPARATOR . $fileName;
        $handle = fopen($fileName, 'rb');
        $obj = fread($handle, filesize($fileName));
        fclose($handle);
        return unserialize($obj);
    }

    /**
     * Write database result to cached file
     * 
     * @param string $fileName - name of the cached file
     * @param object $obj - Object to store in cache
     */
    function write($fileName, $obj) {
        if (!$this->directory) {
            return null;
        }
        $handle = fopen($this->directory . DIRECTORY_SEPARATOR . $fileName, 'a');
        fwrite($handle, serialize($obj));
        fclose($handle);
        return $fileName;
    }

    /**
     * Delete cached file
     * 
     * @param $fileName - name of the cached file
     */
    function delete($fileName) {
        @unlink($this->directory . DIRECTORY_SEPARATOR . $fileName);
    }

}