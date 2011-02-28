<?php

define('GEOGRAPHIC_PROJECTION', 4326);

// implemented by map categories, which have no geometry
interface MapListElement
{
    const DESCRIPTION_TEXT = 0; // used by the majority of map data elements
    const DESCRIPTION_LIST = 1; // used by ArcGIS map features, which store attributes as an array.
                                // if we support backends like PostGIS, ESRI shapefiles/geodatabases
                                // etc. we will start seeing more of these guys.

    public function getTitle();
    public function getSubtitle();
    public function getIndex();
}

// implemented by map data elements that can be displayed on a map
interface MapFeature extends MapListElement
{
    public function getGeometry();
    public function setGeometry(MapGeometry $geometry);
    public function getDescription();
    public function getDescriptionType();
    public function getStyle();
}

interface MapGeometry
{
    // TODO deprecate getType() since we can tell what geometries
    // things are based on whether they implement MapPoly(line|gon)

    const POINT = 'Point';
    const POLYGON = 'Polygon';
    const POLYLINE = 'Polyline';

    // must return an array of the form {'lat' => 2.7182, 'lon' => -3.1415}
    public function getCenterCoordinate();
    
    public function getType();
}

interface MapPolyline extends MapGeometry
{
    public function getPoints();
}

interface MapPolygon extends MapGeometry
{
    public function getRings();
}

interface MapStyle
{
    const POINT = 0;
    const LINE = 1;
    const POLYGON = 2;
    const CALLOUT = 3;

    // these just have to be unique within the enclosing style type
    const COLOR = 'color';             // points
    const FILLCOLOR = 'fillColor';     // polygons, callouts, list view
    const STROKECOLOR = 'strokeColor'; // lines
    const TEXTCOLOR = self::COLOR;     // callouts
    const HEIGHT = 'height';           // points
    const WIDTH = 'width';             // points and lines
    const SIZE = self::WIDTH;          // points
    const WEIGHT = self::WIDTH;        // lines
    const ICON = 'icon';               // points, cell image in list view
    const SCALE = 'scale';             // points, labels -- kml
    const SHAPE = 'shape';             // points -- esri
    const CONSISTENCY = 'consistency'; // lines -- dotted/dashed/etc
    const SHOULD_OUTLINE = 'outline';  // polygons

    public function getStyleForTypeAndParam($type, $param);
}

class EmptyMapFeature implements MapFeature {
    private $geometry;
    private $style;
    
    private $title = '';
    private $address = '';
    private $description = '';
    private $index = 0;
    
    public function __construct($center) {
        $this->geometry = new EmptyMapPoint();
        $this->style = new EmptyMapStyle();
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle($title) {
        $this->title = $title;
    }
    
    public function getSubtitle() {
        return $this->address;
    }
    
    public function setAddress($address) {
        $this->address = $address;
    }
    
    public function getIndex() {
        return $this->index;
    }
    
    public function setIndex($index) {
        return $this->index;
    }
    
    public function getGeometry() {
        return $this->geometry;
    }
    
    public function setGeometry(MapGeometry $geometry) {
        $this->geometry = $geometry;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function setDescription($description) {
        $this->description = $description;
    }

    public function getDescriptionType() {
        return MapListElement::DESCRIPTION_TEXT;
    }

    public function getStyle() {
        return $this->style;
    }
}

class EmptyMapPoint implements MapGeometry {
    private $center;
    public function __construct($lat, $lon) {
        $this->center = array('lat' => $lat, 'lon' => $lon);
    }
    
    public function getCenterCoordinate() {
        return $this->center;
    }
    
    public function getType() {
        return MapGeometry::POINT;
    }
}

class EmptyMapPolyline implements MapPolyline {
    private $points;
    public function __construct($points) {
        $this->points = $points;
    }

    public function getCenterCoordinate()
    {
        $lat = 0;
        $lon = 0;
        $n = 0;
        foreach ($this->points as $coordinate) {
            $lat += $coordinate['lat'];
            $lon += $coordinate['lon'];
            $n += 1;
        }
        return array(
            'lat' => $lat / $n,
            'lon' => $lon / $n,
            );
    }

    public function getPoints() {
        return $this->points;
    }

    public function getType() {
        return MapGeometry::POLYGON;
    }
}

class EmptyMapPolygon implements MapPolygon {

    private $outerBoundary;
    private $innerBoundaries = array();

    public function __construct(Array $rings) {
        $this->outerBoundary = new EmptyMapPolyline($rings[0]);
        if (count($rings) > 1) {
            for ($i = 1; $i < count($rings); $i++) {
                $this->innerBoundaries[] = new EmptyMapPolyline($rings[$i]);
            }
        }
    }

    public function getCenterCoordinate()
    {
    	return $this->outerBoundary->getCenterCoordinate();
    }

    public function getRings()
    {
        $outerRing = $this->outerBoundary->getPoints();
        $result = array($outerRing);
        if (isset($this->innerBoundaries) && count($this->innerBoundaries)) {
            foreach ($this->innerBoundaries as $boundary) {
                $result[] = $boundary->getPoints();
            }
        }
        return $result;
    }

    public function getType() {
        return MapGeometry::POLYGON;
    }
}

class EmptyMapStyle implements MapStyle {
    public function getStyleForTypeAndParam($type, $param) {
        return null;
    }
}
