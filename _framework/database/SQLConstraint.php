<?php

	/*
	 * NOTE: This includes the 'constraints' folder at the bottom of the file, rather than the top, because the class needs
	 *   to be defined before files containing child classes can be included
	 */

	abstract class SQLConstraint {
		const TYPE_PRIMARY = 'PRIMARY KEY';
		const TYPE_FOREIGN = 'FOREIGN KEY';
		const TYPE_UNIQUE = 'UNIQUE';
		const TYPE_INDEX = 'INDEX';
		const TYPE_CHECK = 'CHECK'; // Not currently supported by MySQL

		/**
		 * @var SQLTable
		 */
		protected $name;
		protected $db_name;
		protected $table_name;
		protected $type;
		protected $columns = array();

		public function __construct($info) {
			$this->name = $info['CONSTRAINT_NAME'];
			$this->db_name = $info['TABLE_SCHEMA'];
			$this->table_name = $info['TABLE_NAME'];
			$this->type = $info['CONSTRAINT_TYPE'];
			$this->loadColumns($info);
		}

		protected function loadColumns($info) {
			$this->columns = explode(',', $info['COLUMN_NAMES']);
		}

		public static function build($info) {
			switch ($info['CONSTRAINT_TYPE']) {
				case self::TYPE_PRIMARY: return new SQLPrimaryConstraint($info);
				case self::TYPE_UNIQUE : return new SQLUniqueConstraint($info);
				case self::TYPE_FOREIGN: return new SQLForeignConstraint($info);
				case self::TYPE_CHECK :
					Messages::msg("The CHECK constraint type is not currently supported as it was not supported by MySQL at time of implementation.", Messages::M_CODE_ERROR);
					break;
				case self::TYPE_INDEX :
					// INDEX has no meaning to us, because it doesn't affect the data, only how MySQL retrieves it
					break;
				default:
					Messages::msg("Unknown constraint type '{$info['CONSTRAINT_TYPE']}'.", Messages::M_CODE_ERROR);
			}
			return null;
		}

		public function isUnique() {
			return ($this->type == self::TYPE_PRIMARY) || ($this->type == self::TYPE_UNIQUE);
		}

		public function getName() {
			return $this->name;
		}

		public function getType() {
			return $this->type;
		}

		public function getColumns() {
			return $this->columns;
		}

		public function size() {
			return sizeof($this->columns);
		}

		public function containsColumn($name) {
			return in_array($name, $this->columns);
		}
		
		/**
		 * Checks if an SQLRecord is valid according to this constraint.
		 * @param SQLRecord $sqlrecord
		 * @return boolean
		 */
		public abstract function validate($sqlrecord);
	}

?>