<?php
/**
 * AClass for creating a resource
 *
 * @package The-Datatank/model/resources/create
 * @copyright (C) 2011 by iRail vzw/asbl
 * @license AGPLv3
 * @author Jan Vansteenlandt
 */
abstract class ACreator{

    public function __construct($package, $resource){
        $this->package = $package;
        $this->resource = $resource;
    }
  
    /**
     * set parameters, we leave this to the subclass
     */
    abstract public function setParameter($key,$value);

    /**
     * Creates a resource
     */
    abstract public function create();

    /**
     * get all the parameters to create a resource
     * @return hash with key = parameter name and value = documentation about the parameter
     */
    public function documentParameters(){
        return array("resource_type" => "The type of the resource.", 
                     "package_title" => "An alias for the package name, used for presentation and visualization purposes.", 
                     "resource_title" => "An alias for the resource name, used for presentation and visualization purposes.",
                     "tags" => "A serie of descriptive tags, separated with a semi-colon."
                     );
    }
    
    /**
     * get the required parameters
     * @return array with all of the required parameters
     */
    public function documentRequiredParameters(){
        return array("resource_type");
    }

    /**
     * make package id
     * @return id of the package
     */
    protected function makePackage($package){
        // TODO put this in DBQueries
        $result = R::getAll(
            "SELECT package.id as id 
             FROM package 
             WHERE package_name=:package_name",
            array(":package_name"=>$package)
        );

        if(sizeof($result) == 0){
            $newpackage = R::dispense("package");
            $newpackage->package_name = $package;
            $newpackage->timestamp = time();
            if(isset($this->package_title)){
                $newpackage->package_title = $this->package_title;
            }else{
                $newpackage->package_title = $package;
            }   
            $id = R::store($newpackage);
            return $id;
        }
        return $result[0]["id"];
    }
    
    /**
     * make resource id
     * @return id of the resource
     */
    protected function makeResource($package_id, $resource, $resource_type){
        
        // TODO put this in DBQueries.
        $checkExistence = R::getAll(
            "SELECT resource.id
             FROM resource, package
             WHERE :package_id = package.id and resource.resource_name =:resource and resource.package_id = package.id",
            array(":package_id" => $package_id, ":resource" => $resource)
        );

        if(sizeof($checkExistence) == 0){
            $newResource = R::dispense("resource");
            $newResource->package_id = $package_id;
            $newResource->resource_name =  $resource;
            $newResource->creation_timestamp = time();
            $newResource->last_update_timestamp = time();
            $newResource->type =  $resource_type;
            if(isset($this->resource_title)){
                $newResource->resource_title = $this->resource_title;
            }else{
                $newResource->resource_title = $resource;
            }
            
            if(isset($this->tags)){
                $newResource->tags = $this->tags;
            }
            return R::store($newResource);
        }
        return $checkExistence[0]["id"];
    }
}
?>