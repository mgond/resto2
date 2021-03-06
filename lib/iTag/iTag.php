<?php
/*
 * Copyright 2013 Jérôme Gasperi
 *
 * Licensed under the Apache License, version 2.0 (the "License");
 * You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at:
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
require 'Taggers/Tagger_Always.php';
require 'Taggers/Tagger_Generic.php';
require 'Taggers/Tagger_Geology.php';
require 'Taggers/Tagger_Hydrology.php';
require 'Taggers/Tagger_LandCover.php';
require 'Taggers/Tagger_Political.php';
require 'Taggers/Tagger_Population.php';
class iTag {

    /*
     * iTag version
     */
    const version = '2.0';
    
    /*
     * Database handler
     */
    private $dbh;
    
    /*
     * Configuration
     */
    private $config = array(
        'areaLimit' => 9
    );
    
    /**
     * Constructor
     * 
     * @param array $database : database configuration array
     * @param array $config : configuration 
     */
    public function __construct($database, $config = array()) {
        if (isset($database['dbh'])) {
            $this->dbh = $database['dbh'];
        }
        else if (isset($database['dbname'])) {
            $this->setDatabaseHandler($database);
        }
        else {
            throw new Exception('Database connection error', 500);
        }  
        $this->setConfig($config);
    }
    
    /**
     * Tag a polygon using taggers
     * 
     * @param array $metadata // must include a 'footprint' in WKT format
     *                        // and optionnaly a 'timestamp' ISO8601 date
     * @param array $taggers
     * @return array
     * @throws Exception
     */
    public function tag($metadata, $taggers = array()) {
       
        if (!isset($metadata['footprint'])) {
            throw new Exception('Missing mandatory footprint', 500);
        }
        
        /*
         * Datasources reference information
         */
        $references = array();
        
        /*
         * These tag are always performed
         */
        $content = $this->always($metadata);
        
        /*
         * Add footprint area to metadata
         */
        $metadata['area'] = $content['area'];
        
        /*
         * Call the 'tag' function of all input taggers
         */
        foreach ($taggers as $name => $options) {
            $tagger = $this->instantiateTagger($name);
            if (isset($tagger)) {
                $content = array_merge($content, $tagger->tag($metadata, $options));
                $references = array_merge($references, $tagger->references);
            }
        }
        
        return array(
            'footprint' => $metadata['footprint'],
            'timestamp' => isset($metadata['timestamp']) ? $metadata['timestamp'] : null,
            'content' => $content,
            'references' => $references
        );

    }
    
    /**
     * Always performed tags
     * 
     * @param array $metadata
     */
    private function always($metadata) {
        $tagger = new Tagger_Always($this->dbh, $this->config);
        return $tagger->tag($metadata);
    }
    
    /**
     * Set configuration
     * 
     * @param array $config
     */
    private function setConfig($config) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Return PostgreSQL database handler
     * 
     * @param array $options
     * @throws Exception
     */
    private function setDatabaseHandler($options) {
        try {
            $dbInfo = array(
                'dbname=' . $options['dbname'],
                'user=' . $options['user'],
                'password=' . $options['password']
            );
            /*
             * If host is specified, then TCP/IP connection is used
             * Otherwise socket connection is used
             */
            if (isset($options['host'])) {
                $dbInfo[] = 'host=' . $options['host'];
                $dbInfo[] = 'port=' . (isset($options['port']) ? $options['port'] : '5432');
            }
            $dbh = pg_connect(join(' ', $dbInfo));
            if (!$dbh) {
                throw new Exception();
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error', 500);
        }  
        $this->dbh = $dbh;
    }
  
    /**
     * Instantiate a class with params
     * 
     * @param string $className : class name to instantiate
     */
    private function instantiateTagger($className) {
        
        if (!$className) {
            return null;
        }
        
        try {
            $class = new ReflectionClass('Tagger_' . $className);
            if (!$class->isInstantiable()) {
                throw new Exception();
            }
        } catch (Exception $e) {
            return null;
        }
        
        return $class->newInstance($this->dbh, $this->config);
        
    }

}
