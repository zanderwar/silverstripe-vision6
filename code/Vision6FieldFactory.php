<?php

class Vision6FieldFactory extends Object
{

	/**
	 * @var Vision6List
	 */
	protected $list;

	/**
	 * @var
	 */
	protected $fields;

	/**
	 * @param Vision6List|DataObject|int $listOrId
	 *
	 * @return $this
	 */
	public function setList($listOrId)
	{
		if (!($listOrId instanceof Vision6List)) {
			$listFetch = Vision6List::get()
				->filter(
					array(
						'ListID' => (int)$listOrId
					)
				)
				->first();

			if (!$listFetch) {
				user_error(
					_t(
						'Vision6.ListIdNotFound',
						'The list with ID {list_id} could not be found, have you synced your lists yet?',
						'',
						array(
							'list_id' => $listOrId
						)
					),
					E_USER_ERROR
				);
			}

			$listOrId = $listFetch;
		}

		$this->list = $listOrId;

		return $this;
	}

	/**
	 * @return FieldList
	 */
	public function build()
	{
		if (!($this->list instanceof Vision6List)) {
			user_error(
				_t(
					'Vision6.ListIdToBeSetFirst',
					'You must set the list with setList() before calling generateFields()'
				),
				E_USER_ERROR
			);
		}

		$fields = FieldList::create(HiddenField::create("ListID")->setAttribute("value", $this->list->ListID));

		foreach ($this->list->Fields() as $field) {
			switch ($field->Type) {
				case "text": {
					if ($field->Name == 'Email') {
						$fields->push($this->emailFieldFor($field));
						break;
					}
					$fields->push($this->textFieldFor($field));
				}
					break;

				case "comment": {
					$fields->push($this->textareaFieldFor($field));
				}
					break;

				case "checkbox": {
					if (strstr($field->ValuesArray, ",")) {
						// has multiple
						$fields->push($this->checkboxSetFor($field));
						break;
					}

					$fields->push($this->checkboxFieldFor($field));

				}
					break;
			}
		}

		return $fields;

	}

	/**
	 * Handles text fields
	 *
	 * @param \Vision6Field $field
	 *
	 * @return TextField
	 */
	public function textFieldFor(Vision6Field $field)
	{
		if ($field->Type != "text") {
			throw new \RuntimeException("Field \"{$field->Name}\" is a {$field->Type} field but was provided to _textFieldFor()");
		}

		$ssObj = TextField::create($field->Name, $field->Name, $field->DefaultValue);
		$this->handleAttributes($field, $ssObj);

		return $ssObj;
	}

	/**
	 * Handles text fields that are of Email type
	 *
	 * @param Vision6Field $field
	 *
	 * @return Vision6EmailField
	 */
	public function emailFieldFor(Vision6Field $field)
	{
		if ($field->Type != "text") {
			user_error("Field \"{$field->Name}\" is a {$field->Type} field but was provided to ::emailFieldFor()", E_USER_ERROR);
		}

		$ssObj = Vision6EmailField::create($field->Name, $field->Name)->addExtraClass('text');
		$this->handleAttributes($field, $ssObj);

		return $ssObj;
	}

	/**
	 * Handles groups of checkboxes
	 *
	 * @param Vision6Field $field
	 *
	 * @return CheckboxSetField
	 */
	public function checkboxSetFor(Vision6Field $field)
	{
		if ($field->Type != "checkbox") {
			user_error("Field \"{$field->Name}\" is a {$field->Type} field but was provided to ::checkboxSetFor()", E_USER_ERROR);
		}

		if (!strstr($field->ValuesArray, ",")) {
			user_error("Field \"{$field->Name}\" is a singular checkbox but was provided to ::checkboxSetFor()", E_USER_ERROR);
		}

		$values = explode(",", $field->ValuesArray);
		$valuesArray = array();

		foreach ($values as $value) {
			$valuesArray[$value] = $value;
		}

		$ssObj = CheckboxSetField::create($field->Name, $field->Name, $valuesArray);
		$this->handleAttributes($field, $ssObj);

		return $ssObj;
	}

	/**
	 * Handles singular checkboxes
	 *
	 * @param \Vision6Field $field
	 *
	 * @return CheckboxField
	 */
	public function checkboxFieldFor(Vision6Field $field)
	{
		if ($field->Type != "checkbox") {
			user_error("Field \"{$field->Name}\" is a {$field->Type} field but was provided to ::checkboxFieldFor()", E_USER_ERROR);
		}

		if (strstr($field->ValuesArray, ",")) {
			user_error("Field \"{$field->Name}\" has multiple checkboxes but was provided to ::checkboxFieldFor()", E_USER_ERROR);
		}

		$ssObj = CheckboxField::create($field->Name, $field->Name);
		$this->handleAttributes($field, $ssObj);

		return $ssObj;
	}

	/**
	 * Handles textarea/comment field creation
	 *
	 * @param Vision6Field $field
	 *
	 * @return TextareaField
	 */
	public function textareaFieldFor(Vision6Field $field)
	{
		if ($field->Type != "comment") {
			user_error("Field \"{$field->Name}\" is a {$field->Type} field but was provided to ::textareaFieldFor()", E_USER_ERROR);
		}

		$ssObj = TextareaField::create($field->Name, $field->Name);
		$this->handleAttributes($field, $ssObj);

		return $ssObj;
	}

	/**
	 * Handles dropdown fields
	 *
	 * @param Vision6Field $field
	 *
	 * @return DropdownField
	 */
	public function dropdownFieldFor(Vision6Field $field)
	{
		// havn't found a need for this yet
	}

	/**
	 * Handles attributes required for each field such as mandatory, max length etc
	 *
	 * @param \Vision6Field $field
	 * @param FormField|EmailField $ssObj
	 */
	public function handleAttributes(Vision6Field $field, &$ssObj)
	{
		if ($field->Length) {
			$ssObj->setAttribute("maxlength", $field->Length);
		}

	}

	/**
	 * @return RequiredFields
	 */
	public function getRequired() {
		$required = array();

		foreach ($this->list->Fields() as $field) {
			if ((int)$field->IsMandatory) {
				$required[] = $field->Name;
			}
		}

		return RequiredFields::create($required);
	}
}
