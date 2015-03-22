<?php

	/**
	 * Class representing a value in the database (i.e. a cell in a table).
	 * @author Thomas
	 */
	class SQLValue {

		/** @var SQLColumn The column that the value belongs to. */
		protected $column;

		/** @var mixed The last value that was assigned in its unprocessed state */
		protected $rawValue = null;
		
		/** @var mixed The current value. */
		protected $value;

		/** @var boolean Indicates whether the value has been changed since the record it is in was last committed to the database. */
		protected $hasChanged = false;
		
		/** @var boolean Indicates whether the last assignment to this value was valid or not. Invalid values in DBObjects will cause insert/update operations to fail */
		protected $isInvalid = false;

		/**
		 * SQLValue constructor.
		 * @param SQLColumn $column The column that the value belongs to.
		 * @param mixed $value The current value.
		 * @return SQLValue
		 */
		public function __construct($column, $value) {
			$this->column = $column;
			$this->value = $value;
		}

		public function isErroneous(){
			return $this->isInvalid;
		}
		
		/**
		 * Returns the value in a valid SQL format.
		 * @return string The value in a valid SQL format.
		 */
		public function getSQLValue() {
			return $this->column->valueAsSQL($this->value);
		}

		public function getValue() {
			return $this->value;
		}

		public function setValue($value) {
			$this->rawValue = $value;
			$this->isInvalid = false;
			
			if ($this->column->isNumeric() && (is_numeric($value)) && $this->value === $value) {
				// No change
				return false;
			}
			if ($this->column->isNullable() && (strtoupper($value) === 'NULL' || $value === '')) {
				$value = null;
			}
			if ($this->column->getDatatype() == 'enum' && is_String($value)) {
				if (!in_array(strtolower($value), $this->column->getPossibleValues(true))) {
					Messages::msg("'$value' is not a possible value for enum column '{$this->column->getName()}'.", Messages::M_CODE_ERROR);
					$this->isInvalid = true;
					return false;
				}
			}
			if ($this->column->isText() && $this->column->getMaxLength()) {
				$max = $this->column->getMaxLength();
				if (strlen($value) > $max) {
					Messages::msg("The specified value for {$this->column->getName()} exceeds the maximum allowed length of $max. Only the first $max characters will be saved.", Messages::M_WARNING);
					$value = substr($value, 0, $max);
				}
			}
			if (($this->column->getDatatype() == 'int') && is_string($value) && !is_numeric($value) && !empty($value)) {
				if (DateUtils::isDateString($value)) {
					$value = DateUtils::parseDateString($value);
				}
				elseif (DateUtils::isDateTimeString($value)) {
					$value = DateUtils::parseDateTimeString($value);
				}
			}	elseif ($this->column->isTimestamp() && is_numeric($value) && !empty($value)) {
				if ($this->column->getDataType() == 'date') {
					$value = date('Y-m-d', $value);
				}
				else {
					$value = date('Y-m-d H:i:s', $value);
				}
			}
			if (!$this->column->isNullable() && is_null($value)) {
				Messages::msg("{$this->column->getFullName()} must contain a value.", Messages::M_ERROR);
				return false;
			}
			if ($this->column->isNumeric() && !is_numeric($value) && !is_null($value)) {
				if (!is_bool($value)) {
					$value = var_export($value, true);
					Messages::msg("$value is not a valid numerical value for {$this->column->getName()}.", Messages::M_CODE_ERROR);
					$this->isInvalid = true;
					return false;
				}
				else {
					$value = ( $value ? 1 : 0 );
				}
			}
			if (is_numeric($this->value) && is_numeric($value)) {
				if ($this->value == $value) {
					return false;
				}
			}
			else if ($this->value === $value) {
				return false;
			}
			$this->hasChanged = true;
			$this->value = $value;
			return true;
		}

		/**
		 * Indicates whether the value has been changed since the record it is in was last committed to the database.
		 * @return boolean True if the value has changed, false otherwise.
		 */
		public function hasChanged() {
			return $this->hasChanged;
		}

		public function setHasChanged($value) {
			$this->hasChanged = $value;
		}

	}

?>