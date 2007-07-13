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
 * 				Implementa le operazioni di:
 * 				Inserimento nell'albero, cancellazione, spostamento di ramificazione
 * 				Le operazioni di save e delete vengono completamente  ridefinite.
*/
class Tree extends BEAppModel 
{
	var $name 		= 'Tree';
	var $useTable	= "view_trees" ;
	
	/**
	 * save.
	 * Non fa niente
	 * 
	 */
	function save($data = null, $validate = true, $fieldList = array()) {
		return true ;
	}
	
	/**
	 * definisce la radice dell'albero
	 */
	function setRoot($id) {
		$this->id = $id ;
	}
	
	/**
	 * ritorna l'indice  della radice dell'albero
	 */
	function getRoot() {
		return $this->id  ;
	}
	
	function appendChild($id, $idParent = null) {
		$idParent = (empty($idParent)) ? "NULL" :  $idParent ;
		
		return $this->query("CALL appendChildTree({$id}, {$idParent})");
	}
	
	function moveChildUp($id, $idParent = false) {
		if (!empty($idParent)) {
			$this->id = $idParent ;
		}
		return $this->query("CALL moveChildTreeUp({$id}, {$this->id})");
	}
	
	function moveChildDown($id, $idParent = false) {
		if (!empty($idParent)) {
			$this->id = $idParent ;
		}		
		return $this->query("CALL moveChildTreeDown({$id}, {$this->id})");
	}

	function moveChildFirst($id, $idParent = false) {
		if (!empty($idParent)) {
			$this->id = $idParent ;
		}		
		return $this->query("CALL moveChildTreeFirst({$id}, {$this->id})");
	}
	
	function moveChildLast($id, $idParent = false) {
		if (!empty($idParent)) {
			$this->id = $idParent ;
		}		
		return $this->query("CALL moveChildTreeLast({$id}, {$this->id})");
	}

	function move($idNewParent, $id = false) {
		if (!empty($id)) {
			$this->id = $id ;
		}
		
		// Verifica che il nuovo parent non sia un discendente dell'albero da spostare
		$ret = $this->query("SELECT isParentTree({$this->id}, {$idNewParent}) AS parent");
		if(!empty($ret["parent"])) return  false ;
		
 		return $this->query("CALL moveTree({$this->id}, {$idNewParent})");
 		
 		return true ;
	}
	
	/**
	 * Cancella il ramo con la root con un determinato id.
	 */
	function del($id = null, $cascade = true) {
		if (!empty($id)) {
			$this->id = $id;
		}
		$id = $this->id;		
		return $this->query("CALL deleteTree({$id})");
	}

	/**
	 * Preleva l'abero di cui id � la radice.
	 * Se l'userid e' presente, preleva solo gli oggetti di cui ha i permessi, se '' � un utente anonimo,
	 * altrimenti li prende tutti.
	 * Si possono selezionare i tipi di oggetti da inserire nell'albero.
	 * 
	 * @param integer $id		id della radice da selezionare. 
	 * @param string $userid	l'utente che accede. Se null: non controlla i permessi. Se '': uente guest.
	 * 							Default: non verifica i permessi.
	 * @param string $status	Prende oggetti solo con lo status passato
	 * @param array $filter		definisce i tipi gli oggetti da prelevare. Es.:
	 * 							1 | 3 | 22 ... aree, sezioni, documenti.
	 * 							Default: tutti.
	 */
	function getAll($id = null, $userid = null, $status = null, $filter = 0xFF) {
		$fields  = " * " ;
		
		// Setta l'id
		if (!empty($id)) {
			$this->id = $id;
		}

		// setta le condizioni di ricerca
		$conditions = array() ;
		if($filter) {
			$conditions[] = array("object_type_id" => " (object_type_id & {$filter})  ") ;
		}

		if(isset($userid)) {
			// Preleva i permessi dell'utente sugli oggetti selezionati
			$fields 		= " Tree.*, prmsUserByID ('{$userid}', id, 15) as perms " ;
			$conditions[] 	= " prmsUserByID ('{$userid}', id, ".BEDITA_PERMS_READ.") > 0 " ;
		}
			
		if(isset($status)) {
			$conditions[] = " status = '{$status}' " ;
		}

		if(isset($id)) {
			$conditions[] = " path LIKE (CONCAT((SELECT path FROM trees WHERE id = {$id}), '%')) " ;
		}
		
		// Esegue la ricerca
		$db 		 =& ConnectionManager::getDataSource($this->useDbConfig);
		$sqlClausole = $db->conditions($conditions, false, true) ;
		
		$records  = $this->execute("SELECT {$fields} FROM view_trees AS Tree {$sqlClausole}") ;
		
		// Costruisce l'albero
		$roots 	= array() ;
		$tree 	= array() ;
		$size	= count($records) ;
		
		for ($i=0; $i < $size ; $i++) {
			$root = am($records[$i]['trees'], (isset($records[$i]['objects']))?$records[$i]['objects']:array(), $records[$i][0]) ;

			$root['children']	= array() ;
			$roots[$root['id']] = &$root ;
			
			if(isset($root['parent_id']) && isset($roots[$root['parent_id']])) {
				$roots[$root['parent_id']]['children'][] = &$root ;
			} else {
				$tree[] = &$root ; 
			}
			
			unset($root);
		}
		
		// scarta tutti i rami che non sono root e che non coincidono con $id
		// sono rami su cui l'utente non ha permessi sui parent
		$tmp = array() ;
		for ($i=0; $i < count($tree) ; $i++) {
			if(isset($id) && $tree[$i]['id'] == $id) {
				$tmp[] = &$tree[$i] ;
				
				continue ;
			}

			if(!isset($id) && empty($tree[$i]['parent_id'])) {
				$tmp[] = &$tree[$i] ;

				continue ; 
			}
		}
		
		return $tmp ;
	}
	
/*	
	function getChildren($id, $hiddenField = null, $compact = true) {
		if (!empty($id)) {
			$this->id = $id;
		}
		$id = $this->id;		
		$ret = $this->query("SELECT id, path, priority FROM tree WHERE parent_id = '{$id}' ORDER BY priority ");
		
		return $ret ;
	}
*/
}


?>