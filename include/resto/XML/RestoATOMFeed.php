<?php
/*
 * Copyright 2014 Jérôme Gasperi
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

class RestoATOMFeed extends RestoXML {
    
    /**
     * Constructor
     * 
     * @param string $id
     * @param string $title
     * @param string $subtitle
     */
    public function __construct($id, $title, $subtitle) {
        
        parent::__construct();
        
        /*
         * Start ATOM feed
         */
        $this->startAtomFeed($id, $title, $subtitle);
        
        /*
         * Set title, subtitle and generator
         */
        $this->setBaseElements($title, $subtitle);
        
        /*
         * Set id
         */
        $this->writeElement('id', $id);
        
    }
    
    /**
     * Add Atom feed entry
     * 
     * @param array $feature
     * @param RestoContext $context
     */
    public function addEntry($feature, $context) {
        
        /*
         * Add entry
         */
        $this->startElement('entry');
        
        /*
         * Add entry base elements, time and geometry
         */
        $this->addEntryElements($feature);
        
        /*
         * Links
         */
        $this->addLinks($feature);
        
        /*
         * Media (i.e. Quicklook / Thumbnail / etc.)
         */
        $this->addQuicklooks($feature);
        
        /*
         * Summary
         */
        $this->addSummary($feature, $context);
        
        /*
         * entry - close element
         */
        $this->endElement(); // entry   
    }
    
    /**
     * Add Atom feed entries
     * 
     * @param array $features
     * @param RestoContext $context
     */
    public function addEntries($features, $context) {
        for ($i = 0, $l = count($features); $i < $l; $i++) {
            $this->addEntry($features[$i]->toArray(), $context);
        }
    }
    
    /**
     * Return stringified XML document
     */
    public function toString() {
        
        /*
         * End feed element
         */
        $this->endElement();
        
        /*
         * Write result
         */
        return parent::toString();
    }
    
    /**
     * Set elements for FeatureCollection
     * 
     * @param array $properties
     */
    public function setCollectionElements($properties) {
        
        /*
         * Update outputFormat links except for OSDD 'search'
         */
        $this->setCollectionLinks($properties);
        
        /*
         * Total results, startIndex and itemsPerpage
         */
        if (isset($properties['totalResults'])) {
            $this->writeElement('os:totalResults', $properties['totalResults']);
        }
        if (isset($properties['startIndex'])) {
            $this->writeElement('os:startIndex', $properties['startIndex']);
        }
        if (isset($properties['itemsPerPage'])) {
            $this->writeElement('os:itemsPerPage', $properties['itemsPerPage']);
        }

        /*
         * Query element
         */
        $this->setQuery($properties);
        
    }
    
    /**
     * Start XML ATOM feed with all namespaces attributes
     *
     */
    private function startAtomFeed() {
        $this->startElement('feed');
        $this->writeAttributes(array(
            'xml:lang' => 'en',
            'xmlns' => 'http://www.w3.org/2005/Atom',
            'xmlns:time' => 'http://a9.com/-/opensearch/extensions/time/1.0/',
            'xmlns:os' => 'http://a9.com/-/spec/opensearch/1.1/',
            'xmlns:dc' => 'http://purl.org/dc/elements/1.1/',
            'xmlns:georss' => 'http://www.georss.org/georss',
            'xmlns:gml' => 'http://www.opengis.net/gml',
            'xmlns:geo' => 'http://a9.com/-/opensearch/extensions/geo/1.0/',
            'xmlns:eo' => 'http://a9.com/-/opensearch/extensions/eo/1.0/',
            'xmlns:metalink' => 'urn:ietf:params:xml:ns:metalink',
            'xmlns:xlink' => 'http://www.w3.org/1999/xlink',
            'xmlns:media' => 'http://search.yahoo.com/mrss/'
        ));
    }
    
    /**
     * Set title, subtitle and generator
     * 
     * @param string $id
     * @param string $title
     * @param string $subtitle
     */
    private function setBaseElements($title, $subtitle) {
        
        /*
         *  Title
         */
        $this->writeElement('title', $title);

        /*
         *  Subtitle
         */
        $this->startElement('subtitle');
        $this->writeAttributes(array('type' => 'html'));
        $this->text($subtitle);
        $this->endElement();

        /* 
         * Generator
         */
        $this->startElement('generator');
        $this->writeAttributes(array(
            'uri' => 'http://github.com/jjrom/resto2',
            'version' => Resto::VERSION
        ));
        $this->text('resto');
        $this->endElement();
        
        /*
         * Date of creation is now
         */
        $this->writeElement('updated', date('Y-m-dTH:i:sO'));
    }
    
    /**
     * Add summary entry
     * 
     * @param array $feature
     * @param RestoContext $context
     */
    private function addSummary($feature, $context) {
        $this->startElement('summary');
        $this->writeAttributes(array('type' => 'text'));
        $this->text((isset($feature['properties']['platform']) ? $feature['properties']['platform'] : '') . (isset($feature['properties']['platform']) && isset($feature['properties']['instrument']) ? '/' . $feature['properties']['instrument'] : '') . ' ' . $context->dictionary->translate('_acquiredOn', $feature['properties']['startDate']));
        $this->endElement(); // content
    }
    
    /**
     * Add atom entry base elements
     * 
     * @param array $feature
     */
    private function addEntryElements($feature) {
        
        /*
         * Base elements
         */
        $this->writeElements(array(
            'id' => $feature['id'], // ! THIS SHOULD BE AN ABSOLUTE UNIQUE  AND PERMANENT IDENTIFIER !!
            'dc:identifier' => $feature['id'], // Local identifier - i.e. last part of uri
            'title' => isset($feature['properties']['title']) ? $feature['properties']['title'] : '',
            'published' => $feature['properties']['published'],
            'updated' => $feature['properties']['updated'],
            /*
             * Element 'dc:date' - date of the resource is duration of acquisition following
             * the Dublin Core Collection Description on date
             * (http://www.ukoln.ac.uk/metadata/dcmi/collection-RKMS-ISO8601/)
             */
            'dc:date' => $feature['properties']['startDate'] . '/' . $feature['properties']['completionDate']
        ));
        
        /*
         * Time
         */
        $this->addGmlTime($feature['properties']['startDate'], $feature['properties']['completionDate']);
        
        /*
         * georss:polygon
         */
        $this->addGeorssPolygon($feature['geometry']['coordinates']);

    }
    
    /**
     * Add gml:validTime element
     * Element 'gml:validTime' - acquisition duration between startDate and completionDate
     * 
     * @param string $beginPosition
     * @param string $endPosition
     */
    private function addGmlTime($beginPosition, $endPosition) {
        $this->startElement('gml:validTime');
        $this->startElement('gml:TimePeriod');
        $this->writeElement('gml:beginPosition', $beginPosition);
        $this->writeElement('gml:endPosition', $endPosition);
        $this->endElement(); // gml:TimePeriod
        $this->endElement(); // gml:validTime
    }
    
    /**
     * Add georss:polygon from geojson entry
     * 
     * WARNING !
     *
     *  GeoJSON coordinates order is longitude,latitude
     *  GML coordinates order is latitude,longitude
     * 
     *  @param array $coordinates
     */
    private function addGeorssPolygon($coordinates) {
        if (isset($coordinates)) {
            $geometry = array();
            foreach ($coordinates as $key) {
                foreach ($key as $value) {
                    $geometry[] = $value[1] . ' ' . $value[0];
                }
            }
            $this->startElement('georss:where');
            $this->startElement('gml:Polygon');
            $this->startElement('gml:exterior');
            $this->startElement('gml:LinearRing');
            $this->startElement('gml:posList');
            $this->writeAttributes(array('srsDimensions' => '2'));
            $this->text(join(' ', $geometry));
            $this->endElement(); // gml:posList
            $this->endElement(); // gml:LinearRing
            $this->endElement(); // gml:exterior
            $this->endElement(); // gml:Polygon
            $this->endElement(); // georss:where
        }
    }
    
    /**
     * Add ATOM feed media element
     * 
     * @param string $type (should be THUMBNAIL or QUICKLOOK
     * @param string $url
     */
    private function addMedia($type, $url) {
        $this->startElement('media:content');
        $this->writeAttributes(array(
            'url' => $url,
            'medium' => 'image'
        ));
        $this->startElement('media:category');
        $this->writeAttributes(array('scheme' => 'http://www.opengis.net/spec/EOMPOM/1.0'));
        $this->text($type);
        $this->endElement();
        $this->endElement();
    }
    
    /**
     * Add entry links
     * 
     * @param array $feature
     */
    private function addLinks($feature) {
        
        /*
         * General links
         */
        if (is_array($feature['properties']['links'])) {
            for ($j = 0, $k = count($feature['properties']['links']); $j < $k; $j++) {
                $this->startElement('link');
                $this->writeAttributes(array(
                    'rel' => $feature['properties']['links'][$j]['rel'],
                    'type' => $feature['properties']['links'][$j]['type'],
                    'title' => $feature['properties']['links'][$j]['title'],
                    'href' => $feature['properties']['links'][$j]['href']
                ));
                $this->endElement(); // link
            }
        }
        
        /*
         * Element 'enclosure' - download product
         *  read from $feature['properties']['archive']
         */
        if (isset($feature['properties']['services']['download']['url'])) {
            $this->startElement('link');
            $this->writeAttributes(array(
                'rel' => 'enclosure',
                'type' => isset($feature['properties']['services']['download']['mimeType']) ? $feature['properties']['services']['download']['mimeType'] : 'application/unknown',
                'length' => isset($feature['properties']['services']['download']['size']) ? $feature['properties']['services']['download']['size'] : 0,
                'title' => 'File for ' . $feature['id'] . ' product',
                'metalink:priority' => 50,
                'href' => $feature['properties']['services']['download']['url']
            ));
            $this->endElement(); // link
        }
        
    }
    
    /**
     * Add entry media
     * 
     * @param array $feature
     */
    private function addQuicklooks($feature) {
        
        /*
         * Quicklook / Thumbnail
         */
        if (isset($feature['properties']['thumbnail']) || isset($feature['properties']['quicklook'])) {

            /*
             * rel=icon
             */
            if (isset($feature['properties']['quicklook'])) {
                $this->startElement('link');
                $this->writeAttributes(array(
                    'rel' => 'icon',
                    //'type' => 'TODO',
                    'title' => 'Browse image URL for ' . $feature['id'] . ' product',
                    'href' => $feature['properties']['quicklook']
                ));
                $this->endElement(); // link
            }

            /*
             * media:group
             */
            $this->startElement('media:group');
            if (isset($feature['properties']['thumbnail'])) {
                $this->addMedia('THUMNAIL', $feature['properties']['thumbnail']);
            }
            if (isset($feature['properties']['quicklook'])) {
                $this->addMedia('QUICKLOOK', $feature['properties']['quicklook']);
            }
            $this->endElement();
        }

    }
    
    /**
     * Set ATOM feed links element for FeatureCollection
     * 
     * @param array $properties
     */
    private function setCollectionLinks($properties) {
        if (is_array($properties['links'])) {
            for ($i = 0, $l = count($properties['links']); $i < $l; $i++) {
                $this->startElement('link');
                $this->writeAttributes(array(
                    'rel' => $properties['links'][$i]['rel'],
                    'title' => $properties['links'][$i]['title']
                ));
                if ($properties['links'][$i]['type'] === 'application/opensearchdescription+xml') {
                    $this->writeAttributes(array(
                        'type' => $properties['links'][$i]['type'],
                        'href' => $properties['links'][$i]['href']
                    ));
                }
                else {
                    $this->writeAttributes(array(
                        'type' => RestoUtil::$contentTypes['atom'],
                        'href' => RestoUtil::updateUrlFormat($properties['links'][$i]['href'], 'atom')
                    ));
                }
                $this->endElement(); // link
            }
        }
    }
    
    /**
     * Set ATOM feed Query element from request parameters
     * 
     * @param array $properties
     */
    private function setQuery($properties) {
        $this->startElement('os:Query');
        $this->writeAttributes(array('role' => 'request'));
        if (isset($properties['query'])) {
            $this->writeAttributes($properties['query']['original']);
        }
        $this->endElement();
    }
    
}
