<?php
/*-----8<--------------------------------------------------------------------
 * 
 * BEdita - a semantic content management framework
 * 
 * Copyright 2008, 2010 ChannelWeb Srl, Chialab Srl
 * 
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published 
 * by the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the Affero GNU General Public License for more details.
 * You should have received a copy of the Affero GNU General Public License 
 * version 3 along with BEdita (see LICENSE.AGPL).
 * If not, see <http://gnu.org/licenses/agpl-3.0.html>.
 * 
 *------------------------------------------------------------------->8-----
 */

/**
 * 
 * BuildFilter Class
 * build custom sql statements used in BEAppModel::findObjects() method to filter list of BEdita objects
 * 
 * It can be extended with custom behavior classes to refine query according to your needs
 * 
 * Example:
 * 
 * App::import('Behavior', 'BuildFilter');
 * 
 * ClassNameFilterBehavior extends BuildFilterBehavior {
 * 
 *		public function myFilterMethod($data) {
 * 
 *		}
 * 
 * }
 * 
 * to call that method you have to build your filter to pass at BEAppModel::findObjects() as
 * 
 * $filter['ClassNameFilter.myFilterMethod'] = $yourData;
 * 
 * $yourData item will be passed to myFilterMethod as first argument
 * 
 * methods of your custom filter class should be return an array that can have the following keys:
 *	- "fields" => string of fields to add to query
 *	- "fromStart" => string of FROM statement to insert at the begin of the string (LEFT, RIGHT and INNER JOIN statements)
 *  - "fromEnd" => string of FROM statement to insert at the end of the string (list of tables and aliases)
 *	- "conditions" => array of conditions to add at WHERE statement
 *  - "group" => string to add at GROUP statement
 *  - "order" => string to add at ORDER statement
 * 
 * 
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 * 
 * $Id$
 */
class BuildFilterBehavior extends ModelBehavior {
 	
	private $fields = "";
	private $from = "";
	private $conditions = array();
	private $group = "";
	private $order = "";
	protected $filter = array();
	protected $startQuote = ""; // internal use: start quote
	protected $endQuote = ""; // internal use: end quote
	protected $model = "";
	
	
	function setup(&$model, $settings=array()) {
    	$this->model = $model;
		if(empty($this->sQ)) {
			$db = ConnectionManager::getDataSource($model->useDbConfig);
			$this->startQuote = $db->startQuote;
			$this->endQuote = $db->endQuote;
    	}
	}
	
	/**
	 * set conditions, from, fields, group and order from $filter
	 *
	 * @param array $filter
	 * @return array
	 */
	function getSqlItems(&$model, $filter) {
		$this->initVars($filter);
		$s = $this->startQuote;
		$e = $this->endQuote;
		
		$beObject = ClassRegistry::init("BEObject");
		
		// #CUSTOM QUERY -- all class methods
		foreach ($this->filter as $key => $val) {
			
			$filterExtension = explode(".", $key);
			$filterClassName = null;
			$filterMethodName = null;
			if (count($filterExtension) > 1) {
				$filterClassName = $filterExtension[0];
				$filterMethodName = $filterExtension[1];
			}
			if ($filterClassName && App::import('Behavior', $filterClassName) 
					&& is_subclass_of($filterClassName . 'Behavior', get_class($this))
					&& method_exists($filterClassName . 'Behavior', $filterMethodName)) {
				
				// attach behavior
				if (!$this->model->Behaviors->attached($filterClassName)) {
					$this->model->Behaviors->attach($filterClassName);
				}
				
				$items = $this->model->Behaviors->$filterClassName->$filterMethodName($val);
				if (!empty($items["fields"])) {
					$this->fields .= ", " . $items["fields"];
				}
				if (!empty($items["fromStart"])) {
					$this->from = $items["fromStart"] . $this->from;
				}
				if (!empty($items["fromEnd"])) {
					$this->from .= ", " . $items["fromEnd"];
				}
				if (!empty($items["conditions"])) {
					$this->conditions = array_merge($this->conditions, $items["conditions"]);
				}
				if (!empty($items["group"])) {
					$this->group .= $items["group"];
				}
				if (!empty($items["order"])) {
					$this->order .= $items["order"];
				}
						
			} elseif (method_exists($this, $key . "Filter")) {
				$this->{$key . "Filter"}($s, $e, $val);
			} else {
			
				if ($beObject->hasField($key)) {
					$key = "{$s}BEObject{$e}." . $s . $key . $e;
				} else {
					
					if(strstr($key, ".*")) {
						$mod = str_replace(".*", "", $key);
						$this->group .= "," . $this->model->fieldsString($mod);
						$key = $s . $mod . $e . ".*";
					} else {
						$key = $s . str_replace(".", "{$e}.{$s}", $key) . $e;
						$this->group .= "," . $key;
					}					
				}
				
				$this->fields .= ", " . $key;
				if (is_array($val)) {
					$this->conditions[] = $key . " IN ('" . implode("','", $val) . "')";
				} elseif ($val !== "" && $val !== null) {
					$this->conditions[] = (preg_match("/^[<|>]/", $val))? "(".$key." ".$val.")" : $key . "='" . $val . "'";
				}
	
				if (count($arr = explode(".", $key)) == 2 ) {
					$modelName = str_replace(array($s, $e) ,"",$arr[0]);
					if (!strstr($modelName,"BEObject") && $modelName != "Content") {
						$model = ClassRegistry::init($modelName);
						$f_str = $s. $model->useTable . $e. " as " . $s. $model->alias . $e. "";
						// create join with BEObject
						if (empty($this->from) || !strstr($this->from, $f_str)) {
							$this->from .= ", " . $f_str;
							if (empty($model->hasOne["BEObject"]) && $model->hasField("object_id") && $model->alias != "ObjectRelation")
								$this->conditions[] = "{$s}BEObject{$e}.{$s}id{$e}={$s}" . $model->alias . "{$e}.{$s}object_id{$e}";
							else
								$this->conditions[] = "{$s}BEObject{$e}.{$s}id{$e}={$s}" . $model->alias . "{$e}.{$s}id{$e}";							
						}
					}
					
				}
				
			}
		}
		return array($this->fields, $this->from ,$this->conditions, $this->group, $this->order);
	}
	
	/**
	 * initialize sql items and some filter
	 * 
	 * @param array $filter 
	 */
	private function initVars(array $filter) {
		$this->fields = "";
		$this->from = "";
		$this->conditions = array();
		$this->group = "";
		$this->order = "";
		
		if (array_key_exists("relation", $filter)) {
			$filter["ObjectRelation.switch"] = $filter["relation"];
			unset($filter["relation"]);
		}
			
		if (array_key_exists("rel_object_id", $filter)) {
			$filter["ObjectRelation.object_id"] = $filter["rel_object_id"];
			unset($filter["rel_object_id"]);
		}
		if (array_key_exists("ref_object_details", $filter)) {
			$mod = $filter["ref_object_details"];
			$found = false;
			foreach ($filter as $k => $v) {
				if(strstr($k, $mod.".")) {
					$found = true;
				}
			}
			if(!$found) {
				$filter[$mod.".*"] = "";
			}
		}
		$this->filter = $filter;
	}
	
	/**
	 * filter to get user_id (as obj_userid) joined to an object through object_users table
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value, if not empty to get user_id joined to an object with $value relation (object_users.swicth)
	 */
	protected function object_userFilter($s, $e, $value = null) {
		//$this->fields .= ", {$s}UserOU{$e}.{$s}userid{$e} AS obj_userid";
		$this->fields .= ", {$s}ObjectUser{$e}.{$s}user_id{$e} AS obj_userid";
		$from = " LEFT OUTER JOIN {$s}object_users{$e} AS {$s}ObjectUser{$e} ON {$s}BEObject{$e}.{$s}id{$e}={$s}ObjectUser{$e}.{$s}object_id{$e}";
		if(!empty($value)) {
			$from .= " AND {$s}ObjectUser{$e}.{$s}switch{$e} = '$value'";
		}
		//$from .= " LEFT OUTER JOIN {$s}users{$e} AS {$s}UserOU{$e} ON {$s}ObjectUser{$e}.{$s}user_id{$e}={$s}UserOU{$e}.{$s}id{$e}";
		$this->from = $from . $this->from;
		//$this->group .= ", {$s}ObjectUser{$e}.object_id, {$s}UserOU{$e}.{$s}userid{$e}";
		$this->group .= ", obj_userid";
	}
	
	/**
	 * add a count of Annotation objects as Comment, EditoreNote, etc...
	 * If 'object_type_id' specified in BuildFilter::filter then get annotations for that object type/s
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value, annotation model or an array of annotation models
	 *			Example:
	 * 
	 *			$value = "Comment" => add count of comment as 'num_of_comment'
	 *			$value = array("Comment", "EditorNote") => add count of comments as 'num_of_comment' 
	 *													   and count of editor notes as 'num_of_editor_note'
	 */
	protected function count_annotationFilter($s, $e, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		
		if (!empty($this->filter["object_type_id"])) {
			$object_type_id = $this->filter["object_type_id"];
		} elseif (!empty($this->filter["BEObject.object_type_id"])) {
			$object_type_id = $this->filter["BEObject.object_type_id"];
		}
		
		foreach ($value as $key => $annotationType) {
			$annotationModel = ClassRegistry::init($annotationType);
			$refObj_type_id = Configure::read("objectTypes." . Inflector::underscore($annotationModel->name) . ".id");
			$numOf = "num_of_" . Inflector::underscore($annotationModel->name);
			$this->fields .= ", SUM(" . $numOf . ") AS " . $numOf;
			$from = " LEFT OUTER JOIN (
						SELECT DISTINCT {$s}BEObject{$e}.{$s}id{$e}, COUNT({$s}" . $annotationModel->name . "{$e}.{$s}id{$e}) AS " . $numOf ."
						FROM {$s}objects{$e} AS {$s}BEObject{$e} 
						LEFT OUTER JOIN {$s}annotations{$e} AS {$s}" . $annotationModel->name . "{$e} ON {$s}BEObject{$e}.{$s}id{$e}={$s}" . $annotationModel->name . "{$e}.{$s}object_id{$e}
						RIGHT OUTER JOIN {$s}objects{$e} AS {$s}RefObj{$e} ON ({$s}RefObj{$e}.{$s}id{$e} = {$s}" . $annotationModel->name . "{$e}.{$s}id{$e} AND {$s}RefObj{$e}.{$s}object_type_id{$e}=" . $refObj_type_id . ")
					";
			if (!empty($object_type_id)) {
				$from .= (is_array($object_type_id))? "WHERE {$s}BEObject{$e}.{$s}object_type_id{$e} IN (" . implode(",", $object_type_id) . ")" : "WHERE {$s}BEObject{$e}.{$s}object_type_id{$e}=".$object_type_id;
			}
			
			$from .= " GROUP BY {$s}BEObject{$e}.{$s}id{$e}
					) AS {$s}".$annotationModel->name."{$e} ON {$s}".$annotationModel->name."{$e}.{$s}id{$e} = {$s}BEObject{$e}.{$s}id{$e}";
			
			$this->from = $from . $this->from;
		}
	}
	
	/**
	 * get a category of an object naming it mediatype (used in Multimedia module)
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 */
	protected function mediatypeFilter($s, $e) {
		$this->fields .= ", {$s}Category{$e}.{$s}name{$e} AS mediatype";
		$this->from = " LEFT OUTER JOIN {$s}object_categories{$e} AS {$s}ObjectCategory{$e} ON {$s}BEObject{$e}.{$s}id{$e}={$s}ObjectCategory{$e}.{$s}object_id{$e}
				LEFT OUTER JOIN {$s}categories{$e} AS {$s}Category{$e} ON {$s}ObjectCategory{$e}.{$s}category_id{$e}={$s}Category{$e}.{$s}id{$e} AND {$s}Category{$e}.{$s}object_type_id{$e} IS NOT NULL"
				. $this->from;
	}
	
	/**
	 * search text filter
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param string $value, the string to search for
	 */
	protected function queryFilter($s, $e, $value) {
		// #MYSQL
		App::import('Sanitize');
		$value = Sanitize::html($value, array('remove' => true));
		$this->fields .= ", SearchText.object_id AS oid, SUM( MATCH (SearchText.content) AGAINST ('" . $value . "') * SearchText.relevance ) AS points";
		$this->from .= ", search_texts AS SearchText";
		$this->conditions[] = "SearchText.object_id = BEObject.id AND SearchText.lang = BEObject.lang AND MATCH (SearchText.content) AGAINST ('" . $value . "')";
		$this->order .= "points DESC ";
	}
	
	/**
	 * filter objects by category
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value, id or category name
	 */
	protected function categoryFilter($s, $e, $value) {
		$cat_field = (is_numeric($value))? "id" : "name";
		if (!strstr($this->from, "Category") && !array_key_exists("mediatype", $this->filter))
			$this->from .= ", {$s}categories{$s} AS {$s}Category{$s}, {$s}object_categories{$s} AS {$s}ObjectCategory{$s}";
		$this->conditions[] = "{$s}Category{$e}.{$s}" . $cat_field . "{$e}='" . $value . "' 
						AND {$s}ObjectCategory{$e}.{$s}object_id{$e}={$s}BEObject{$e}.{$s}id{$e}
						AND {$s}ObjectCategory{$e}.{$s}category_id{$e}={$s}Category{$e}.{$s}id{$e}
						AND {$s}Category{$e}.{$s}object_type_id{$e} IS NOT NULL";
	}
	
	/**
	 * filter objects by tag
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value, id or tag name
	 */
	protected function tagFilter($s, $e, $value) {
		$cat_field = (is_numeric($value))? "id" : "name";
		if (!strstr($this->from, "Category") && !array_key_exists("mediatype", $this->filter))
			$this->from .= ", {$s}categories{$e} AS {$s}Category{$e}, {$s}object_categories{$e} AS {$s}ObjectCategory{$e}";
		$this->conditions[] = "{$s}Category{$e}.{$s}" . $cat_field . "{$e}='" . $value . "' 
						AND {$s}ObjectCategory{$e}.{$s}object_id{$e}={$s}BEObject{$e}.{$s}id{$e}
						AND {$s}ObjectCategory{$e}.{$s}category_id{$e}={$s}Category{$e}.{$s}id{$e}
						AND {$s}Category{$e}.{$s}object_type_id{$e} IS NULL";
	}
	
	/**
	 * get RelatedObject fields
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param type $value [unused?? To verify]
	 */
	protected function rel_detailFilter($s, $e, $value) {
		if (!empty($value)) {
			if (!isset($this->filter["ObjectRelation.switch"]))
				$this->filter["ObjectRelation.switch"] = "";
			$relFields = $this->model->fieldsString("BEObject", "RelatedObject");
			$this->fields .= ", " . $refFields;
			$this->from .= ", {$s}objects{$e} AS {$s}RelatedObject{$e}";
			$this->conditions[] = "{$s}ObjectRelation{$e}.{$s}object_id{$e}={$s}RelatedObject{$e}.{$s}id{$e}";
			$this->order .= ( (!empty($this->order))? "," : "" ) . "{$s}ObjectRelation{$e}.{$s}priority{$e}";
			$this->group .= ", " . $refFields;
		}		
	}
	
	/**
	 * get the object referenced from an Annotation object as Comment, EditorNote
	 * 
	 * Example: getting a list of comment you can take also the object commented with
	 * 
	 * $filter['object_type_id'] = Configure::read('objectTypes.comment.id'); // to filter comments
	 * $filter['ref_object_details'] = 'Comment'; // to get also the object commented
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param string $value, the Annotation Model as Comment, EditorNote, ...
	 */
	protected function ref_object_detailsFilter($s, $e, $value) {
		if (!empty($value)) {
			$refFields = $this->model->fieldsString("BEObject", "ReferenceObject");
			$this->fields .= ", $refFields";
			$this->from .= ", {$s}objects{$e} AS {$s}ReferenceObject{$e}";
			$this->conditions[] = $s . ClassRegistry::init($value)->alias . "{$e}.{$s}object_id{$s}={$s}ReferenceObject{$e}.{$s}id{$e}";
			$this->group .= "," . $refFields;
		}
	}
	
	/**
	 * filter the cards joined at a mail group (used in Newsletter module)
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param integer $value, mail_group_id
	 */
	protected function mail_groupFilter($s, $e, $value) {
		$this->from .= ", {$s}mail_group_cards{$e} AS {$s}MailGroupCard{$e}";
		$this->conditions[] = "{$s}MailGroupCard{$e}.{$s}mail_group_id{$e}='" . $value . "' 
					AND {$s}MailGroupCard{$e}.{$s}card_id{$e}={$s}BEObject{$e}.{$s}id{$e}";
	}

	/**
	 * get userid (username) and real name of user that has created the object
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 */
	protected function user_createdFilter($s, $e) {
		$locFields = ", {$s}User{$e}.{$s}userid{$e}, {$s}User{$e}.{$s}realname{$e}";
		$this->fields .= $locFields;
		$this->from .= ", {$s}users{$e} AS {$s}User{$e}";
		$this->conditions[] = "{$s}User{$e}.{$s}id{$e}={$s}BEObject{$e}.{$s}user_created{$e}";
		$this->group .= $locFields;
	}

	/**
	 * count objects' permissions as num_of_permission
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value, if it's integer then count $value permission
	 */
	protected function count_permissionFilter($s, $e, $value) {
		$this->fields .= ", COUNT({$s}Permission{$e}.{$s}id{$e}) AS num_of_permission";
		$from = " LEFT OUTER JOIN {$s}permissions{$e} as {$s}Permission{$e} ON {$s}Permission{$e}.{$s}object_id{$e} = {$s}BEObject{$e}.{$s}id{$e}";
		if (is_numeric($value)) {
			$from .= " AND {$s}Permission{$e}.{$s}flag{$e} = " . $value;
		}
		$this->from = $from . $this->from;
	}
	
	/**
	 * custom property filter
	 * get object_properties fields as ObjectProperty
	 * 
	 * filter rules:
	 *	- default join used is INNER JOIN. It can be overriden with 'join' key in $value
	 *  - if $value is a string or a number set condition respectively to property_value or property_id
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value can be a string/number or an array with keys equal to object_properties table field
	 */
	protected function custom_propertyFilter($s, $e, $value) {
		if (!is_array($value)) {
			$value = (is_numeric($value))? array('property_id' => $value) : array('property_value' => $value);
		}
		$defaultOptions = array('join' => 'INNER JOIN');
		$value = array_merge($defaultOptions, $value);
		$this->fields .= ", " . $this->model->fieldsString("ObjectProperty");
		$from = " " . $value['join'] . " {$s}object_properties{$e} AS {$s}ObjectProperty{$e} ON {$s}BEObject{$e}.{$s}id{$e}={$s}ObjectProperty{$e}.{$s}object_id{$e}";
		unset($value['join']);
		if (!empty($value)) {
			foreach ($value as $k => $v) {
				$from .= " AND {$s}ObjectProperty{$e}.{$s}{$k}{$e}='" . $v ."'";
			}
		}
		$this->from = $from . $this->from;
	}
	
	/**
	 * date item filter
	 * get date_items fields as DateItem
	 * 
	 * filter rules:
	 *	- default join used is INNER JOIN. It can be overriden with 'join' key in $value
	 *  - if $value is a number set condition start_date
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value can be a number or an array with keys equal to date_items table field
	 */
	protected function date_itemFilter($s, $e, $value) {
		if (!is_array($value)) {
			$value = array('start_date' => $value);
		}
		$defaultOptions = array('join' => 'INNER JOIN');
		$value = array_merge($defaultOptions, $value);
		$this->fields .= ", " . $this->model->fieldsString("DateItem");
		$from = " " . $value['join'] . "  {$s}date_items{$e} AS {$s}DateItem{$e} ON {$s}BEObject{$e}.{$s}id{$e}={$s}DateItem{$e}.{$s}object_id{$e}";
		unset($value['join']);
		if (!empty($value)) {
			foreach ($value as $k => $v) {
				$from .= " AND {$s}DateItem{$e}.{$s}{$k}{$e}='" . $v ."'";
			}
		}
		$this->from = $from . $this->from;
	}
	
	/**
	 * count relation filter
	 * If 'object_type_id' specified in BuildFilter::filter then get relations for that object type/s
	 * 
	 * @param string $s, start quote sql
	 * @param string $e, end quote sql
	 * @param mixed $value, relation name or array of relations (object_relations.switch field)
	 * 
	 *		Example:
	 * 
	 *			$value = "seealso" => add count of seealso relation as 'num_of_relations_seealso'
	 *			$value = array("seealso", "download") => add count of seealso relation as 'num_of_relations_seealso'
	 *													 and count of download relation as 'num_of_relations_download'
	 */
	protected function count_relationsFilter($s, $e, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		if (!empty($this->filter["object_type_id"])) {
			$object_type_id = $this->filter["object_type_id"];
		} elseif (!empty($this->filter["BEObject.object_type_id"])) {
			$object_type_id = $this->filter["BEObject.object_type_id"];
		}
		foreach ($value as $relation) {
			$numOf =  "num_of_relations_" . $relation;
			$alias = "Relation" . Inflector::camelize($relation);
			$this->fields .= ", SUM(" . $numOf . ") AS " . $numOf;
			$from = " LEFT OUTER JOIN (
						SELECT DISTINCT {$s}BEObject{$e}.{$s}id{$e}, COUNT({$s}{$alias}{$e}.{$s}id{$e}) AS " . $numOf ."
						FROM {$s}objects{$e} AS {$s}BEObject{$e} 
						LEFT OUTER JOIN {$s}object_relations{$e} as {$s}{$alias}{$e} ON {$s}{$alias}{$e}.{$s}id{$e} = {$s}BEObject{$e}.{$s}id{$e} AND {$s}{$alias}{$e}.{$s}switch{$e} = '{$relation}'
					";
			
			if (!empty($object_type_id)) {
				$from .= (is_array($object_type_id))? "WHERE {$s}BEObject{$e}.{$s}object_type_id{$e} IN (" . implode(",", $object_type_id) . ")" : "WHERE {$s}BEObject{$e}.{$s}object_type_id{$e}=".$object_type_id;
			}
			
			$from .= " GROUP BY {$s}BEObject{$e}.{$s}id{$e}
					) AS {$s}{$alias}{$e} ON {$s}{$alias}{$e}.{$s}id{$e} = {$s}BEObject{$e}.{$s}id{$e}";
			
			
			$this->from = $from . $this->from;
		}
	}
}
 
?>