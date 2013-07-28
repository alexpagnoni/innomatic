<?php   

require_once('innomatic/module/ModuleObject.php');
require_once('innomatic/dataaccess/DataAccessResult.php');

/**
 * @author Alex Pagnoni <alex.pagnoni@innoteam.it>
 * @copyright Copyright 2004-2013 Innoteam S.r.l.
 * @since 5.1
 */
class ModuleReadOnlyResultSet {
	private $resultSet;

	function __construct(DataAccessResult $resultSet) {
		$this->resultSet = $resultSet;
	}

	function getNext(ModuleObject $businessObject) {
		$row = $this->resultSet->next();

		$class = new ReflectionObject($businessObject->moduleGetVO());
		$properties = $class->getProperties();

		for ($i = 0; $i < count($properties); $i ++) {
			$prop_name = $properties[$i]->getName();
			$businessObject->moduleGetVO()->setValue($prop_name, $row[$prop_name]);
		}

		return $businessObject;
	}

	public function hasNext() {
		return $this->resultSet->hasNext();
	}

	function rowCount() {
		return $this->resultSet->rowCount();
	}
}

?>