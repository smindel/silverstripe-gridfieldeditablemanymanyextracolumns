<?php

class GridFieldEditableManyManyExtraColumns implements GridField_ColumnProvider, GridField_ActionProvider {

	/**
	 * Set the columns and their corresponding field types that can be edited.
	 * This is a way to limit the set of many_many_extraFields that get rendered.
	 *
	 * @param $columns array - map of extrafields with field names as keys and field type as values
	 */

	protected $_editableColumns = null;

	function setEditableColumns($columns) {
		$this->_editableColumns = $columns;
	}

	/**
	 * Get the columns and their corresponding field types that can be edited.
	 *
	 * @param $gridField GridField - the current gridfield
	 * @return array - map of extra fields that can be edited with field names as key and field types as values
	 */

	function getEditableColumns(GridField $gridField) {

		if(!is_array($this->_editableColumns)) {

			$columns = array();

			if($gridField->getList() instanceof ManyManyList) {

				$record = $gridField->getForm()->getRecord();

				$extrafields = $record->stat('many_many_extraFields');

				if(is_array($extrafields) && isset($extrafields[$gridField->Name]) && is_array($extrafields[$gridField->Name])) {
					foreach($extrafields as $relation => $fields) foreach($fields as $name => $type) $columns[$name] = $type;
				}

				$manymanyrelations = $record->stat('belongs_many_many');
				if(is_array($manymanyrelations)) foreach($manymanyrelations as $name => $class) {
					$remotemanymanyrelations = Config::inst()->get($class, 'many_many');
					foreach($remotemanymanyrelations as $remotename => $remoteclass) {
						if($record instanceof $remoteclass) {
							$extrafields = Config::inst()->get($class, 'many_many_extraFields');
							$columns = array_merge($columns, $extrafields[$remotename]);
						}
					}
				}
			}

			$this->_editableColumns = $columns;
		}

		return $this->_editableColumns;
	}

	/**
	 * Set autosave flag, false meaning a button for saving will be displayed, true meaning the save function will be executed onBlur of the input field
	 *
	 * @param $value bool - do or don't autosave
	 */

	protected $_save_automatically;

	function setSaveAutomatically($value) {
		$this->_save_automatically = $value;
	}

	function __construct($columns = null, $saveAutomatically = true) {
		$this->_editableColumns = $columns;
		$this->_save_automatically = $saveAutomatically;
	}

	/**
	 * Add extra fields to the column list
	 * 
	 * @param GridField $gridField
	 * @param array - List reference of all column names.
	 */
	public function augmentColumns($gridField, &$columns) {
		$additionalfields = $this->getColumnsHandled($gridField);
		$columns = array_keys(array_merge(array_flip($columns), array_flip($additionalfields)));
	}

	/**
	 * List of handled columns
	 * 
	 * @param GridField $gridField
	 * @return array 
	 */

	protected $_columnsHandled = null;

	public function getColumnsHandled($gridField) {

		if(is_null($this->_columnsHandled)) {

			$columns = array();

			$columns = array_keys($this->getEditableColumns($gridField));

			if(count($columns) && !in_array('Actions', $columns)) $columns[] = 'Actions';
			
			$this->_columnsHandled = $columns;
		}
		return $this->_columnsHandled;
	}

	/**
	 * Return a formfield for the extra field column or an edit button for the actions column
	 * 
	 * @param  GridField $gridField
	 * @param  DataObject $record - Record displayed in this row
	 * @param  string $columnName
	 * @return string - HTML for the column. Return NULL to skip.
	 */
	public function getColumnContent($gridField, $record, $columnName) {

		if($columnName == 'Actions') {
			$field = GridField_FormAction::create($gridField, 'SaveRelation'.$record->ID, false, "saverelation", array('RecordID' => $record->ID))
				->addExtraClass('gridfield-button-save')
				->setAttribute('title', _t('GridAction.SaveRelation', "Save"))
				->setAttribute('data-icon', 'chain--pencil');
			return $field->Field();
		}

		Requirements::javascript('gridfieldeditablemanymanyextracolumns/javascript/GridFieldEditableManyManyExtraColumns.js');
		Requirements::css('gridfieldeditablemanymanyextracolumns/css/GridFieldEditableManyManyExtraColumns.css');

		return $this->_scaffoldFormField($gridField, $record, $columnName);
	}

	protected function _scaffoldFormField($gridField, $record, $columnName) {

		// @todo: this is realy clumsy, there must be a better way...

		$editablefields = $this->getEditableColumns($gridField);
		foreach($editablefields as $name => $type) {

			preg_match("/(\w+)(\(.*\))?/i", $type, $matches);
			$dbclass = $matches[1];
			$fieldname = "{$gridField->Name}[\"{$columnName}\"][{$record->ID}]";
			$attr1 = $attr2 = $attr3 = null;
			if(isset($matches[2]) && preg_match_all('/\'[^\']+\'|[^,]+/', trim($matches[2], "()"), $matches)) {
				if(isset($matches[0][0])) $attr1 = trim($matches[0][0], " '");
				if(isset($matches[0][1])) $attr2 = trim($matches[0][1], " '");
				if(isset($matches[0][2])) $attr3 = trim($matches[0][2], " '");
			}
			$dbfield = new $dbclass($fieldname, $attr1, $attr2, $attr3);
			$formfield = $dbfield->scaffoldFormField($columnName);
			$formfield->setValue($record->$columnName);
			return $formfield->Field();
		}
	}

	/**
	 * Generate HTML attributes for each individual cells as selectors for CSS and JS
	 * 
	 * @param  GridField $gridField
	 * @param  DataObject $record displayed in this row
	 * @param  string $columnName
	 * @return array
	 */
	public function getColumnAttributes($gridField, $record, $columnName) {

		// if(!$record->canEdit()) return array();

		if($columnName == 'Actions') return array('class' => 'col-buttons');

		return array(
			"data-gridfield-cell-name" => $gridField->Name,
			"data-gridfield-cell-id" =>$record->ID,
			"data-gridfield-cell-column" => $columnName,
			"data-gridfield-cell-automatically" => (int)$this->_save_automatically,
			"data-gridfield-cell-dirty" => 0,
		);
	}

	/**
	 * Set titles for the column header
	 * 
	 * @param GridField $gridField
	 * @param string $columnName
	 * @return array - Map of arbitrary metadata identifiers to their values.
	 */
	public function getColumnMetadata($gridField, $columnName) {

		if($columnName == 'Actions') return array('title' => '');

		return array('title' => $columnName);
	}

	/**
	 * Add a save relation action
	 * 
	 * @param GridField
	 * @return Array with action identifier strings. 
	 */
	public function getActions($gridField) {
		return array('saverelation', 'saverelation');
	}
	
	/**
	 * Save (remove and re-add) the relation with the new values.
	 * 
	 * @param GridField
	 * @param String Action identifier, see {@link getActions()}.
	 * @param Array Arguments relevant for this 
	 * @param Array All form data
	 */
	public function handleAction(GridField $gridField, $actionName, $arguments, $data) {

		if($actionName == 'saverelation') {

			$list = $gridField->getList();

			$record = $list->find('ID', $arguments['RecordID']);

			$readd = $write = false;

			$extrafieldvalues = $list->getExtraData(
				$gridField->Name,
				$record->ID
			);

			foreach($this->getColumnsHandled($gridField) as $column) {

				// ignore actions here
				if($column == 'Actions') continue;

				if(in_array($column, array_keys($extrafieldvalues))) {
					if($data[$gridField->Name]["\"$column\""][$record->ID] != $extrafieldvalues[$column]) {
						$extrafieldvalues[$column] = $data[$gridField->Name]["\"$column\""][$record->ID];
						$readd = true;
					}
				} else {
					$record->$column = $data[$gridField->Name]["\"$column\""][$record->ID];
					$write = true;
				}
			}

			if($readd) {
				$list->remove($record);
				$list->add($record, $extrafieldvalues);
			}

			if($write) {
				$record->write();
			}
		}
	}
}