<?php
/**
 * GridField component for select rows
 *
 * @author Guy Watson <guy.watson@internetrix.com.au>
 * @package recurringevents
 * 
 */
class GridFieldSelectColumns implements GridField_HTMLProvider, GridField_ColumnProvider
{	

	/**
	 * Add select column
	 * 
	 * @param  GridField $gridField Current GridField instance
	 * @param  array     $columns   Columns list
	 */
	function augmentColumns($gridField, &$columns)
	{
		if(!in_array('Select', $columns)) $columns[] = 'Select';
	}

	
	/**
	 * Which columns are handled by the component
	 * 
	 * @param  GridField $gridField Current GridField instance
	 * @return array                List of handled column names
	 */
	function getColumnsHandled($gridField)
	{
		return array('Select');
	}

	
	/**
	 * Sets the column's content
	 * 
	 * @param  GridField  $gridField  Current GridField instance
	 * @param  DataObject $record     Record intance for this row
	 * @param  string     $columnName Column's name for which we need content
	 * @return mixed                  Column's field content
	 */
	function getColumnContent($gridField, $record, $columnName)
	{
		$cb = CheckboxField::create('Select_'.$record->ID)
		    ->addExtraClass('Select no-change-track')
		    ->setAttribute('data-record', $record->ID);
		return $cb->Field();
	}

	
	/**
	 * Set the column's HTML attributes
	 * 
	 * @param  GridField  $gridField  Current GridField instance
	 * @param  DataObject $record     Record intance for this row
	 * @param  string     $columnName Column's name for which we need attributes
	 * @return array                  List of HTML attributes
	 */
	function getColumnAttributes($gridField, $record, $columnName)
	{
		return array('class' => 'col-Select');
	}
	

	/**
	 * Set the column's meta data
	 * 
	 * @param  GridField  $gridField  Current GridField instance
	 * @param  string     $columnName Column's name for which we need meta data
	 * @return array                  List of meta data
	 */
	function getColumnMetadata($gridField, $columnName)
	{
		if($columnName == 'Select') {
			return array('title' => 'Select');
		}
	}
	
	public function getHTMLFragments($grid) {
		Requirements::javascript('recurringevents/javascript/GridFieldSelectColumns.js');
// 		$grid->addExtraClass('ss-gridfield-editable');
		$templateData = array(
			'Select' => array(
				'Label' => 'Select all'
			),
			'Colspan' => (count($grid->getColumns()) - 1)
		);
		
		$templateData = new ArrayData($templateData);
		
		return array(
			'header' => $templateData->renderWith('SelectAll')
		);
	}

}