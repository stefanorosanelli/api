<?php
/**
 *
 * PHP versions 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * @filesource
 * @copyright		Copyright (c) 2007
 * @link			
 * @package			
 * @subpackage		
 * @since			
 * @version			
 * @modifiedby		
 * @lastmodified	
 * @license
 * @author 		giangi giangi@qwerg.com	
 * 		
 * 						
*/
class BiblioItem extends BEAppObjectModel
{
	var $name 		= 'BiblioItem';
	var $recursive 	= 2 ;
	var $actsAs 	= array(
			'CompactResult' 		=> array(),
			'CreateIndexFields'		=> array(),
			'ForeignDependenceSave' => array('Object', 'ContentBase'),
			'DeleteObject' 			=> 'objects',
	); 
	
	var $validate = array(
		'bibliography_id'	=> array(array('rule' => VALID_NUMBER, 		'required' => true)),
	) ;

	var $hasOne = array(
			'Object' =>
				array(
					'className'		=> 'BEObject',
					'conditions'   => '',
					'foreignKey'	=> 'id',
					'dependent'		=> true
				),
			'ContentBase' =>
				array(
					'className'		=> 'ContentBase',
					'conditions'   => '',
					'foreignKey'	=> 'id',
					'dependent'		=> true
				),
		) ;			

	function __construct() {
		parent::__construct() ;
	}

	/**
	 * Associa il commento creato/modificato all'oggetto commentato
	 */
	function afterSave($created) {
		if (!$created) return ;
		
		if(!class_exists('Bibliography')) loadModel('Bibliography') ;

		$biblio = new Bibliography() ;
		
		$biblio->appendChild($this->{$this->primaryKey} , $this->data[$this->name]['bibliography_id']) ;
	}
}
?>
