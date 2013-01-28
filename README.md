## GridFieldEditableManyManyExtraColumns

The class adds extra form fields to the gridfield to edit DataObject::$many_many_extraFields

## Requirements ##

SilverStripe 3.x

## Installation ##

Download the module from here https://github.com/smindel/
Extract the downloaded archive into your site root so that the destination folder is called gridfieldeditablemanymanyextracolumns.
Run dev/build?flush=all

## Basic Usage ##

	class Foo extends DataObject {

		static $many_many = array(
			'Bars' => 'Bar',
		);

		static $many_many_extraFields = array(
			'Bars' => array(
				'MyExtraField' => 'Varchar',
			),
		);

		function getCMSFields() {
			$fields = parent::getCMSFields();
			$fields->dataFieldByName('Bars')->getConfig()->addComponent(new GridFieldEditableManyManyExtraColumns(), 'GridFieldEditButton');
			return $fields;
		}
	}

	class Bar extends DataObject {

		static $belongs_many_many = array(
			'Foos' => 'Foo',
		);

		function getCMSFields() {
			$fields = parent::getCMSFields();
			$fields->dataFieldByName('Foos')->getConfig()->addComponent(new GridFieldEditableManyManyExtraColumns(), 'GridFieldEditButton');
			return $fields;
		}
	}

The rest is pure magic