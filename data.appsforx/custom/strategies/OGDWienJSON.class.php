<?php
/**
 * This class handles a XLS file
 *
 * @package The-Datatank/custom/strategies
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Lieven Janssen
 */
include_once("custom/strategies/ATabularData.class.php");

class OGDWienJSON extends ATabularData {

    /**
     * Returns an array with params => documentation pairs that can be used to create this type of resource.
     * @return array with parameter => documentation pairs
     */
    public function documentCreateParameters(){
        return array("url" => "The url of where the OGD Wien JSON is found.",
                     "columns" => "The columns that are to be published from the OGD Wien JSON.",
                     "PK" => "The primary key of each row."
        );  
    }
    
    /**
     * Returns an array with params => documentation pairs who are required to create this type of resource.
     * @return array with parameter => documentation pairs
     */
    public function documentCreateRequiredParameters(){
        return array("url");    
    }

    /**
     * Document all the read required parameters for documentation purposes. 
     * @return array with necessary parameters to read this type of resource.
     */
    public function documentReadRequiredParameters(){
        return array();
    }
    
    /**
     * Returns an array with parameter => documentation pairs that can be used to read this resource.
     * @return array with parameter => documentation pairs
     */
    public function documentReadParameters(){
        return array("long", "lat", "radius");
    }
    

    protected function isValid($package_id,$generic_resource_id) {
        if(!isset($this->url)){
			$this->throwException($package_id,$generic_resource_id, "Can't find url of the OGD Wien JSON");
        }
		
        if (!isset($this->columns)) {
            $this->columns = array();
        }

        if (!isset($this->PK)) {
            $this->PK = "id";
        }

		$url = $this->url;
		$columns = $this->columns;
        
		if(empty($this->columns)){ 
			try { 

				$json = file_get_contents($url,0,null,null);
				$json = utf8_encode($json);
				$json = json_decode($json);
				
				$feature = $json->features[0];
				
				foreach($feature->properties as $property => $value) {
					$property = strtolower($property);
					$this->columns[$property] = $property;
				}
				$this->columns["id"] = "id";
				$this->columns["long"] = "long";
				$this->columns["lat"] = "lat";
				$this->columns["distance"] = "distance";
			} catch( Exception $ex) {
				throw new CouldNotGetDataTDTException( $url );
			}
		}
		
        return true;
    }

    public function readPaged($package,$resource,$page){
        //TODO ( as this proxy's a json resource, this will probably not use any paging )
    }

    public function read(&$configObject){
		set_time_limit(1000);
	
		parent::read($configObject);
       
        if(isset($configObject->url)){
            $url = $configObject->url;
        }else{
            throw new ResourceTDTException("Can't find url of the OGD Wien JSON");
        }
		
        $columns = array();
        
        $PK = $configObject->PK;
            
        $columns = $configObject->columns;

        //$gen_res_id = $configObject->gen_res_id;
        
        $resultobject = new stdClass();
        $arrayOfRowObjects = array();
        $row = 0;
		
        /*
         * First retrieve the values for the generic fields of the OGD Wien JSON logic
         */
/*
		 $result = DBQueries::getOGDWienJSONResource($package, $resource);
*/
        

/*
        if(isset($result["url"])){
            $url = $result["url"];
        }else{
            throw new ResourceTDTException("Can't find url of the OGD Wien JSON.");
        }
*/		
       
        //$columns = array();
        
        // get the columns from the columns table
        //$allowed_columns = DBQueries::getPublishedColumns($gen_res_id);
            
        //$columns = array();
        //$PK = "id";
		
/*
        foreach($allowed_columns as $result){
            array_push($columns,$result["column_name"]);
        }
*/		
        $arrayOfRowObjects = array();
        //$row = 0;
     
        try { 

            $json = file_get_contents($url,0,null,null);
            $json = utf8_encode($json);
            $json = json_decode($json);
            
            foreach($json->features as $feature) {
                $distance = NULL;
                if (isset($this->radius) && isset($this->long) && isset($this->lat)) {
                    $olat = $feature->geometry->coordinates[1];
                    $olon = $feature->geometry->coordinates[0];
                    $R = 6371; // earth's radius in km
                    $dLat = deg2rad($this->lat-$olat);
                    $dLon = deg2rad($this->long-$olon);
                    $rolat = deg2rad($olat);
                    $rlat = deg2rad($this->lat);

                    $a = sin($dLat/2) * sin($dLat/2) + sin($dLon/2) * sin($dLon/2) * cos($rolat) * cos($rlat); 
                    $c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
                    $distance = $R * $c;             
                }
                if(!isset($distance) || $distance < $this->radius) {
                    $rowobject = new stdClass();    
                    $rowobject->id = substr($feature->id, strpos($feature->id,".") + 1);
                    $rowobject->long = $feature->geometry->coordinates[0];
                    $rowobject->lat = $feature->geometry->coordinates[1];
                    // distance is null when no geo search is performed
                    $rowobject->distance = round($distance,3); // round on 1m precision
                    
                    foreach($feature->properties as $property => $value) {
                        $property = strtolower($property);
                        if(sizeof($columns) == 0 || in_array($property,$columns)) {                        
                            $rowobject->$property = $value;
                        }
                    }
                    $arrayOfRowObjects[$rowobject->id] = $rowobject;
                }
            }

            return $arrayOfRowObjects;
        } catch( Exception $ex) {
            throw new CouldNotGetDataTDTException( $url );
        }
    }
}
?>
