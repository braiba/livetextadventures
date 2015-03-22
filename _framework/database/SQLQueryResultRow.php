<?php

	addToIncludePath(FRAMEWORK_ROOT."/utils/array/");
	
	/**
	 * * 
	 * Wraps the array so that dashes, underscores and case are ignored when using a key to access a value.
	 * For example, the keys 'record_ID' and 'record-id' would reference the same value. 
	 * This means that we can access database values without having to check the exact formatting.
	 *
	 * @author Thomas
	 */
	class SQLQueryResultRow extends ArrayWrapper {

		public function makeQuickName($name) {
			return preg_replace('/[`_\\- ]+/', '', strtolower($name));
		}

		/**
		 * Returns an SQLQueryResultRow for the given data if possible.
		 * @param mixed $data The data to be wrapped.
		 * @return SQLQueryResultRow The wrapped data.
		 */
		public static function wrap($data) {
			if (is_array($data)){
				return new SQLQueryResultRow($data);
			}
			if ($data instanceof SQLQueryResultRow){
				return $data;
			}
			if ($data instanceof ArrayWrapper){
				return new SQLQueryResultRow($data->getWrappedArray());
			}
			Messages::msg('Object of type ' . gettype($data) . ' is not a valid target for wrapping. Must be an array or ArrayWrapper.', Messages::M_CODE_ERROR);
			return null;
		}

		public function __get($name) {
			return $this->offsetGet($name);
		}

		public function __set($name, $value) {
			return $this->offsetSet($name, $value);
		}

	}

?>